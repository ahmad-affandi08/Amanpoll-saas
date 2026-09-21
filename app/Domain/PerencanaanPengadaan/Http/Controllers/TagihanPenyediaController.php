<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Controllers;

use App\Domain\PerencanaanPengadaan\Application\Actions\CatatPembayaranPenyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaTagihanPenyedia;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPembayaranPenyediaRequest;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanTagihanPenyediaRequest;
use App\Domain\PerencanaanPengadaan\Http\Resources\TagihanPenyediaResource;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class TagihanPenyediaController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TagihanPenyedia::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in([
                TagihanPenyedia::STATUS_BELUM_DIBAYAR,
                TagihanPenyedia::STATUS_DIBAYAR_SEBAGIAN,
                TagihanPenyedia::STATUS_DIBAYAR,
            ])],
        ]);

        $tagihan = TagihanPenyedia::query()
            ->with(['penyedia', 'pesananPembelian'])
            ->withCount('pembayaran')
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where('NomorTagihan', 'like', "%{$cari}%"))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->latest('DibuatPada')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('TagihanPenyedia/Index', [
            'tagihan' => TagihanPenyediaResource::collection($tagihan),
            'filter' => $filter,
        ]);
    }

    public function store(SimpanTagihanPenyediaRequest $request, PesananPembelian $pesananPembelian, KelolaTagihanPenyedia $aksi): RedirectResponse
    {
        $this->authorize('create', TagihanPenyedia::class);
        $tagihan = $aksi->buat($pesananPembelian, $request->validated());

        return redirect()
            ->route('perencanaanPengadaan.tagihan.show', $tagihan)
            ->with('sukses', 'Tagihan lolos matching PO/penerimaan.');
    }

    public function show(TagihanPenyedia $tagihanPenyedia): Response
    {
        $this->authorize('view', $tagihanPenyedia);
        $tagihanPenyedia->load(['penyedia', 'pesananPembelian', 'pembayaran.dibuatOleh']);

        return Inertia::render('TagihanPenyedia/Show', [
            'tagihan' => new TagihanPenyediaResource($tagihanPenyedia),
        ]);
    }

    public function bayar(SimpanPembayaranPenyediaRequest $request, TagihanPenyedia $tagihanPenyedia, CatatPembayaranPenyedia $aksi): RedirectResponse
    {
        $this->authorize('update', $tagihanPenyedia);
        $aksi->jalankan($tagihanPenyedia, $request->validated(), $request->user('web')->Id);

        return back()->with('sukses', 'Pembayaran dicatat dan sisa tagihan dihitung ulang.');
    }
}
