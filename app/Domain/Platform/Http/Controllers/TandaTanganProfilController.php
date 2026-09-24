<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Platform\Application\Actions\HapusTandaTanganPengguna;
use App\Domain\Platform\Application\Actions\SimpanTandaTanganPengguna;
use App\Domain\Platform\Http\Requests\SimpanTandaTanganRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tanda tangan tersimpan milik pengguna yang sedang masuk (PRD 8.22).
 *
 * Hanya pemiliknya yang bisa melihat, mengganti, atau melepas tanda tangan
 * lewat rute ini; tidak ada parameter pengguna, jadi tidak ada jalur ke tanda
 * tangan orang lain.
 */
final class TandaTanganProfilController extends Controller
{
    public function lihat(Request $request, PenyimpanBerkas $penyimpanBerkas): StreamedResponse
    {
        $berkas = $request->user('web')->tandaTangan()->first();
        abort_if($berkas === null, 404);

        return $penyimpanBerkas->responsThumbnail($berkas);
    }

    public function simpan(SimpanTandaTanganRequest $request, SimpanTandaTanganPengguna $aksi): JsonResponse|RedirectResponse
    {
        $file = $request->file('TandaTangan');
        abort_unless($file instanceof UploadedFile, 422);

        $aksi->jalankan($request->user('web'), $file);

        return $request->expectsJson()
            ? response()->json(['pesan' => 'Tanda tangan tersimpan.'])
            : back()->with('sukses', 'Tanda tangan tersimpan.');
    }

    public function hapus(Request $request, HapusTandaTanganPengguna $aksi): JsonResponse|RedirectResponse
    {
        $aksi->jalankan($request->user('web'));

        return $request->expectsJson()
            ? response()->json(['pesan' => 'Tanda tangan dihapus.'])
            : back()->with('sukses', 'Tanda tangan dihapus.');
    }
}
