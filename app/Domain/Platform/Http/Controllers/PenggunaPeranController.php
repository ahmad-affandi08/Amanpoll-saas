<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Application\Actions\CabutPeranDariPengguna;
use App\Domain\Platform\Application\Actions\TetapkanPeranKePengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PenggunaPeranController extends Controller
{
    public function __construct(private readonly PemeriksaIzin $pemeriksaIzin) {}

    public function store(Request $request, Pengguna $pengguna, TetapkanPeranKePengguna $aksi): RedirectResponse
    {
        $this->pastikanBerizin($request);

        $data = $request->validate([
            'PeranId' => ['required', 'string'],
            'UnitOrganisasiId' => ['nullable', 'string'],
            'LokasiId' => ['nullable', 'string'],
        ]);

        $peran = Peran::query()->where('Id', $data['PeranId'])->firstOrFail();

        $aksi->jalankan($pengguna, $peran, $data['UnitOrganisasiId'] ?? null, $data['LokasiId'] ?? null);

        return back()->with('sukses', 'Peran berhasil ditetapkan ke pengguna.');
    }

    public function destroy(Request $request, PenggunaPeran $penggunaPeran, CabutPeranDariPengguna $aksi): RedirectResponse
    {
        $this->pastikanBerizin($request);

        $aksi->jalankan($penggunaPeran);

        return back()->with('sukses', 'Peran berhasil dicabut dari pengguna.');
    }

    private function pastikanBerizin(Request $request): void
    {
        abort_unless(
            $this->pemeriksaIzin->boleh((string) $request->user()->Id, 'Pengguna.Kelola'),
            403,
        );
    }
}
