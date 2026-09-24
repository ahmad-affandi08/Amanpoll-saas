<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\Persediaan\Application\Services\LingkupGudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Database\Eloquent\Builder;

/** KPI persediaan (21.01: stock). */
final class QueryStok implements PenyediaKpi
{
    public function __construct(private readonly LingkupGudang $lingkupGudang) {}

    public function kunciDilayani(): array
    {
        return ['stok.nilai', 'stok.di_bawah_minimum'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'stok.nilai' => $this->nilai($filter),
            'stok.di_bawah_minimum' => $this->diBawahMinimum($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryStok."),
        };
    }

    private function nilai(FilterMetrik $filter): HasilKpi
    {
        $baris = $this->lingkup($filter)
            ->join('SukuCadang', 'SukuCadang.Id', '=', 'StokSukuCadang.SukuCadangId')
            ->whereNull('SukuCadang.DihapusPada')
            ->join('Gudang', 'Gudang.Id', '=', 'StokSukuCadang.GudangId')
            ->selectRaw('Gudang.Nama as Gudang, SUM(StokSukuCadang.JumlahTersedia * SukuCadang.HargaRataRata) as Nilai')
            ->groupBy('Gudang.Nama')
            ->orderByDesc('Nilai')
            ->toBase()
            ->get()
            ->map(fn (object $baris): array => get_object_vars($baris));

        return new HasilKpi(
            (float) $baris->sum(fn (array $satu): float => (float) $satu['Nilai']),
            $baris->map(fn (array $satu): array => [
                'Label' => (string) $satu['Gudang'],
                'Nilai' => (float) $satu['Nilai'],
            ])->values()->all(),
        );
    }

    private function diBawahMinimum(FilterMetrik $filter): HasilKpi
    {
        $baris = $this->lingkup($filter)
            ->join('SukuCadang', 'SukuCadang.Id', '=', 'StokSukuCadang.SukuCadangId')
            ->whereNull('SukuCadang.DihapusPada')
            ->where('SukuCadang.StokMinimum', '>', 0)
            ->selectRaw('SukuCadang.Id as SukuCadangId, SukuCadang.Kode, SukuCadang.Nama, SukuCadang.StokMinimum, SUM(StokSukuCadang.JumlahTersedia) as Tersedia')
            ->groupBy('SukuCadang.Id', 'SukuCadang.Kode', 'SukuCadang.Nama', 'SukuCadang.StokMinimum')
            ->havingRaw('SUM(StokSukuCadang.JumlahTersedia) < SukuCadang.StokMinimum')
            ->toBase()
            ->get()
            ->map(fn (object $baris): array => get_object_vars($baris));

        return new HasilKpi(
            (float) $baris->count(),
            $baris->take(20)->map(fn (array $satu): array => [
                'Label' => $satu['Kode'].' — '.$satu['Nama'],
                'Nilai' => (float) $satu['Tersedia'],
                'Minimum' => (float) $satu['StokMinimum'],
            ])->values()->all(),
        );
    }

    /**
     * Stok mengikuti gudangnya: lokasi gudang dan unit pengelola gudang (PRD 8.21).
     * Gudang tidak punya unit organisasi, jadi filter itu tidak berlaku di sini.
     * Tanpa filter pun, pengguna berlingkup hanya menghitung stok gudang yang
     * terlihat olehnya, sama seperti halaman stok.
     *
     * @return Builder<StokSukuCadang>
     */
    private function lingkup(FilterMetrik $filter): Builder
    {
        $query = $this->lingkupGudang->saring(StokSukuCadang::query(), 'StokSukuCadang.GudangId');

        if (! $filter->adaFilterLokasi() && ! $filter->adaFilterUnitPengelola()) {
            return $query;
        }

        $gudang = Gudang::query()->select('Id');

        if ($filter->adaFilterLokasi()) {
            $gudang->whereIn('LokasiId', $filter->lokasiId);
        }
        if ($filter->adaFilterUnitPengelola()) {
            $gudang->whereIn('UnitPengelolaId', $filter->unitPengelolaId);
        }

        return $query->whereIn('GudangId', $gudang->getQuery());
    }
}
