<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\Services;

use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Notifikasi\Domain\Contracts\DapatMengirimNotifikasiWhatsApp;
use App\Domain\Notifikasi\Domain\Contracts\PenyediaEmail;
use App\Domain\Notifikasi\Domain\ValueObjects\PengirimOrganisasi;
use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Application\Services\PembacaKredensialPenyedia;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;

/**
 * Memilih pengantar notifikasi email dan WhatsApp satu organisasi (PRD 8.23).
 *
 * Penyedia milik organisasi dipakai hanya bila aktif dan paket langganannya memuat
 * "Email & WhatsApp Milik Sendiri". Paket yang turun tidak menghapus pengaturannya;
 * notifikasi saja yang kembali lewat penyedia platform sampai paketnya naik lagi.
 * Tidak ada cache lintas permintaan: layanan ini ikut hidup di worker antrian.
 */
final class PemilihPengirimNotifikasi
{
    public function __construct(
        private readonly PembacaKredensialPenyedia $pembaca,
        private readonly KatalogPenyediaLayanan $katalog,
        private readonly PemeriksaEntitlement $entitlement,
    ) {}

    public function bolehPenyediaSendiri(string $organisasiId): bool
    {
        return $this->entitlement->bolehFitur(KatalogFitur::LAYANAN_PENYEDIA_SENDIRI, $organisasiId);
    }

    /** @return PengirimOrganisasi<DeskripsiPenyediaLayanan&PenyediaEmail>|null */
    public function emailOrganisasi(string $organisasiId): ?PengirimOrganisasi
    {
        $terpilih = $this->milikOrganisasi($organisasiId, KategoriPenyediaLayanan::Email);

        if ($terpilih === null || ! $terpilih[0] instanceof PenyediaEmail) {
            return null;
        }

        return new PengirimOrganisasi($terpilih[0], $terpilih[1], $terpilih[2]);
    }

    /** @return PengirimOrganisasi<DeskripsiPenyediaLayanan&DapatMengirimNotifikasiWhatsApp>|null */
    public function whatsAppOrganisasi(string $organisasiId): ?PengirimOrganisasi
    {
        $terpilih = $this->milikOrganisasi($organisasiId, KategoriPenyediaLayanan::WhatsApp);

        if ($terpilih === null || ! $terpilih[0] instanceof DapatMengirimNotifikasiWhatsApp) {
            return null;
        }

        return new PengirimOrganisasi($terpilih[0], $terpilih[1], $terpilih[2]);
    }

    /** @return array{0: DeskripsiPenyediaLayanan, 1: KredensialPenyedia, 2: string}|null */
    private function milikOrganisasi(string $organisasiId, KategoriPenyediaLayanan $kategori): ?array
    {
        $baris = $this->pembaca->milikOrganisasi($organisasiId, $kategori);

        if ($baris === null || ! $this->bolehPenyediaSendiri($organisasiId)) {
            return null;
        }

        $penyedia = $this->katalog->untuk($kategori, (string) $baris->Kode);

        return $penyedia === null ? null : [$penyedia, $baris->keKredensial(), (string) $baris->Id];
    }
}
