<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Controllers;

use App\Domain\Persetujuan\Application\Actions\BuatTahapPersetujuan;
use App\Domain\Persetujuan\Application\Actions\HapusTahapPersetujuan;
use App\Domain\Persetujuan\Application\Actions\UbahTahapPersetujuan;
use App\Domain\Persetujuan\Http\Requests\SimpanTahapPersetujuanRequest;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

final class TahapPersetujuanController extends Controller
{
    public function store(SimpanTahapPersetujuanRequest $request, AlurPersetujuan $alurPersetujuan, BuatTahapPersetujuan $aksi): RedirectResponse
    {
        $this->authorize('create', TahapPersetujuan::class);

        $aksi->jalankan($alurPersetujuan, $request->validated());

        return back()->with('sukses', 'Tahap persetujuan berhasil ditambahkan.');
    }

    public function update(SimpanTahapPersetujuanRequest $request, TahapPersetujuan $tahapPersetujuan, UbahTahapPersetujuan $aksi): RedirectResponse
    {
        $this->authorize('update', $tahapPersetujuan);

        $aksi->jalankan($tahapPersetujuan, $request->validated());

        return back()->with('sukses', 'Tahap persetujuan berhasil diperbarui.');
    }

    public function destroy(TahapPersetujuan $tahapPersetujuan, HapusTahapPersetujuan $aksi): RedirectResponse
    {
        $this->authorize('delete', $tahapPersetujuan);

        $aksi->jalankan($tahapPersetujuan);

        return back()->with('sukses', 'Tahap persetujuan berhasil dihapus.');
    }
}
