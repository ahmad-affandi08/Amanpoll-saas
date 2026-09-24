<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\Services;

use App\Domain\Notifikasi\Domain\Enums\KanalNotifikasi;
use App\Domain\Notifikasi\Domain\Enums\StatusNotifikasi;
use App\Domain\Notifikasi\Domain\KatalogPeristiwaNotifikasi;
use App\Domain\Notifikasi\Domain\Repositories\NotifikasiRepository;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\PreferensiNotifikasi;
use App\Domain\Notifikasi\Jobs\KirimNotifikasi;

/** Titik masuk tunggal untuk mengirim notifikasi. */
final class LayananNotifikasi
{
    /**
     * @var list<string>
     */
    private const KANAL_BAWAAN = [KanalNotifikasi::InApp->value];

    public function __construct(
        private readonly NotifikasiRepository $notifikasiRepository,
        private readonly TujuanWhatsAppNotifikasi $tujuanWhatsApp,
    ) {}

    /**
     * @param  list<string>|null  $kanal
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
        // Tanpa daftar kanal eksplisit, WhatsApp ikut dicoba; preferensi bawaannya yang memutuskan (KatalogPeristiwaNotifikasi).
        $daftarKanal = $kanal ?? [...self::KANAL_BAWAAN, KanalNotifikasi::WhatsApp->value];

        foreach (array_unique($daftarKanal) as $satuKanal) {
            if (! $this->aktifUntukPengguna($penggunaId, $jenisPeristiwa, $satuKanal)) {
                continue;
            }

            if ($satuKanal === KanalNotifikasi::WhatsApp->value && ! $this->whatsAppDapatDikirim($penggunaId)) {
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
                'Status' => StatusNotifikasi::Antri->value,
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

        if ($preferensi !== null) {
            return $preferensi->Aktif;
        }

        $kanalDikenal = KanalNotifikasi::tryFrom($kanal);

        return $kanalDikenal === null || KatalogPeristiwaNotifikasi::aktifBawaan($jenisPeristiwa, $kanalDikenal);
    }

    /** Tanpa penyedia aktif atau nomor yang sah, baris WhatsApp hanya akan menjadi kegagalan yang pasti. */
    private function whatsAppDapatDikirim(string $penggunaId): bool
    {
        return $this->tujuanWhatsApp->penyediaAktif() && $this->tujuanWhatsApp->nomorUntuk($penggunaId) !== null;
    }
}
