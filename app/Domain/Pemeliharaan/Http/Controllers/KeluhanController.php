<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Kolaborasi\Application\Actions\LampirkanBerkas;
use App\Domain\Kolaborasi\Application\Actions\UnggahBerkas;
use App\Domain\Pemeliharaan\Application\Actions\BuatKeluhan;
use App\Domain\Pemeliharaan\Application\Actions\UbahPrioritasKeluhan;
use App\Domain\Pemeliharaan\Application\Actions\UbahStatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Http\Requests\SimpanKeluhanRequest;
use App\Domain\Pemeliharaan\Http\Requests\UbahPrioritasKeluhanRequest;
use App\Domain\Pemeliharaan\Http\Requests\UbahStatusKeluhanRequest;
use App\Domain\Pemeliharaan\Http\Resources\KeluhanResource;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class KeluhanController extends Controller
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Keluhan::class);
        $filter = $request->validate([
            'status' => ['nullable', Rule::enum(StatusKeluhan::class)],
            'prioritas' => ['nullable', Rule::enum(PrioritasKeluhan::class)],
        ]);
        $dapatMengelola = $this->izin->boleh($request->user('web')->Id, 'Keluhan.Kelola');

        $keluhan = $this->kueriTersaring($request, $filter)->paginate(25)->withQueryString();

        return Inertia::render('Keluhan/Index', [
            'wajib' => ['keluhan' => AturanWajib::untuk(SimpanKeluhanRequest::class)],
            'keluhan' => KeluhanResource::collection($keluhan),
            'kategori' => KategoriKeluhan::query()->where('Aktif', true)->orderBy('Nama')->get(['Id', 'Nama', 'PrioritasBawaan', 'AsetWajib']),
            'aset' => Aset::query()->where('Status', StatusAset::Aktif->value)->orderBy('Nama')->get(['Id', 'KodeAset', 'Nama', 'LokasiId']),
            'lokasi' => Lokasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama']),
            'filter' => $filter,
            'dapatMengelola' => $dapatMengelola,
        ]);
    }

    /**
     * Penyaring daftar keluhan, dipakai bersama halaman dan ekspornya.
     *
     * Pembatasan "hanya keluhan sendiri" bagi yang tidak memegang
     * Keluhan.Kelola ikut di sini, bukan hanya di halaman: ekspor yang
     * melewatinya akan menyerahkan seluruh keluhan organisasi kepada pelapor
     * biasa.
     *
     * @param  array<string, mixed>  $filter
     * @return Builder<Keluhan>
     */
    private function kueriTersaring(Request $request, array $filter): Builder
    {
        $dapatMengelola = $this->izin->boleh($request->user('web')->Id, 'Keluhan.Kelola');

        return Keluhan::query()
            ->with(['kategoriKeluhan', 'tingkatLayanan', 'aset', 'lokasi', 'pelapor'])
            ->when(! $dapatMengelola, fn ($query) => $query->where('PelaporId', $request->user('web')->Id))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->when($filter['prioritas'] ?? null, fn ($query, $prioritas) => $query->where('Prioritas', $prioritas))
            ->latest('DilaporkanPada')
            ->orderBy('Id');
    }

    /** Daftar keluhan seperti yang tampil di layar, lengkap dengan penyaringnya. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', Keluhan::class);

        $filter = $request->validate([
            'status' => ['nullable', Rule::enum(StatusKeluhan::class)],
            'prioritas' => ['nullable', Rule::enum(PrioritasKeluhan::class)],
        ]);

        return $ekspor->unduh(
            $this->kueriTersaring($request, $filter),
            [
                KolomEkspor::atribut('Nomor', 'Nomor'),
                KolomEkspor::atribut('Judul', 'Judul'),
                KolomEkspor::dari('Kategori', fn (Keluhan $k): string => BacaRelasi::teks(BacaRelasi::model($k, 'kategoriKeluhan'), 'Nama')),
                KolomEkspor::atribut('Prioritas', 'Prioritas'),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::dari('Aset', fn (Keluhan $k): string => BacaRelasi::teks(BacaRelasi::model($k, 'aset'), 'Nama')),
                KolomEkspor::dari('Kode Aset', fn (Keluhan $k): string => BacaRelasi::teks(BacaRelasi::model($k, 'aset'), 'KodeAset')),
                KolomEkspor::dari('Lokasi', fn (Keluhan $k): string => BacaRelasi::teks(BacaRelasi::model($k, 'lokasi'), 'Nama')),
                KolomEkspor::dari('Pelapor', fn (Keluhan $k): string => BacaRelasi::teks(BacaRelasi::model($k, 'pelapor'), 'Nama')),
                KolomEkspor::tanggal('Dilaporkan', 'DilaporkanPada', 'Y-m-d H:i'),
                KolomEkspor::tanggal('Direspons', 'DiresponsPada', 'Y-m-d H:i'),
                KolomEkspor::tanggal('Diresolusikan', 'DiresolusikanPada', 'Y-m-d H:i'),
                KolomEkspor::tanggal('Batas Respons', 'BatasResponsPada', 'Y-m-d H:i'),
                KolomEkspor::tanggal('Batas Penyelesaian', 'BatasPenyelesaianPada', 'Y-m-d H:i'),
            ],
            'daftar-keluhan',
            EksporDaftar::formatDari($request),
        );
    }

    public function store(
        SimpanKeluhanRequest $request,
        BuatKeluhan $aksi,
        UnggahBerkas $unggahBerkas,
        LampirkanBerkas $lampirkanBerkas,
    ): RedirectResponse {
        $this->authorize('create', Keluhan::class);
        $data = $request->validated();
        $lampiran = $data['Lampiran'] ?? [];
        unset($data['Lampiran']);
        if (! $this->izin->boleh($request->user('web')->Id, 'Keluhan.Kelola')) {
            unset($data['Prioritas']);
        }
        $keluhan = $aksi->jalankan($data, $request->user('web')->Id);
        foreach ($lampiran as $berkasTerunggah) {
            $berkas = $unggahBerkas->jalankan($berkasTerunggah, $request->user('web')->Id);
            $lampirkanBerkas->jalankan('Keluhan', $keluhan->Id, $berkas->Id, 'Bukti', null, $request->user('web')->Id);
        }

        return redirect()->route('pemeliharaan.keluhan.show', $keluhan)->with('sukses', 'Keluhan berhasil dilaporkan.');
    }

    public function show(Keluhan $keluhan): Response
    {
        $this->authorize('view', $keluhan);
        $keluhan->load(['kategoriKeluhan', 'tingkatLayanan', 'aset', 'lokasi', 'pelapor', 'riwayatStatus.diubahOleh']);

        return Inertia::render('Keluhan/Show', [
            'wajib' => ['status' => AturanWajib::untuk(UbahStatusKeluhanRequest::class), 'prioritas' => AturanWajib::untuk(UbahPrioritasKeluhanRequest::class)],
            'keluhan' => new KeluhanResource($keluhan),
            'dapatMengelola' => $this->izin->boleh((string) auth()->id(), 'Keluhan.Kelola'),
            'prioritasAwal' => $this->prioritasAwal($keluhan),
            'transisiDiizinkan' => array_map(fn (StatusKeluhan $status) => $status->value, StatusKeluhan::from($keluhan->Status)->tujuanYangDiizinkan()),
        ]);
    }

    /**
     * Pilihan awal formulir Ubah Prioritas. Selama prioritasnya belum pernah
     * ditetapkan lewat formulir itu, pilihan terisi dari usulan urgensi pelapor
     * (PRD 8.20); sesudahnya yang tampil adalah prioritas yang berlaku.
     * Hanya mengisi formulir: prioritas tetap berubah lewat `ubahPrioritas`.
     */
    private function prioritasAwal(Keluhan $keluhan): string
    {
        if ($keluhan->UsulanUrgensi === null) {
            return $keluhan->Prioritas;
        }

        $sudahDitetapkan = CatatanAudit::query()
            ->where('JenisEntitas', 'Keluhan')
            ->where('EntitasId', $keluhan->Id)
            ->where('Aksi', 'UbahPrioritas')
            ->exists();

        return $sudahDitetapkan ? $keluhan->Prioritas : $keluhan->UsulanUrgensi->prioritas()->value;
    }

    public function ubahStatus(UbahStatusKeluhanRequest $request, Keluhan $keluhan, UbahStatusKeluhan $aksi): RedirectResponse
    {
        $this->authorize('ubahStatus', [$keluhan, $request->validated('Status')]);
        $aksi->jalankan($keluhan, StatusKeluhan::from($request->validated('Status')), $request->validated('Catatan'), $request->integer('Versi'), $request->user('web')->Id);

        return back()->with('sukses', 'Status keluhan berhasil diperbarui.');
    }

    public function ubahPrioritas(UbahPrioritasKeluhanRequest $request, Keluhan $keluhan, UbahPrioritasKeluhan $aksi): RedirectResponse
    {
        $this->authorize('ubahPrioritas', $keluhan);
        $aksi->jalankan($keluhan, PrioritasKeluhan::from($request->validated('Prioritas')), $request->validated('Alasan'), $request->integer('Versi'));

        return back()->with('sukses', 'Prioritas dan deadline SLA berhasil diperbarui.');
    }
}
