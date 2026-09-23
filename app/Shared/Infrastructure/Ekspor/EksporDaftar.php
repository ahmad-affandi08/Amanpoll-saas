<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Clock\LayananZonaWaktu;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mengunduh satu daftar operasional apa adanya seperti yang tampil di layar.
 *
 * Kuerinya dioper sudah tersaring oleh controller-nya sendiri, bukan disusun
 * ulang di sini. Itu disengaja: ekspor yang menyusun ulang penyaringnya cepat
 * atau lambat akan berselisih dengan daftarnya, dan yang memegang berkasnya
 * tidak punya cara tahu bahwa isinya bukan yang ia lihat.
 *
 * Kopnya juga disusun di sini, bukan di tiap controller: berkas ekspor beredar
 * di luar aplikasi, dan pembacanya harus tahu ini milik rumah sakit mana,
 * laporan apa, dicetak kapan, dan dengan penyaring apa tanpa perlu bertanya.
 * Satu tempat berarti seluruh 59 ekspor berkop sama dan tidak ada yang
 * ketinggalan karena penulis controller-nya lupa.
 */
final class EksporDaftar
{
    /** Potongan baca; cukup besar untuk hemat round-trip, cukup kecil untuk hemat memori. */
    private const UKURAN_POTONGAN = 500;

    /** Zona waktu bila organisasinya belum menyetel apa pun. */
    private const ZONA_WAKTU_BAKU = 'Asia/Jakarta';

    /** Parameter teknis yang mengatur berkasnya, bukan menyaring isinya. */
    private const PARAMETER_BUKAN_PENYARING = ['format', 'page'];

