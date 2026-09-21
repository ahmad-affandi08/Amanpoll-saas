<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Controllers;

use App\Domain\PreventifInspeksi\Application\Actions\KelolaPelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanJawabanDaftarPeriksaRequest;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanPelaksanaanDaftarPeriksaRequest;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PelaksanaanDaftarPeriksaController extends Controller
{
    public function __construct(
        private readonly KelolaPelaksanaanDaftarPeriksa $kelolaPelaksanaan,
    ) {}

    public function store(SimpanPelaksanaanDaftarPeriksaRequest $request): RedirectResponse
    {
        $this->authorize('create', PelaksanaanDaftarPeriksa::class);

        $pelaksanaan = $this->kelolaPelaksanaan->mulai(
            $request->validated(),
            $request->user('web')->Id
        );

        return redirect()->route('preventifInspeksi.pelaksanaan-daftar-periksa.show', $pelaksanaan->Id)
            ->with('sukses', 'Pelaksanaan daftar periksa dimulai.');
    }

    public function show(PelaksanaanDaftarPeriksa $pelaksanaanDaftarPeriksa): Response
    {
        $this->authorize('view', $pelaksanaanDaftarPeriksa);

        $pelaksanaanDaftarPeriksa->load([
            'templatDaftarPeriksa.butir' => fn ($q) => $q->orderBy('Urutan'),
            'jawaban',
            'aset',
            'perintahKerja',
            'dilaksanakanOleh',
        ]);

        return Inertia::render('DaftarPeriksa/Pelaksanaan/Show', [
            'pelaksanaan' => $pelaksanaanDaftarPeriksa,
        ]);
    }

    public function simpanJawaban(
        SimpanJawabanDaftarPeriksaRequest $request,
        PelaksanaanDaftarPeriksa $pelaksanaanDaftarPeriksa
    ): RedirectResponse {
        $this->authorize('update', $pelaksanaanDaftarPeriksa);

        $this->kelolaPelaksanaan->simpanJawaban(
            $pelaksanaanDaftarPeriksa,
            $request->validated('jawaban'),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Jawaban berhasil disimpan.');
    }

    public function finalisasi(
        Request $request,
        PelaksanaanDaftarPeriksa $pelaksanaanDaftarPeriksa
    ): RedirectResponse {
        $this->authorize('update', $pelaksanaanDaftarPeriksa);

        $data = $request->validate([
            'catatan' => ['nullable', 'string'],
        ]);

        $pelaksanaan = $this->kelolaPelaksanaan->finalisasi(
            $pelaksanaanDaftarPeriksa,
            $data['catatan'] ?? null,
            $request->user('web')->Id
        );

        return back()->with('sukses', "Daftar periksa berhasil diselesaikan dengan skor {$pelaksanaan->Skor}%.");
    }
}
