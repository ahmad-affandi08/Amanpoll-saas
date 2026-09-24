<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Requests;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\Pelaporan\Application\Services\PembatasRentangMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use Illuminate\Validation\Validator;

/**
 * Batas rentang dan jumlah KPI untuk laporan yang dihitung di antrean (FASE 45).
 *
 * Ekspor dan laporan tersimpan boleh berentang lebih panjang daripada layar,
 * tetapi tidak tanpa batas: rentang paling panjang
 * `amanpoll.pelaporan.rentang_maks_hari_ekspor` hari, dan bila rentangnya
 * melebihi batas layar, jumlah KPI dibatasi
 * `amanpoll.pelaporan.kpi_maks_ekspor_rentang_panjang` -- satu KPI lima tahun
 * jauh lebih mahal daripada satu KPI tiga puluh hari. Tanggal dibaca dengan
 * aturan yang sama dengan FilterMetrik, jadi yang lolos di sini tidak akan
 * diam-diam diganti tanggal bawaan di job.
 */
final class BatasLaporanAsinkron
{
    public static function kpiMaks(): int
    {
        return max(1, (int) config('amanpoll.pelaporan.kpi_maks_ekspor', 30));
    }

    /**
     * @param  string  $awalan  awalan nama ruas, mis. `Filter.` atau `Konfigurasi.Filter.`
     * @param  string  $ruasKpi  nama ruas daftar KPI untuk pesan galat
     */
    public static function periksa(Validator $validator, mixed $filter, mixed $kunciKpi, string $awalan, string $ruasKpi): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $filter = is_array($filter) ? $filter : [];
        $zona = app(KalenderOrganisasi::class)->zona();

        foreach (['Dari' => 'awal', 'Sampai' => 'akhir'] as $kunci => $label) {
            if (FilterMetrik::tanggalTidakSah($filter[$kunci] ?? null, $zona)) {
                $validator->errors()->add($awalan.$kunci, "Tanggal {$label} harus tanggal yang sah berformat TTTT-BB-HH.");

                return;
            }
        }

        $jumlahHari = FilterMetrik::dariArray($filter, $zona)->jumlahHari();
        $maksHari = PembatasRentangMetrik::maksHariEkspor();

        if ($jumlahHari > $maksHari) {
            $validator->errors()->add(
                $awalan.'Sampai',
                "Rentang laporan paling panjang {$maksHari} hari; yang diminta {$jumlahHari} hari.",
            );

            return;
        }

        $maksKpi = max(1, (int) config('amanpoll.pelaporan.kpi_maks_ekspor_rentang_panjang', 15));
        $jumlahKpi = is_array($kunciKpi) ? count(array_unique(array_filter($kunciKpi, 'is_string'))) : 0;

        if ($jumlahHari > PembatasRentangMetrik::maksHariInteraktif() && $jumlahKpi > $maksKpi) {
            $validator->errors()->add(
                $ruasKpi,
                'Untuk rentang lebih dari '.PembatasRentangMetrik::maksHariInteraktif()
                ." hari, pilih paling banyak {$maksKpi} KPI (dipilih {$jumlahKpi}).",
            );
        }
    }
}