    /** Batas panjang satu nilai penyaring di kop, supaya kopnya tetap terbaca. */
    private const BATAS_NILAI_PENYARING = 120;

    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly Request $permintaan,
        private readonly LayananZonaWaktu $zonaWaktu,
    ) {}

    /**
     * @param  Builder<covariant Model>  $kueri
     * @param  list<KolomEkspor>  $kolom
     * @param  array<string, string>  $meta  keterangan tambahan; menimpa kop bila kuncinya sama
     * @param  string|null  $judul  judul laporan bila penurunan dari $namaDasar kurang tepat
     */
    public function unduh(
        Builder $kueri,
        array $kolom,
        string $namaDasar,
        FormatEkspor $format,
        array $meta = [],
        ?string $judul = null,
    ): StreamedResponse {
        $kepala = array_map(static fn (KolomEkspor $satu): string => $satu->judul, $kolom);
        $organisasiId = $this->konteks->wajibId();
        $namaBerkas = $namaDasar.'-'.now()->format('Ymd-His').'.'.$format->ekstensi();

        // Penyaringnya dibaca sekarang, bukan di dalam closure: query string
        // adalah milik permintaan yang sedang berjalan, dan membacanya di sini
        // membuat jelas bahwa yang tercetak di kop adalah penyaring permintaan
        // ini -- bukan apa pun yang kebetulan terikat ke container saat streamnya
        // dialirkan.
        $penyaring = $this->penyaringBerlaku();
        $judulLaporan = $judul ?? self::judulDari($namaDasar);

        return response()->streamDownload(
            function () use ($format, $kueri, $kolom, $kepala, $meta, $organisasiId, $judulLaporan, $penyaring): void {
                // Isi unduhan dihasilkan sesudah middleware selesai dan konteks
                // organisasinya sudah dibersihkan; tanpa ditetapkan ulang di sini,
                // ScopeOrganisasi menutup seluruh kuerinya dan berkasnya terkirim
                // hanya berisi kepala kolom.
                $this->konteks->tetapkan($organisasiId);

                // Organisasinya dibaca DI SINI, sesudah konteksnya dipulihkan,
                // bukan sebelum respons dikembalikan. Pilihannya disengaja:
                // seluruh isi berkas -- kop maupun barisnya -- dibaca di bawah
                // satu konteks yang sama, sehingga kop tidak pernah dapat
                // menyebut tenant yang berbeda dari isi tabelnya. Organisasi
                // kebetulan tidak ber-ScopeOrganisasi hari ini, jadi membacanya
                // lebih awal pun berhasil; tetapi pembacaan yang bergantung pada
                // konteks di luar closure akan diam-diam menghasilkan kop kosong
                // begitu ada scope yang ikut berlaku -- kegagalan yang baru
                // ketahuan setelah berkasnya beredar di luar aplikasi.
                $organisasi = Organisasi::query()->find($organisasiId);

                $penulis = $this->penulis($format, $organisasi);
                $sementara = tempnam(sys_get_temp_dir(), 'ekspor-daftar');

                if ($sementara === false) {
                    return;
                }

                try {
                    $penulis->tulis(
                        $sementara,
                        $kepala,
                        $this->baris($kueri, $kolom),
                        // Meta dari pemanggil menimpa kop, bukan sebaliknya:
                        // pemanggil yang menyebut kunci yang sama berarti ia
                        // memang punya keterangan yang lebih tepat.
                        array_merge($this->kop($organisasi, $judulLaporan, $penyaring), $meta),
                    );
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
     * Kepala dokumen yang dicetak di ketiga format.
     *
     * Nilainya berasal dari data tenant (nama organisasi) dan dari query string
     * (penyaring), jadi keduanya melewati penetralan rumus di penulisnya -- itu
     * berlaku karena kop dioper sebagai `$meta` biasa, bukan lewat jalur sendiri.
     *
     * @return array<string, string>
     */
    private function kop(?Organisasi $organisasi, string $judul, string $penyaring): array
    {
        $kop = [
            'Organisasi' => $this->namaOrganisasi($organisasi),
            'Judul' => $judul,
            'Dicetak' => $this->dicetakPada($organisasi),
        ];

        if ($penyaring !== '') {
            $kop['Penyaring'] = $penyaring;
        }

        return $kop;
    }

    /** Nama legal ikut disebut bila berbeda, karena itu yang dikenali di luar rumah sakit. */
    private function namaOrganisasi(?Organisasi $organisasi): string
    {
        $nama = trim((string) ($organisasi->Nama ?? ''));
        $namaLegal = trim((string) ($organisasi->NamaLegal ?? ''));

        if ($namaLegal === '' || $namaLegal === $nama) {
            return $nama;
        }

        return $nama === '' ? $namaLegal : $nama.' ('.$namaLegal.')';
    }

    /** Waktu cetak dalam zona waktu organisasinya, bukan zona waktu server. */
    private function dicetakPada(?Organisasi $organisasi): string
    {
        $zona = trim((string) ($organisasi->ZonaWaktu ?? '')) ?: self::ZONA_WAKTU_BAKU;

        return $this->zonaWaktu->keZonaWaktu($this->zonaWaktu->sekarangUtc(), $zona)->format('d-m-Y H:i').' '.$zona;
    }

    /**
     * Penyaring yang sedang berlaku, dibaca dari query string permintaannya.
     *
     * Query string-lah yang menentukan isi berkas, jadi itu pula yang jujur
     * dituliskan; parameter yang hanya mengatur bentuk berkasnya dibuang supaya
     * tidak terbaca sebagai penyaring.
     */
    private function penyaringBerlaku(): string
    {
        $bagian = [];

        foreach ($this->permintaan->query() as $kunci => $nilai) {
            if (in_array(strtolower((string) $kunci), self::PARAMETER_BUKAN_PENYARING, true)) {
                continue;
            }

            $teks = $this->nilaiPenyaring($nilai);

            if ($teks === '') {
                continue;
            }

            $bagian[] = Str::headline((string) $kunci).': '.Str::limit($teks, self::BATAS_NILAI_PENYARING);
        }

        return implode('; ', $bagian);
    }

    /** Nilai bersarang di luar skalar dan daftar skalar dilewati: tidak ada bentuk terbaca untuknya. */
    private function nilaiPenyaring(mixed $nilai): string
    {
        if (is_array($nilai)) {
            $skalar = array_filter($nilai, static fn (mixed $satu): bool => is_scalar($satu));

            return implode(', ', array_map(static fn (mixed $satu): string => trim((string) $satu), $skalar));
        }

        return is_scalar($nilai) ? trim((string) $nilai) : '';
    }

    /** `daftar-aset` menjadi "Daftar Aset"; nama dasarnya sudah menyebut isi daftarnya. */
    private static function judulDari(string $namaDasar): string
    {
        $judul = Str::headline($namaDasar);

        return $judul === '' ? 'Daftar' : $judul;
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
     * @param  Builder<covariant Model>  $kueri
     * @param  list<KolomEkspor>  $kolom
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

    private function penulis(FormatEkspor $format, ?Organisasi $organisasi): PenulisEkspor
    {
        return match ($format) {
            FormatEkspor::Csv => new PenulisEksporCsv,
            FormatEkspor::Xlsx => new PenulisEksporXlsx,
            // Hanya PDF yang punya tempat untuk logo; CSV dan XLSX tidak.
            FormatEkspor::Pdf => new PenulisEksporPdf(LogoKopEkspor::dataUri($organisasi?->LogoUrl)),
        };
    }

    /** Format yang diminta bila dikenal; CSV bila tidak disebut. */
    public static function formatDari(Request $permintaan): FormatEkspor
    {
        return FormatEkspor::tryFrom(ucfirst(strtolower((string) $permintaan->query('format', ''))))
            ?? FormatEkspor::Csv;
    }
}
