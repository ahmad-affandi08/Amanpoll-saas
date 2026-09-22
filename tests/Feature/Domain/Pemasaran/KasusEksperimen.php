<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PenetapVarianEksperimen;
use App\Domain\Pemasaran\Domain\Enums\MetrikEksperimen;
use App\Domain\Pemasaran\Domain\Enums\StatusEksperimen;
use App\Domain\Pemasaran\Domain\Enums\TargetEksperimen;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksperimenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PartisipasiEksperimen;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VarianEksperimen;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/** Dasar test eksperimen: satu eksperimen dua varian dengan bobot yang sama. */
abstract class KasusEksperimen extends KasusPemasaran
{
    protected function buatEksperimen(
        int $minimumSampel = 200,
        StatusEksperimen $status = StatusEksperimen::Aktif,
        MetrikEksperimen $metrik = MetrikEksperimen::Ctr,
        string $kode = 'headline-q1',
    ): EksperimenPemasaran {
        $eksperimen = EksperimenPemasaran::create([
            'Kode' => $kode,
            'Nama' => 'Headline kuartal 1',
            'Target' => TargetEksperimen::Headline,
            'MetrikUtama' => $metrik,
            'Hipotesis' => 'Headline berangka menaikkan klik.',
            'Status' => $status,
            'MinimumSampel' => $minimumSampel,
            'MulaiPada' => CarbonImmutable::now(),
        ]);

        foreach ([['A', 'Kontrol', true], ['B', 'Varian angka', false]] as [$kode, $nama, $kontrol]) {
            VarianEksperimen::create([
                'EksperimenPemasaranId' => $eksperimen->Id,
                'Kode' => $kode,
                'Nama' => $nama,
                'Bobot' => 1,
                'Kontrol' => $kontrol,
            ]);
        }

        return $eksperimen->fresh() ?? $eksperimen;
    }

    protected function varian(EksperimenPemasaran $eksperimen, string $kode): VarianEksperimen
    {
        return VarianEksperimen::query()
            ->where('EksperimenPemasaranId', $eksperimen->Id)
            ->where('Kode', $kode)
            ->firstOrFail();
    }

    /**
     * Mendaftarkan sejumlah pengunjung ke satu varian, tanpa lewat roda bobot.
     *
     * @return list<string>
     */
    protected function daftarkanKeVarian(
        EksperimenPemasaran $eksperimen,
        VarianEksperimen $varian,
        int $jumlah,
    ): array {
        $pengenal = [];

        foreach (range(1, $jumlah) as $ke) {
            $satu = (string) Str::ulid();
            $pengenal[] = $satu;

            PartisipasiEksperimen::create([
                'EksperimenPemasaranId' => $eksperimen->Id,
                'VarianEksperimenId' => $varian->Id,
                'PengenalPengunjung' => $satu,
                'DitetapkanPada' => CarbonImmutable::now(),
            ]);
        }

        return $pengenal;
    }

    /** @param list<string> $pengenal */
    protected function catatKlik(array $pengenal, int $jumlah): void
    {
        foreach (array_slice($pengenal, 0, $jumlah) as $satu) {
            EventPemasaran::create([
                'PengenalPengunjung' => $satu,
                'Jenis' => KatalogPeristiwaPemasaran::CTA_DIKLIK,
                'TerjadiPada' => CarbonImmutable::now(),
            ]);
        }
    }

    protected function penetap(): PenetapVarianEksperimen
    {
        return app(PenetapVarianEksperimen::class);
    }
}
