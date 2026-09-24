<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Actions;

use App\Domain\Langganan\Domain\Contracts\PenyediaPembayaran;
use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\StatusSesiPembayaran;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Domain\ValueObjects\InstruksiPembayaran;
use App\Domain\Langganan\Domain\ValueObjects\PesananPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PembayaranLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\SesiPembayaranLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Memulai pembayaran sebuah tagihan di penyedia pilihan tenant (22.06, PRD 8.23).
 *
 * Gateway menolak order id ganda, jadi setiap sesi bayar punya order id sendiri
 * yang disimpan di SesiPembayaranLangganan. Klik "Bayar" berulang memakai sesi yang
 * masih berlaku untuk jumlah yang sama, alih-alih membuka invoice baru tiap kali.
 */
final class MulaiPembayaranLangganan
{
    /** Cukup lama untuk transfer VA, dan masih di bawah batas 24 jam Stripe Checkout. */
    public const MENIT_BERLAKU = 720;

    /** Sesi yang hampir habis tidak dipakai ulang supaya pengguna tidak tiba di halaman yang langsung kedaluwarsa. */
    private const SISA_MENIT_MINIMUM = 10;

    public function jalankan(TagihanLangganan $tagihan, PenyediaPembayaran $penyedia, Pengguna $pembayar): InstruksiPembayaran
    {
        $status = StatusTagihanLangganan::tryFrom((string) $tagihan->Status);
        if ($status === null || ! $status->masihDapatDibayar()) {
            throw new AturanBisnisDilanggar('Tagihan ini sudah tidak dapat dibayar.');
        }

        $jumlah = $this->sisaTagihan($tagihan);
        if ($jumlah <= 0) {
            throw new AturanBisnisDilanggar('Tagihan ini sudah lunas.');
        }

        $sesi = $this->sesiBerlaku($tagihan, $penyedia->kode(), $jumlah);
        if ($sesi !== null) {
            return InstruksiPembayaran::pengalihan($sesi->UrlPembayaran, $sesi->KedaluwarsaPada, $sesi->ReferensiPenyedia);
        }

        $pesanan = $this->susunPesanan($tagihan, $penyedia, $pembayar, $jumlah);
        $instruksi = $penyedia->mulaiPembayaran($tagihan, $pesanan);

        if ($instruksi->urlPembayaran !== null) {
            SesiPembayaranLangganan::create([
                'OrganisasiId' => $tagihan->OrganisasiId,
                'TagihanLanggananId' => $tagihan->Id,
                'Penyedia' => $penyedia->kode(),
                'IdPesananPenyedia' => $pesanan->idPesanan,
                'ReferensiPenyedia' => $instruksi->referensiPenyedia,
                'UrlPembayaran' => $instruksi->urlPembayaran,
                'Jumlah' => $jumlah,
                'KedaluwarsaPada' => $instruksi->kedaluwarsaPada ?? $pesanan->kedaluwarsaPada,
                'Status' => StatusSesiPembayaran::Menunggu->value,
            ]);
        }

        return $instruksi;
    }

    /** Sisa yang belum dibayar, dibulatkan ke atas ke rupiah penuh. */
    private function sisaTagihan(TagihanLangganan $tagihan): int
    {
        $dibayar = (float) PembayaranLangganan::query()
            ->withoutGlobalScopes()
            ->where('TagihanLanggananId', $tagihan->Id)
            ->where('Status', StatusPembayaranLangganan::Berhasil->value)
            ->sum('Jumlah');

        return (int) ceil(round((float) $tagihan->Total - $dibayar, 2));
    }

    private function sesiBerlaku(TagihanLangganan $tagihan, string $kodePenyedia, int $jumlah): ?SesiPembayaranLangganan
    {
        return SesiPembayaranLangganan::query()
            ->where('TagihanLanggananId', $tagihan->Id)
            ->where('Penyedia', $kodePenyedia)
            ->where('Status', StatusSesiPembayaran::Menunggu->value)
            ->where('Jumlah', $jumlah)
            ->where('KedaluwarsaPada', '>', CarbonImmutable::now()->addMinutes(self::SISA_MENIT_MINIMUM))
            ->orderByDesc('DibuatPada')
            ->orderByDesc('Id')
            ->first();
    }

    private function susunPesanan(
        TagihanLangganan $tagihan,
        PenyediaPembayaran $penyedia,
        Pengguna $pembayar,
        int $jumlah,
    ): PesananPembayaran {
        $nomor = (string) $tagihan->Nomor;
        // Huruf dan angka saja: Midtrans, Duitku, dan DOKU membatasi karakter order id.
        $awalan = Str::limit((string) preg_replace('/[^A-Za-z0-9]/', '', $nomor), 30, '');

        return new PesananPembayaran(
            idPesanan: ($awalan !== '' ? $awalan.'-' : '').Str::upper(Str::random(10)),
            nomorTagihan: $nomor,
            jumlah: $jumlah,
            deskripsi: "Tagihan langganan Amanpoll {$nomor}",
            namaPelanggan: (string) $pembayar->Nama,
            emailPelanggan: (string) $pembayar->Email,
            teleponPelanggan: filled($pembayar->Telepon) ? (string) $pembayar->Telepon : null,
            urlKembali: route('langganan.tagihan.kembali', $tagihan),
            urlNotifikasi: route('langganan.webhook.pembayaran', ['penyedia' => $penyedia->kode()]),
            kedaluwarsaPada: CarbonImmutable::now()->addMinutes(self::MENIT_BERLAKU),
            menitBerlaku: self::MENIT_BERLAKU,
        );
    }
}
