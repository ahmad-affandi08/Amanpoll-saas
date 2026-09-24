<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Query\Builder as KontrakBuilder;
use Illuminate\Database\Eloquent\Builder;

/**
 * Penerapan filter unit organisasi, lokasi, dan unit pengelola (21.02, PRD 8.21) ke query metrik.
 *
 * Unit pengelola selalu dibaca dari kolom `UnitPengelolaId` baris yang paling
 * dekat dengan angka yang dihitung: tiket memakai kolomnya sendiri, tabel yang
 * menunjuk aset memakai kolom aset. Filter unit organisasi tidak pernah dipakai
 * sebagai pengganti -- keduanya dimensi berbeda ("milik siapa" lawan "siapa
 * yang memelihara").
 */
trait MenyaringLingkup
{
    /** Rentang terpanjang (hari) yang deretnya masih per hari; kira-kira satu kuartal. */
    private const BATAS_HARI_DERET_HARIAN = 92;

    /** Rentang terpanjang (hari) yang deretnya per minggu (~105 titik); di atasnya per bulan. */
    private const BATAS_HARI_DERET_MINGGUAN = 732;

    /**
     * Tabel yang punya kolom UnitOrganisasiId, LokasiId, dan UnitPengelolaId sendiri
     * (Aset, PerintahKerja).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function saringLangsung(Builder $query, FilterMetrik $filter, bool $adaKolomUnit = true): Builder
    {
        if ($adaKolomUnit && $filter->adaFilterUnit()) {
            $query->whereIn('UnitOrganisasiId', $filter->unitOrganisasiId);
        }

        if ($filter->adaFilterLokasi()) {
            $query->whereIn('LokasiId', $filter->lokasiId);
        }

        if ($filter->adaFilterUnitPengelola()) {
            $query->whereIn('UnitPengelolaId', $filter->unitPengelolaId);
        }

        return $query;
    }

    /**
     * Tabel yang menunjuk aset; seluruh dimensi diambil dari aset tersebut.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function saringLewatAset(Builder $query, FilterMetrik $filter, string $kolomAset = 'AsetId'): Builder
    {
        if (! $filter->adaFilterUnit() && ! $filter->adaFilterLokasi() && ! $filter->adaFilterUnitPengelola()) {
            return $query;
        }

        return $query->whereIn($kolomAset, $this->asetDalamLingkup($filter));
    }

    /**
     * Subquery id aset yang masuk lingkup filter.
     *
     * `$termasukUnitPengelola` dimatikan oleh tabel yang punya kolom unit
     * pengelola sendiri (Keluhan): di sana aset hanya menjawab unit organisasi,
     * sedangkan unit pengelola dibaca dari tiketnya.
     */
    private function asetDalamLingkup(FilterMetrik $filter, bool $termasukUnitPengelola = true): KontrakBuilder
    {
        $subquery = Aset::query()
            ->select('Id')
            ->whereNull('DihapusPada');

        if ($filter->adaFilterUnit()) {
            $subquery->whereIn('UnitOrganisasiId', $filter->unitOrganisasiId);
        }
        if ($filter->adaFilterLokasi()) {
            $subquery->whereIn('LokasiId', $filter->lokasiId);
        }
        if ($termasukUnitPengelola && $filter->adaFilterUnitPengelola()) {
            $subquery->whereIn('UnitPengelolaId', $filter->unitPengelolaId);
        }

        return $subquery->getQuery();
    }

