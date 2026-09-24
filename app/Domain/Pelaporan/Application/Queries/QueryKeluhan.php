<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Database\Eloquent\Builder;

/** KPI keluhan (21.01: complaint). */
final class QueryKeluhan implements PenyediaKpi
{
    use MenyaringLingkup;

    /**
     * Status keluhan yang dianggap belum tuntas: semua yang belum final dan
     * belum Selesai. Diturunkan dari enum supaya penambahan status baru tidak
     * diam-diam hilang dari hitungan.
     *
     * @return list<string>
     */
    private function statusTerbuka(): array
    {
        return array_values(array_map(
            fn (StatusKeluhan $status): string => $status->value,
            array_filter(
                StatusKeluhan::cases(),
                fn (StatusKeluhan $status): bool => ! $status->final() && $status !== StatusKeluhan::Selesai,
            ),
        ));
    }

    public function kunciDilayani(): array
    {
        return ['keluhan.terbuka', 'keluhan.masuk', 'keluhan.waktu_respons'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'keluhan.terbuka' => $this->terbuka($filter),
            'keluhan.masuk' => $this->masuk($filter),
            'keluhan.waktu_respons' => $this->waktuRespons($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryKeluhan."),
        };
    }

    private function terbuka(FilterMetrik $filter): HasilKpi
    {
        $perStatus = $this->lingkup($filter)
            ->whereIn('Status', $this->statusTerbuka())
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

    private function masuk(FilterMetrik $filter): HasilKpi
    {
        $perHari = $this->lingkup($filter)
            ->whereBetween('DilaporkanPada', [$filter->dari, $filter->sampai])
            ->selectRaw('DATE(CONVERT_TZ(DilaporkanPada, ?, ?)) as Tanggal, COUNT(*) as Jumlah', ['+00:00', $filter->offsetSql()])
            ->groupBy('Tanggal')
            ->pluck('Jumlah', 'Tanggal')
            ->all();

        $perPrioritas = $this->lingkup($filter)
            ->whereBetween('DilaporkanPada', [$filter->dari, $filter->sampai])
            ->selectRaw('Prioritas, COUNT(*) as Jumlah')
            ->groupBy('Prioritas')
            ->pluck('Jumlah', 'Prioritas');

        return new HasilKpi(
            (float) array_sum($perHari),
            $this->deretHarian($filter, $perHari),
            ['PerPrioritas' => $perPrioritas->all()],
        );
    }

    /**
     * Rata-rata menit dari laporan sampai respons pertama, diagregasi di basis
     * data. Per keluhan: selisih menit terpotong ke bawah, minimal nol --
     * `TIMESTAMPDIFF(MINUTE, ...)` memotong ke arah nol dengan presisi
     * mikrodetik, persis `(int) diffInMinutes()` yang dulu dijumlah di PHP.
     * Pembulatan rata-ratanya tetap di PHP supaya aturannya tidak berubah.
     */
    private function waktuRespons(FilterMetrik $filter): HasilKpi
    {
        $agregat = $this->lingkup($filter)
            ->whereNotNull('DiresponsPada')
            ->whereBetween('DilaporkanPada', [$filter->dari, $filter->sampai])
            ->selectRaw('COUNT(*) AS Jumlah, SUM(GREATEST(0, TIMESTAMPDIFF(MINUTE, DilaporkanPada, DiresponsPada))) AS TotalMenit')
            ->toBase()
            ->first();

        $jumlah = (int) ($agregat->Jumlah ?? 0);
        if ($jumlah === 0) {
            return new HasilKpi(0.0, [], ['AdaData' => false, 'Penyebut' => 0]);
        }

        $totalMenit = (int) $agregat->TotalMenit;

        return new HasilKpi(
            round($totalMenit / $jumlah, 0),
            [],
            ['AdaData' => true, 'Penyebut' => $jumlah, 'TotalMenit' => $totalMenit],
        );
    }

    /**
     * Keluhan tidak punya kolom unit organisasi, jadi filter unit organisasi
     * dibaca dari asetnya (makna lama, tidak berubah). Unit pengelola dibaca
     * dari kolom keluhan sendiri -- antrian yang menerima keluhan, bukan
     * pengelola aset saat ini.
     *
     * @return Builder<Keluhan>
     */
    private function lingkup(FilterMetrik $filter): Builder
    {
        $query = Keluhan::query();

        if ($filter->adaFilterLokasi()) {
            $query->whereIn('LokasiId', $filter->lokasiId);
        }
        if ($filter->adaFilterUnit()) {
            $query->whereIn('AsetId', $this->asetDalamLingkup($filter, termasukUnitPengelola: false));
        }
        if ($filter->adaFilterUnitPengelola()) {
            $query->whereIn('UnitPengelolaId', $filter->unitPengelolaId);
        }

        return $query;
    }
}
