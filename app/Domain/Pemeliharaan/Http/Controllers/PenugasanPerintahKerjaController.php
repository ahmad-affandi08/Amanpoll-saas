<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Domain\Pemeliharaan\Application\Actions\ResponsPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\TugaskanPerintahKerja;
use App\Domain\Pemeliharaan\Http\Requests\ResponsPenugasanPerintahKerjaRequest;
use App\Domain\Pemeliharaan\Http\Requests\SimpanPenugasanPerintahKerjaRequest;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

final class PenugasanPerintahKerjaController extends Controller
{
    public function store(SimpanPenugasanPerintahKerjaRequest $request, PerintahKerja $perintahKerja, TugaskanPerintahKerja $aksi): RedirectResponse
    {
        $this->authorize('assign', $perintahKerja);
        $aksi->jalankan(
            $perintahKerja,
            $request->validated('PenggunaIds'),
            $request->validated('PeranTugas'),
            $request->boolean('GantiPenugasanAktif'),
            $request->user()->Id,
        );

        return back()->with('sukses', 'Penugasan teknisi berhasil diperbarui.');
    }

    public function respons(
        ResponsPenugasanPerintahKerjaRequest $request,
        PerintahKerja $perintahKerja,
        PenugasanPerintahKerja $penugasan,
        ResponsPenugasanPerintahKerja $aksi,
    ): RedirectResponse {
        $this->authorize('responsPenugasan', [$perintahKerja, $penugasan]);
        $aksi->jalankan($penugasan, $request->validated('Respons'), $request->validated('Catatan'), $request->user()->Id);

        return back()->with('sukses', 'Respons penugasan berhasil disimpan.');
    }
}
