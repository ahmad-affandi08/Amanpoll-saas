<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset as KategoriAsetModel;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\Persediaan\Application\Actions\BuatKelompokSukuCadang;
use App\Domain\Persediaan\Application\Actions\BuatSukuCadang;
use App\Domain\Persediaan\Application\Actions\HapusKelompokSukuCadang;
use App\Domain\Persediaan\Application\Actions\HapusSukuCadang;
use App\Domain\Persediaan\Application\Actions\UbahKelompokSukuCadang;
use App\Domain\Persediaan\Application\Actions\UbahSukuCadang;
use App\Domain\Persediaan\Http\Requests\SimpanKelompokSukuCadangRequest;
use App\Domain\Persediaan\Http\Requests\SimpanSukuCadangRequest;
use App\Domain\Persediaan\Http\Resources\KelompokSukuCadangResource;
use App\Domain\Persediaan\Http\Resources\KompatibilitasSukuCadangResource;
use App\Domain\Persediaan\Http\Resources\SukuCadangResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class SukuCadangController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', SukuCadang::class);

        $sukuCadang = SukuCadang::query()->with('kategoriSukuCadang')->orderBy('Nama')->get();

        $agregatStok = DB::table('StokSukuCadang')
            ->select('SukuCadangId', DB::raw('SUM(JumlahTersedia) - SUM(JumlahDitahan) as bersih'))
            ->whereIn('SukuCadangId', $sukuCadang->pluck('Id'))
            ->groupBy('SukuCadangId')
            ->pluck('bersih', 'SukuCadangId');

        $sukuCadang->each(function (SukuCadang $s) use ($agregatStok): void {
            $s->setAttribute('JumlahTersediaBersih', (float) ($agregatStok[$s->Id] ?? 0));
        });

        return Inertia::render('SukuCadang/Index', [
            'sukuCadang' => SukuCadangResource::collection($sukuCadang),
            'kategoriSukuCadang' => KategoriSukuCadang::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function show(SukuCadang $sukuCadang): Response
    {
        $this->authorize('view', $sukuCadang);

        $sukuCadang->load(['kategoriSukuCadang', 'kompatibilitasSukuCadang.kategoriAset', 'kompatibilitasSukuCadang.modelAset', 'kompatibilitasSukuCadang.aset']);
        $kelompok = KelompokSukuCadang::query()->where('SukuCadangId', $sukuCadang->Id)->orderByDesc('DibuatPada')->get();

        return Inertia::render('SukuCadang/Show', [
            'sukuCadang' => new SukuCadangResource($sukuCadang),
            'kelompokSukuCadang' => KelompokSukuCadangResource::collection($kelompok),
            'kompatibilitasSukuCadang' => KompatibilitasSukuCadangResource::collection($sukuCadang->kompatibilitasSukuCadang),
            'kategoriAset' => KategoriAsetModel::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'modelAset' => ModelAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'aset' => Aset::query()->orderBy('Nama')->get(['Id', 'Nama', 'KodeAset']),
        ]);
    }

    public function store(SimpanSukuCadangRequest $request, BuatSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('create', SukuCadang::class);

        $sukuCadang = $aksi->jalankan($request->validated());

        return redirect("/suku-cadang/{$sukuCadang->Id}")->with('sukses', 'Suku cadang berhasil dibuat.');
    }

    public function update(SimpanSukuCadangRequest $request, SukuCadang $sukuCadang, UbahSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('update', $sukuCadang);

        $aksi->jalankan($sukuCadang, $request->validated());

        return back()->with('sukses', 'Suku cadang berhasil diperbarui.');
    }

    public function destroy(SukuCadang $sukuCadang, HapusSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('delete', $sukuCadang);

        $aksi->jalankan($sukuCadang);

        return redirect('/suku-cadang')->with('sukses', 'Suku cadang berhasil dihapus.');
    }

    public function storeKelompok(SimpanKelompokSukuCadangRequest $request, SukuCadang $sukuCadang, BuatKelompokSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('update', $sukuCadang);

        $data = $request->validated();
        $data['SukuCadangId'] = $sukuCadang->Id;
        $aksi->jalankan($data);

        return back()->with('sukses', 'Kelompok/batch suku cadang berhasil dibuat.');
    }

    public function updateKelompok(SimpanKelompokSukuCadangRequest $request, KelompokSukuCadang $kelompokSukuCadang, UbahKelompokSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('update', $kelompokSukuCadang->sukuCadang);

        $aksi->jalankan($kelompokSukuCadang, $request->validated());

        return back()->with('sukses', 'Kelompok/batch suku cadang berhasil diperbarui.');
    }

    public function destroyKelompok(KelompokSukuCadang $kelompokSukuCadang, HapusKelompokSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('update', $kelompokSukuCadang->sukuCadang);

        $aksi->jalankan($kelompokSukuCadang);

        return back()->with('sukses', 'Kelompok/batch suku cadang berhasil dihapus.');
    }
}
