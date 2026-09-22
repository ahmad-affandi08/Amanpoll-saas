<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Controllers;

use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaInspeksi;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanInspeksiRequest;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\Inspeksi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatInspeksi;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InspeksiController extends Controller
{
    public function __construct(
        private readonly KelolaInspeksi $kelolaInspeksi,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Inspeksi::class);

        $cari = trim((string) $request->input('cari'));

        /*
         * Penyaring dipakai dua kali: sekali untuk halaman yang tampil, sekali
         * untuk kartu ringkasannya. Menghitung kartu dari baris yang tampil akan
         * membuatnya berubah-ubah mengikuti halaman, dan angka yang hanya benar
         * di halaman pertama lebih buruk daripada tidak ada angka sama sekali.
         */
        $tersaring = fn () => Inspeksi::query()
            ->when($request->input('status'), fn ($q, $status) => $q->where('Status', $status))
            ->when($request->input('hasil'), fn ($q, $hasil) => $q->where('Hasil', $hasil))
            ->when($cari !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('Nomor', 'like', "%{$cari}%")
                ->orWhereHas('aset', fn ($aset) => $aset->where('Nama', 'like', "%{$cari}%"))
                ->orWhereHas('templatInspeksi', fn ($templat) => $templat->where('Nama', 'like', "%{$cari}%"))));

        $daftarInspeksi = $tersaring()
            ->with(['templatInspeksi', 'aset.lokasi', 'perintahKerja', 'dilaksanakanOleh'])
            ->latest('DijadwalkanPada')
            ->paginate(25)
            ->withQueryString();

        $ringkasan = $tersaring()
            ->selectRaw('Hasil, count(*) as jumlah')
            ->groupBy('Hasil')
            ->pluck('jumlah', 'Hasil');

        $templatList = TemplatInspeksi::query()->where('Aktif', true)->orderBy('Nama')->get(['Id', 'Nama', 'Kode']);
        $asetList = Aset::query()->where('Status', StatusAset::Aktif->value)->orderBy('Nama')->get(['Id', 'KodeAset', 'Nama', 'LokasiId']);
        $inspektorList = Pengguna::query()->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama']);

        return Inertia::render('Inspeksi/Index', [
            'inspeksi' => $daftarInspeksi,
            'templatInspeksi' => $templatList,
            'aset' => $asetList,
            'inspektor' => $inspektorList,
            'ringkasan' => [
                'Lolos' => (int) ($ringkasan['Lolos'] ?? 0),
                'PerluPerhatian' => (int) ($ringkasan['PerluPerhatian'] ?? 0),
                'Gagal' => (int) ($ringkasan['Gagal'] ?? 0),
            ],
            'filter' => [
                'status' => $request->input('status'),
                'hasil' => $request->input('hasil'),
                'cari' => $cari === '' ? null : $cari,
            ],
        ]);
    }

    public function store(SimpanInspeksiRequest $request): RedirectResponse
    {
        $this->authorize('create', Inspeksi::class);

        $inspeksi = $this->kelolaInspeksi->jadwalkan(
            $request->validated(),
            $request->user('web')->Id
        );

        return redirect()->route('preventifInspeksi.inspeksi.show', $inspeksi->Id)
            ->with('sukses', "Inspeksi {$inspeksi->Nomor} berhasil dijadwalkan.");
    }

    public function show(Inspeksi $inspeksi): Response
    {
        $this->authorize('view', $inspeksi);

        $inspeksi->load([
            'templatInspeksi',
            'aset.lokasi',
            'pelaksanaanDaftarPeriksa.templatDaftarPeriksa.butir' => fn ($q) => $q->orderBy('Urutan'),
            'pelaksanaanDaftarPeriksa.jawaban',
            'perintahKerja',
            'dilaksanakanOleh',
        ]);

        return Inertia::render('Inspeksi/Show', [
            'inspeksi' => $inspeksi,
        ]);
    }

    public function laksanakan(Request $request, Inspeksi $inspeksi): RedirectResponse
    {
        $this->authorize('update', $inspeksi);

        $data = $request->validate([
            'Hasil' => ['required', 'string', 'in:Lolos,PerluPerhatian,Gagal'],
            'Temuan' => ['nullable', 'string'],
            'TindakLanjut' => ['nullable', 'string'],
            'DilaksanakanPada' => ['nullable', 'date'],
        ]);

        $this->kelolaInspeksi->laksanakan(
            $inspeksi,
            $data,
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Hasil inspeksi berhasil dicatat.');
    }

    public function buatPerintahKerja(Request $request, Inspeksi $inspeksi): RedirectResponse
    {
        $this->authorize('update', $inspeksi);

        $data = $request->validate([
            'Judul' => ['nullable', 'string', 'max:200'],
            'Deskripsi' => ['nullable', 'string'],
            'Prioritas' => ['nullable', 'string', 'max:30'],
        ]);

        $perintahKerja = $this->kelolaInspeksi->buatPerintahKerjaKorektif(
            $inspeksi,
            $data,
            $request->user('web')->Id
        );

        return back()->with('sukses', "Perintah kerja korektif {$perintahKerja->Nomor} berhasil dibuat dari temuan inspeksi.");
    }
}
