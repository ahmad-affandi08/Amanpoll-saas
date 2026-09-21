<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JadwalPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaanAset;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * KPI pemeliharaan preventif (21.01: preventive).
 */
final class QueryPreventif implements PenyediaKpi
{
    use MenyaringLingkup;

    public function kunciDilayani(): array
    {
        return ['preventif.jatuh_tempo', 'preventif.kepatuhan'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'preventif.jatuh_tempo' => $this->jatuhTempo($filter),
            'preventif.kepatuhan' => $this->kepatuhan($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryPreventif."),
        };
    }

    private function jatuhTempo(FilterMetrik $filter): HasilKpi
    {
        $hariIni = CarbonImmutable::now()->toDateString();

        $terlambat = (int) $this->lingkup($filter)
            ->where('Status', 'Terjadwal')
            ->where('TanggalJadwal', '<', $hariIni)
            ->count();

        $hariIniJumlah = (int) $this->lingkup($filter)
            ->where('Status', 'Terjadwal')
            ->whereDate('TanggalJadwal', $hariIni)
            ->count();

        return new HasilKpi((float) ($terlambat + $hariIniJumlah), [
            ['Label' => 'Terlambat', 'Nilai' => (float) $terlambat],
            ['Label' => 'Jatuh tempo hari ini', 'Nilai' => (float) $hariIniJumlah],
        ]);
    }

    private function kepatuhan(FilterMetrik $filter): HasilKpi
    {
        $perStatus = $this->lingkup($filter)
            ->whereBetween('TanggalJadwal', [$filter->dari->toDateString(), $filter->sampai->toDateString()])
            ->selectRaw('Status, COUNT(*) as Jumlah')
            ->groupBy('Status')
            ->pluck('Jumlah', 'Status');

        return HasilKpi::persen(
            (float) ($perStatus['Selesai'] ?? 0),
            (float) $perStatus->sum(),
            $perStatus->map(fn (int|string $jumlah, string $status): array => [
                'Label' => $status,
                'Nilai' => (float) $jumlah,
            ])->values()->all(),
        );
    }

    /** @return Builder<JadwalPemeliharaan> */
    private function lingkup(FilterMetrik $filter): Builder
    {
        $query = JadwalPemeliharaan::query();

        if ($filter->adaFilterUnit() || $filter->adaFilterLokasi()) {
            $query->whereIn(
                'RencanaPemeliharaanAsetId',
                RencanaPemeliharaanAset::query()
                    ->select('Id')
                    ->whereIn('AsetId', $this->asetDalamLingkup($filter))
                    ->getQuery(),
            );
        }

        return $query;
    }
}
