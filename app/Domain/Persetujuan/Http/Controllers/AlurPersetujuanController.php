<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Controllers;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Persetujuan\Application\Actions\AktifkanAlurPersetujuan;
use App\Domain\Persetujuan\Application\Actions\BuatAlurPersetujuan;
use App\Domain\Persetujuan\Application\Actions\HapusAlurPersetujuan;
use App\Domain\Persetujuan\Application\Actions\NonaktifkanAlurPersetujuan;
use App\Domain\Persetujuan\Application\Actions\UbahAlurPersetujuan;
use App\Domain\Persetujuan\Http\Requests\SimpanAlurPersetujuanRequest;
use App\Domain\Persetujuan\Http\Resources\AlurPersetujuanResource;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Platform\Http\Resources\PenggunaResource;
use App\Domain\Platform\Http\Resources\PeranResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AlurPersetujuanController extends Controller
{
    public function __construct(private readonly RegistriEntitas $registriEntitas) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AlurPersetujuan::class);

        $daftar = DaftarTersaring::untuk(
            $request,
            AlurPersetujuan::query()->with(['tahapPersetujuan.peran', 'tahapPersetujuan.pengguna']),
        )
            ->cari(['Kode', 'Nama'])
            ->urut(['Nama', 'Kode', 'JenisEntitas'], bawaan: 'Nama')
            ->faset(['JenisEntitas', 'Aktif']);

        return Inertia::render('AlurPersetujuan/Index', [
            'alurPersetujuan' => AlurPersetujuanResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
            'jenisEntitasTersedia' => $this->registriEntitas->jenisDikenal(),
            'peran' => PeranResource::collection(Peran::query()->orderBy('Nama')->get()),
            'pengguna' => PenggunaResource::collection(Pengguna::query()->orderBy('Nama')->get()),
        ]);
    }

    public function store(SimpanAlurPersetujuanRequest $request, BuatAlurPersetujuan $aksi): RedirectResponse
    {
        $this->authorize('create', AlurPersetujuan::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Alur persetujuan berhasil dibuat.');
    }

    public function update(SimpanAlurPersetujuanRequest $request, AlurPersetujuan $alurPersetujuan, UbahAlurPersetujuan $aksi): RedirectResponse
    {
        $this->authorize('update', $alurPersetujuan);

        $aksi->jalankan($alurPersetujuan, $request->validated());

        return back()->with('sukses', 'Alur persetujuan berhasil diperbarui.');
    }

    public function destroy(AlurPersetujuan $alurPersetujuan, HapusAlurPersetujuan $aksi): RedirectResponse
    {
        $this->authorize('delete', $alurPersetujuan);

        $aksi->jalankan($alurPersetujuan);

        return back()->with('sukses', 'Alur persetujuan berhasil dihapus.');
    }

    public function aktifkan(AlurPersetujuan $alurPersetujuan, AktifkanAlurPersetujuan $aksi): RedirectResponse
    {
        $this->authorize('update', $alurPersetujuan);

        $aksi->jalankan($alurPersetujuan);

        return back()->with('sukses', 'Alur persetujuan berhasil diaktifkan.');
    }

    public function nonaktifkan(AlurPersetujuan $alurPersetujuan, NonaktifkanAlurPersetujuan $aksi): RedirectResponse
    {
        $this->authorize('update', $alurPersetujuan);

        $aksi->jalankan($alurPersetujuan);

        return back()->with('sukses', 'Alur persetujuan berhasil dinonaktifkan.');
    }
}
