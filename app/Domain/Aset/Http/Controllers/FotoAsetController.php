<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\HapusFotoAset;
use App\Domain\Aset\Application\Actions\JadikanFotoUtamaAset;
use App\Domain\Aset\Application\Actions\TambahFotoAset;
use App\Domain\Aset\Application\Services\GaleriFotoAset;
use App\Domain\Aset\Http\Requests\SimpanFotoAsetRequest;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Galeri foto aset (PRD 8.4 "Foto Aset"). Dipakai detail aset dasbor
 * (kunjungan Inertia) dan layar aset teknisi Mode Lapangan, termasuk unggahan
 * tertunda dari HP (`@/lib/http`, dijawab JSON).
 */
final class FotoAsetController extends Controller
{
    /** Izin `tambahFoto` diperiksa `SimpanFotoAsetRequest::authorize()` sebelum validasi. */
    public function store(SimpanFotoAsetRequest $request, Aset $aset, TambahFotoAset $aksi): RedirectResponse|JsonResponse
    {
        $berkas = $aksi->jalankan($aset, $request->foto(), $request->user('web')->Id);
        $jumlah = count($berkas);

        if ($request->wantsJson()) {
            return response()->json([
                'BerkasId' => array_map(fn (Berkas $satu): string => $satu->Id, $berkas),
                'FotoUtamaBerkasId' => $aset->FotoUtamaBerkasId,
                'FotoUtamaThumbnailUrl' => GaleriFotoAset::urlThumbnail($aset->FotoUtamaBerkasId),
            ], 201);
        }

        return back()->with('sukses', $jumlah === 1 ? 'Foto aset ditambahkan.' : "{$jumlah} foto aset ditambahkan.");
    }

    public function destroy(Aset $aset, Berkas $berkas, HapusFotoAset $aksi): RedirectResponse
    {
        $this->authorize('kelolaFoto', $aset);

        $aksi->jalankan($aset, $berkas);

        return back()->with('sukses', 'Foto aset dihapus.');
    }

    public function jadikanUtama(Aset $aset, Berkas $berkas, JadikanFotoUtamaAset $aksi): RedirectResponse
    {
        $this->authorize('kelolaFoto', $aset);

        $aksi->jalankan($aset, $berkas);

        return back()->with('sukses', 'Foto utama aset diperbarui.');
    }
}
