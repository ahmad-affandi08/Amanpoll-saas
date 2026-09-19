<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\Services;

use App\Domain\Notifikasi\Domain\Repositories\NotifikasiRepository;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\PreferensiNotifikasi;
use App\Domain\Notifikasi\Jobs\KirimNotifikasi;

/**
 * Titik masuk tunggal untuk mengirim notifikasi. Setiap kanal dicek
 * terhadap PreferensiNotifikasi milik penerima (default: aktif, model
 * opt-out) sebelum baris Notifikasi dibuat dan job pengiriman di-queue.
 */
final class LayananNotifikasi
{
    /**
     * @var list<string>
     */
    private const KANAL_BAWAAN = [Notifikasi::KANAL_IN_APP];

    public function __construct(private readonly NotifikasiRepository $notifikasiRepository) {}

    /**
     * @param list<string>|null $kanal
     */
    public function kirim(
        string $penggunaId,
        string $jenisPeristiwa,
        string $isi,
        ?string $judul = null,
        ?string $jenisEntitas = null,
        ?string $entitasId = null,
        ?array $kanal = null,
    ): void {
        foreach ($kanal ?? self::KANAL_BAWAAN as $satuKanal) {
            if (!$this->aktifUntukPengguna($penggunaId, $jenisPeristiwa, $satuKanal)) {
                continue;
            }

            $notifikasi = $this->notifikasiRepository->simpan(new Notifikasi([
                'PenggunaId' => $penggunaId,
                'Kanal' => $satuKanal,
                'JenisPeristiwa' => $jenisPeristiwa,
                'Judul' => $judul,
                'Isi' => $isi,
                'JenisEntitas' => $jenisEntitas,
                'EntitasId' => $entitasId,
                'Status' => Notifikasi::STATUS_ANTRI,
                'JadwalKirimPada' => now(),
            ]));

            KirimNotifikasi::dispatch($notifikasi->Id);
        }
    }

    private function aktifUntukPengguna(string $penggunaId, string $jenisPeristiwa, string $kanal): bool
    {
        $preferensi = PreferensiNotifikasi::query()
            ->where('PenggunaId', $penggunaId)
            ->where('JenisPeristiwa', $jenisPeristiwa)
            ->where('Kanal', $kanal)
            ->first();

        return $preferensi === null || $preferensi->Aktif;
    }
}
