<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Controllers;

use App\Domain\PreventifInspeksi\Application\Actions\KelolaButirDaftarPeriksa;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanButirTemplatDaftarPeriksaRequest;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ButirTemplatDaftarPeriksaController extends Controller
{
    public function __construct(
        private readonly KelolaButirDaftarPeriksa $kelolaButir,
    ) {}

    public function store(SimpanButirTemplatDaftarPeriksaRequest $request, TemplatDaftarPeriksa $templatDaftarPeriksa): RedirectResponse
    {
        $this->authorize('update', $templatDaftarPeriksa);

        $this->kelolaButir->simpan(
            $templatDaftarPeriksa,
            $request->validated()
        );

        return back()->with('sukses', 'Butir pertanyaan berhasil ditambahkan.');
    }

    public function update(
        SimpanButirTemplatDaftarPeriksaRequest $request,
        TemplatDaftarPeriksa $templatDaftarPeriksa,
        ButirTemplatDaftarPeriksa $butir
    ): RedirectResponse {
        $this->authorize('update', $templatDaftarPeriksa);

        $this->kelolaButir->simpan(
            $templatDaftarPeriksa,
            $request->validated(),
            $butir->Id
        );

        return back()->with('sukses', 'Butir pertanyaan berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        TemplatDaftarPeriksa $templatDaftarPeriksa,
        ButirTemplatDaftarPeriksa $butir
    ): RedirectResponse {
        $this->authorize('update', $templatDaftarPeriksa);

        $this->kelolaButir->hapus($butir);

        return back()->with('sukses', 'Butir pertanyaan berhasil dihapus.');
    }

    public function urutkan(Request $request, TemplatDaftarPeriksa $templatDaftarPeriksa): RedirectResponse
    {
        $this->authorize('update', $templatDaftarPeriksa);

        $data = $request->validate([
            'urutanIds' => ['required', 'array'],
            'urutanIds.*' => ['required', 'string', 'size:26'],
        ]);

        $this->kelolaButir->urutkanUlang($templatDaftarPeriksa, $data['urutanIds']);

        return back()->with('sukses', 'Urutan pertanyaan berhasil disimpan.');
    }
}
