<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Services;

use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * Meratakan hasil KPI menjadi baris tabel untuk diekspor (21.05).
 *
 * Setiap baris membawa kunci KPI, rumusnya, dan label rincian penyusunnya,
 * sehingga berkas yang sudah lepas dari aplikasi tetap dapat ditelusuri kembali
 * ke definisi angkanya (PRD 8.18) alih-alih menjadi deretan angka tanpa asal.
 */
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
                    (string) ($rincian['Label'] ?? '-'),
                    (float) ($rincian['Nilai'] ?? 0),
                    (string) $kpi['Satuan'],
                    '',
                ];
            }
        }

        return $baris;
    }
}
