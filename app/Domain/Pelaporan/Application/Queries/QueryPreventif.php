<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JadwalPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaanAset;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Database\Eloquent\Builder;

/** KPI pemeliharaan preventif (21.01: preventive). */
final class QueryPreventif implements PenyediaKpi
{
    use MenyaringLingkup;

    public function kunciDilayani(): array
    {
        return ['preventif.jatuh_tempo', 'preventif.kepatuhan'];
    }

    public function hitung(string $kunci, FilterMetrik $filter): HasilKpi
    {
        return match ($kunci) {
            'preventif.jatuh_tempo' => $this->jatuhTempo($filter),
            'preventif.kepatuhan' => $this->kepatuhan($filter),
            default => throw new DataTidakDitemukan("KPI {$kunci} bukan milik QueryPreventif."),
        };
    }

    private function jatuhTempo(FilterMetrik $filter): HasilKpi
    {
        $hariIni = $filter->hariIni()->toDateString();

        $terlambat = (int) $this->lingkup($filter)
            ->where('Status', 'Terjadwal')
            ->where('TanggalJadwal', '<', $hariIni)
            ->count();

        $hariIniJumlah = (int) $this->lingkup($filter)
            ->where('Status', 'Terjadwal')
            ->whereDate('TanggalJadwal', $hariIni)
            ->count();

        return new HasilKpi((float) ($terlambat + $hariIniJumlah), [
            ['Label' => 'Terlambat', 'Nilai' => (float) $terlambat],
            ['Label' => 'Jatuh tempo hari ini', 'Nilai' => (float) $hariIniJumlah],
        ]);
    }

    private function kepatuhan(FilterMetrik $filter): HasilKpi
    {
        $perStatus = $this->lingkup($filter)
            ->whereBetween('TanggalJadwal', [$filter->tanggalDari(), $filter->tanggalSampai()])
            ->selectRaw('Status, COUNT(*) as Jumlah')
            ->groupBy('Status')
            ->pluck('Jumlah', 'Status');

        return HasilKpi::persen(
            (float) ($perStatus['Selesai'] ?? 0),
            (float) $perStatus->sum(),
            $perStatus->map(fn (int|string $jumlah, string $status): array => [
                'Label' => $status,
                'Nilai' => (float) $jumlah,
            ])->values()->all(),
        );
    }

    /**
     * Jadwal dimiliki pasangan rencana-aset. Unit organisasi dan lokasi dibaca
     * dari asetnya; unit pengelola dari rencana, atau dari aset bila rencana
     * belum diisi.
     *
     * @return Builder<JadwalPemeliharaan>
     */
    private function lingkup(FilterMetrik $filter): Builder
    {
        $query = JadwalPemeliharaan::query();

        if (! $filter->adaFilterUnit() && ! $filter->adaFilterLokasi() && ! $filter->adaFilterUnitPengelola()) {
            return $query;
        }

        $rencanaAset = RencanaPemeliharaanAset::query()->select('RencanaPemeliharaanAset.Id');

        if ($filter->adaFilterUnit() || $filter->adaFilterLokasi()) {
            $rencanaAset->whereIn('RencanaPemeliharaanAset.AsetId', $this->asetDalamLingkup($filter, termasukUnitPengelola: false));
        }

        if ($filter->adaFilterUnitPengelola()) {
            $rencanaAset->join('RencanaPemeliharaan', 'RencanaPemeliharaan.Id', '=', 'RencanaPemeliharaanAset.RencanaPemeliharaanId');
            $this->saringUnitPengelolaRencana(
                $rencanaAset,
                $filter,
                'RencanaPemeliharaan.UnitPengelolaId',
                'RencanaPemeliharaanAset.AsetId',
            );
        }

        return $query->whereIn('RencanaPemeliharaanAsetId', $rencanaAset->getQuery());
    }
}
