<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Domain\Pemeliharaan\Application\Actions\KelolaWaktuKerja;
use App\Domain\Pemeliharaan\Http\Requests\AksiWaktuKerjaRequest;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

final class WaktuKerjaController extends Controller
{
    public function store(AksiWaktuKerjaRequest $request, PerintahKerja $perintahKerja, KelolaWaktuKerja $aksi): RedirectResponse
    {
        $this->authorize('operate', $perintahKerja);
        $aksi->jalankan($perintahKerja, $request->user()->Id, $request->validated('Aksi'), $request->validated('Catatan'));

        return back()->with('sukses', 'Waktu kerja berhasil diperbarui.');
    }
}
