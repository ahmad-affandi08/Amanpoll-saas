<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Database\Eloquent\Builder;

/** KPI kalibrasi (21.01: calibration). */
final class QueryKalibrasi implements PenyediaKpi
{
    use MenyaringLingkup;

    public function kunciDilayani(): array
    {
        return ['kalibrasi.jatuh_tempo', 'kalibrasi.kepatuhan'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'kalibrasi.jatuh_tempo' => $this->jatuhTempo($filter),
            'kalibrasi.kepatuhan' => $this->kepatuhan($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryKalibrasi."),
        };
    }

    private function jatuhTempo(FilterMetrik $filter): HasilKpi
    {
        $hariIni = $filter->hariIni()->toDateString();

        $terlambat = (int) $this->lingkup($filter)
            ->where('Aktif', true)
            ->where('TanggalBerikutnya', '<', $hariIni)
            ->count();

        $segera = (int) $this->lingkup($filter)
            ->where('Aktif', true)
            ->where('TanggalBerikutnya', '>=', $hariIni)
            ->whereRaw('TanggalBerikutnya <= DATE_ADD(?, INTERVAL PeringatanHariSebelum DAY)', [$hariIni])
            ->count();

        return new HasilKpi((float) ($terlambat + $segera), [
            ['Label' => 'Terlambat', 'Nilai' => (float) $terlambat],
            ['Label' => 'Segera jatuh tempo', 'Nilai' => (float) $segera],
        ]);
    }

    private function kepatuhan(FilterMetrik $filter): HasilKpi
    {
        $hariIni = $filter->hariIni()->toDateString();

        $penyebut = (int) $this->lingkup($filter)->where('Aktif', true)->count();
        $pembilang = (int) $this->lingkup($filter)
            ->where('Aktif', true)
            ->where('TanggalBerikutnya', '>=', $hariIni)
            ->count();

        return HasilKpi::persen((float) $pembilang, (float) $penyebut, [
            ['Label' => 'Masih berlaku', 'Nilai' => (float) $pembilang],
            ['Label' => 'Lewat jatuh tempo', 'Nilai' => (float) ($penyebut - $pembilang)],
        ]);
    }

    /** @return Builder<RencanaKalibrasi> */
    private function lingkup(FilterMetrik $filter): Builder
    {
        return $this->saringLewatAset(RencanaKalibrasi::query(), $filter);
    }
}
