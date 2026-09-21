<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Database\Eloquent\Builder;

/**
 * KPI pengadaan (21.01: procurement).
 *
 * Pesanan pembelian tidak membawa unit organisasi maupun lokasi, sehingga
 * filter dimensi tidak berlaku di sini.
 */
final class QueryPengadaan implements PenyediaKpi
{
    /** Status yang tidak dihitung sebagai komitmen belanja. */
    private const STATUS_DIKECUALIKAN = ['Draft', 'Dibatalkan'];

    public function kunciDilayani(): array
    {
        return ['pengadaan.nilai_pesanan', 'pengadaan.jumlah_pesanan'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'pengadaan.nilai_pesanan' => $this->nilaiPesanan($filter),
            'pengadaan.jumlah_pesanan' => $this->jumlahPesanan($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryPengadaan."),
        };
    }

    private function nilaiPesanan(FilterMetrik $filter): HasilKpi
    {
        $perStatus = $this->lingkup($filter)
            ->whereNotIn('Status', self::STATUS_DIKECUALIKAN)
            ->selectRaw('Status, SUM(Total) as Nilai')
            ->groupBy('Status')
            ->pluck('Nilai', 'Status')
            ->map(fn ($nilai): float => (float) $nilai);

        return new HasilKpi(
            (float) $perStatus->sum(),
            $perStatus->map(fn (float $nilai, string $status): array => [
                'Label' => $status,
                'Nilai' => $nilai,
            ])->values()->all(),
            ['FilterDimensiBerlaku' => false],
        );
    }

    private function jumlahPesanan(FilterMetrik $filter): HasilKpi
    {
        $perStatus = $this->lingkup($filter)
            ->selectRaw('Status, COUNT(*) as Jumlah')
            ->groupBy('Status')
            ->pluck('Jumlah', 'Status');

        return new HasilKpi(
            (float) $perStatus->sum(),
            $perStatus->map(fn (int|string $jumlah, string $status): array => [
                'Label' => $status,
                'Nilai' => (float) $jumlah,
            ])->values()->all(),
            ['FilterDimensiBerlaku' => false],
        );
    }

    /** @return Builder<PesananPembelian> */
    private function lingkup(FilterMetrik $filter): Builder
    {
        return PesananPembelian::query()
            ->whereBetween('TanggalPesanan', [$filter->dari->toDateString(), $filter->sampai->toDateString()]);
    }
}
