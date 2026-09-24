<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Impor;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use DateTimeInterface;
use ErrorException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\OpenSpoutException;
use OpenSpout\Reader\CSV\Options as OpsiCsv;
use OpenSpout\Reader\CSV\Reader as PembacaCsv;
use OpenSpout\Reader\XLSX\Options as OpsiXlsx;
use OpenSpout\Reader\XLSX\Reader as PembacaXlsx;

/**
 * Membaca lembar pertama berkas CSV atau XLSX menjadi kepala kolom dan baris teks.
 *
 * Seluruh sel dikembalikan sebagai teks yang sudah dipangkas, apa pun tipenya
 * di berkas: angka XLSX menjadi `15000000`, tanggal XLSX menjadi `2024-05-31`.
 * Dengan begitu pemeriksa di atasnya memperlakukan kedua format persis sama.
 *
 * Nomor baris mengikuti nomor baris di lembar kerja (kepala = baris 1), bukan
 * urutan baris berisi, supaya galat "baris 7" menunjuk baris yang sama dengan
 * yang dilihat pengguna di Excel. Baris kosong dilewati dan tidak dihitung.
 */
final class PembacaBerkasTabel
{
    /** Pemisah CSV yang dikenali; Excel berlocale Indonesia menyimpan CSV dengan titik koma. */
    private const PEMISAH_CSV = [',', ';'];

    /**
     * @param  'csv'|'xlsx'  $format
     * @return array{kepala: list<string>, baris: list<array{nomor: int, sel: list<string>}>}
     *
     * @throws AturanBisnisDilanggar bila berkas tidak terbaca, kosong, atau barisnya melebihi batas
     */
    public function baca(string $jalur, string $format, int $maksBaris): array
    {
        $pembaca = $format === 'xlsx'
            ? new PembacaXlsx(new OpsiXlsx(SHOULD_PRESERVE_EMPTY_ROWS: true))
            : new PembacaCsv(new OpsiCsv(SHOULD_PRESERVE_EMPTY_ROWS: true, FIELD_DELIMITER: $this->tebakPemisah($jalur)));

        try {
            $pembaca->open($jalur);

            return $this->bacaLembarPertama($pembaca, $maksBaris);
        } catch (OpenSpoutException|ErrorException) {
            throw new AturanBisnisDilanggar('Berkas tidak dapat dibaca. Pastikan berkasnya CSV atau XLSX yang utuh, misalnya dengan menyimpan ulang dari templat.');
        } finally {
            $pembaca->close();
        }
    }

    /**
     * @return array{kepala: list<string>, baris: list<array{nomor: int, sel: list<string>}>}
     */
    private function bacaLembarPertama(PembacaCsv|PembacaXlsx $pembaca, int $maksBaris): array
    {
        $kepala = null;
        $baris = [];

        foreach ($pembaca->getSheetIterator() as $lembar) {
            $nomor = 0;

            foreach ($lembar->getRowIterator() as $satu) {
                $nomor++;
                $sel = $this->selTeks($satu);

                if ($this->kosong($sel)) {
                    continue;
                }

                if ($kepala === null) {
                    $kepala = $sel;

                    continue;
                }

                if (count($baris) >= $maksBaris) {
                    throw new AturanBisnisDilanggar(sprintf(
                        'Berkas berisi lebih dari %s baris data. Pecah menjadi beberapa berkas lalu impor satu per satu.',
                        number_format($maksBaris, 0, ',', '.'),
                    ));
                }

                $baris[] = ['nomor' => $nomor, 'sel' => $sel];
            }

            // Hanya lembar pertama; lembar lain (mis. "Petunjuk" di templat) bukan data.
            break;
        }

        if ($kepala === null) {
            throw new AturanBisnisDilanggar('Berkas kosong. Baris pertama harus berisi judul kolom seperti di templat.');
        }

        return ['kepala' => $kepala, 'baris' => $baris];
    }

    /**
     * @return list<string>
     */
    private function selTeks(Row $baris): array
    {
        $hasil = [];

        // Sel kosong di tengah baris XLSX bisa tidak punya indeks; isi celahnya
        // supaya indeks sel selalu sama dengan indeks kolom kepala.
        foreach ($baris->toArray() as $indeks => $nilai) {
            $hasil[$indeks] = match (true) {
                $nilai instanceof DateTimeInterface => $nilai->format('Y-m-d'),
                is_float($nilai) && floor($nilai) === $nilai && abs($nilai) < 1e15 => (string) (int) $nilai,
                is_bool($nilai) => $nilai ? '1' : '0',
                is_string($nilai) => trim($this->utf8($nilai)),
                is_int($nilai), is_float($nilai) => (string) $nilai,
                default => '',
            };
        }

        $rapat = [];

        for ($indeks = 0, $akhir = $hasil === [] ? -1 : max(array_keys($hasil)); $indeks <= $akhir; $indeks++) {
            $rapat[] = $hasil[$indeks] ?? '';
        }

        return $rapat;
    }

    /**
     * CSV yang disimpan Excel sebagai "CSV (Comma delimited)" berpengodean
     * Windows-1252, bukan UTF-8; tanpa diubah, huruf beraksen merusak balasan JSON.
     */
    private function utf8(string $nilai): string
    {
        return mb_check_encoding($nilai, 'UTF-8') ? $nilai : mb_convert_encoding($nilai, 'UTF-8', 'Windows-1252');
    }

    /**
     * @param  list<string>  $sel
     */
    private function kosong(array $sel): bool
    {
        foreach ($sel as $nilai) {
            if ($nilai !== '') {
                return false;
            }
        }

        return true;
    }

    /** Pemisah yang paling banyak muncul di baris pertama; koma bila seri atau tidak ada. */
    private function tebakPemisah(string $jalur): string
    {
        $pegangan = @fopen($jalur, 'rb');

        if ($pegangan === false) {
            return ',';
        }

        $barisPertama = (string) fgets($pegangan);
        fclose($pegangan);

        $terbaik = ',';
        $jumlahTerbaik = 0;

        foreach (self::PEMISAH_CSV as $pemisah) {
            $jumlah = substr_count($barisPertama, $pemisah);

            if ($jumlah > $jumlahTerbaik) {
                $terbaik = $pemisah;
                $jumlahTerbaik = $jumlah;
            }
        }

        return $terbaik;
    }
}
