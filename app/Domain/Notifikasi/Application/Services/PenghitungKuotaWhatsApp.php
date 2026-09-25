<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\Services;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Notifikasi\Domain\Enums\KanalNotifikasi;
use App\Domain\Notifikasi\Domain\Enums\StatusNotifikasi;
use App\Domain\Notifikasi\Domain\Enums\SumberPenyediaNotifikasi;
use App\Domain\Notifikasi\Domain\ValueObjects\KuotaWhatsApp;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;

/**
 * Menghitung kuota WhatsApp bawaan organisasi bulan ini (PRD 8.23).
 *
 * Yang dihitung hanya notifikasi WhatsApp lewat nomor Amanpoll yang sedang antri atau
 * sudah terkirim; yang gagal tidak memakan kuota. Bulan dibaca di zona organisasi, jadi
 * kuota rumah sakit WIB berganti pukul 00.00 WIB tanggal 1, bukan pukul 07.00.
 */
final class PenghitungKuotaWhatsApp
{
    public function __construct(
        private readonly PemeriksaEntitlement $entitlement,
        private readonly KalenderOrganisasi $kalender,
    ) {}

    public function untuk(string $organisasiId): KuotaWhatsApp
    {
        $entitlement = $this->entitlement->untukOrganisasi($organisasiId);
        $batas = $entitlement->batas(KatalogFitur::BATAS_WHATSAPP_BULANAN);

        return new KuotaWhatsApp(
            $this->terpakai($organisasiId),
            $batas === null ? null : (int) floor($batas),
            $entitlement->bolehFitur(KatalogFitur::BATAS_WHATSAPP_BULANAN),
        );
    }

    public function terpakai(string $organisasiId): int
    {
        $awalBulan = $this->kalender->sekarang($organisasiId)->startOfMonth();

        return Notifikasi::query()
            ->withoutGlobalScope(ScopeOrganisasi::class)
            ->where('OrganisasiId', $organisasiId)
            ->where('Kanal', KanalNotifikasi::WhatsApp->value)
            ->where('JadwalKirimPada', '>=', $awalBulan->utc())
            ->where('JadwalKirimPada', '<', $awalBulan->addMonth()->utc())
            ->where('SumberPenyedia', SumberPenyediaNotifikasi::Platform->value)
            ->whereIn('Status', [StatusNotifikasi::Antri->value, StatusNotifikasi::Terkirim->value])
            ->count();
    }
}
