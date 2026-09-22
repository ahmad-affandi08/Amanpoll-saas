<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Domain\Pemeliharaan\Application\Actions\HapusTingkatLayanan;
use App\Domain\Pemeliharaan\Application\Actions\SimpanTingkatLayanan;
use App\Domain\Pemeliharaan\Http\Requests\SimpanTingkatLayananRequest;
use App\Domain\Pemeliharaan\Http\Resources\TingkatLayananResource;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class TingkatLayananController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', TingkatLayanan::class);

        return Inertia::render('TingkatLayanan/Index', [
            'wajib' => ['tingkatLayanan' => AturanWajib::untuk(SimpanTingkatLayananRequest::class)],
            'tingkatLayanan' => TingkatLayananResource::collection(
                TingkatLayanan::query()
                    ->with(['aturan' => fn ($query) => $query->orderBy('MenitPenyelesaian'), 'eskalasi' => fn ($query) => $query->with(['peran', 'pengguna'])->orderBy('Tahap')])
                    ->orderBy('Nama')
                    ->get(),
            ),
            'peran' => Peran::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'pengguna' => Pengguna::query()->where('OrganisasiId', auth('web')->user()->OrganisasiId)->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanTingkatLayananRequest $request, SimpanTingkatLayanan $aksi): RedirectResponse
    {
        $this->authorize('create', TingkatLayanan::class);
        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Tingkat layanan berhasil dibuat.');
    }

    public function update(SimpanTingkatLayananRequest $request, TingkatLayanan $tingkatLayanan, SimpanTingkatLayanan $aksi): RedirectResponse
    {
        $this->authorize('update', $tingkatLayanan);
        $aksi->jalankan($request->validated(), $tingkatLayanan);

        return back()->with('sukses', 'Tingkat layanan berhasil diperbarui.');
    }

    public function destroy(TingkatLayanan $tingkatLayanan, HapusTingkatLayanan $aksi): RedirectResponse
    {
        $this->authorize('delete', $tingkatLayanan);
        $aksi->jalankan($tingkatLayanan);

        return back()->with('sukses', 'Tingkat layanan berhasil dihapus.');
    }
}
