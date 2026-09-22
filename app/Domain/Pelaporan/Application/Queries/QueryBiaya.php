<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\BiayaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Database\Eloquent\Builder;

/** KPI biaya pemeliharaan (21.01: cost). */
final class QueryBiaya implements PenyediaKpi
{
    use MenyaringLingkup;

    public function kunciDilayani(): array
    {
        return ['biaya.pemeliharaan', 'biaya.per_aset'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'biaya.pemeliharaan' => $this->pemeliharaan($filter),
            'biaya.per_aset' => $this->perAset($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryBiaya."),
        };
    }

    private function pemeliharaan(FilterMetrik $filter): HasilKpi
    {
        $perBulan = $this->lingkup($filter)
            ->selectRaw("DATE_FORMAT(TanggalBiaya, '%Y-%m') as Bulan, SUM(Jumlah) as Nilai")
            ->groupBy('Bulan')
            ->pluck('Nilai', 'Bulan')
            ->map(fn ($nilai): float => (float) $nilai)
            ->all();

        $perJenis = $this->lingkup($filter)
            ->selectRaw('JenisBiaya, SUM(Jumlah) as Nilai')
            ->groupBy('JenisBiaya')
            ->pluck('Nilai', 'JenisBiaya')
            ->map(fn ($nilai): float => (float) $nilai);

        return new HasilKpi(
            (float) array_sum($perBulan),
            $this->deretBulanan($filter, $perBulan),
            ['PerJenis' => $perJenis->all()],
        );
    }

    private function perAset(FilterMetrik $filter): HasilKpi
    {
        $total = (float) $this->lingkup($filter)->sum('Jumlah');

        $perintahKerjaId = $this->lingkup($filter)->distinct()->pluck('PerintahKerjaId');
        $jumlahAset = (int) PerintahKerjaAset::query()
            ->whereIn('PerintahKerjaId', $perintahKerjaId)
            ->distinct()
            ->count('AsetId');

        if ($jumlahAset === 0) {
            return new HasilKpi(0.0, [], ['AdaData' => false, 'Penyebut' => 0, 'TotalBiaya' => $total]);
        }

        return new HasilKpi(
            round($total / $jumlahAset, 2),
            [],
            ['AdaData' => true, 'Penyebut' => $jumlahAset, 'TotalBiaya' => $total],
        );
    }

    /** @return Builder<BiayaPerintahKerja> */
    private function lingkup(FilterMetrik $filter): Builder
    {
        $query = BiayaPerintahKerja::query()
            ->whereBetween('TanggalBiaya', [$filter->dari->toDateString(), $filter->sampai->toDateString()]);

        if ($filter->adaFilterUnit() || $filter->adaFilterLokasi()) {
            $query->whereIn('PerintahKerjaId', $this->perintahKerjaDalamLingkup($filter));
        }

        return $query;
    }

    private function perintahKerjaDalamLingkup(FilterMetrik $filter): \Illuminate\Contracts\Database\Query\Builder
    {
        $subquery = PerintahKerja::query()
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
}
