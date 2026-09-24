<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Core\Izin\LingkupAkses;
use App\Domain\Platform\Application\Actions\BuatPengguna;
use App\Domain\Platform\Application\Actions\UbahPengguna;
use App\Domain\Platform\Application\Actions\UbahStatusPengguna;
use App\Domain\Platform\Application\DTO\PenggunaData;
use App\Domain\Platform\Application\Services\LingkupEfektifPengguna;
use App\Domain\Platform\Http\Requests\SimpanPenggunaRequest;
use App\Domain\Platform\Http\Resources\PenggunaResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PenggunaController extends Controller
{
    /**
     * Penyaring daftar, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<Pengguna>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        return DaftarTersaring::untuk($request, Pengguna::query()->with(['penggunaPeran.peran']))
            ->cari(['Nama', 'Email', 'Jabatan'])
            ->urut(['Nama', 'Jabatan', 'JenisPengguna', 'Status'], bawaan: 'Nama')
            ->faset(['Status', 'JenisPengguna']);
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', Pengguna::class);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::atribut('Email', 'Email'),
                KolomEkspor::atribut('Telepon', 'Telepon'),
                KolomEkspor::atribut('Nomor Pegawai', 'NomorPegawai'),
                KolomEkspor::atribut('Jabatan', 'Jabatan'),
                KolomEkspor::atribut('Jenis Pengguna', 'JenisPengguna'),
                KolomEkspor::dari('Peran', fn (Pengguna $p): string => $p->penggunaPeran
                    ->map(fn ($satu): string => BacaRelasi::teks(BacaRelasi::model($satu, 'peran'), 'Nama'))
                    ->filter()
                    ->implode(', ')),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::tanggal('Terakhir Masuk', 'TerakhirMasukPada', 'Y-m-d H:i'),
            ],
            'daftar-pengguna',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request, LingkupAkses $lingkupAkses): Response
    {
        $this->authorize('viewAny', Pengguna::class);

        $daftar = $this->daftar($request);
        $halaman = $daftar->halaman();
        // Putusan LingkupAkses per baris (PRD 8.21): kolom Lingkup dan peringatan di dialog
        // peran. Dibaca sebelum Resource membungkus isi paginator.
        $lingkupSeluruhOrganisasi = $lingkupAkses->tanpaBatasUntuk(
            $halaman->getCollection()->map(fn (Pengguna $satu): string => (string) $satu->Id),
        );

        return Inertia::render('Pengguna/Index', [
            'pengguna' => PenggunaResource::collection($halaman),
            'filter' => $daftar->filterBerlaku(),
            'lingkupSeluruhOrganisasi' => $lingkupSeluruhOrganisasi,
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
    public function show(Pengguna $pengguna, LingkupEfektifPengguna $lingkupEfektif): Response
    {
        $this->authorize('view', $pengguna);

        $pengguna->load(['penggunaPeran.peran', 'penggunaPeran.unitOrganisasi', 'penggunaPeran.lokasi', 'unitOrganisasi']);

        return Inertia::render('Pengguna/Show', [
            'pengguna' => new PenggunaResource($pengguna),
            'ringkasan' => $this->ringkasanPengguna($pengguna),
            'lingkupEfektif' => $lingkupEfektif->untuk($pengguna),
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
