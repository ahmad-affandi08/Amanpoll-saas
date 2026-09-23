<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Services\PenghitungKelayakanAset;
use App\Domain\Aset\Domain\ValueObjects\ParameterKelayakan;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Aset::class);

        $parameter = ParameterKelayakan::dariKonfigurasi();

        $daftar = DaftarTersaring::untuk($request, Aset::query()->with(['kategoriAset', 'lokasi']))
            ->cari(['KodeAset', 'Nama', 'NomorSeri'])
            ->urut(['Nama', 'KodeAset', 'HargaPerolehan', 'TanggalPerolehan'], bawaan: 'Nama')
            ->faset(['Status', 'Kondisi']);

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
