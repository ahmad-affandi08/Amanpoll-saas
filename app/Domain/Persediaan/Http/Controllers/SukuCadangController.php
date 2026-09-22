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
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class SukuCadangController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SukuCadang::class);

        $daftar = DaftarTersaring::untuk($request, SukuCadang::query()->with('kategoriSukuCadang'))
            ->cari(['Kode', 'Nama'])
            // Hanya kolom nyata; Kategori dan Stok Tersedia turunan, jadi tidak dapat diurutkan server.
            ->urut(['Nama', 'Status'], bawaan: 'Nama')
            ->faset(['KategoriSukuCadangId']);

        $halaman = $daftar->halaman();

        // Stok dijumlahkan hanya untuk baris yang benar-benar tampil di halaman ini.
        $agregatStok = DB::table('StokSukuCadang')
            ->select('SukuCadangId', DB::raw('SUM(JumlahTersedia) - SUM(JumlahDitahan) as bersih'))
            ->whereIn('SukuCadangId', $halaman->getCollection()->pluck('Id'))
            ->groupBy('SukuCadangId')
            ->pluck('bersih', 'SukuCadangId');

        $halaman->getCollection()->each(function (SukuCadang $s) use ($agregatStok): void {
            $s->setAttribute('JumlahTersediaBersih', (float) ($agregatStok[$s->Id] ?? 0));
        });

        return Inertia::render('SukuCadang/Index', [
            'sukuCadang' => SukuCadangResource::collection($halaman),
            'filter' => $daftar->filterBerlaku(),
            'jumlahDibawahMinimum' => $this->jumlahDibawahMinimum(),
            'kategoriSukuCadang' => KategoriSukuCadang::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    /**
     * Dihitung di basis data, bukan dari baris yang kebetulan tampil.
     *
     * Halaman ini dipaginasi, jadi menghitungnya dari koleksi di tangan akan
     * melaporkan angka yang mengecil setiap kali pengguna berpindah halaman.
     */
    private function jumlahDibawahMinimum(): int
    {
        $saldo = DB::table('StokSukuCadang')
            ->select('SukuCadangId', DB::raw('SUM(JumlahTersedia) - SUM(JumlahDitahan) as bersih'))
            ->groupBy('SukuCadangId');

        return SukuCadang::query()
            ->leftJoinSub($saldo, 'saldo', 'saldo.SukuCadangId', '=', 'SukuCadang.Id')
            ->whereRaw('COALESCE(saldo.bersih, 0) <= SukuCadang.StokMinimum')
            ->count();
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
