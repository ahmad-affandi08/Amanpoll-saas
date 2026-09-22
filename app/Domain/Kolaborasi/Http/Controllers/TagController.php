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

        $tag = Tag::query()->orderBy('Nama')->get();

        if ($request->wantsJson()) {
            return TagResource::collection($tag);
        }

        return Inertia::render('Tag/Index', [
            'tag' => TagResource::collection($tag),
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
