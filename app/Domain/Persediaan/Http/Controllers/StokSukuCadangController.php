<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Http\Resources\StokSukuCadangResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Hanya baca. */
final class StokSukuCadangController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StokSukuCadang::class);

        // Nama suku cadang dan gudang ikut digabung supaya keduanya dapat dicari dan diurutkan di server.
        $kueri = StokSukuCadang::query()
            ->with(['gudang', 'lokasiGudang', 'sukuCadang', 'kelompokSukuCadang'])
            ->select('StokSukuCadang.*')
            ->leftJoin('SukuCadang', 'SukuCadang.Id', '=', 'StokSukuCadang.SukuCadangId')
            ->leftJoin('Gudang', 'Gudang.Id', '=', 'StokSukuCadang.GudangId');

        $daftar = DaftarTersaring::untuk($request, $kueri)
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

        return Inertia::render('StokSukuCadang/Index', [
            'stok' => StokSukuCadangResource::collection($daftar->halaman()),
            'gudang' => Gudang::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'sukuCadang' => SukuCadang::query()->where('Status', StatusSukuCadang::Aktif->value)->orderBy('Nama')->get(['Id', 'Nama', 'Kode']),
            'filter' => $daftar->filterBerlaku(),
        ]);
    }
}
