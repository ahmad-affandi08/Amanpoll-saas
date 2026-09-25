<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\Services;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Notifikasi\Domain\Enums\KanalNotifikasi;
use App\Domain\Notifikasi\Domain\Enums\StatusNotifikasi;
use App\Domain\Notifikasi\Domain\Enums\SumberPenyediaNotifikasi;
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
        private readonly PemilihPengirimNotifikasi $pemilihPengirim,
        private readonly PenghitungKuotaWhatsApp $kuotaWhatsApp,
        private readonly KonteksOrganisasi $konteks,
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

            $sumber = null;

            if ($satuKanal === KanalNotifikasi::WhatsApp->value) {
                $sumber = $this->sumberWhatsApp($penggunaId);

                if ($sumber === null) {
                    continue;
                }
            }

            $notifikasi = $this->notifikasiRepository->simpan(new Notifikasi([
                'PenggunaId' => $penggunaId,
                'Kanal' => $satuKanal,
                'SumberPenyedia' => $sumber?->value,
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

    /**
     * Pengantar WhatsApp yang direncanakan, atau null bila baris WhatsApp hanya akan menjadi
     * kegagalan yang pasti (PRD 8.23).
     *
     * Nomor milik organisasi didahulukan dan tidak berkuota. Tanpanya, nomor Amanpoll dipakai
     * selama kuota bawaan bulan ini masih ada. Baris yang masih antri ikut dihitung, jadi dua
     * puluh notifikasi beruntun dari satu peristiwa tidak semuanya lolos dari sisa kuota satu.
     * Batas ini lunak: dua permintaan yang benar-benar bersamaan bisa melampauinya satu-dua
     * pesan, harga yang diterima ketimbang mengunci tabel Notifikasi di setiap kiriman.
     */
    private function sumberWhatsApp(string $penggunaId): ?SumberPenyediaNotifikasi
    {
        $organisasiId = $this->konteks->id();

        if ($organisasiId === null || $this->tujuanWhatsApp->nomorUntuk($penggunaId) === null) {
            return null;
        }

        if ($this->pemilihPengirim->whatsAppOrganisasi($organisasiId) !== null) {
            return SumberPenyediaNotifikasi::Organisasi;
        }

        if (! $this->tujuanWhatsApp->penyediaAktif()) {
            return null;
        }

        $kuota = $this->kuotaWhatsApp->untuk($organisasiId);

        if ($kuota->habis()) {
            // Diresolusi saat dibutuhkan: pemberitahu itu sendiri mengirim lewat layanan ini.
            app(PemberitahuLayananPengirim::class)->kuotaWhatsAppHabis($organisasiId, $kuota->batas);

            return null;
        }

        return SumberPenyediaNotifikasi::Platform;
    }
}
