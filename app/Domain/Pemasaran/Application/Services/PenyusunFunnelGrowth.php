<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\TahapFunnelGrowth;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Tiap tahap funnel dihitung dari tabelnya sendiri, bukan diturunkan dari angka tahap sebelumnya (MARKETING.md 5). */
final class PenyusunFunnelGrowth
{
    public function __construct(
        private readonly PenyaringGrowth $penyaring,
        private readonly LayananKonfigurasiPemasaran $konfigurasi,
    ) {}

    /** @return array<string, int> */
    public function hitung(FilterGrowth $filter): array
    {
        $pengunjung = $this->penyaring->pengunjung($filter);

        return [
            TahapFunnelGrowth::Visitor->value => $this->visitor($filter, $pengunjung),
            TahapFunnelGrowth::Lead->value => $this->lead($filter, $pengunjung),
            TahapFunnelGrowth::Demo->value => $this->demo($filter, $pengunjung),
            TahapFunnelGrowth::Trial->value => $this->trial($filter, $pengunjung),
            TahapFunnelGrowth::Activated->value => $this->activated($filter, $pengunjung),
            TahapFunnelGrowth::Qualified->value => $this->qualified($filter, $pengunjung),
            TahapFunnelGrowth::Paid->value => $this->paid($filter, $pengunjung),
        ];
    }

    public function visitor(FilterGrowth $filter, ?Builder $pengunjung): int
    {
        return $this->batasi(
            DB::table('SesiPengunjung')->distinct(),
            $filter, $pengunjung, 'SesiPengunjung.PengenalPengunjung', 'SesiPengunjung.DimulaiPada',
        )->count('SesiPengunjung.PengenalPengunjung');
    }

    public function lead(FilterGrowth $filter, ?Builder $pengunjung): int
    {
        return $this->batasi(
            DB::table('Prospek'),
            $filter, $pengunjung, 'Prospek.PengenalPengunjung', 'Prospek.DibuatPada',
        )->count();
    }

    public function demo(FilterGrowth $filter, ?Builder $pengunjung): int
    {
        return $this->batasi(
            DB::table('EventPemasaran')
                ->where('Jenis', KatalogPeristiwaPemasaran::DEMO_DIMULAI)
                ->distinct(),
            $filter, $pengunjung, 'EventPemasaran.PengenalPengunjung', 'EventPemasaran.TerjadiPada',
        )->count('EventPemasaran.PengenalPengunjung');
    }

    public function trial(FilterGrowth $filter, ?Builder $pengunjung): int
    {
        return $this->batasi(
            DB::table('Trial'),
            $filter, $pengunjung, 'Trial.PengenalPengunjung', 'Trial.MulaiPada',
        )->count();
    }

    public function activated(FilterGrowth $filter, ?Builder $pengunjung): int
    {
        return $this->batasi(
            DB::table('Trial')->whereNotNull('TeraktivasiPada'),
            $filter, $pengunjung, 'Trial.PengenalPengunjung', 'Trial.TeraktivasiPada',
        )->count();
    }

    /** Qualified dibaca dari skor yang berlaku sekarang, bukan dari skor saat prospeknya lahir. */
    public function qualified(FilterGrowth $filter, ?Builder $pengunjung): int
    {
        $ambang = $this->konfigurasi->angka(KatalogKonfigurasiPemasaran::SKOR_AMBANG_QUALIFIED);

        return $this->batasi(
            DB::table('Prospek')->where('Skor', '>=', $ambang),
            $filter, $pengunjung, 'Prospek.PengenalPengunjung', 'Prospek.DibuatPada',
        )->count();
    }

    public function paid(FilterGrowth $filter, ?Builder $pengunjung): int
    {
        return $this->batasi(
            DB::table('Trial')->whereNotNull('KonversiPada'),
            $filter, $pengunjung, 'Trial.PengenalPengunjung', 'Trial.KonversiPada',
        )->count();
    }

    private function batasi(
        Builder $kueri,
        FilterGrowth $filter,
        ?Builder $pengunjung,
        string $kolomPengunjung,
        string $kolomWaktu,
    ): Builder {
        $kueri->whereBetween($kolomWaktu, [$filter->dari, $filter->sampai]);

        if ($pengunjung !== null) {
            $kueri->whereIn($kolomPengunjung, $pengunjung);
        }

        return $kueri;
    }
}
