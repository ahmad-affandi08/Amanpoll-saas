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
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InspeksiController extends Controller
{
    public function __construct(
        private readonly KelolaInspeksi $kelolaInspeksi,
    ) {}

    /**
     * Penyaring daftar inspeksi, dipakai bersama halaman, kartu ringkasan, dan
     * ekspornya.
     *
     * @return Builder<Inspeksi>
     */
    private function kueriTersaring(Request $request): Builder
    {
        $cari = trim((string) $request->input('cari'));

        return Inspeksi::query()
            ->when($request->input('status'), fn ($q, $status) => $q->where('Status', $status))
            ->when($request->input('hasil'), fn ($q, $hasil) => $q->where('Hasil', $hasil))
            ->when($cari !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('Nomor', 'like', "%{$cari}%")
                ->orWhereHas('aset', fn ($aset) => $aset->where('Nama', 'like', "%{$cari}%"))
                ->orWhereHas('templatInspeksi', fn ($templat) => $templat->where('Nama', 'like', "%{$cari}%"))));
    }

    /** Daftar inspeksi berkala beserta temuan dan tindak lanjutnya. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', Inspeksi::class);

        return $ekspor->unduh(
            $this->kueriTersaring($request)
                ->with(['templatInspeksi', 'aset.lokasi', 'perintahKerja', 'dilaksanakanOleh'])
                ->latest('DijadwalkanPada')
                ->orderBy('Id'),
            [
                KolomEkspor::atribut('Nomor', 'Nomor'),
                KolomEkspor::dari('Templat Inspeksi', fn (Inspeksi $i): string => BacaRelasi::teks(BacaRelasi::model($i, 'templatInspeksi'), 'Nama')),
                KolomEkspor::dari('Kode Aset', fn (Inspeksi $i): string => BacaRelasi::teks(BacaRelasi::model($i, 'aset'), 'KodeAset')),
                KolomEkspor::dari('Aset', fn (Inspeksi $i): string => BacaRelasi::teks(BacaRelasi::model($i, 'aset'), 'Nama')),
                KolomEkspor::dari('Lokasi', function (Inspeksi $i): string {
                    $aset = BacaRelasi::model($i, 'aset');

                    return $aset === null ? '' : BacaRelasi::teks(BacaRelasi::model($aset, 'lokasi'), 'Nama');
                }),
                KolomEkspor::tanggal('Dijadwalkan', 'DijadwalkanPada'),
                KolomEkspor::tanggal('Dilaksanakan', 'DilaksanakanPada'),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::atribut('Hasil', 'Hasil'),
                KolomEkspor::atribut('Temuan', 'Temuan'),
                KolomEkspor::atribut('Tindak Lanjut', 'TindakLanjut'),
                KolomEkspor::dari('Perintah Kerja', fn (Inspeksi $i): string => BacaRelasi::teks(BacaRelasi::model($i, 'perintahKerja'), 'Nomor')),
                KolomEkspor::dari('Dilaksanakan Oleh', fn (Inspeksi $i): string => BacaRelasi::teks(BacaRelasi::model($i, 'dilaksanakanOleh'), 'Nama')),
            ],
            'daftar-inspeksi',
            EksporDaftar::formatDari($request),
        );
    }

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
        $tersaring = fn (): Builder => $this->kueriTersaring($request);

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
            'wajib' => ['inspeksi' => AturanWajib::untuk(SimpanInspeksiRequest::class)],
            // Dibungkus agar halaman menerima `meta`; paginator mentah berserialisasi datar dan KontrolPaginasi jatuh.
            'inspeksi' => DaftarTersaring::paginasi($daftarInspeksi, fn (Inspeksi $satu): Inspeksi => $satu),
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
