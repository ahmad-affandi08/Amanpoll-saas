<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Queries;

use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpiBerkelompok;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\RumusKeandalan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * KPI keandalan: downtime, ketersediaan, MTTR, dan MTBF (21.01).
 *
 * Keempatnya dihitung dari satu ringkasan sesi waktu henti yang diagregasi di
 * basis data (dua kueri), bukan dari seluruh baris sesi yang ditarik ke PHP
 * sekali per KPI. Menit efektif satu sesi tetap sama persis dengan versi PHP
 * sebelumnya: selisih menit terpotong ke bawah antara `MulaiPada` dan
 * `min(SelesaiPada ?? akhir rentang, akhir rentang)`, minimal nol --
 * `TIMESTAMPDIFF(MINUTE, ...)` memotong ke arah nol dengan presisi mikrodetik,
 * sama dengan `(int) diffInMinutes()`.
 *
 * @phpstan-type BarisRingkasan array{Jenis: string, Bulan: string, Jumlah: int, JumlahSelesai: int, Menit: int, MenitSelesai: int}
 * @phpstan-type RingkasanKeandalan array{Kelompok: list<BarisRingkasan>, AsetSemua: int, AsetGagal: int}
 */
final class QueryKeandalan implements PenyediaKpiBerkelompok
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
        return $this->hitungBanyak([$kunci], $filter)[$kunci];
    }

    public function hitungBanyak(array $kunci, FilterMetrik $filter): array
    {
        foreach ($kunci as $satu) {
            if (! in_array($satu, $this->kunciDilayani(), true)) {
                throw new DataTidakDitemukan("KPI {$satu} bukan milik QueryKeandalan.");
            }
        }

        // Diambil sekali untuk seluruh KPI keandalan yang diminta bersama.
        $ringkasan = $this->ringkasan($filter);

        $hasil = [];
        foreach ($kunci as $satu) {
            $hasil[$satu] = match ($satu) {
                'downtime.total_jam' => $this->totalJam($ringkasan, $filter),
                'downtime.ketersediaan' => $this->ketersediaan($ringkasan, $filter),
                'keandalan.mttr' => $this->mttr($ringkasan),
                default => $this->mtbf($ringkasan, $filter),
            };
        }

        return $hasil;
    }

    /** @param RingkasanKeandalan $ringkasan */
    private function totalJam(array $ringkasan, FilterMetrik $filter): HasilKpi
    {
        $perJenis = [];
        $perBulan = [];

        foreach ($ringkasan['Kelompok'] as $baris) {
            $perJenis[$baris['Jenis']] = ($perJenis[$baris['Jenis']] ?? 0) + $baris['Menit'];
            $perBulan[$baris['Bulan']] = ($perBulan[$baris['Bulan']] ?? 0) + $baris['Menit'];
        }
        ksort($perJenis);

        return new HasilKpi(
            RumusKeandalan::totalJam(array_sum($perJenis)),
            $this->deretBulanan($filter, array_map(RumusKeandalan::totalJam(...), $perBulan)),
            ['PerJenisJam' => array_map(RumusKeandalan::totalJam(...), $perJenis)],
        );
    }

    /** @param RingkasanKeandalan $ringkasan */
    private function ketersediaan(array $ringkasan, FilterMetrik $filter): HasilKpi
    {
        if ($ringkasan['Kelompok'] === []) {
            return new HasilKpi(0.0, [], ['AdaData' => false, 'Penyebut' => 0]);
        }

        $totalMenit = array_sum(array_column($ringkasan['Kelompok'], 'Menit'));
        $menitTersedia = $this->menitOperasional($filter, $ringkasan['AsetSemua']);

        $menitAktif = RumusKeandalan::menitAktif($menitTersedia, $totalMenit);

        return HasilKpi::persen(
            $menitAktif,
            $menitTersedia,
            [
                ['Label' => 'Tersedia', 'Nilai' => RumusKeandalan::totalJam((int) $menitAktif)],
                ['Label' => 'Downtime', 'Nilai' => RumusKeandalan::totalJam($totalMenit)],
            ],
        );
    }

    /** @param RingkasanKeandalan $ringkasan */
    private function mttr(array $ringkasan): HasilKpi
    {
        $jumlah = 0;
        $totalMenit = 0;
        foreach ($this->kegagalan($ringkasan) as $baris) {
            $jumlah += $baris['JumlahSelesai'];
            $totalMenit += $baris['MenitSelesai'];
        }

        if ($jumlah === 0) {
            return new HasilKpi(0.0, [], ['AdaData' => false, 'Penyebut' => 0]);
        }

        return new HasilKpi(
            RumusKeandalan::mttr($totalMenit, $jumlah),
            [],
            ['AdaData' => true, 'Penyebut' => $jumlah, 'TotalMenit' => $totalMenit],
        );
    }

    /** @param RingkasanKeandalan $ringkasan */
    private function mtbf(array $ringkasan, FilterMetrik $filter): HasilKpi
    {
        $jumlah = 0;
        $menitDowntime = 0;
        foreach ($this->kegagalan($ringkasan) as $baris) {
            $jumlah += $baris['Jumlah'];
            $menitDowntime += $baris['Menit'];
        }

        if ($jumlah === 0) {
            return new HasilKpi(0.0, [], ['AdaData' => false, 'Penyebut' => 0]);
        }

        $menitOperasional = $this->menitOperasional($filter, $ringkasan['AsetGagal']);

        return new HasilKpi(
            RumusKeandalan::mtbf($menitOperasional, $menitDowntime, $jumlah),
            [],
            [
                'AdaData' => true,
                'Penyebut' => $jumlah,
                'MenitOperasional' => $menitOperasional,
                'MenitDowntime' => $menitDowntime,
            ],
        );
    }

    /**
     * @param  RingkasanKeandalan  $ringkasan
     * @return list<BarisRingkasan>
     */
    private function kegagalan(array $ringkasan): array
    {
        return array_values(array_filter(
            $ringkasan['Kelompok'],
            fn (array $baris): bool => strcasecmp($baris['Jenis'], self::JENIS_KEGAGALAN) === 0,
        ));
    }

    /**
     * Ringkasan sesi yang dimulai di dalam rentang: per jenis dan bulan (zona
     * organisasi) berisi jumlah sesi, jumlah yang sudah selesai, dan menit
     * efektifnya; ditambah jumlah aset berbeda untuk seluruh sesi dan untuk
     * kegagalan saja.
     *
     * @return RingkasanKeandalan
     */
    private function ringkasan(FilterMetrik $filter): array
    {
        // Mikrodetik ikut dikirim: akhir rentang adalah 23:59:59.999999, dan
        // tanpa pecahannya menit sesi yang masih terbuka bisa berbeda satu.
        $akhir = $filter->sampai->utc()->format('Y-m-d H:i:s.u');
        $menit = 'GREATEST(0, TIMESTAMPDIFF(MINUTE, MulaiPada, LEAST(COALESCE(SelesaiPada, ?), ?)))';
        [$sqlBulan, $ikatanBulan] = $this->ekspresiBulan($filter);

        $kelompok = $this->lingkup($filter)
            ->selectRaw(
                "Jenis, {$sqlBulan} AS Bulan, COUNT(*) AS Jumlah,"
                .' SUM(CASE WHEN SelesaiPada IS NOT NULL THEN 1 ELSE 0 END) AS JumlahSelesai,'
                ." SUM({$menit}) AS Menit,"
                ." SUM(CASE WHEN SelesaiPada IS NOT NULL THEN {$menit} ELSE 0 END) AS MenitSelesai",
                [...$ikatanBulan, $akhir, $akhir, $akhir, $akhir],
            )
            ->groupBy('Jenis', 'Bulan')
            ->toBase()
            ->get()
            ->map(fn (object $baris): array => [
                'Jenis' => (string) $baris->Jenis,
                'Bulan' => (string) $baris->Bulan,
                'Jumlah' => (int) $baris->Jumlah,
                'JumlahSelesai' => (int) $baris->JumlahSelesai,
                'Menit' => (int) $baris->Menit,
                'MenitSelesai' => (int) $baris->MenitSelesai,
            ])
            ->all();
        $kelompok = array_values($kelompok);

        if ($kelompok === []) {
            return ['Kelompok' => [], 'AsetSemua' => 0, 'AsetGagal' => 0];
        }

        $aset = $this->lingkup($filter)
            ->selectRaw(
                'COUNT(DISTINCT AsetId) AS AsetSemua, COUNT(DISTINCT CASE WHEN Jenis = ? THEN AsetId END) AS AsetGagal',
                [self::JENIS_KEGAGALAN],
            )
            ->toBase()
            ->first();

        return [
            'Kelompok' => $kelompok,
            'AsetSemua' => (int) ($aset->AsetSemua ?? 0),
            'AsetGagal' => (int) ($aset->AsetGagal ?? 0),
        ];
    }

    /**
     * Bulan kalender (zona organisasi) dari `MulaiPada`, sebagai CASE atas batas
     * awal tiap bulan dalam UTC.
     *
     * Batasnya dihitung PHP dari nama zona, jadi tepat juga untuk zona yang
     * mengenal waktu musim panas dan tidak menuntut tabel zona waktu MySQL --
     * shared hosting tidak menjamin `CONVERT_TZ` dengan nama zona.
     *
     * @return array{0: literal-string, 1: list<string>}
     */
    private function ekspresiBulan(FilterMetrik $filter): array
    {
        $bulan = CarbonImmutable::parse($filter->tanggalDari(), $filter->zona)->startOfMonth();
        $akhir = CarbonImmutable::parse($filter->tanggalSampai(), $filter->zona)->startOfMonth();

        $ikatan = [];
        while ($bulan->lessThan($akhir)) {
            $berikutnya = $bulan->addMonth();
            $ikatan[] = $berikutnya->utc()->format('Y-m-d H:i:s.u');
            $ikatan[] = $bulan->format('Y-m');
            $bulan = $berikutnya;
        }

        $ikatan[] = $akhir->format('Y-m');

        // Rentang satu bulan: CASE tanpa WHEN bukan SQL yang sah.
        if (count($ikatan) === 1) {
            return ['?', $ikatan];
        }

        $cabang = str_repeat(' WHEN MulaiPada < ? THEN ?', intdiv(count($ikatan), 2));

        return ["CASE{$cabang} ELSE ? END", $ikatan];
    }

    private function menitOperasional(FilterMetrik $filter, int $jumlahAset): float
    {
        return RumusKeandalan::menitOperasional(
            $jumlahAset,
            (int) $filter->dari->diffInMinutes($filter->sampai),
        );
    }

    /** @return Builder<WaktuHentiAset> */
    private function lingkup(FilterMetrik $filter): Builder
    {
        return $this->saringLewatAset(WaktuHentiAset::query(), $filter)
            ->whereBetween('MulaiPada', [$filter->dari, $filter->sampai]);
    }
}
