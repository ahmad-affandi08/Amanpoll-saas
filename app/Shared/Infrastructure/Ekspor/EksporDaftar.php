<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

use App\Core\Organisasi\KonteksOrganisasi;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mengunduh satu daftar operasional apa adanya seperti yang tampil di layar.
 *
 * Kuerinya dioper sudah tersaring oleh controller-nya sendiri, bukan disusun
 * ulang di sini. Itu disengaja: ekspor yang menyusun ulang penyaringnya cepat
 * atau lambat akan berselisih dengan daftarnya, dan yang memegang berkasnya
 * tidak punya cara tahu bahwa isinya bukan yang ia lihat.
 */
final class EksporDaftar
{
    /** Potongan baca; cukup besar untuk hemat round-trip, cukup kecil untuk hemat memori. */
    private const UKURAN_POTONGAN = 500;

    public function __construct(private readonly KonteksOrganisasi $konteks) {}

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $kueri
     * @param  list<KolomEkspor<TModel>>  $kolom
     * @param  array<string, string>  $meta
     */
    public function unduh(
        Builder $kueri,
        array $kolom,
        string $namaDasar,
        FormatEkspor $format,
        array $meta = [],
    ): StreamedResponse {
        $penulis = $this->penulis($format);
        $kepala = array_map(static fn (KolomEkspor $satu): string => $satu->judul, $kolom);
        $organisasiId = $this->konteks->wajibId();
        $namaBerkas = $namaDasar.'-'.now()->format('Ymd-His').'.'.$format->ekstensi();

        return response()->streamDownload(
            function () use ($penulis, $kueri, $kolom, $kepala, $meta, $organisasiId): void {
                // Isi unduhan dihasilkan sesudah middleware selesai dan konteks
                // organisasinya sudah dibersihkan; tanpa ditetapkan ulang di sini,
                // ScopeOrganisasi menutup seluruh kuerinya dan berkasnya terkirim
                // hanya berisi kepala kolom.
                $this->konteks->tetapkan($organisasiId);

                $sementara = tempnam(sys_get_temp_dir(), 'ekspor-daftar');

                if ($sementara === false) {
                    return;
                }

                try {
                    $penulis->tulis($sementara, $kepala, $this->baris($kueri, $kolom), $meta);
                    readfile($sementara);
                } finally {
                    @unlink($sementara);
                    $this->konteks->bersihkan();
                }
            },
            $namaBerkas,
            ['Content-Type' => $format->jenisMime()],
        );
    }

    /**
     * Baris dihasilkan per potongan lewat generator, sehingga hanya satu
     * potongan yang pernah ada di memori sekaligus.
     *
     * `lazy()`, bukan `cursor()`: cursor mengalirkan baris satu per satu tetapi
     * tidak memuat relasi di muka, sehingga kolom yang menyebut nama lokasi
     * atau kategori akan memicu satu kueri tambahan per baris. Urutan yang
     * diberikan pemanggil dipertahankan, jadi kuerinya harus sudah punya urutan
     * yang pasti -- tanpa itu, potongan berbasis offset dapat melewatkan atau
     * menggandakan baris.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $kueri
     * @param  list<KolomEkspor<TModel>>  $kolom
     * @return Generator<int, list<string|float|int|null>>
     */
    private function baris(Builder $kueri, array $kolom): Generator
    {
        foreach ($kueri->lazy(self::UKURAN_POTONGAN) as $model) {
            $baris = [];

            foreach ($kolom as $satu) {
                $baris[] = $satu->nilai($model);
            }

            yield $baris;
        }
    }

    private function penulis(FormatEkspor $format): PenulisEkspor
    {
        return match ($format) {
            FormatEkspor::Csv => new PenulisEksporCsv,
            FormatEkspor::Xlsx => new PenulisEksporXlsx,
            FormatEkspor::Pdf => new PenulisEksporPdf,
        };
    }

    /** Format yang diminta bila dikenal; CSV bila tidak disebut. */
    public static function formatDari(Request $permintaan): FormatEkspor
    {
        return FormatEkspor::tryFrom(ucfirst(strtolower((string) $permintaan->query('format', ''))))
            ?? FormatEkspor::Csv;
    }
}
