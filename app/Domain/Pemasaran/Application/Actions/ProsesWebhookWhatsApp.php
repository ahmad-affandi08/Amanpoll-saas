<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Application\Services\PengirimWhatsAppPemasaran;
use App\Domain\Pemasaran\Application\Services\PenjawabWhatsAppMasuk;
use App\Domain\Pemasaran\Domain\Contracts\DapatMembalasWhatsApp;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\KanalPesan;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\PeristiwaWebhookWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanMasukWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\StatusKirimanWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanWhatsAppPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Menerapkan satu kiriman webhook WhatsApp: status kiriman maju, pesan masuk dijawab
 * dari menu, dan permintaan berhenti mencabut consent (MARKETING.md 16, 27).
 *
 * Penyedia mengulang webhook yang tidak dijawab 200, jadi setiap langkahnya aman diulang:
 * status hanya maju dan supresi tidak berlipat.
 */
final class ProsesWebhookWhatsApp
{
    public function __construct(
        private readonly PengirimWhatsAppPemasaran $pengirim,
        private readonly PenjawabWhatsAppMasuk $penjawab,
        private readonly LayananKonsen $konsen,
    ) {}

    /** @return array{Status: int, PesanMasuk: int} */
    public function jalankan(PenyediaWhatsApp $penyedia, PeristiwaWebhookWhatsApp $peristiwa): array
    {
        return [
            'Status' => $this->terapkanStatus($peristiwa),
            'PesanMasuk' => $this->jawabPesanMasuk($penyedia, $peristiwa->pesanMasuk),
        ];
    }

    private function terapkanStatus(PeristiwaWebhookWhatsApp $peristiwa): int
    {
        if ($peristiwa->status === []) {
            return 0;
        }

        $pengiriman = PengirimanWhatsAppPemasaran::query()
            ->with('prospek')
            ->whereIn('IdPesanPenyedia', array_map(fn (StatusKirimanWhatsApp $laporan): string => $laporan->idPesan, $peristiwa->status))
            ->get()
            ->keyBy('IdPesanPenyedia');

        $diterapkan = 0;

        foreach ($peristiwa->status as $laporan) {
            $satu = $pengiriman->get($laporan->idPesan);

            if (! $satu instanceof PengirimanWhatsAppPemasaran) {
                continue;
            }

            $this->pengirim->perbaruiStatus($satu, $laporan->status, $laporan->keterangan);
            $diterapkan++;

            // Penerima yang menolak pesan pemasaran di sisi WhatsApp diperlakukan sama dengan STOP.
            if ($laporan->status === StatusPengirimanWhatsApp::Unsubscribe
                && ! $this->konsen->disupresi($satu->Nomor, KanalPesan::WhatsApp)) {
                $this->konsen->cabut(
                    $satu->Nomor,
                    AlasanSupresi::Unsubscribe,
                    $satu->prospek,
                    'Penerima menolak pesan pemasaran menurut penyedia WhatsApp.',
                    KanalPesan::WhatsApp,
                );
            }
        }

        return $diterapkan;
    }

    /** @param list<PesanMasukWhatsApp> $pesanMasuk */
    private function jawabPesanMasuk(PenyediaWhatsApp $penyedia, array $pesanMasuk): int
    {
        foreach ($pesanMasuk as $pesan) {
            $jawaban = $this->penjawab->jawab($pesan->dari, $pesan->teks);

            if ($penyedia instanceof DapatMembalasWhatsApp && $jawaban['Balasan'] !== '') {
                $this->balas($penyedia, $pesan->dari, $jawaban['Balasan']);
            }
        }

        return count($pesanMasuk);
    }

    /** Balasan yang gagal tidak boleh menggagalkan webhook: STOP-nya sudah tercatat, dan penyedia akan mengulang semuanya. */
    private function balas(PenyediaWhatsApp&DapatMembalasWhatsApp $penyedia, string $kepada, string $teks): void
    {
        try {
            $penyedia->balas($kepada, $teks);
        } catch (Throwable $galat) {
            Log::warning('Balasan WhatsApp otomatis gagal dikirim.', [
                'Penyedia' => $penyedia->kode(),
                // Pesan AturanBisnisDilanggar dari adapter sudah disensor; pesan lain bisa memuat URL bertoken.
                'Galat' => $galat instanceof AturanBisnisDilanggar ? $galat->getMessage() : $galat::class,
            ]);
        }
    }
}
