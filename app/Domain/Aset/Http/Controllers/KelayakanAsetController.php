<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Services\PenghitungKelayakanAset;
use App\Domain\Aset\Domain\ValueObjects\ParameterKelayakan;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kelayakan ekonomi aset: AIC dan MMEL (PRD 34).
 *
 * Menjawab pertanyaan yang selama ini hanya tertulis sebagai tujuan produk --
 * aset mana yang layak diperbaiki dan mana yang lebih ekonomis diganti.
 */
final class KelayakanAsetController extends Controller
{
    public function __construct(private readonly PenghitungKelayakanAset $penghitung) {}

    /**
     * Angka kelayakan satu aset, untuk tab di halaman detailnya.
     *
     * @return array<string, mixed>
     */
    public function satu(Aset $aset): array
    {
        $this->authorize('view', $aset);

        return [
            ...$this->penghitung->untuk($aset, ParameterKelayakan::dariKonfigurasi()),
            'Parameter' => $this->parameter(),
        ];
    }

    /** Daftar aset beserta putusan kelayakannya, untuk menyusun usulan penggantian. */
    /**
     * Penyaring daftar kelayakan, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<Aset>
     */
    /**
     * @param  Builder<Aset>|null  $kueri  kueri dasar, bila pemanggilnya perlu menambahkan kolom hitungan
     * @return DaftarTersaring<Aset>
     */
    private function daftar(Request $request, ?EloquentBuilder $kueri = null): DaftarTersaring
    {
        return DaftarTersaring::untuk($request, $kueri ?? Aset::query()->with(['kategoriAset', 'lokasi']))
            ->cari(['KodeAset', 'Nama', 'NomorSeri'])
            ->urut(['Nama', 'KodeAset', 'HargaPerolehan', 'TanggalPerolehan'], bawaan: 'Nama')
            ->faset(['Status', 'Kondisi']);
    }

    /**
     * Analisis kelayakan per aset, untuk melampiri usulan penggantian.
     *
     * Angkanya dihitung per aset, dan setiap kolom membutuhkan hasil hitungan
     * yang sama. Hasil aset terakhir disimpan satu slot saja -- cukup karena
     * kolom dinilai berurutan per baris, dan tidak menumpuk seperti peta yang
     * tumbuh mengikuti jumlah barisnya.
     */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', Aset::class);

        $parameter = ParameterKelayakan::dariKonfigurasi();
        $idTerakhir = null;
        $angkaTerakhir = [];

        $angka = function (Aset $aset) use ($parameter, &$idTerakhir, &$angkaTerakhir): array {
            if ($idTerakhir !== $aset->Id) {
                $idTerakhir = $aset->Id;
                $angkaTerakhir = $this->penghitung->untuk($aset, $parameter);
            }

            return $angkaTerakhir;
        };

        // Biaya kumulatifnya diikutkan sebagai subkueri, bukan dihitung per
        // aset: ekspor tidak punya halaman, dan satu kueri agregat per baris
        // membuat unduhan 8.000 aset putus di tengah tanpa pesan galat.
        $kueri = Aset::query()
            ->with(['kategoriAset', 'lokasi'])
            ->select('Aset.*')
            ->selectSub(
                PenghitungKelayakanAset::subkueriBiayaKumulatif(),
                PenghitungKelayakanAset::ALIAS_BIAYA_KUMULATIF,
            );

        return $ekspor->unduh(
            $this->daftar($request, $kueri)->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode Aset', 'KodeAset'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::dari('Kategori', fn (Aset $a): string => BacaRelasi::teks(BacaRelasi::model($a, 'kategoriAset'), 'Nama')),
                KolomEkspor::dari('Lokasi', fn (Aset $a): string => BacaRelasi::teks(BacaRelasi::model($a, 'lokasi'), 'Nama')),
                KolomEkspor::atribut('Kondisi', 'Kondisi'),
                KolomEkspor::atribut('Harga Perolehan', 'HargaPerolehan'),
                KolomEkspor::dari('Usia Pakai (tahun)', fn (Aset $a): float => (float) $angka($a)['UsiaPakaiTahun']),
                KolomEkspor::dari('Sisa Usia Manfaat (tahun)', fn (Aset $a): float => (float) $angka($a)['SisaUsiaManfaatTahun']),
                KolomEkspor::dari('Harga Perkiraan Pengganti', fn (Aset $a): float => (float) $angka($a)['HargaPerkiraanPengganti']),
                KolomEkspor::dari('AIC', fn (Aset $a): float => (float) $angka($a)['Aic']),
                KolomEkspor::dari('MMEL', fn (Aset $a): float => (float) $angka($a)['Mmel']),
                KolomEkspor::dari('Biaya Perbaikan Kumulatif', fn (Aset $a): float => (float) $angka($a)['BiayaPerbaikanKumulatif']),
                KolomEkspor::dari('Layak Diperbaiki', fn (Aset $a): string => $angka($a)['LayakDiperbaiki'] ? 'Ya' : 'Tidak'),
                KolomEkspor::dari('Alasan', fn (Aset $a): string => (string) $angka($a)['Alasan']),
            ],
            'kelayakan-aset',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Aset::class);

        $parameter = ParameterKelayakan::dariKonfigurasi();

        $daftar = $this->daftar($request);

        return Inertia::render('Aset/Kelayakan', [
            'aset' => $daftar->halamanTerpeta(function (Aset $satu) use ($parameter): array {
                $angka = $this->penghitung->untuk($satu, $parameter);

                return [
                    'Id' => $satu->Id,
                    'KodeAset' => $satu->KodeAset,
                    'Nama' => $satu->Nama,
                    'NamaKategoriAset' => $satu->kategoriAset?->Nama,
                    'NamaLokasi' => $satu->lokasi?->Nama,
                    'Kondisi' => $satu->Kondisi,
                    'UsiaPakaiTahun' => $angka['UsiaPakaiTahun'],
                    'SisaUsiaManfaatTahun' => $angka['SisaUsiaManfaatTahun'],
                    'Aic' => $angka['Aic'],
                    'Mmel' => $angka['Mmel'],
                    'BiayaPerbaikanKumulatif' => $angka['BiayaPerbaikanKumulatif'],
                    'LayakDiperbaiki' => $angka['LayakDiperbaiki'],
                    'Alasan' => $angka['Alasan'],
                ];
            }),
            'filter' => $daftar->filterBerlaku(),
            'parameter' => $this->parameter(),
        ]);
    }

    /** @return array{LajuInflasi: float, FaktorMel: float, PersenPemeliharaanAic: float} */
    private function parameter(): array
    {
        $parameter = ParameterKelayakan::dariKonfigurasi();

        return [
            'LajuInflasi' => $parameter->lajuInflasi,
            'FaktorMel' => $parameter->faktorMel,
            'PersenPemeliharaanAic' => $parameter->persenPemeliharaanAic,
        ];
    }
}
