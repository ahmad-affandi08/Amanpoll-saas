<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Services;

use App\Domain\Aset\Domain\ValueObjects\ParameterKelayakan;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Kelayakan ekonomi aset: AIC dan MMEL.
 *
 * Rumusnya mengikuti praktik yang lazim dipakai rumah sakit di Indonesia:
 *
 *   AIC  = IIC x (1 + i)^t / L
 *   MMEL = FaktorMel x PersentaseUsiaManfaat x HargaPerkiraanPengganti
 *
 * dengan IIC harga perolehan, i laju inflasi, t usia pakai dalam tahun, L umur
 * teknis dalam tahun, dan PersentaseUsiaManfaat = sisa usia manfaat dibagi
 * usia teknis. Sifat AIC yang penting: nilainya membesar seiring usia alat,
 * karena itulah ia dipakai sebagai dasar anggaran, bukan harga perolehan
 * mentah.
 *
 * MMEL adalah batas: biaya satu perbaikan yang melampauinya tidak ekonomis,
 * jadi alatnya sebaiknya diganti. Alat yang usia teknisnya sudah habis
 * bernilai MMEL nol -- tidak ada biaya perbaikan yang dapat dibenarkan.
 *
 * FaktorMel tidak seragam antar kelas alat dan antar acuan, jadi ia parameter
 * organisasi, bukan angka mati di kode. Bawaannya hanya titik mulai yang harus
 * disesuaikan dengan acuan yang dipakai rumah sakit.
 */
final class PenghitungKelayakanAset
{
    /** Nama kolom hasil subkueri biaya, bila kueri daftarnya sudah membawanya. */
    public const ALIAS_BIAYA_KUMULATIF = 'BiayaPerbaikanKumulatifTerhitung';

    /**
     * @return array{
     *     HargaPerolehan: float,
     *     UsiaPakaiTahun: float,
     *     UsiaTeknisTahun: float,
     *     SisaUsiaManfaatTahun: float,
     *     PersentaseUsiaManfaat: float,
     *     HargaPerkiraanPengganti: float,
     *     Aic: float,
     *     AnggaranPemeliharaanTahunan: float,
     *     Mmel: float,
     *     BiayaPerbaikanKumulatif: float,
     *     RasioBiayaTerhadapPerolehan: float,
     *     LayakDiperbaiki: bool,
     *     Alasan: string
     * }
     */
    public function untuk(Aset $aset, ParameterKelayakan $parameter): array
    {
        /*
         * Bila kuerinya sudah membawa jumlah biayanya sebagai subkueri, itu
         * yang dipakai. Halaman index memanggil ini 25 kali dan tidak
         * terganggu, tetapi ekspor tidak punya halaman: tanpa jalan pintas ini
         * rumah sakit dengan 8.000 aset menerbitkan 8.000 kueri agregat
         * berurutan, dan unduhannya putus di tengah tanpa pesan galat.
         */
        $biayaTerbawa = $aset->getAttribute(self::ALIAS_BIAYA_KUMULATIF);

        $harga = (float) ($aset->HargaPerolehan ?? 0.0);
        $usiaTeknis = ((int) ($aset->UmurManfaatBulan ?? 0)) / 12;
        $usiaPakai = $this->usiaPakaiTahun($aset);

        $sisa = max(0.0, $usiaTeknis - $usiaPakai);
        $persentaseUsia = $usiaTeknis > 0 ? $sisa / $usiaTeknis : 0.0;

        // Harga pengganti tanpa penawaran nyata didekati dengan harga perolehan
        // yang dikoreksi inflasi -- besaran yang sama yang menjadi pembilang AIC.
        $penggantiTeoretis = $harga * (1 + $parameter->lajuInflasi) ** $usiaPakai;

        $aic = $usiaTeknis > 0 ? $penggantiTeoretis / $usiaTeknis : 0.0;
        $mmel = $parameter->faktorMel * $persentaseUsia * $penggantiTeoretis;

        $kumulatif = $biayaTerbawa === null
            ? $this->biayaPerbaikanKumulatif($aset)
            : (float) $biayaTerbawa;

        return [
            'HargaPerolehan' => $harga,
            'UsiaPakaiTahun' => round($usiaPakai, 2),
            'UsiaTeknisTahun' => round($usiaTeknis, 2),
            'SisaUsiaManfaatTahun' => round($sisa, 2),
            'PersentaseUsiaManfaat' => round($persentaseUsia, 4),
            'HargaPerkiraanPengganti' => round($penggantiTeoretis, 2),
            'Aic' => round($aic, 2),
            'AnggaranPemeliharaanTahunan' => round($aic * $parameter->persenPemeliharaanAic, 2),
            'Mmel' => round($mmel, 2),
            'BiayaPerbaikanKumulatif' => round($kumulatif, 2),
            'RasioBiayaTerhadapPerolehan' => $harga > 0 ? round($kumulatif / $harga, 4) : 0.0,
            ...$this->putusan($aset, $mmel, $kumulatif, $sisa, $usiaTeknis),
        ];
    }