    /**
     * Unit pengelola untuk baris turunan rencana (jadwal preventif, rencana kalibrasi).
     *
     * Kolom rencana dipakai bila terisi -- PRD 8.21 menyebutnya sumber
     * penyaringan dan penentu unit tiket yang dihasilkan. Rencana yang belum
     * diisi (dibuat sebelum fitur ini, atau sengaja dikosongkan) mengikuti unit
     * pengelola asetnya, sehingga tidak hilang dari laporan bagian mana pun
     * yang memelihara asetnya.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function saringUnitPengelolaRencana(Builder $query, FilterMetrik $filter, string $kolomUnitRencana, string $kolomAset): Builder
    {
        if (! $filter->adaFilterUnitPengelola()) {
            return $query;
        }

        return $query->where(fn ($syarat) => $syarat
            ->whereIn($kolomUnitRencana, $filter->unitPengelolaId)
            ->orWhere(fn ($cadangan) => $cadangan
                ->whereNull($kolomUnitRencana)
                ->whereIn($kolomAset, Aset::query()
                    ->select('Id')
                    ->whereNull('DihapusPada')
                    ->whereIn('UnitPengelolaId', $filter->unitPengelolaId)
                    ->getQuery())));
    }

    /**
     * Menyusun deret tanggal kosong sepanjang rentang, lalu mengisinya dari
     * hasil agregasi. Tanpa ini, grafik tren akan melompati hari tanpa data dan
     * membuat garis terlihat lebih mulus daripada kenyataannya.
     *
     * Rentang panjang (ekspor bertahun-tahun) tidak dijadikan ribuan titik:
     * di atas BATAS_HARI_DERET_HARIAN hari nilainya dijumlah per minggu (Senin
     * sampai Minggu, dipotong batas rentang), dan di atas
     * BATAS_HARI_DERET_MINGGUAN per bulan. Label minggu adalah tanggal awalnya
     * dengan `SampaiTanggal` sebagai akhirnya; label bulan `Y-m`, sama dengan
     * deretBulanan(). Hanya dipakai untuk hitungan (jumlah per hari), jadi
     * menjumlahkan per kelompok tidak mengubah totalnya.
     *
     * @param  array<string, float|int>  $nilaiPerTanggal
     * @return list<array{Label: string, Nilai: float, SampaiTanggal?: string}>
     */
    private function deretHarian(FilterMetrik $filter, array $nilaiPerTanggal): array
    {
        $jumlahHari = $filter->jumlahHari();

        if ($jumlahHari > self::BATAS_HARI_DERET_MINGGUAN) {
            $perBulan = [];
            foreach ($nilaiPerTanggal as $tanggal => $nilai) {
                $bulan = substr((string) $tanggal, 0, 7);
                $perBulan[$bulan] = ($perBulan[$bulan] ?? 0) + $nilai;
            }

            return $this->deretBulanan($filter, $perBulan);
        }

        $deret = [];
        $tanggal = CarbonImmutable::parse($filter->tanggalDari());
        $akhir = CarbonImmutable::parse($filter->tanggalSampai());
        $mingguan = $jumlahHari > self::BATAS_HARI_DERET_HARIAN;

        while ($tanggal->lessThanOrEqualTo($akhir)) {
            $ujung = $mingguan ? $tanggal->endOfWeek(CarbonImmutable::SUNDAY)->startOfDay() : $tanggal;
            if ($ujung->greaterThan($akhir)) {
                $ujung = $akhir;
            }

            $nilai = 0.0;
            for ($hari = $tanggal; $hari->lessThanOrEqualTo($ujung); $hari = $hari->addDay()) {
                $nilai += (float) ($nilaiPerTanggal[$hari->toDateString()] ?? 0);
            }

            $titik = ['Label' => $tanggal->toDateString(), 'Nilai' => $nilai];
            if ($mingguan) {
                $titik['SampaiTanggal'] = $ujung->toDateString();
            }

            $deret[] = $titik;
            $tanggal = $ujung->addDay();
        }

        return $deret;
    }

    /**
     * Sama seperti deretHarian tetapi per bulan, untuk metrik biaya yang tidak
     * bermakna dibaca harian.
     *
     * @param  array<string, float|int>  $nilaiPerBulan
     * @return list<array{Label: string, Nilai: float}>
     */
    private function deretBulanan(FilterMetrik $filter, array $nilaiPerBulan): array
    {
        $deret = [];
        $bulan = CarbonImmutable::parse($filter->tanggalDari())->startOfMonth();
        $akhir = CarbonImmutable::parse($filter->tanggalSampai());

        while ($bulan->lessThanOrEqualTo($akhir)) {
            $kunci = $bulan->format('Y-m');
            $deret[] = ['Label' => $kunci, 'Nilai' => (float) ($nilaiPerBulan[$kunci] ?? 0)];
            $bulan = $bulan->addMonth();
        }

        return $deret;
    }
}
