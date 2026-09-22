<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use Illuminate\Contracts\Database\Query\Builder as KontrakBuilder;
use Illuminate\Database\Eloquent\Builder;

/** Penerapan filter unit organisasi dan lokasi (21.02) ke query metrik. */
trait MenyaringLingkup
{
    /**
     * Tabel yang punya kolom UnitOrganisasiId dan/atau LokasiId sendiri.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function saringLangsung(Builder $query, FilterMetrik $filter, bool $adaKolomUnit = true): Builder
    {
        if ($adaKolomUnit && $filter->adaFilterUnit()) {
            $query->whereIn('UnitOrganisasiId', $filter->unitOrganisasiId);
        }

        if ($filter->adaFilterLokasi()) {
            $query->whereIn('LokasiId', $filter->lokasiId);
        }

        return $query;
    }

    /**
     * Tabel yang menunjuk aset; lingkup diambil dari aset tersebut.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function saringLewatAset(Builder $query, FilterMetrik $filter, string $kolomAset = 'AsetId'): Builder
    {
        if (! $filter->adaFilterUnit() && ! $filter->adaFilterLokasi()) {
            return $query;
        }

        return $query->whereIn($kolomAset, $this->asetDalamLingkup($filter));
    }

    /** Subquery id aset yang masuk lingkup filter. */
    private function asetDalamLingkup(FilterMetrik $filter): KontrakBuilder
    {
        $subquery = Aset::query()
            ->select('Id')
            ->whereNull('DihapusPada');

        if ($filter->adaFilterUnit()) {
            $subquery->whereIn('UnitOrganisasiId', $filter->unitOrganisasiId);
        }
        if ($filter->adaFilterLokasi()) {
            $subquery->whereIn('LokasiId', $filter->lokasiId);
        }

        return $subquery->getQuery();
    }

    /**
     * Menyusun deret tanggal kosong sepanjang rentang, lalu mengisinya dari
     * hasil agregasi. Tanpa ini, grafik tren akan melompati hari tanpa data dan
     * membuat garis terlihat lebih mulus daripada kenyataannya.
     *
     * @param  array<string, float|int>  $nilaiPerTanggal
     * @return list<array{Label: string, Nilai: float}>
     */
    private function deretHarian(FilterMetrik $filter, array $nilaiPerTanggal): array
    {
        $deret = [];
        $tanggal = $filter->dari->startOfDay();

        while ($tanggal->lessThanOrEqualTo($filter->sampai)) {
            $kunci = $tanggal->toDateString();
            $deret[] = ['Label' => $kunci, 'Nilai' => (float) ($nilaiPerTanggal[$kunci] ?? 0)];
            $tanggal = $tanggal->addDay();
        }

        return $deret;
    }

    /**
     * Sama seperti deretHarian tetapi per bulan, untuk metrik biaya yang tidak
     * bermakna dibaca harian.
     *
     * @param  array<string, float|int>  $nilaiPerBulan
     * @return list<array{Label: string, Nilai: float}>
     */
    private function deretBulanan(FilterMetrik $filter, array $nilaiPerBulan): array
    {
        $deret = [];
        $bulan = $filter->dari->startOfMonth();

        while ($bulan->lessThanOrEqualTo($filter->sampai)) {
            $kunci = $bulan->format('Y-m');
            $deret[] = ['Label' => $kunci, 'Nilai' => (float) ($nilaiPerBulan[$kunci] ?? 0)];
            $bulan = $bulan->addMonth();
        }

        return $deret;
    }
}
