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
 * KPI perintah kerja (21.01: work order).
 */
final class QueryPerintahKerja implements PenyediaKpi
{
    use MenyaringLingkup;

    /** Jenis pekerjaan yang dianggap terencana. */
    private const JENIS_TERENCANA = ['Preventif', 'Inspeksi', 'Kalibrasi'];

    public function kunciDilayani(): array
    {
        return [
            'perintah_kerja.aktif',
            'perintah_kerja.selesai',
            'perintah_kerja.terlambat',
            'perintah_kerja.terencana',
        ];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'perintah_kerja.aktif' => $this->aktif($filter),
            'perintah_kerja.selesai' => $this->selesai($filter),
            'perintah_kerja.terlambat' => $this->terlambat($filter),
            'perintah_kerja.terencana' => $this->terencana($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryPerintahKerja."),
        };
    }

    /**
     * Pekerjaan yang masih berjalan adalah keadaan saat ini, jadi sengaja tidak
     * dibatasi rentang tanggal: pekerjaan lama yang menggantung justru yang
     * paling perlu terlihat.
     */
    private function aktif(FilterMetrik $filter): HasilKpi
    {
        $perStatus = $this->lingkup($filter)
            ->whereNotIn('Status', $this->statusTuntas())
            ->selectRaw('Status, COUNT(*) as Jumlah')
            ->groupBy('Status')
            ->pluck('Jumlah', 'Status');

        return new HasilKpi(
            (float) $perStatus->sum(),
            $perStatus->map(fn (int|string $jumlah, string $status): array => [
                'Label' => $status,
                'Nilai' => (float) $jumlah,
            ])->values()->all(),
        );
    }

    private function selesai(FilterMetrik $filter): HasilKpi
    {
        $perHari = $this->lingkup($filter)
            ->whereNotNull('DiselesaikanPada')
            ->whereBetween('DiselesaikanPada', [$filter->dari, $filter->sampai])
            ->selectRaw('DATE(DiselesaikanPada) as Tanggal, COUNT(*) as Jumlah')
            ->groupBy('Tanggal')
            ->pluck('Jumlah', 'Tanggal')
            ->all();

        return new HasilKpi((float) array_sum($perHari), $this->deretHarian($filter, $perHari));
    }

    private function terlambat(FilterMetrik $filter): HasilKpi
    {
        $sekarang = CarbonImmutable::now();

        $perPrioritas = $this->lingkup($filter)
            ->whereNotIn('Status', $this->statusTuntas())
            ->whereNotNull('BatasPenyelesaianPada')
            ->where('BatasPenyelesaianPada', '<', $sekarang)
            ->selectRaw('Prioritas, COUNT(*) as Jumlah')
            ->groupBy('Prioritas')
            ->pluck('Jumlah', 'Prioritas');

        return new HasilKpi(
            (float) $perPrioritas->sum(),
            $perPrioritas->map(fn (int|string $jumlah, string $prioritas): array => [
                'Label' => $prioritas,
                'Nilai' => (float) $jumlah,
            ])->values()->all(),
        );
    }

    private function terencana(FilterMetrik $filter): HasilKpi
    {
        $perJenis = $this->lingkup($filter)
            ->whereBetween('DibuatPada', [$filter->dari, $filter->sampai])
            ->selectRaw('Jenis, COUNT(*) as Jumlah')
            ->groupBy('Jenis')
            ->pluck('Jumlah', 'Jenis');

        $terencana = 0.0;
        foreach (self::JENIS_TERENCANA as $jenis) {
            $terencana += (float) ($perJenis[$jenis] ?? 0);
        }

        return HasilKpi::persen(
            $terencana,
            (float) $perJenis->sum(),
            $perJenis->map(fn (int|string $jumlah, string $jenis): array => [
                'Label' => $jenis,
                'Nilai' => (float) $jumlah,
            ])->values()->all(),
        );
    }

    /** @return list<string> */
    private function statusTuntas(): array
    {
        return [
            StatusPerintahKerja::Selesai->value,
            StatusPerintahKerja::Ditutup->value,
            StatusPerintahKerja::Dibatalkan->value,
        ];
    }

    /** @return Builder<PerintahKerja> */
    private function lingkup(FilterMetrik $filter): Builder
    {
        return $this->saringLangsung(PerintahKerja::query(), $filter);
    }
}
