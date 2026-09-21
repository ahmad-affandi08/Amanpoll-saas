<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Domain\Pemeliharaan\Application\Actions\CatatBiayaPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\GunakanSukuCadangPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\SimpanAnalisisKegagalan;
use App\Domain\Pemeliharaan\Http\Requests\GunakanSukuCadangPerintahKerjaRequest;
use App\Domain\Pemeliharaan\Http\Requests\SimpanAnalisisKegagalanRequest;
use App\Domain\Pemeliharaan\Http\Requests\SimpanBiayaPerintahKerjaRequest;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Persediaan\Application\Actions\BuatReservasiSukuCadang;
use App\Domain\Persediaan\Http\Requests\SimpanReservasiSukuCadangRequest;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

final class OperasionalPerintahKerjaController extends Controller
{
    public function reservasi(
        SimpanReservasiSukuCadangRequest $request,
        PerintahKerja $perintahKerja,
        BuatReservasiSukuCadang $aksi,
    ): RedirectResponse {
        $this->authorize('operate', $perintahKerja);
        $aksi->jalankan([...$request->validated(), 'PerintahKerjaId' => $perintahKerja->Id], $request->user('web')->Id);

        return back()->with('sukses', 'Suku cadang berhasil direservasi untuk pekerjaan.');
    }

    public function sukuCadang(
        GunakanSukuCadangPerintahKerjaRequest $request,
        PerintahKerja $perintahKerja,
        GunakanSukuCadangPerintahKerja $aksi,
    ): RedirectResponse {
        $this->authorize('operate', $perintahKerja);
        $reservasi = ReservasiSukuCadang::query()->findOrFail($request->validated('ReservasiSukuCadangId'));
        $aksi->jalankan($perintahKerja, $reservasi, $request->validated('Aksi'), $request->user('web')->Id);

        return back()->with('sukses', $request->validated('Aksi') === 'Pakai' ? 'Suku cadang berhasil dipakai dan biaya tercatat.' : 'Sisa reservasi berhasil dikembalikan.');
    }

    public function biaya(
        SimpanBiayaPerintahKerjaRequest $request,
        PerintahKerja $perintahKerja,
        CatatBiayaPerintahKerja $aksi,
    ): RedirectResponse {
        $this->authorize('manageCost', $perintahKerja);
        $aksi->jalankan($perintahKerja, $request->validated(), $request->user('web')->Id);

        return back()->with('sukses', 'Biaya pekerjaan berhasil dicatat.');
    }

    public function analisis(
        SimpanAnalisisKegagalanRequest $request,
        PerintahKerja $perintahKerja,
        SimpanAnalisisKegagalan $aksi,
    ): RedirectResponse {
        $this->authorize('operate', $perintahKerja);
        $aksi->jalankan($perintahKerja, $request->validated(), $request->user('web')->Id);

        return back()->with('sukses', 'Analisis kegagalan berhasil disimpan.');
    }
}
