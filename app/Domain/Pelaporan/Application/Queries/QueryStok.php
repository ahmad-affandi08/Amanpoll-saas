<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Database\Eloquent\Builder;

/**
 * KPI persediaan (21.01: stock).
 *
 * Stok adalah posisi saat ini, jadi rentang tanggal tidak dipakai. Filter unit
 * organisasi juga tidak berlaku karena gudang tidak berada di bawah unit; yang
 * berlaku hanya lokasi, lewat gudang.
 */
final class QueryStok implements PenyediaKpi
{
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

    /** @return Builder<StokSukuCadang> */
    private function lingkup(FilterMetrik $filter): Builder
    {
        $query = StokSukuCadang::query();

        if ($filter->adaFilterLokasi()) {
            $query->whereIn(
                'GudangId',
                Gudang::query()->select('Id')->whereIn('LokasiId', $filter->lokasiId)->getQuery(),
            );
        }

        return $query;
    }
}
