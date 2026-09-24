<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Services;

use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/** Meratakan hasil KPI menjadi baris tabel untuk diekspor (21.05). */
final class PenyusunBarisLaporan
{
    /** @var list<string> */
    public const KEPALA = ['KPI', 'Kelompok', 'Rincian', 'Nilai', 'Satuan', 'Formula'];

    public function __construct(private readonly LayananMetrik $metrik) {}

    /**
     * @param  list<string>  $kunciKpi
     * @return list<list<string|float>>
     */
    public function baris(array $kunciKpi, FilterMetrik $filter, Pengguna $pengguna): array
    {
        $baris = [];

        foreach ($this->metrik->hitungBanyak($kunciKpi, $filter, $pengguna) as $kpi) {
            $baris[] = [
                (string) $kpi['Nama'],
                (string) $kpi['LabelKelompok'],
                'Total',
                (float) $kpi['Nilai'],
                (string) $kpi['Satuan'],
                (string) $kpi['Formula'],
            ];

            foreach ($kpi['Rincian'] as $rincian) {
                $baris[] = [
                    (string) $kpi['Nama'],
                    (string) $kpi['LabelKelompok'],
                    self::labelRincian($rincian),
                    (float) ($rincian['Nilai'] ?? 0),
                    (string) $kpi['SatuanRincian'],
                    '',
                ];
            }
        }

        return $baris;
    }

    /**
     * Deret panjang dikelompokkan per minggu (FASE 45); labelnya tanggal awal,
     * jadi berkas ekspor menyebut rentang minggunya supaya tidak terbaca harian.
     *
     * @param  array<string, mixed>  $rincian
     */
    private static function labelRincian(array $rincian): string
    {
        $label = (string) ($rincian['Label'] ?? '-');
        $sampai = $rincian['SampaiTanggal'] ?? null;

        return is_string($sampai) ? $label.' s.d. '.$sampai : $label;
    }
}
