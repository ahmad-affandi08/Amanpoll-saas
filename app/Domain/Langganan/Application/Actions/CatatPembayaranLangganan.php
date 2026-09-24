<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\StatusSesiPembayaran;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Domain\Events\PeristiwaLangganan;
use App\Domain\Langganan\Domain\ValueObjects\PeristiwaPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PembayaranLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\SesiPembayaranLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;

/** Pencatatan pembayaran dan pelunasan tagihan (22.06). */
final class CatatPembayaranLangganan
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly KelolaLangganan $kelolaLangganan,
    ) {}

    public function dariPeristiwa(string $kodePenyedia, PeristiwaPembayaran $peristiwa): PembayaranLangganan
    {
        $sudahAda = $this->cariYangSudahAda($kodePenyedia, $peristiwa->idPeristiwa);
        if ($sudahAda !== null) {
            return $sudahAda;
        }

        $sesi = $this->sesiUntuk($kodePenyedia, $peristiwa);
        $tagihan = $sesi !== null
            ? $this->tagihanDariSesi($sesi)
            : TagihanLangganan::query()
                ->withoutGlobalScopes()
                ->where('Nomor', $peristiwa->nomorTagihan)
                ->first()
                ?? throw new DataTidakDitemukan("Tagihan {$peristiwa->nomorTagihan} tidak ditemukan.");

        try {
            return $this->transaksi->jalankan(
                fn (): PembayaranLangganan => $this->tulis($kodePenyedia, $peristiwa, $tagihan, $sesi),
            );
        } catch (UniqueConstraintViolationException) {
            // Pengiriman kembar tiba bersamaan; yang satu sudah menang.
            return $this->cariYangSudahAda($kodePenyedia, $peristiwa->idPeristiwa)
                ?? throw new DataTidakDitemukan('Pembayaran kembar tidak dapat dibaca kembali.');
        }
    }

    private function tulis(
        string $kodePenyedia,
        PeristiwaPembayaran $peristiwa,
        TagihanLangganan $tagihan,
        ?SesiPembayaranLangganan $sesi,
    ): PembayaranLangganan {
        $pembayaran = PembayaranLangganan::create([
            'OrganisasiId' => $tagihan->OrganisasiId,
            'TagihanLanggananId' => $tagihan->Id,
            'PenyediaPembayaran' => $kodePenyedia,
            'IdPeristiwaPenyedia' => $peristiwa->idPeristiwa,
            'ReferensiEksternal' => $peristiwa->referensiEksternal,
            'Metode' => $peristiwa->metode,
            'Jumlah' => $peristiwa->jumlah,
            'Status' => $peristiwa->status->value,
            'DibayarPada' => $peristiwa->status->mengurangiTagihan() ? CarbonImmutable::now() : null,
            'MuatanData' => $peristiwa->muatanMentah,
        ]);

        // Sesi yang sudah dibayar tidak turun lagi oleh kabar gagal/kedaluwarsa yang tiba terlambat.
        if ($sesi !== null && (string) $sesi->Status !== StatusSesiPembayaran::Dibayar->value) {
            $sesi->Status = StatusSesiPembayaran::dariPeristiwa($peristiwa)->value;
            $sesi->save();
        }

        $this->perbaruiStatusTagihan($tagihan);

        $this->audit->catat('PembayaranLangganan.Dicatat', 'PembayaranLangganan', $pembayaran->Id, dataSesudah: [
            'TagihanLanggananId' => $tagihan->Id,
            'Jumlah' => $peristiwa->jumlah,
            'Status' => $peristiwa->status->value,
        ]);

        Event::dispatch(new PeristiwaLangganan(
            $peristiwa->status === StatusPembayaranLangganan::Berhasil
                ? PeristiwaLangganan::PEMBAYARAN_BERHASIL
                : PeristiwaLangganan::PEMBAYARAN_GAGAL,
            (string) $tagihan->OrganisasiId,
            (string) $tagihan->LanggananId,
            ['PembayaranId' => $pembayaran->Id, 'Jumlah' => $peristiwa->jumlah],
        ));

        return $pembayaran;
    }

    /** Status tagihan selalu dihitung ulang dari seluruh pembayaran berhasil. */
    public function perbaruiStatusTagihan(TagihanLangganan $tagihan): TagihanLangganan
    {
        $statusLama = StatusTagihanLangganan::tryFrom((string) $tagihan->Status);
        if ($statusLama === StatusTagihanLangganan::Dibatalkan) {
            return $tagihan;
        }

        $dibayar = (float) PembayaranLangganan::query()
            ->withoutGlobalScopes()
            ->where('TagihanLanggananId', $tagihan->Id)
            ->where('Status', StatusPembayaranLangganan::Berhasil->value)
            ->sum('Jumlah');

        $total = (float) $tagihan->Total;
        $status = match (true) {
            $dibayar <= 0.0 => StatusTagihanLangganan::BelumDibayar,
            // Toleransi satu sen menghindari tagihan yang tidak pernah lunas karena pembulatan nilai desimal.
            $dibayar + 0.01 >= $total => StatusTagihanLangganan::Lunas,
            default => StatusTagihanLangganan::SebagianDibayar,
        };

        if ((string) $tagihan->Status === $status->value) {
            return $tagihan;
        }

        $tagihan->Status = $status->value;
        $tagihan->save();

        // Pelunasan adalah satu-satunya peristiwa yang memperpanjang langganan.
        if ($status === StatusTagihanLangganan::Lunas) {
            // Webhook berjalan tanpa konteks organisasi, jadi relasinya dibaca lepas dari global scope tenant.
            $langganan = Langganan::query()
                ->withoutGlobalScopes()
                ->find((string) $tagihan->LanggananId);

            if ($langganan !== null) {
                $this->kelolaLangganan->perpanjang($langganan);
            }
        }

        return $tagihan;
    }

    /**
     * Webhook gateway menyebut order id sesi, bukan nomor tagihan. Order id yang
     * tidak dikenal ditolak: menebak tagihan dari data kiriman membuka jalan
     * melunasi tagihan lain.
     */
    private function sesiUntuk(string $kodePenyedia, PeristiwaPembayaran $peristiwa): ?SesiPembayaranLangganan
    {
        if ($peristiwa->idPesananPenyedia === null) {
            return null;
        }

        return SesiPembayaranLangganan::query()
            ->withoutGlobalScopes()
            ->where('Penyedia', $kodePenyedia)
            ->where('IdPesananPenyedia', $peristiwa->idPesananPenyedia)
            ->first()
            ?? throw new DataTidakDitemukan('Sesi pembayaran tidak ditemukan.');
    }

    private function tagihanDariSesi(SesiPembayaranLangganan $sesi): TagihanLangganan
    {
        return TagihanLangganan::query()
            ->withoutGlobalScopes()
            ->find($sesi->TagihanLanggananId)
            ?? throw new DataTidakDitemukan('Tagihan sesi pembayaran tidak ditemukan.');
    }

    private function cariYangSudahAda(string $kodePenyedia, string $idPeristiwa): ?PembayaranLangganan
    {
        return PembayaranLangganan::query()
            ->withoutGlobalScopes()
            ->where('PenyediaPembayaran', $kodePenyedia)
            ->where('IdPeristiwaPenyedia', $idPeristiwa)
            ->first();
    }
}
