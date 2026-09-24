<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Persediaan\Application\Services\LingkupGudang;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Http\Resources\StokSukuCadangResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Hanya baca. */
final class StokSukuCadangController extends Controller
{
    public function __construct(private readonly LingkupGudang $lingkupGudang) {}

    /**
     * Penyaring daftar stok, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<StokSukuCadang>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        // Nama suku cadang dan gudang ikut digabung supaya keduanya dapat dicari dan diurutkan di server.
        $kueri = StokSukuCadang::query()
            ->with(['gudang', 'lokasiGudang', 'sukuCadang', 'kelompokSukuCadang'])
            ->select('StokSukuCadang.*')
            ->leftJoin('SukuCadang', 'SukuCadang.Id', '=', 'StokSukuCadang.SukuCadangId')
            ->leftJoin('Gudang', 'Gudang.Id', '=', 'StokSukuCadang.GudangId');

        // Hanya stok di gudang yang terlihat pengguna (PRD 8.21); ikut terbawa ke ekspor.
        $this->lingkupGudang->saring($kueri, 'StokSukuCadang.GudangId');

        return DaftarTersaring::untuk($request, $kueri)
            ->cari(['SukuCadang.Nama', 'SukuCadang.Kode', 'Gudang.Nama'])
            ->urut([
                'NamaSukuCadang' => 'SukuCadang.Nama',
                'NamaGudang' => 'Gudang.Nama',
                'JumlahTersedia' => 'StokSukuCadang.JumlahTersedia',
                'JumlahDitahan' => 'StokSukuCadang.JumlahDitahan',
            ], bawaan: 'NamaSukuCadang')
            ->faset([
                'gudangId' => 'StokSukuCadang.GudangId',
                'sukuCadangId' => 'StokSukuCadang.SukuCadangId',
            ]);
    }

    /** Kartu stok per gudang, seperti yang tampil di layar. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', StokSukuCadang::class);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::dari('Kode Suku Cadang', fn (StokSukuCadang $s): string => BacaRelasi::teks(BacaRelasi::model($s, 'sukuCadang'), 'Kode')),
                KolomEkspor::dari('Suku Cadang', fn (StokSukuCadang $s): string => BacaRelasi::teks(BacaRelasi::model($s, 'sukuCadang'), 'Nama')),
                KolomEkspor::dari('Gudang', fn (StokSukuCadang $s): string => BacaRelasi::teks(BacaRelasi::model($s, 'gudang'), 'Nama')),
                KolomEkspor::dari('Lokasi Gudang', fn (StokSukuCadang $s): string => BacaRelasi::teks(BacaRelasi::model($s, 'lokasiGudang'), 'Nama')),
                KolomEkspor::atribut('Tersedia', 'JumlahTersedia'),
                KolomEkspor::atribut('Dipesan', 'JumlahDipesan'),
                KolomEkspor::atribut('Ditahan', 'JumlahDitahan'),
            ],
            'kartu-stok',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StokSukuCadang::class);

        $daftar = $this->daftar($request);

        return Inertia::render('StokSukuCadang/Index', [
            'stok' => StokSukuCadangResource::collection($daftar->halaman()),
            'gudang' => Gudang::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'sukuCadang' => SukuCadang::query()->where('Status', StatusSukuCadang::Aktif->value)->orderBy('Nama')->get(['Id', 'Nama', 'Kode']),
            'filter' => $daftar->filterBerlaku(),
        ]);
    }
}
