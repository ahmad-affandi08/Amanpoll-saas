<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Services;

use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use Carbon\CarbonImmutable;

/** Pembacaan status langganan satu organisasi (22.04). */
final class LayananLangganan
{
    public function __construct(private readonly LayananKebijakanTenggang $kebijakan) {}

    /** Langganan yang sedang berlaku untuk sebuah organisasi. */
    public function untukOrganisasi(string $organisasiId): ?Langganan
    {
        return Langganan::query()
            ->withoutGlobalScopes()
            ->with('paketLangganan')
            ->where('OrganisasiId', $organisasiId)
            ->orderByDesc('MulaiPada')
            ->orderByDesc('DibuatPada')
            ->first();
    }

    /** Status efektif hari ini. */
    public function statusEfektif(Langganan $langganan, ?CarbonImmutable $pada = null): StatusLangganan
    {
        $pada ??= CarbonImmutable::now();
        $hariIni = $pada->startOfDay();

        $tersimpan = StatusLangganan::tryFrom((string) $langganan->Status);
        if ($tersimpan === StatusLangganan::Dibatalkan) {
            return StatusLangganan::Dibatalkan;
        }

        $ujiCobaSampai = $langganan->UjiCobaSampai;
        if ($ujiCobaSampai !== null && $hariIni->lessThanOrEqualTo(CarbonImmutable::parse($ujiCobaSampai)->startOfDay())) {
            return StatusLangganan::UjiCoba;
        }

        $berakhirPada = $langganan->BerakhirPada;
        if ($berakhirPada === null) {
            // Langganan tanpa tanggal akhir adalah langganan berjalan.
            return StatusLangganan::Aktif;
        }

        $akhir = CarbonImmutable::parse($berakhirPada)->startOfDay();
        if ($hariIni->lessThanOrEqualTo($akhir)) {
            return StatusLangganan::Aktif;
        }

        $akhirTenggang = $akhir->addDays($this->kebijakan->hariTenggang());

        return $hariIni->lessThanOrEqualTo($akhirTenggang)
            ? StatusLangganan::Tenggang
            : StatusLangganan::Kedaluwarsa;
    }

    /** Uji coba yang sudah lewat tidak boleh ikut dilaporkan sebagai uji coba berjalan. */
    public function ujiCobaMasihBerjalan(Langganan $langganan, ?CarbonImmutable $pada = null): bool
    {
        $ujiCobaSampai = $langganan->UjiCobaSampai;
        if ($ujiCobaSampai === null) {
            return false;
        }

        $pada ??= CarbonImmutable::now();

        return $pada->startOfDay()->lessThanOrEqualTo(CarbonImmutable::parse($ujiCobaSampai)->startOfDay());
    }
}
