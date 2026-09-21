<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * KPI kepatuhan tingkat layanan (21.01: SLA).
 *
 * Penyebut selalu dibatasi pada pekerjaan yang benar-benar punya batas waktu.
 */
final class QueryTingkatLayanan implements PenyediaKpi
{
    use MenyaringLingkup;

    /** Ambang "berisiko": batas penyelesaian jatuh dalam sekian jam ke depan. */
    private const JAM_BERISIKO = 24;

    public function kunciDilayani(): array
    {
        return ['sla.kepatuhan_penyelesaian', 'sla.kepatuhan_respons', 'sla.berisiko'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'sla.kepatuhan_penyelesaian' => $this->kepatuhanPenyelesaian($filter),
            'sla.kepatuhan_respons' => $this->kepatuhanRespons($filter),
            'sla.berisiko' => $this->berisiko($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryTingkatLayanan."),
        };
    }

    private function kepatuhanPenyelesaian(FilterMetrik $filter): HasilKpi
    {
        $dasar = fn (): Builder => $this->lingkup($filter)
            ->whereNotNull('DiselesaikanPada')
            ->whereNotNull('BatasPenyelesaianPada')
            ->whereBetween('DiselesaikanPada', [$filter->dari, $filter->sampai]);

        $penyebut = (int) $dasar()->count();
        $pembilang = (int) $dasar()->whereColumn('DiselesaikanPada', '<=', 'BatasPenyelesaianPada')->count();

        return HasilKpi::persen((float) $pembilang, (float) $penyebut, [
            ['Label' => 'Memenuhi SLA', 'Nilai' => (float) $pembilang],
            ['Label' => 'Melewati SLA', 'Nilai' => (float) ($penyebut - $pembilang)],
        ]);
    }

    private function kepatuhanRespons(FilterMetrik $filter): HasilKpi
    {
        $dasar = fn (): Builder => $this->lingkup($filter)
            ->whereNotNull('DiterimaPada')
            ->whereNotNull('BatasResponsPada')
            ->whereBetween('DibuatPada', [$filter->dari, $filter->sampai]);

        $penyebut = (int) $dasar()->count();
        $pembilang = (int) $dasar()->whereColumn('DiterimaPada', '<=', 'BatasResponsPada')->count();

        return HasilKpi::persen((float) $pembilang, (float) $penyebut, [
            ['Label' => 'Respons tepat waktu', 'Nilai' => (float) $pembilang],
            ['Label' => 'Respons terlambat', 'Nilai' => (float) ($penyebut - $pembilang)],
        ]);
    }

    private function berisiko(FilterMetrik $filter): HasilKpi
    {
        $sekarang = CarbonImmutable::now();

        $jumlah = (int) $this->lingkup($filter)
            ->whereNotIn('Status', [
                StatusPerintahKerja::Selesai->value,
                StatusPerintahKerja::Ditutup->value,
                StatusPerintahKerja::Dibatalkan->value,
            ])
            ->whereNotNull('BatasPenyelesaianPada')
            ->whereBetween('BatasPenyelesaianPada', [$sekarang, $sekarang->addHours(self::JAM_BERISIKO)])
            ->count();

        return new HasilKpi((float) $jumlah, [], ['AmbangJam' => self::JAM_BERISIKO]);
    }

    /** @return Builder<PerintahKerja> */
    private function lingkup(FilterMetrik $filter): Builder
    {
        return $this->saringLangsung(PerintahKerja::query(), $filter);
    }
}
