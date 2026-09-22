<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Carbon\CarbonImmutable;

/** KPI kontrak (21.01: contract). */
final class QueryKontrak implements PenyediaKpi
{
    public function kunciDilayani(): array
    {
        return ['kontrak.akan_berakhir', 'kontrak.nilai_aktif'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'kontrak.akan_berakhir' => $this->akanBerakhir(),
            'kontrak.nilai_aktif' => $this->nilaiAktif($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryKontrak."),
        };
    }

    private function akanBerakhir(): HasilKpi
    {
        $hariIni = CarbonImmutable::now()->toDateString();

        $sudahBerakhir = (int) Kontrak::query()
            ->where('Status', 'Aktif')
            ->where('BerakhirPada', '<', $hariIni)
            ->count();

        $segera = (int) Kontrak::query()
            ->where('Status', 'Aktif')
            ->where('BerakhirPada', '>=', $hariIni)
            ->whereRaw('BerakhirPada <= DATE_ADD(CURRENT_DATE, INTERVAL PeringatanHariSebelum DAY)')
            ->count();

        return new HasilKpi((float) ($sudahBerakhir + $segera), [
            ['Label' => 'Lewat tanggal berakhir', 'Nilai' => (float) $sudahBerakhir],
            ['Label' => 'Segera berakhir', 'Nilai' => (float) $segera],
        ], ['FilterDimensiBerlaku' => false]);
    }

    private function nilaiAktif(FilterMetrik $filter): HasilKpi
    {
        $perJenis = Kontrak::query()
            ->where('Status', 'Aktif')
            ->where('MulaiPada', '<=', $filter->sampai->toDateString())
            ->where('BerakhirPada', '>=', $filter->dari->toDateString())
            ->selectRaw('Jenis, SUM(Nilai) as Nilai')
            ->groupBy('Jenis')
            ->pluck('Nilai', 'Jenis')
            ->map(fn ($nilai): float => (float) $nilai);

        return new HasilKpi(
            (float) $perJenis->sum(),
            $perJenis->map(fn (float $nilai, string $jenis): array => [
                'Label' => $jenis,
                'Nilai' => $nilai,
            ])->values()->all(),
            ['FilterDimensiBerlaku' => false],
        );
    }
}
