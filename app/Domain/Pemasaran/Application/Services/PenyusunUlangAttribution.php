<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AttributionPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiPengunjung;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\UtmPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;

/** Menyusun ulang attribution dari sesi yang tersimpan (MARKETING.md 14). */
final class PenyusunUlangAttribution
{
    private const SUMBER_LANGSUNG = 'direct';

    public function __construct(private readonly TransaksiDatabase $transaksi) {}

    public function susunUlang(string $pengenalPengunjung): ?AttributionPemasaran
    {
        return $this->transaksi->jalankan(function () use ($pengenalPengunjung): ?AttributionPemasaran {
            /** @var list<SesiPengunjung> $sesi */
            $sesi = SesiPengunjung::query()
                ->where('PengenalPengunjung', $pengenalPengunjung)
                ->orderBy('DimulaiPada')
                ->orderBy('Id')
                ->get()
                ->all();

            if ($sesi === []) {
                return null;
            }

            $pertama = $sesi[0];
            $terakhir = $sesi[count($sesi) - 1];

            // UTM diambil sekali untuk kedua sesi yang dipakai, bukan lewat properti relasi.
            $utm = UtmPemasaran::query()
                ->whereIn('SesiPengunjungId', array_unique([$pertama->Id, $terakhir->Id]))
                ->get()
                ->keyBy('SesiPengunjungId');

            $attribution = AttributionPemasaran::query()->firstOrNew([
                'PengenalPengunjung' => $pengenalPengunjung,
            ]);

            $attribution->fill([
                ...$this->kolom($pertama, $utm->get($pertama->Id), 'Pertama'),
                ...$this->kolom($terakhir, $utm->get($terakhir->Id), 'Terakhir'),
            ]);

            $attribution->save();

            return $attribution;
        });
    }

    /** @return array<string, mixed> */
    private function kolom(SesiPengunjung $sesi, ?UtmPemasaran $utm, string $akhiran): array
    {
        $campaign = $utm?->Campaign;
        $sumberUtm = $utm?->Source;
        $mediumUtm = $utm?->Medium;

        $sumber = $sumberUtm !== null ? $sumberUtm : $this->sumberDariReferrer($sesi->Referrer);
        $medium = match (true) {
            $mediumUtm !== null => $mediumUtm,
            $sumberUtm === null && $sesi->Referrer === null => self::SUMBER_LANGSUNG,
            default => 'referral',
        };

        return [
            'Sumber'.$akhiran => $sumber,
            'Medium'.$akhiran => $medium,
            'Kampanye'.$akhiran => $campaign,
            // Kampanye yang baru didaftarkan belakangan tetap tertaut di sini.
            'KampanyeId'.$akhiran => $campaign === null
                ? null
                : Kampanye::query()->where('Kode', $campaign)->value('Id'),
            'Landing'.$akhiran => $sesi->LandingUrl,
            'Referrer'.$akhiran => $sesi->Referrer,
            'Sentuhan'.($akhiran === 'Pertama' ? 'Pertama' : 'Terakhir').'Pada' => $sesi->DimulaiPada,
        ];
    }

    private function sumberDariReferrer(?string $referrer): string
    {
        if ($referrer === null || $referrer === '') {
            return self::SUMBER_LANGSUNG;
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : self::SUMBER_LANGSUNG;
    }
}
