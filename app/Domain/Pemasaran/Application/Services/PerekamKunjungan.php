<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\JenisPerangkat;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AttributionPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiPengunjung;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\UtmPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Illuminate\Http\Request;

/** Merekam satu kedatangan beserta attribution-nya (MARKETING.md 14). */
final class PerekamKunjungan
{
    private const SUMBER_LANGSUNG = 'direct';

    public function __construct(private readonly TransaksiDatabase $transaksi) {}

    public function rekam(Request $request, string $pengenalPengunjung): SesiPengunjung
    {
        return $this->transaksi->jalankan(function () use ($request, $pengenalPengunjung): SesiPengunjung {
            $sesi = SesiPengunjung::create([
                'PengenalPengunjung' => $pengenalPengunjung,
                'AlamatIp' => $request->ip(),
                'AgenPengguna' => $request->userAgent(),
                'Perangkat' => $this->perangkat($request->userAgent())->value,
                'Referrer' => $request->headers->get('referer'),
                'LandingUrl' => $request->fullUrl(),
                'Host' => $request->getHost(),
                'DimulaiPada' => now(),
                'TerakhirAktifPada' => now(),
            ]);

            $utm = $this->rekamUtm($request, $sesi);
            $this->perbaruiAttribution($sesi, $utm);

            return $sesi;
        });
    }

    private function rekamUtm(Request $request, SesiPengunjung $sesi): UtmPemasaran
    {
        $campaign = $this->parameter($request, 'utm_campaign');

        return UtmPemasaran::create([
            'SesiPengunjungId' => $sesi->Id,
            'KampanyeId' => $campaign === null ? null : $this->kampanyeUntuk($campaign),
            'Source' => $this->parameter($request, 'utm_source'),
            'Medium' => $this->parameter($request, 'utm_medium'),
            'Campaign' => $campaign,
            'Term' => $this->parameter($request, 'utm_term'),
            'Content' => $this->parameter($request, 'utm_content'),
        ]);
    }

    private function perbaruiAttribution(SesiPengunjung $sesi, UtmPemasaran $utm): void
    {
        $attribution = AttributionPemasaran::query()->firstOrNew([
            'PengenalPengunjung' => $sesi->PengenalPengunjung,
        ]);

        $sumber = $utm->Source ?? $this->sumberDariReferrer($sesi->Referrer);
        $medium = $utm->Medium ?? ($utm->Source === null && $sesi->Referrer === null ? self::SUMBER_LANGSUNG : 'referral');

        if (! $attribution->sudahAdaSentuhanPertama()) {
            $attribution->fill([
                'SumberPertama' => $sumber,
                'MediumPertama' => $medium,
                'KampanyePertama' => $utm->Campaign,
                'KampanyeIdPertama' => $utm->KampanyeId,
                'LandingPertama' => $sesi->LandingUrl,
                'ReferrerPertama' => $sesi->Referrer,
                'SentuhanPertamaPada' => $sesi->DimulaiPada,
            ]);
        }

        $attribution->fill([
            'SumberTerakhir' => $sumber,
            'MediumTerakhir' => $medium,
            'KampanyeTerakhir' => $utm->Campaign,
            'KampanyeIdTerakhir' => $utm->KampanyeId,
            'LandingTerakhir' => $sesi->LandingUrl,
            'ReferrerTerakhir' => $sesi->Referrer,
            'SentuhanTerakhirPada' => $sesi->DimulaiPada,
        ]);

        $attribution->save();
    }

    /** Kode kampanye dicocokkan ke barisnya bila ada. */
    private function kampanyeUntuk(string $kode): ?string
    {
        return Kampanye::query()->where('Kode', $kode)->value('Id');
    }

    private function sumberDariReferrer(?string $referrer): string
    {
        if ($referrer === null || $referrer === '') {
            return self::SUMBER_LANGSUNG;
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : self::SUMBER_LANGSUNG;
    }

    private function parameter(Request $request, string $nama): ?string
    {
        $nilai = $request->query($nama);

        if (! is_string($nilai)) {
            return null;
        }

        $bersih = trim($nilai);

        // Dipotong pada panjang kolomnya: nilai UTM datang dari URL dan panjang apa pun dapat dikirim orang luar.
        return $bersih === '' ? null : mb_substr($bersih, 0, 190);
    }

    private function perangkat(?string $agen): JenisPerangkat
    {
        if ($agen === null) {
            return JenisPerangkat::Lainnya;
        }

        $agen = strtolower($agen);

        return match (true) {
            str_contains($agen, 'ipad') || str_contains($agen, 'tablet') => JenisPerangkat::Tablet,
            str_contains($agen, 'mobi') || str_contains($agen, 'android') => JenisPerangkat::Ponsel,
            str_contains($agen, 'mozilla') || str_contains($agen, 'chrome') => JenisPerangkat::Desktop,
            default => JenisPerangkat::Lainnya,
        };
    }
}
