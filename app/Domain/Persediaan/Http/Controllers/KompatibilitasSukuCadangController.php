<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Persediaan\Application\Actions\BuatKompatibilitasSukuCadang;
use App\Domain\Persediaan\Application\Actions\HapusKompatibilitasSukuCadang;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Http\Requests\SimpanKompatibilitasSukuCadangRequest;
use App\Domain\Persediaan\Http\Resources\SukuCadangResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KompatibilitasSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class KompatibilitasSukuCadangController extends Controller
{
    public function store(SimpanKompatibilitasSukuCadangRequest $request, BuatKompatibilitasSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('create', KompatibilitasSukuCadang::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Kompatibilitas suku cadang berhasil ditambahkan.');
    }

    public function destroy(KompatibilitasSukuCadang $kompatibilitasSukuCadang, HapusKompatibilitasSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('delete', $kompatibilitasSukuCadang);

        $aksi->jalankan($kompatibilitasSukuCadang);

        return back()->with('sukses', 'Kompatibilitas suku cadang berhasil dihapus.');
    }

    /** Filter suku cadang yang kompatibel dengan satu aset spesifik. */
    public function untukAset(Aset $aset): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SukuCadang::class);

        $sukuCadangId = KompatibilitasSukuCadang::query()
            ->where('AsetId', $aset->Id)
            ->orWhere(fn ($q) => $aset->ModelAsetId ? $q->where('ModelAsetId', $aset->ModelAsetId) : $q->whereRaw('1 = 0'))
            ->orWhere(fn ($q) => $aset->KategoriAsetId ? $q->where('KategoriAsetId', $aset->KategoriAsetId) : $q->whereRaw('1 = 0'))
            ->pluck('SukuCadangId');

        $sukuCadang = SukuCadang::query()
            ->whereIn('Id', $sukuCadangId)
            ->where('Status', StatusSukuCadang::Aktif->value)
            ->orderBy('Nama')
            ->get();

        return SukuCadangResource::collection($sukuCadang);
    }
}
