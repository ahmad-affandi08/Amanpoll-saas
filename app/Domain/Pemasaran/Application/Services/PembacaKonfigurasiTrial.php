<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Langganan\Application\Services\LayananKebijakanTenggang;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\KonfigurasiTrial;

/**
 * Setelan trial, dirakit dari domain Langganan (MARKETING.md 12).
 *
 * Durasi, paket, batas, dan grace period tidak pernah disalin ke Pemasaran —
 * dua sumber untuk angka yang sama adalah dua angka yang akan berbeda.
 */
final class PembacaKonfigurasiTrial
{
    public function __construct(
        private readonly LayananKebijakanTenggang $kebijakan,
        private readonly LayananKonfigurasiPemasaran $konfigurasi,
        private readonly PemeriksaEntitlement $entitlement,
    ) {}

    public function berlaku(): KonfigurasiTrial
    {
        $paket = $this->paketTrial();

        return new KonfigurasiTrial(
            durasiHari: $this->kebijakan->hariUjiCoba(),
            paketId: $paket?->Id,
            namaPaket: $paket?->Nama,
            kartuDiperlukan: (bool) $this->konfigurasi->ambil(KatalogKonfigurasiPemasaran::TRIAL_KARTU_DIPERLUKAN),
            batasPengguna: $this->batas($paket, KatalogFitur::BATAS_PENGGUNA),
            batasLokasi: $this->batas($paket, KatalogFitur::BATAS_LOKASI),
            batasAset: $this->batas($paket, KatalogFitur::BATAS_ASET),
            hariTenggang: $this->kebijakan->hariTenggang(),
            perpanjanganMaksHari: $this->konfigurasi->angka(
                KatalogKonfigurasiPemasaran::TRIAL_PERPANJANGAN_MAKS_HARI,
            ),
        );
    }

    /** Batas satu organisasi yang sedang berjalan, lengkap dengan paket yang benar-benar dipakainya. */
    public function batasOrganisasi(string $organisasiId): KonfigurasiTrial
    {
        $dasar = $this->berlaku();
        $entitlement = $this->entitlement->untukOrganisasi($organisasiId);

        return new KonfigurasiTrial(
            durasiHari: $dasar->durasiHari,
            paketId: $entitlement->paketId ?? $dasar->paketId,
            namaPaket: $entitlement->namaPaket ?? $dasar->namaPaket,
            kartuDiperlukan: $dasar->kartuDiperlukan,
            batasPengguna: $entitlement->batas[KatalogFitur::BATAS_PENGGUNA] ?? $dasar->batasPengguna,
            batasLokasi: $entitlement->batas[KatalogFitur::BATAS_LOKASI] ?? $dasar->batasLokasi,
            batasAset: $entitlement->batas[KatalogFitur::BATAS_ASET] ?? $dasar->batasAset,
            hariTenggang: $dasar->hariTenggang,
            perpanjanganMaksHari: $dasar->perpanjanganMaksHari,
        );
    }

    /** Paket yang dipakai trial: yang kodenya disetel, atau paket aktif termurah. */
    private function paketTrial(): ?PaketLangganan
    {
        $kode = $this->konfigurasi->ambil(KatalogKonfigurasiPemasaran::TRIAL_PAKET_KODE);

        if (is_string($kode) && $kode !== '') {
            $paket = PaketLangganan::query()->where('Kode', $kode)->first();

            if ($paket !== null) {
                return $paket;
            }
        }

        return PaketLangganan::query()
            ->where('Aktif', true)
            ->orderBy('HargaBulanan')
            ->first();
    }

    private function batas(?PaketLangganan $paket, string $kodeFitur): ?float
    {
        if ($paket === null) {
            return KatalogFitur::ambil($kodeFitur)->batasBawaan;
        }

        $baris = PaketFitur::query()
            ->where('PaketLanggananId', $paket->Id)
            ->whereHas('fiturPaket', fn ($q) => $q->where('Kode', $kodeFitur))
            ->first();

        return $baris?->BatasNilai === null
            ? KatalogFitur::ambil($kodeFitur)->batasBawaan
            : (float) $baris->BatasNilai;
    }
}
