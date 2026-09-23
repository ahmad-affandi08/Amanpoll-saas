<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\BuatPengguna;
use App\Domain\Platform\Application\Actions\UbahPengguna;
use App\Domain\Platform\Application\Actions\UbahStatusPengguna;
use App\Domain\Platform\Application\DTO\PenggunaData;
use App\Domain\Platform\Http\Requests\SimpanPenggunaRequest;
use App\Domain\Platform\Http\Resources\PenggunaResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PenggunaController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Pengguna::class);

        $daftar = DaftarTersaring::untuk($request, Pengguna::query()->with(['penggunaPeran.peran']))
            ->cari(['Nama', 'Email', 'Jabatan'])
            ->urut(['Nama', 'Jabatan', 'JenisPengguna', 'Status'], bawaan: 'Nama')
            ->faset(['Status', 'JenisPengguna']);

        return Inertia::render('Pengguna/Index', [
            'pengguna' => PenggunaResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
            'peranTersedia' => Peran::query()->orderBy('Nama')->get(['Id', 'Nama']),
            // Dipakai membatasi cakupan penugasan peran; tanpa keduanya peran
            // hanya dapat ditetapkan untuk seluruh organisasi.
            'unitOrganisasi' => UnitOrganisasi::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'lokasi' => Lokasi::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'wajib' => ['pengguna' => AturanWajib::untuk(SimpanPenggunaRequest::class)],
        ]);
    }

    /**
     * Profil pengguna beserta beban kerja dan tanggung jawabnya.
     *
     * Daftar pengguna hanya menjawab "siapa saja dan perannya apa". Sebelum
     * menonaktifkan atau memindahkan seseorang, penyelia perlu tahu apa yang
     * sedang dipegangnya; rinciannya diambil tab lewat RiwayatPenggunaController.
     */
    public function show(Pengguna $pengguna): Response
    {
        $this->authorize('view', $pengguna);

        $pengguna->load(['penggunaPeran.peran', 'penggunaPeran.unitOrganisasi', 'penggunaPeran.lokasi', 'unitOrganisasi']);

        return Inertia::render('Pengguna/Show', [
            'pengguna' => new PenggunaResource($pengguna),
            'ringkasan' => $this->ringkasanPengguna($pengguna),
        ]);
    }

    /**
     * Dihitung di basis data supaya angkanya tidak bergantung pada baris yang
     * kebetulan termuat di salah satu tab.
     *
     * @return array{PenugasanBerjalan: int, TotalMenitKerja: int, AsetDitanggung: int, JumlahPeran: int}
     */
    private function ringkasanPengguna(Pengguna $pengguna): array
    {
        return [
            'PenugasanBerjalan' => $pengguna->penugasanPerintahKerja()->whereNull('SelesaiPada')->count(),
            'TotalMenitKerja' => (int) $pengguna->waktuKerja()->sum('DurasiMenit'),
            'AsetDitanggung' => $pengguna->riwayatPenanggungJawabAset()->whereNull('SelesaiPada')->count(),
            'JumlahPeran' => $pengguna->penggunaPeran()->count(),
        ];
    }

    public function store(SimpanPenggunaRequest $request, BuatPengguna $aksi): RedirectResponse
    {
        $this->authorize('create', Pengguna::class);

        $aksi->jalankan(PenggunaData::dariArray($request->validated()));

        return back()->with('sukses', 'Pengguna berhasil dibuat.');
    }

    public function update(SimpanPenggunaRequest $request, Pengguna $pengguna, UbahPengguna $aksi): RedirectResponse
    {
        $this->authorize('update', $pengguna);

        $aksi->jalankan($pengguna, PenggunaData::dariArray($request->validated()));

        return back()->with('sukses', 'Pengguna berhasil diperbarui.');
    }

    public function ubahStatus(Request $request, Pengguna $pengguna, UbahStatusPengguna $aksi): RedirectResponse
    {
        $this->authorize('ubahStatus', $pengguna);

        $data = $request->validate([
            'Status' => ['required', Rule::in(['Aktif', 'Nonaktif'])],
        ]);

        $aksi->jalankan($request->user('web'), $pengguna, $data['Status']);

        return back()->with('sukses', 'Status pengguna berhasil diperbarui.');
    }
}
