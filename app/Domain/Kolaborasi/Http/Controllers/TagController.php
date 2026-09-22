<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Controllers;

use App\Domain\Kolaborasi\Application\Actions\BuatTag;
use App\Domain\Kolaborasi\Application\Actions\HapusTag;
use App\Domain\Kolaborasi\Application\Actions\UbahTag;
use App\Domain\Kolaborasi\Http\Requests\SimpanTagRequest;
use App\Domain\Kolaborasi\Http\Resources\TagResource;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Tag;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

final class TagController extends Controller
{
    /** Dipakai baik untuk halaman admin Tag maupun sebagai daftar sumber pemilihan tag (mis. */
    public function index(Request $request): Response|AnonymousResourceCollection
    {
        $this->authorize('viewAny', Tag::class);

        // Pemilih tag di layar lain menghabiskan seluruh daftar sekaligus, jadi cabang JSON tidak dipaginasi.
        if ($request->wantsJson()) {
            return TagResource::collection(Tag::query()->orderBy('Nama')->get());
        }

        $daftar = DaftarTersaring::untuk($request, Tag::query())
            ->cari(['Nama'])
            ->urut(['Nama'], bawaan: 'Nama');

        return Inertia::render('Tag/Index', [
            'tag' => TagResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
        ]);
    }

    public function store(SimpanTagRequest $request, BuatTag $aksi): RedirectResponse
    {
        $this->authorize('create', Tag::class);

        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Tag berhasil dibuat.');
    }

    public function update(SimpanTagRequest $request, Tag $tag, UbahTag $aksi): RedirectResponse
    {
        $this->authorize('update', $tag);

        $aksi->jalankan($tag, $request->validated());

        return back()->with('sukses', 'Tag berhasil diperbarui.');
    }

    public function destroy(Tag $tag, HapusTag $aksi): RedirectResponse
    {
        $this->authorize('delete', $tag);

        $aksi->jalankan($tag);

        return back()->with('sukses', 'Tag berhasil dihapus.');
    }
}
