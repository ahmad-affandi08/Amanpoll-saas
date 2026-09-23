<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Domain\Pemeliharaan\Application\Actions\HapusTingkatLayanan;
use App\Domain\Pemeliharaan\Application\Actions\SimpanTingkatLayanan;
use App\Domain\Pemeliharaan\Http\Requests\SimpanTingkatLayananRequest;
use App\Domain\Pemeliharaan\Http\Resources\TingkatLayananResource;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TingkatLayananController extends Controller
{
    /**
     * Daftar tingkat layanan, dipakai bersama halaman dan ekspornya.
     *
     * @return Builder<TingkatLayanan>
     */
    private function kueriTersaring(): Builder
    {
        return TingkatLayanan::query()
            ->with(['aturan' => fn ($query) => $query->orderBy('MenitPenyelesaian'), 'eskalasi' => fn ($query) => $query->with(['peran', 'pengguna'])->orderBy('Tahap')])
            ->withCount(['aturan', 'eskalasi'])
            ->orderBy('Nama')
            ->orderBy('Id');
    }

    /** Definisi SLA beserta kalender kerjanya, untuk ditinjau di luar aplikasi. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', TingkatLayanan::class);

        return $ekspor->unduh(
            $this->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::atribut('Deskripsi', 'Deskripsi'),
                KolomEkspor::dari('Hari Kerja', fn (TingkatLayanan $sla): string => $this->hariKerja($sla)),
                KolomEkspor::atribut('Jam Kerja Mulai', 'JamKerjaMulai'),
                KolomEkspor::atribut('Jam Kerja Selesai', 'JamKerjaSelesai'),
                KolomEkspor::dari('Memperhitungkan Hari Libur', fn (TingkatLayanan $sla): string => $sla->MemperhitungkanHariLibur ? 'Ya' : 'Tidak'),
                KolomEkspor::atribut('Jumlah Aturan Prioritas', 'aturan_count'),
                KolomEkspor::atribut('Jumlah Tahap Eskalasi', 'eskalasi_count'),
                KolomEkspor::dari('Aktif', fn (TingkatLayanan $sla): string => $sla->Aktif ? 'Ya' : 'Tidak'),
            ],
            'daftar-tingkat-layanan',
            EksporDaftar::formatDari($request),
        );
    }

    /** Nomor hari disebut namanya supaya berkasnya terbaca tanpa kamus di sampingnya. */
    private function hariKerja(TingkatLayanan $tingkatLayanan): string
    {
        $nama = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];
        $hari = $tingkatLayanan->HariKerja;

        if (! is_array($hari)) {
            return '';
        }

        return implode(', ', array_map(static fn (mixed $satu): string => $nama[(int) $satu] ?? '', $hari));
    }

    public function index(): Response
    {
        $this->authorize('viewAny', TingkatLayanan::class);

        return Inertia::render('TingkatLayanan/Index', [
            'wajib' => ['tingkatLayanan' => AturanWajib::untuk(SimpanTingkatLayananRequest::class)],
            'tingkatLayanan' => TingkatLayananResource::collection($this->kueriTersaring()->get()),
            'peran' => Peran::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'pengguna' => Pengguna::query()->where('OrganisasiId', auth('web')->user()->OrganisasiId)->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    public function store(SimpanTingkatLayananRequest $request, SimpanTingkatLayanan $aksi): RedirectResponse
    {
        $this->authorize('create', TingkatLayanan::class);
        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Tingkat layanan berhasil dibuat.');
    }

    public function update(SimpanTingkatLayananRequest $request, TingkatLayanan $tingkatLayanan, SimpanTingkatLayanan $aksi): RedirectResponse
    {
        $this->authorize('update', $tingkatLayanan);
        $aksi->jalankan($request->validated(), $tingkatLayanan);

        return back()->with('sukses', 'Tingkat layanan berhasil diperbarui.');
    }

    public function destroy(TingkatLayanan $tingkatLayanan, HapusTingkatLayanan $aksi): RedirectResponse
    {
        $this->authorize('delete', $tingkatLayanan);
        $aksi->jalankan($tingkatLayanan);

        return back()->with('sukses', 'Tingkat layanan berhasil dihapus.');
    }
}
