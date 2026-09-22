<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Http\Controllers;

use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kontrak\Application\Actions\KelolaCakupanAsetKontrak;
use App\Domain\Kontrak\Application\Actions\KelolaKontrak;
use App\Domain\Kontrak\Application\Actions\KelolaLayananKontrak;
use App\Domain\Kontrak\Application\Services\LayananPeringatanKontrak;
use App\Domain\Kontrak\Domain\Enums\StatusKontrak;
use App\Domain\Kontrak\Http\Requests\BatalkanKontrakRequest;
use App\Domain\Kontrak\Http\Requests\CatatPemakaianLayananRequest;
use App\Domain\Kontrak\Http\Requests\SimpanKontrakAsetRequest;
use App\Domain\Kontrak\Http\Requests\SimpanKontrakRequest;
use App\Domain\Kontrak\Http\Requests\SimpanLayananKontrakRequest;
use App\Domain\Kontrak\Http\Resources\KontrakResource;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\KontrakAset;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\LayananKontrak;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Penyedia\Domain\Enums\StatusPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class KontrakController extends Controller
{
    public function index(Request $request, LayananPeringatanKontrak $peringatan): Response
    {
        $this->authorize('viewAny', Kontrak::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::enum(StatusKontrak::class)],
            'penyedia' => ['nullable', 'string'],
        ]);

        $kontrak = Kontrak::query()
            ->with(['penyedia', 'tingkatLayanan'])
            ->withCount(['kontrakAset', 'layanan'])
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where(fn ($sub) => $sub
                ->where('Nomor', 'like', "%{$cari}%")
                ->orWhere('Nama', 'like', "%{$cari}%")))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->when($filter['penyedia'] ?? null, fn ($query, $penyedia) => $query->where('PenyediaId', $penyedia))
            ->orderBy('BerakhirPada')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Kontrak/Index', [
            'wajib' => ['kontrak' => AturanWajib::untuk(SimpanKontrakRequest::class)],
            'kontrak' => KontrakResource::collection($kontrak),
            'penyedia' => Penyedia::query()->where('Status', StatusPenyedia::Aktif->value)->orderBy('Nama')->get(['Id', 'Kode', 'Nama']),
            'tingkatLayanan' => TingkatLayanan::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'ringkasan' => $peringatan->ringkasan($request->user('web')->OrganisasiId),
            'filter' => $filter,
        ]);
    }

    public function store(SimpanKontrakRequest $request, KelolaKontrak $aksi): RedirectResponse
    {
        $this->authorize('create', Kontrak::class);
        $kontrak = $aksi->buat($request->validated());

        return redirect()
            ->route('kontrak.show', $kontrak)
            ->with('sukses', 'Kontrak dibuat.');
    }

    public function show(Kontrak $kontrak): Response
    {
        $this->authorize('view', $kontrak);
        $kontrak->load(['penyedia', 'tingkatLayanan', 'kontrakAset.aset', 'layanan']);

        return Inertia::render('Kontrak/Show', [
            'wajib' => ['batalkan' => AturanWajib::untuk(BatalkanKontrakRequest::class), 'aset' => AturanWajib::untuk(SimpanKontrakAsetRequest::class), 'layanan' => AturanWajib::untuk(SimpanLayananKontrakRequest::class), 'pemakaian' => AturanWajib::untuk(CatatPemakaianLayananRequest::class)],
            'kontrak' => new KontrakResource($kontrak),
            'aset' => Aset::query()->where('Status', StatusAset::Aktif->value)->orderBy('Nama')->get(['Id', 'KodeAset', 'Nama']),
        ]);
    }

    public function update(SimpanKontrakRequest $request, Kontrak $kontrak, KelolaKontrak $aksi): RedirectResponse
    {
        $this->authorize('update', $kontrak);
        $aksi->ubah($kontrak, $request->validated());

        return back()->with('sukses', 'Kontrak diperbarui.');
    }

    public function destroy(Kontrak $kontrak, KelolaKontrak $aksi): RedirectResponse
    {
        $this->authorize('delete', $kontrak);
        $aksi->hapus($kontrak);

        return redirect()->route('kontrak.index')->with('sukses', 'Kontrak dihapus.');
    }

    public function batalkan(BatalkanKontrakRequest $request, Kontrak $kontrak, KelolaKontrak $aksi): RedirectResponse
    {
        $this->authorize('update', $kontrak);
        $aksi->batalkan($kontrak, $request->validated()['Alasan']);

        return back()->with('sukses', 'Kontrak dibatalkan.');
    }

    public function storeAset(SimpanKontrakAsetRequest $request, Kontrak $kontrak, KelolaCakupanAsetKontrak $aksi): RedirectResponse
    {
        $this->authorize('update', $kontrak);
        $aksi->lampirkan($kontrak, $request->validated());

        return back()->with('sukses', 'Aset ditambahkan ke cakupan kontrak.');
    }

    public function destroyAset(Kontrak $kontrak, KontrakAset $kontrakAset, KelolaCakupanAsetKontrak $aksi): RedirectResponse
    {
        $this->authorize('update', $kontrak);
        $aksi->lepaskan($kontrak, $kontrakAset);

        return back()->with('sukses', 'Aset dilepas dari cakupan kontrak.');
    }

    public function storeLayanan(SimpanLayananKontrakRequest $request, Kontrak $kontrak, KelolaLayananKontrak $aksi): RedirectResponse
    {
        $this->authorize('update', $kontrak);
        $aksi->tambah($kontrak, $request->validated());

        return back()->with('sukses', 'Layanan kontrak ditambahkan.');
    }

    public function destroyLayanan(Kontrak $kontrak, LayananKontrak $layananKontrak, KelolaLayananKontrak $aksi): RedirectResponse
    {
        $this->authorize('update', $kontrak);
        $aksi->hapus($kontrak, $layananKontrak);

        return back()->with('sukses', 'Layanan kontrak dihapus.');
    }

    public function catatPemakaian(CatatPemakaianLayananRequest $request, Kontrak $kontrak, LayananKontrak $layananKontrak, KelolaLayananKontrak $aksi): RedirectResponse
    {
        $this->authorize('update', $kontrak);
        abort_unless($layananKontrak->KontrakId === $kontrak->Id, 404);
        $aksi->catatPemakaian($layananKontrak, (string) $request->validated()['Jumlah']);

        return back()->with('sukses', 'Pemakaian layanan dicatat.');
    }
}
