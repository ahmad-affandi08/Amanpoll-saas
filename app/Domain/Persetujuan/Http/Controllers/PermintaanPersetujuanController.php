<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Controllers;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Persetujuan\Application\Actions\AjukanPermintaanPersetujuan;
use App\Domain\Persetujuan\Application\Actions\BatalkanPermintaanPersetujuan;
use App\Domain\Persetujuan\Application\Actions\SetujuiPermintaanPersetujuan;
use App\Domain\Persetujuan\Application\Actions\TolakPermintaanPersetujuan;
use App\Domain\Persetujuan\Application\Services\LayananPenyetuju;
use App\Domain\Persetujuan\Http\Requests\SimpanKeputusanPersetujuanRequest;
use App\Domain\Persetujuan\Http\Requests\SimpanPermintaanPersetujuanRequest;
use App\Domain\Persetujuan\Http\Resources\PermintaanPersetujuanResource;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AksesDitolak;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

final class PermintaanPersetujuanController extends Controller
{
    public function __construct(private readonly RegistriEntitas $registriEntitas) {}

    public function halaman(): Response
    {
        return Inertia::render('PermintaanPersetujuan/Index');
    }

    public function store(SimpanPermintaanPersetujuanRequest $request, AjukanPermintaanPersetujuan $aksi): RedirectResponse
    {
        $data = $request->validated();

        /** @var AlurPersetujuan $alurPersetujuan */
        $alurPersetujuan = AlurPersetujuan::query()->findOrFail($data['AlurPersetujuanId']);
        $this->registriEntitas->pastikanBolehKelola($request->user(), $alurPersetujuan->JenisEntitas);

        $aksi->jalankan($alurPersetujuan, $data['EntitasId'], $data['DataTambahan'] ?? null, $request->user()->Id);

        return back()->with('sukses', 'Permintaan persetujuan berhasil diajukan.');
    }

    public function destroy(PermintaanPersetujuan $permintaanPersetujuan, BatalkanPermintaanPersetujuan $aksi, Request $request): RedirectResponse
    {
        $bolehBatal = $permintaanPersetujuan->DimintaOleh === $request->user()->Id
            || $this->registriEntitas->bolehKelola($request->user(), $permintaanPersetujuan->JenisEntitas);

        if (!$bolehBatal) {
            throw new AksesDitolak('Anda tidak berhak membatalkan permintaan ini.');
        }

        $aksi->jalankan($permintaanPersetujuan);

        return back()->with('sukses', 'Permintaan persetujuan berhasil dibatalkan.');
    }

    public function setujui(SimpanKeputusanPersetujuanRequest $request, PermintaanPersetujuan $permintaanPersetujuan, SetujuiPermintaanPersetujuan $aksi): RedirectResponse
    {
        $aksi->jalankan($permintaanPersetujuan, $request->user(), $request->validated()['Catatan'] ?? null);

        return back()->with('sukses', 'Permintaan berhasil disetujui.');
    }

    public function tolak(SimpanKeputusanPersetujuanRequest $request, PermintaanPersetujuan $permintaanPersetujuan, TolakPermintaanPersetujuan $aksi): RedirectResponse
    {
        $aksi->jalankan($permintaanPersetujuan, $request->user(), $request->validated()['Catatan'] ?? null);

        return back()->with('sukses', 'Permintaan berhasil ditolak.');
    }

    public function milikSaya(Request $request): AnonymousResourceCollection
    {
        $permintaan = PermintaanPersetujuan::query()
            ->with(['alurPersetujuan', 'dimintaOleh', 'keputusan.penyetuju'])
            ->where('DimintaOleh', $request->user()->Id)
            ->latest('DimintaPada')
            ->get();

        return PermintaanPersetujuanResource::collection($permintaan);
    }

    public function inbox(Request $request, LayananPenyetuju $layananPenyetuju): AnonymousResourceCollection
    {
        $pengguna = $request->user();

        $menunggu = PermintaanPersetujuan::query()
            ->with(['alurPersetujuan', 'dimintaOleh'])
            ->where('Status', PermintaanPersetujuan::STATUS_MENUNGGU)
            ->get();

        if ($menunggu->isEmpty()) {
            return PermintaanPersetujuanResource::collection($menunggu);
        }

        $tahapPerAlurUrutan = TahapPersetujuan::query()
            ->whereIn('AlurPersetujuanId', $menunggu->pluck('AlurPersetujuanId')->unique())
            ->get()
            ->groupBy(fn (TahapPersetujuan $t) => $t->AlurPersetujuanId.'#'.$t->Urutan);

        $entitasPerJenis = $menunggu->groupBy('JenisEntitas')->map(
            fn ($grup, string $jenisEntitas) => $this->registriEntitas->cariBanyakEntitas(
                $jenisEntitas,
                array_values(array_map(strval(...), $grup->pluck('EntitasId')->unique()->all())),
            ),
        );

        $perluTindakan = $menunggu->filter(function (PermintaanPersetujuan $permintaan) use ($pengguna, $layananPenyetuju, $tahapPerAlurUrutan, $entitasPerJenis) {
            $tahap = $tahapPerAlurUrutan->get($permintaan->AlurPersetujuanId.'#'.$permintaan->TahapSaatIni)?->first();
            $entitas = $entitasPerJenis->get($permintaan->JenisEntitas)?->get($permintaan->EntitasId);

            if (!$tahap || !$entitas) {
                return false;
            }

            return $layananPenyetuju->bolehMemutuskan($tahap, $entitas, $pengguna, $permintaan->DimintaOleh);
        })->values();

        return PermintaanPersetujuanResource::collection($perluTindakan);
    }
}
