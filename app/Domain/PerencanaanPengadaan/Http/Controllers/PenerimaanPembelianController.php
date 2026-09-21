<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Controllers;

use App\Domain\PerencanaanPengadaan\Application\Actions\CatatPenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPenerimaanPembelianRequest;
use App\Domain\PerencanaanPengadaan\Http\Resources\PenerimaanPembelianResource;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PenerimaanPembelianController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PenerimaanPembelian::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
        ]);

        $penerimaan = PenerimaanPembelian::query()
            ->with(['pesananPembelian.penyedia', 'gudang', 'diterimaOleh'])
            ->withCount('detail')
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where(fn ($sub) => $sub
                ->where('Nomor', 'like', "%{$cari}%")
                ->orWhere('NomorSuratJalan', 'like', "%{$cari}%")))
            ->latest('DibuatPada')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('PenerimaanPembelian/Index', [
            'penerimaan' => PenerimaanPembelianResource::collection($penerimaan),
            'filter' => $filter,
        ]);
    }

    public function store(SimpanPenerimaanPembelianRequest $request, PesananPembelian $pesananPembelian, CatatPenerimaanPembelian $aksi): RedirectResponse
    {
        $this->authorize('create', PenerimaanPembelian::class);
        $aksi->jalankan($pesananPembelian, $request->validated(), $request->user('web')->Id);

        return back()->with('sukses', 'Penerimaan dicatat; stok/aset dan status PO telah disinkronkan.');
    }
}
