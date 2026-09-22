<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\KepatuhanAset;
use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/** KPI kepatuhan aset (21.01: compliance). */
final class QueryKepatuhan implements PenyediaKpi
{
    use MenyaringLingkup;

    /** Ambang "akan kedaluwarsa" dalam hari. */
    private const HARI_PERINGATAN = 30;

    public function kunciDilayani(): array
    {
        return ['kepatuhan.tingkat', 'kepatuhan.akan_kedaluwarsa'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'kepatuhan.tingkat' => $this->tingkat($filter),
            'kepatuhan.akan_kedaluwarsa' => $this->akanKedaluwarsa($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryKepatuhan."),
        };
    }

    private function tingkat(FilterMetrik $filter): HasilKpi
    {
        $perStatus = $this->lingkup($filter)
            ->where('Status', '!=', 'BelumDiperiksa')
            ->selectRaw('Status, COUNT(*) as Jumlah')
            ->groupBy('Status')
            ->pluck('Jumlah', 'Status');

        return HasilKpi::persen(
            (float) ($perStatus['Patuh'] ?? 0),
            (float) $perStatus->sum(),
            $perStatus->map(fn (int|string $jumlah, string $status): array => [
                'Label' => $status,
                'Nilai' => (float) $jumlah,
            ])->values()->all(),
        );
    }

    private function akanKedaluwarsa(FilterMetrik $filter): HasilKpi
    {
        $batas = CarbonImmutable::now()->addDays(self::HARI_PERINGATAN)->toDateString();

        $jumlah = (int) $this->lingkup($filter)
            ->whereNotNull('BerlakuSampai')
            ->where('BerlakuSampai', '<=', $batas)
            ->where('Status', '!=', 'TidakPatuh')
            ->count();

        return new HasilKpi((float) $jumlah, [], ['AmbangHari' => self::HARI_PERINGATAN]);
    }

    /** @return Builder<KepatuhanAset> */
    private function lingkup(FilterMetrik $filter): Builder
    {
        return $this->saringLewatAset(KepatuhanAset::query(), $filter);
    }
}
