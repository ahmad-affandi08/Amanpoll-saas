<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;

/** KPI populasi aset (21.01: asset counts, asset condition). */
final class QueryAset implements PenyediaKpi
{
    use MenyaringLingkup;

    public function kunciDilayani(): array
    {
        return ['aset.jumlah', 'aset.nilai_perolehan', 'aset.kondisi'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'aset.jumlah' => $this->jumlah($filter),
            'aset.nilai_perolehan' => $this->nilaiPerolehan($filter),
            'aset.kondisi' => $this->kondisi($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryAset."),
        };
    }

    private function jumlah(FilterMetrik $filter): HasilKpi
    {
        $perStatus = $this->saringLangsung(Aset::query(), $filter)
            ->selectRaw('Status, COUNT(*) as Jumlah')
            ->groupBy('Status')
            ->pluck('Jumlah', 'Status');

        $rincian = $perStatus
            ->map(fn (int|string $jumlah, string $status): array => ['Label' => $status, 'Nilai' => (float) $jumlah])
            ->values()
            ->all();

        return new HasilKpi((float) $perStatus->sum(), $rincian);
    }

    private function nilaiPerolehan(FilterMetrik $filter): HasilKpi
    {
        $perKategori = $this->saringLangsung(Aset::query(), $filter)
            ->join('KategoriAset', 'KategoriAset.Id', '=', 'Aset.KategoriAsetId')
            ->selectRaw('KategoriAset.Nama as Kategori, SUM(Aset.HargaPerolehan) as Nilai')
            ->groupBy('KategoriAset.Nama')
            ->orderByDesc('Nilai')
            ->limit(10)
            ->toBase()
            ->get()
            ->map(fn (object $baris): array => get_object_vars($baris));

        $total = (float) $this->saringLangsung(Aset::query(), $filter)->sum('HargaPerolehan');

        return new HasilKpi($total, $perKategori
            ->map(fn (array $baris): array => [
                'Label' => (string) $baris['Kategori'],
                'Nilai' => (float) $baris['Nilai'],
            ])
            ->values()
            ->all());
    }

    private function kondisi(FilterMetrik $filter): HasilKpi
    {
        $perKondisi = $this->saringLangsung(Aset::query(), $filter)
            ->whereNotNull('Kondisi')
            ->selectRaw('Kondisi, COUNT(*) as Jumlah')
            ->groupBy('Kondisi')
            ->pluck('Jumlah', 'Kondisi');

        $rincian = $perKondisi
            ->map(fn (int|string $jumlah, string $kondisi): array => ['Label' => $kondisi, 'Nilai' => (float) $jumlah])
            ->values()
            ->all();

        return HasilKpi::persen(
            (float) ($perKondisi[KondisiAset::Baik->value] ?? 0),
            (float) $perKondisi->sum(),
            $rincian,
        );
    }
}
