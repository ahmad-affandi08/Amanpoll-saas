<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Services;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Langganan\Domain\Enums\TipeBatasFitur;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Notifikasi\Application\Services\PenghitungKuotaWhatsApp;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Exceptions\LanggananTidakMengizinkan;

/** Penegakan batas kuota (22.05). */
final class PenjagaBatasLangganan
{
    public function __construct(
        private readonly PemeriksaEntitlement $entitlement,
        private readonly KonteksOrganisasi $konteks,
        private readonly PenghitungKuotaWhatsApp $kuotaWhatsApp,
    ) {}

    /** Memastikan penambahan satu baris tidak melewati batas paket. */
    public function pastikanMasihMuat(string $kodeFitur, int $tambahan = 1): void
    {
        $definisi = KatalogFitur::ambil($kodeFitur);
        $batas = $this->entitlement->sekarang()->batas($kodeFitur);

        if ($batas === null) {
            return;
        }

        $terpakai = $this->terpakai($kodeFitur);
        if ($terpakai + $tambahan <= $batas) {
            return;
        }

        $satuan = $definisi->satuanBatas ?? 'entri';

        throw new LanggananTidakMengizinkan(
            "Paket langganan Anda dibatasi {$this->angka($batas)} {$satuan} dan saat ini sudah terpakai "
            ."{$this->angka((float) $terpakai)}. Naikkan paket untuk menambah {$satuan}.",
        );
    }

    /**
     * Pemakaian terkini per fitur berbatas, untuk ditampilkan di halaman
     * langganan dan dikirim sebagai prop ke UI.
     *
     * @return array<string, int>
     */
    public function pemakaian(): array
    {
        $hasil = [];

        foreach (KatalogFitur::bertipe(TipeBatasFitur::Angka) as $definisi) {
            $hasil[$definisi->kode] = $this->terpakai($definisi->kode);
        }

        return $hasil;
    }

    /** Baris yang dihitung terhadap kuota. */
    private function terpakai(string $kodeFitur): int
    {
        $organisasiId = $this->konteks->id();
        if ($organisasiId === null) {
            return 0;
        }

        return match ($kodeFitur) {
            KatalogFitur::BATAS_ASET => Aset::query()
                ->withoutGlobalScopes()
                ->where('OrganisasiId', $organisasiId)
                ->whereNull('DihapusPada')
                ->count(),
            KatalogFitur::BATAS_PENGGUNA => Pengguna::query()
                ->withoutGlobalScopes()
                ->where('OrganisasiId', $organisasiId)
                ->whereNull('DihapusPada')
                ->where('Status', 'Aktif')
                ->count(),
            // Kuota bulanan ini ditegakkan saat notifikasi dibuat (LayananNotifikasi), bukan lewat pastikanMasihMuat().
            KatalogFitur::BATAS_WHATSAPP_BULANAN => $this->kuotaWhatsApp->terpakai($organisasiId),
            default => 0,
        };
    }

    private function angka(float $nilai): string
    {
        return number_format($nilai, 0, ',', '.');
    }
}
