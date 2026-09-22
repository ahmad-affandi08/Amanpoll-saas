<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\BuatAset;
use App\Domain\Aset\Application\Actions\HapusAset;
use App\Domain\Aset\Application\Actions\UbahAset;
use App\Domain\Aset\Http\Requests\CetakLabelAsetRequest;
use App\Domain\Aset\Http\Requests\SimpanAsetRequest;
use App\Domain\Aset\Http\Resources\AsetResource;
use App\Domain\Aset\Http\Resources\KategoriAsetResource;
use App\Domain\Aset\Http\Resources\ModelAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\Penyedia\Http\Resources\PenyediaResource;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Http\Resources\LokasiResource;
use App\Domain\Platform\Http\Resources\UnitOrganisasiResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Qr\PembuatQrAset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AsetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Aset::class);

        $filter = $request->validate([
            'cari' => ['nullable', 'string'],
            'kategoriAsetId' => ['nullable', 'string'],
            'lokasiId' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'urutkan' => ['nullable', 'string', 'in:Nama,KodeAset,Status,DibuatPada'],
            'arah' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $urutkan = $filter['urutkan'] ?? 'DibuatPada';
        $arah = $filter['arah'] ?? 'desc';

        $aset = Aset::query()
            ->with(['kategoriAset', 'lokasi', 'modelAset'])
            ->when($filter['cari'] ?? null, fn ($q, $v) => $q->where(fn ($qq) => $qq
                ->where('Nama', 'like', "%{$v}%")
                ->orWhere('KodeAset', 'like', "%{$v}%")
                ->orWhere('NomorSeri', 'like', "%{$v}%")))
            ->when($filter['kategoriAsetId'] ?? null, fn ($q, $v) => $q->where('KategoriAsetId', $v))
            ->when($filter['lokasiId'] ?? null, fn ($q, $v) => $q->where('LokasiId', $v))
            ->when($filter['status'] ?? null, fn ($q, $v) => $q->where('Status', $v))
            ->orderBy($urutkan, $arah)
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Aset/Index', [
            'aset' => AsetResource::collection($aset),
            'filter' => $filter,
            // Dikirim dari server supaya batas di tombol cetak tidak pernah beda dengan validasinya.
            'maksLabel' => CetakLabelAsetRequest::MAKS_LABEL,
            'kategoriAset' => KategoriAsetResource::collection(KategoriAset::query()->orderBy('Nama')->get()),
            'lokasi' => LokasiResource::collection(Lokasi::query()->orderBy('Nama')->get()),
        ]);
    }

    public function show(Aset $aset, PembuatQrAset $pembuat): Response
    {
        $this->authorize('view', $aset);

        $aset->load(['kategoriAset', 'modelAset.merek', 'lokasi', 'unitOrganisasi', 'penyedia', 'dibuatOleh']);

        return Inertia::render('Aset/Show', [
            'aset' => new AsetResource($aset),
            // KodeQr dulu hanya ditampilkan sebagai teks, jadi tidak pernah bisa dipindai.
            'qr' => $aset->KodeQr === null ? null : $pembuat->untuk([$aset->KodeQr], 1)[0]['Svg'],
            'kategoriAset' => KategoriAsetResource::collection(KategoriAset::query()->orderBy('Nama')->get()),
            'modelAset' => ModelAsetResource::collection(ModelAset::query()->orderBy('Nama')->get()),
            'penyedia' => PenyediaResource::collection(Penyedia::query()->orderBy('Nama')->get()),
            'unitOrganisasi' => UnitOrganisasiResource::collection(UnitOrganisasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get()),
            'lokasi' => LokasiResource::collection(Lokasi::query()->orderBy('Nama')->get()),
        ]);
    }

    public function store(SimpanAsetRequest $request, BuatAset $aksi): RedirectResponse
    {
        $this->authorize('create', Aset::class);

        $aset = $aksi->jalankan($request->validated(), $request->user('web')->Id);

        return redirect("/aset/{$aset->Id}")->with('sukses', 'Aset berhasil didaftarkan.');
    }

    public function update(SimpanAsetRequest $request, Aset $aset, UbahAset $aksi): RedirectResponse
    {
        $this->authorize('update', $aset);

        $aksi->jalankan($aset, $request->validated());

        return back()->with('sukses', 'Aset berhasil diperbarui.');
    }

    public function destroy(Aset $aset, HapusAset $aksi): RedirectResponse
    {
        $this->authorize('delete', $aset);

        $aksi->jalankan($aset);

        return redirect('/aset')->with('sukses', 'Aset berhasil diarsipkan.');
    }
}
