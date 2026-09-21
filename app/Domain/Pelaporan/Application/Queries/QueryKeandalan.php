<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * KPI keandalan: downtime, ketersediaan, MTTR, dan MTBF (21.01).
 *
 * Dua keputusan yang menentukan arti angkanya:
 *
 * 1. Sesi downtime yang belum berakhir tetap dihitung, dipotong pada batas
 *    akhir rentang. Mengabaikannya akan membuat aset yang sedang mati total
 *    justru terlihat paling sehat.
 * 2. Penyebut ketersediaan dan MTBF hanya memuat aset yang pernah mengalami
 *    downtime. Memakai seluruh populasi aset akan mengencerkan angka sampai
 *    selalu mendekati 100% dan tidak ada gunanya dibaca.
 */
final class QueryKeandalan implements PenyediaKpi
{
    use MenyaringLingkup;

    /** Jenis downtime yang dihitung sebagai kegagalan untuk MTTR/MTBF. */
    private const JENIS_KEGAGALAN = 'TidakTerencana';

    public function kunciDilayani(): array
    {
        return ['downtime.total_jam', 'downtime.ketersediaan', 'keandalan.mttr', 'keandalan.mtbf'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'downtime.total_jam' => $this->totalJam($filter),
            'downtime.ketersediaan' => $this->ketersediaan($filter),
            'keandalan.mttr' => $this->mttr($filter),
            'keandalan.mtbf' => $this->mtbf($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryKeandalan."),
        };
    }

    private function totalJam(FilterMetrik $filter): HasilKpi
    {
        $sesi = $this->sesiDalamRentang($filter);
        $perJenis = [];
        $perBulan = [];

        foreach ($sesi as $satu) {
            $menit = $this->menitEfektif($satu, $filter);
            $jenis = (string) $satu->Jenis;
            $perJenis[$jenis] = ($perJenis[$jenis] ?? 0) + $menit;
            $bulan = $satu->MulaiPada->format('Y-m');
            $perBulan[$bulan] = ($perBulan[$bulan] ?? 0) + $menit;
        }

        $totalMenit = array_sum($perJenis);

        return new HasilKpi(
            round($totalMenit / 60, 1),
            $this->deretBulanan($filter, array_map(fn (int $menit): float => round($menit / 60, 1), $perBulan)),
            ['PerJenisJam' => array_map(fn (int $menit): float => round($menit / 60, 1), $perJenis)],
        );
    }

    private function ketersediaan(FilterMetrik $filter): HasilKpi
    {
        $sesi = $this->sesiDalamRentang($filter);
        if ($sesi->isEmpty()) {
            return new HasilKpi(0.0, [], ['AdaData' => false, 'Penyebut' => 0]);
        }

        $totalMenit = $sesi->sum(fn (WaktuHentiAset $satu): int => $this->menitEfektif($satu, $filter));
        $menitTersedia = $this->menitOperasional($filter, $sesi->pluck('AsetId')->unique()->count());

        return HasilKpi::persen(
            max(0.0, $menitTersedia - $totalMenit),
            $menitTersedia,
            [
                ['Label' => 'Tersedia', 'Nilai' => round(max(0.0, $menitTersedia - $totalMenit) / 60, 1)],
                ['Label' => 'Downtime', 'Nilai' => round($totalMenit / 60, 1)],
            ],
        );
    }

    private function mttr(FilterMetrik $filter): HasilKpi
    {
        $selesai = $this->sesiDalamRentang($filter)
            ->where('Jenis', self::JENIS_KEGAGALAN)
            ->filter(fn (WaktuHentiAset $satu): bool => $satu->SelesaiPada !== null);

        if ($selesai->isEmpty()) {
            return new HasilKpi(0.0, [], ['AdaData' => false, 'Penyebut' => 0]);
        }

        $totalMenit = $selesai->sum(fn (WaktuHentiAset $satu): int => $this->menitEfektif($satu, $filter));

        return new HasilKpi(
            round($totalMenit / $selesai->count() / 60, 1),
            [],
            ['AdaData' => true, 'Penyebut' => $selesai->count(), 'TotalMenit' => $totalMenit],
        );
    }

    private function mtbf(FilterMetrik $filter): HasilKpi
    {
        $kegagalan = $this->sesiDalamRentang($filter)->where('Jenis', self::JENIS_KEGAGALAN);
        if ($kegagalan->isEmpty()) {
            return new HasilKpi(0.0, [], ['AdaData' => false, 'Penyebut' => 0]);
        }

        $menitDowntime = $kegagalan->sum(fn (WaktuHentiAset $satu): int => $this->menitEfektif($satu, $filter));
        $menitOperasional = $this->menitOperasional($filter, $kegagalan->pluck('AsetId')->unique()->count());
        $menitAktif = max(0.0, $menitOperasional - $menitDowntime);

        return new HasilKpi(
            round($menitAktif / $kegagalan->count() / 60, 1),
            [],
            [
                'AdaData' => true,
                'Penyebut' => $kegagalan->count(),
                'MenitOperasional' => $menitOperasional,
                'MenitDowntime' => $menitDowntime,
            ],
        );
    }

    /** @return Collection<int, WaktuHentiAset> */
    private function sesiDalamRentang(FilterMetrik $filter): Collection
    {
        return $this->lingkup($filter)
            ->whereBetween('MulaiPada', [$filter->dari, $filter->sampai])
            ->get(['Id', 'AsetId', 'Jenis', 'MulaiPada', 'SelesaiPada', 'DurasiMenit']);
    }

    /**
     * Menit downtime yang jatuh di dalam rentang. Sesi yang masih berjalan
     * dipotong pada batas akhir rentang, bukan diabaikan.
     */
    private function menitEfektif(WaktuHentiAset $sesi, FilterMetrik $filter): int
    {
        $selesai = $sesi->SelesaiPada ?? $filter->sampai;
        if ($selesai->greaterThan($filter->sampai)) {
            $selesai = $filter->sampai;
        }

        return max(0, (int) $sesi->MulaiPada->diffInMinutes($selesai));
    }

    private function menitOperasional(FilterMetrik $filter, int $jumlahAset): float
    {
        return (float) $jumlahAset * max(1, (int) $filter->dari->diffInMinutes($filter->sampai));
    }

    /** @return Builder<WaktuHentiAset> */
    private function lingkup(FilterMetrik $filter): Builder
    {
        return $this->saringLewatAset(WaktuHentiAset::query(), $filter);
    }
}