    /**
     * Putusan layak-tidaknya diperbaiki, beserta alasan yang dapat dibaca orang.
     *
     * Alasannya ikut dikembalikan karena angka saja tidak dapat
     * dipertanggungjawabkan di rapat: yang ditanya selalu "kenapa".
     *
     * @return array{LayakDiperbaiki: bool, Alasan: string}
     */
    private function putusan(Aset $aset, float $mmel, float $kumulatif, float $sisa, float $usiaTeknis): array
    {
        if ($aset->HargaPerolehan === null || $usiaTeknis <= 0) {
            return [
                'LayakDiperbaiki' => true,
                'Alasan' => 'Belum dapat dinilai: harga perolehan atau umur manfaat aset belum diisi.',
            ];
        }

        if ($sisa <= 0) {
            return [
                'LayakDiperbaiki' => false,
                'Alasan' => 'Usia teknis sudah habis, sehingga tidak ada biaya perbaikan yang dapat dibenarkan.',
            ];
        }

        if ($kumulatif > $mmel) {
            return [
                'LayakDiperbaiki' => false,
                'Alasan' => 'Biaya perbaikan kumulatif sudah melampaui MMEL; penggantian lebih ekonomis.',
            ];
        }

        return [
            'LayakDiperbaiki' => true,
            'Alasan' => 'Biaya perbaikan kumulatif masih di bawah MMEL.',
        ];
    }

    private function usiaPakaiTahun(Aset $aset): float
    {
        $mulai = $aset->TanggalMulaiOperasi ?? $aset->TanggalPerolehan;

        if ($mulai === null) {
            return 0.0;
        }

        return max(0.0, $mulai->diffInDays(now()) / 365.25);
    }

    /**
     * Seluruh biaya perintah kerja yang pernah menyentuh aset ini.
     *
     * Dijumlahkan di basis data lewat PerintahKerjaAset: biaya melekat pada
     * perintah kerja, sedangkan satu perintah kerja dapat mengerjakan beberapa
     * aset sekaligus.
     */
    private function biayaPerbaikanKumulatif(Aset $aset): float
    {
        return (float) DB::table('BiayaPerintahKerja')
            ->join('PerintahKerjaAset', 'PerintahKerjaAset.PerintahKerjaId', '=', 'BiayaPerintahKerja.PerintahKerjaId')
            ->where('PerintahKerjaAset.AsetId', $aset->Id)
            ->where('BiayaPerintahKerja.OrganisasiId', $aset->OrganisasiId)
            ->sum('BiayaPerintahKerja.Jumlah');
    }

    /**
     * Subkueri jumlah biaya per aset, untuk dipasang pada kueri daftar.
     *
     * Bentuknya harus sama persis dengan `biayaPerbaikanKumulatif()` di atas,
     * jadi keduanya tinggal bersebelahan: angka ekspor yang berbeda dari angka
     * layar adalah cacat yang tidak akan dilaporkan siapa pun, hanya dipercaya.
     */
    public static function subkueriBiayaKumulatif(): QueryBuilder
    {
        return DB::table('BiayaPerintahKerja')
            ->selectRaw('COALESCE(SUM(BiayaPerintahKerja.Jumlah), 0)')
            ->join('PerintahKerjaAset', 'PerintahKerjaAset.PerintahKerjaId', '=', 'BiayaPerintahKerja.PerintahKerjaId')
            ->whereColumn('PerintahKerjaAset.AsetId', 'Aset.Id')
            ->whereColumn('BiayaPerintahKerja.OrganisasiId', 'Aset.OrganisasiId');
    }
}
