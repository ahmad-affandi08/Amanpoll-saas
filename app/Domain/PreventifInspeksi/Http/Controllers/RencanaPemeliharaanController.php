<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Controllers;

use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\PreventifInspeksi\Application\Actions\JadwalkanPemeliharaanPreventif;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaRencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanRencanaPemeliharaanAsetRequest;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanRencanaPemeliharaanRequest;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RencanaPemeliharaanController extends Controller
{
    public function __construct(
        private readonly KelolaRencanaPemeliharaan $kelolaRencana,
        private readonly JadwalkanPemeliharaanPreventif $penjadwalPreventif,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RencanaPemeliharaan::class);

        $daftarRencana = RencanaPemeliharaan::query()
            ->with(['templatDaftarPeriksa'])
            ->withCount('aset')
            ->orderBy('Nama')
            ->get();

        $templatList = TemplatDaftarPeriksa::query()
            ->where('Aktif', true)
            ->orderBy('Nama')
            ->get(['Id', 'Nama', 'Kode']);

        return Inertia::render('RencanaPemeliharaan/Index', [
            'rencana' => $daftarRencana,
            'templatDaftarPeriksa' => $templatList,
        ]);
    }

    public function store(SimpanRencanaPemeliharaanRequest $request): RedirectResponse
    {
        $this->authorize('create', RencanaPemeliharaan::class);

        $rencana = $this->kelolaRencana->buat(
            $request->validated(),
            $request->user('web')->Id
        );

        return redirect()->route('preventifInspeksi.rencana-pemeliharaan.show', $rencana->Id)
            ->with('sukses', 'Rencana pemeliharaan preventif berhasil dibuat.');
    }

    public function show(RencanaPemeliharaan $rencanaPemeliharaan): Response
    {
        $this->authorize('view', $rencanaPemeliharaan);

        $rencanaPemeliharaan->load([
            'templatDaftarPeriksa',
            'aset' => fn ($q) => $q->with(['aset.lokasi', 'jadwal' => fn ($j) => $j->latest('TanggalJadwal')->limit(5)]),
        ]);

        $asetTersedia = Aset::query()
            ->where('Status', StatusAset::Aktif->value)
            ->orderBy('Nama')
            ->get(['Id', 'KodeAset', 'Nama', 'LokasiId']);

        $templatList = TemplatDaftarPeriksa::query()
            ->where('Aktif', true)
            ->orderBy('Nama')
            ->get(['Id', 'Nama', 'Kode']);

        return Inertia::render('RencanaPemeliharaan/Show', [
            'rencana' => $rencanaPemeliharaan,
            'asetTersedia' => $asetTersedia,
            'templatDaftarPeriksa' => $templatList,
        ]);
    }

    public function update(
        SimpanRencanaPemeliharaanRequest $request,
        RencanaPemeliharaan $rencanaPemeliharaan
    ): RedirectResponse {
        $this->authorize('update', $rencanaPemeliharaan);

        $this->kelolaRencana->perbarui(
            $rencanaPemeliharaan,
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Rencana pemeliharaan berhasil diperbarui.');
    }

    public function tetapkanAset(
        SimpanRencanaPemeliharaanAsetRequest $request,
        RencanaPemeliharaan $rencanaPemeliharaan
    ): RedirectResponse {
        $this->authorize('update', $rencanaPemeliharaan);

        $this->kelolaRencana->tetapkanAset(
            $rencanaPemeliharaan,
            $request->validated('AsetId'),
            $request->validated('TanggalMulai'),
            $request->validated('TanggalBerikutnya'),
        );

        return back()->with('sukses', 'Aset berhasil didaftarkan ke dalam rencana pemeliharaan.');
    }

    public function lepasAset(
        Request $request,
        RencanaPemeliharaan $rencanaPemeliharaan,
        string $asetId
    ): RedirectResponse {
        $this->authorize('update', $rencanaPemeliharaan);

        $this->kelolaRencana->lepasAset($rencanaPemeliharaan, $asetId);

        return back()->with('sukses', 'Aset berhasil dilepas dari rencana pemeliharaan.');
    }

    public function jalankanScheduler(Request $request): RedirectResponse
    {
        $this->authorize('create', RencanaPemeliharaan::class);

        $hasil = $this->penjadwalPreventif->jalankan(
            organisasiId: $request->user('web')->OrganisasiId,
            penggunaId: $request->user('web')->Id
        );

        return back()->with('sukses', "Penjadwalan selesai. Jadwal dibuat: {$hasil['jadwalDibuat']}, Perintah Kerja dibuat: {$hasil['perintahKerjaDibuat']}, Dilewati: {$hasil['dilewati']}.");
    }
}
