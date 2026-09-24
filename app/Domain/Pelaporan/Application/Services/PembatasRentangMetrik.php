<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Services;

use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use Carbon\CarbonImmutable;

/**
 * Batas rentang untuk KPI yang dihitung sinkron di layar (dasbor, laporan tersimpan).
 *
 * Setiap KPI dihitung dalam permintaan HTTP yang sama, jadi rentang bebas
 * (2018–2026) berarti kueri besar dan deret ribuan titik di shared hosting.
 * Layar memotong rentangnya ke `amanpoll.pelaporan.rentang_maks_hari_interaktif`
 * hari terakhir dan mengatakannya; rentang lebih panjang tetap tersedia lewat
 * ekspor, yang berjalan di antrean dan divalidasi MintaEksporLaporanRequest.
 *
 * Tanggal yang tidak dapat dibaca (`?Dari=abc`) tidak menghasilkan 500 maupun
 * 422: ini halaman GET yang sering dibuka dari tautan tersimpan, jadi yang
 * ramah adalah kembali ke tanggal bawaan dan memberi tahu pengguna alasannya.
 */
final class PembatasRentangMetrik
{
    /** @var array<string, string> */
    private const LABEL_TANGGAL = ['Dari' => 'awal', 'Sampai' => 'akhir'];

    /**
     * `filter` dihitung di layar; `diminta` adalah rentang yang diminta (hanya
     * dibatasi batas ekspor) untuk disimpan atau diekspor; `catatan` pesan
     * untuk pengguna tentang apa yang diabaikan atau dipotong.
     *
     * @param  array<string, mixed>  $masukan
     * @return array{filter: FilterMetrik, diminta: FilterMetrik, catatan: list<string>}
     */
    public function interaktif(array $masukan, string $zona): array
    {
        $catatan = [];

        foreach (self::LABEL_TANGGAL as $kunci => $label) {
            $nilai = $masukan[$kunci] ?? null;
            if (FilterMetrik::tanggalTidakSah($nilai, $zona)) {
                $teks = is_string($nilai) ? mb_strimwidth($nilai, 0, 24, '…') : 'bukan tanggal';
                $catatan[] = "Tanggal {$label} \"{$teks}\" tidak dikenali, jadi tanggal bawaan yang dipakai.";
            }
        }

        $diminta = FilterMetrik::dariArray($masukan, $zona);
        $jumlahDiminta = $diminta->jumlahHari();

        $maksEkspor = self::maksHariEkspor();
        if ($jumlahDiminta > $maksEkspor) {
            $diminta = $diminta->dibatasiHari($maksEkspor);
        }

        $filter = $diminta->dibatasiHari(self::maksHariInteraktif());
        if ($jumlahDiminta > self::maksHariInteraktif()) {
            $catatan[] = 'Layar menampilkan paling banyak '.self::maksHariInteraktif().' hari, jadi yang tampil '
                .self::teksRentang($filter)." dari rentang {$jumlahDiminta} hari yang diminta. "
                .($jumlahDiminta > $maksEkspor
                    ? "Ekspor laporan dapat mencakup paling banyak {$maksEkspor} hari (".self::teksRentang($diminta).').'
                    : 'Untuk rentang penuh, gunakan ekspor laporan.');
        }

        return ['filter' => $filter, 'diminta' => $diminta, 'catatan' => $catatan];
    }

    public static function maksHariInteraktif(): int
    {
        return max(1, (int) config('amanpoll.pelaporan.rentang_maks_hari_interaktif', 365));
    }

    public static function maksHariEkspor(): int
    {
        return max(self::maksHariInteraktif(), (int) config('amanpoll.pelaporan.rentang_maks_hari_ekspor', 1827));
    }

    private static function teksRentang(FilterMetrik $filter): string
    {
        return CarbonImmutable::parse($filter->tanggalDari())->format('d-m-Y')
            .' s.d. '.CarbonImmutable::parse($filter->tanggalSampai())->format('d-m-Y');
    }
}
