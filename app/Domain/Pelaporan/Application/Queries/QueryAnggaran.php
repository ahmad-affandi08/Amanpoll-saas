<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;

/** KPI anggaran (21.01: budget). */
final class QueryAnggaran implements PenyediaKpi
{
    public function kunciDilayani(): array
    {
        return ['anggaran.serapan', 'anggaran.sisa'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'anggaran.serapan' => $this->serapan($filter),
            'anggaran.sisa' => $this->sisa($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryAnggaran."),
        };
    }

    private function serapan(FilterMetrik $filter): HasilKpi
    {
        $baris = $this->rekap($filter);

        return HasilKpi::persen(
            (float) $baris->sum(fn (array $satu): float => (float) $satu['Terpakai']),
            (float) $baris->sum(fn (array $satu): float => (float) $satu['Pagu']),
            $baris->map(fn (array $satu): array => [
                'Label' => (string) $satu['Nama'],
                'Nilai' => (float) $satu['Terpakai'],
                'Pagu' => (float) $satu['Pagu'],
                'Ditahan' => (float) $satu['Ditahan'],
            ])->values()->all(),
        );
    }

    private function sisa(FilterMetrik $filter): HasilKpi
    {
        $baris = $this->rekap($filter);

        $sisa = $baris->sum(
            fn (array $satu): float => (float) $satu['Pagu'] - (float) $satu['Terpakai'] - (float) $satu['Ditahan'],
        );

        return new HasilKpi((float) $sisa, $baris->map(fn (array $satu): array => [
            'Label' => (string) $satu['Nama'],
            'Nilai' => (float) $satu['Pagu'] - (float) $satu['Terpakai'] - (float) $satu['Ditahan'],
        ])->values()->all());
    }

    /** @return Collection<int, array<string, mixed>> */
    private function rekap(FilterMetrik $filter): Collection
    {
        return PosAnggaran::query()
            ->join('Anggaran', 'Anggaran.Id', '=', 'PosAnggaran.AnggaranId')
            ->whereIn('PosAnggaran.AnggaranId', $this->anggaranDalamLingkup($filter))
            ->selectRaw('Anggaran.Nama as Nama, SUM(PosAnggaran.Jumlah) as Pagu, SUM(PosAnggaran.Terpakai) as Terpakai, SUM(PosAnggaran.Ditahan) as Ditahan')
            ->groupBy('Anggaran.Nama')
            ->orderBy('Anggaran.Nama')
            ->toBase()
            ->get()
            ->map(fn (object $baris): array => $this->keArrayAsosiatif($baris));
    }

    /** @return array<string, mixed> */
    private function keArrayAsosiatif(object $baris): array
    {
        return get_object_vars($baris);
    }

    private function anggaranDalamLingkup(FilterMetrik $filter): Builder
    {
        $query = Anggaran::query()
            ->select('Id')
            ->whereBetween('Tahun', [(int) substr($filter->tanggalDari(), 0, 4), (int) substr($filter->tanggalSampai(), 0, 4)]);

        if ($filter->adaFilterUnit()) {
            $query->whereIn('UnitOrganisasiId', $filter->unitOrganisasiId);
        }

        return $query->getQuery();
    }
}
