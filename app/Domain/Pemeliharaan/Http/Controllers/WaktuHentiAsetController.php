<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Domain\Pemeliharaan\Application\Actions\KelolaWaktuHentiAset;
use App\Domain\Pemeliharaan\Http\Requests\AksiWaktuHentiAsetRequest;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

final class WaktuHentiAsetController extends Controller
{
    public function store(AksiWaktuHentiAsetRequest $request, PerintahKerja $perintahKerja, KelolaWaktuHentiAset $aksi): RedirectResponse
    {
        $this->authorize('operate', $perintahKerja);
        $aksi->jalankan(
            $perintahKerja,
            $request->validated('AsetId'),
            $request->validated('Aksi'),
            $request->validated('Jenis'),
            $request->validated('Alasan'),
        );

        return back()->with('sukses', 'Downtime aset berhasil diperbarui.');
    }
}
