<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Application\Actions\BuatPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\UbahStatusPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Http\Requests\SimpanPerintahKerjaRequest;
use App\Domain\Pemeliharaan\Http\Requests\UbahStatusPerintahKerjaRequest;
use App\Domain\Pemeliharaan\Http\Resources\PerintahKerjaResource;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KodeKegagalan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PerintahKerjaController extends Controller
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PerintahKerja::class);
        $filter = $request->validate([
            'status' => ['nullable', Rule::enum(StatusPerintahKerja::class)],
            'prioritas' => ['nullable', Rule::enum(PrioritasKeluhan::class)],
        ]);
        $dapatMengelola = $this->izin->boleh($request->user('web')->Id, 'PerintahKerja.Kelola');

        $daftar = PerintahKerja::query()
            ->with(['keluhan', 'lokasi', 'aset', 'penugasan.pengguna'])
            ->withSum('waktuKerja as TotalWaktuKerjaMenit', 'DurasiMenit')
            ->withSum('waktuHenti as TotalDowntimeMenit', 'DurasiMenit')
            ->withSum('biaya as TotalBiaya', 'Jumlah')
            ->when(! $dapatMengelola, fn ($query) => $query->whereHas('penugasan', fn ($penugasan) => $penugasan->where('PenggunaId', $request->user('web')->Id)->whereIn('Status', ['Ditugaskan', 'Diterima'])))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->when($filter['prioritas'] ?? null, fn ($query, $prioritas) => $query->where('Prioritas', $prioritas))
            ->latest('DibuatPada')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('PerintahKerja/Index', [
            'perintahKerja' => PerintahKerjaResource::collection($daftar),
            'keluhan' => Keluhan::query()->whereIn('Status', ['Diterima', 'Diproses'])->latest('DilaporkanPada')->get(['Id', 'Nomor', 'Judul', 'Prioritas', 'LokasiId', 'AsetId']),
            'aset' => Aset::query()->where('Status', StatusAset::Aktif->value)->orderBy('Nama')->get(['Id', 'KodeAset', 'Nama', 'LokasiId']),
            'lokasi' => Lokasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama']),
            'filter' => $filter,
            'dapatMengelola' => $dapatMengelola,
        ]);
    }

    public function store(SimpanPerintahKerjaRequest $request, BuatPerintahKerja $aksi): RedirectResponse
    {
        $this->authorize('create', PerintahKerja::class);
        $perintahKerja = $aksi->jalankan($request->validated(), $request->user('web')->Id);

        return redirect()->route('pemeliharaan.perintah-kerja.show', $perintahKerja)->with('sukses', 'Perintah kerja berhasil dibuat.');
    }

    public function show(Request $request, PerintahKerja $perintahKerja): Response
    {
        $this->authorize('view', $perintahKerja);
        $perintahKerja->load([
            'keluhan', 'lokasi', 'aset', 'penugasan.pengguna', 'riwayatStatus.diubahOleh',
            'waktuKerja.pengguna', 'waktuHenti.aset', 'biaya',
            'analisisKegagalan.kodeMasalah', 'analisisKegagalan.kodePenyebab', 'analisisKegagalan.kodeTindakan',
            'reservasiSukuCadang.sukuCadang', 'reservasiSukuCadang.gudang', 'pemakaianSukuCadang.sukuCadang',
        ])->loadSum('waktuKerja as TotalWaktuKerjaMenit', 'DurasiMenit')
            ->loadSum('waktuHenti as TotalDowntimeMenit', 'DurasiMenit')
            ->loadSum('biaya as TotalBiaya', 'Jumlah');

        $dapatMengelola = $this->izin->boleh($request->user('web')->Id, 'PerintahKerja.Kelola');
        $beban = PenugasanPerintahKerja::query()
            ->whereIn('Status', ['Ditugaskan', 'Diterima'])
            ->selectRaw('PenggunaId, COUNT(*) as jumlah')
            ->groupBy('PenggunaId')
            ->pluck('jumlah', 'PenggunaId');
        $teknisi = Pengguna::query()->where('OrganisasiId', $request->user('web')->OrganisasiId)->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama', 'Jabatan'])
            ->map(fn (Pengguna $pengguna) => [
                'Id' => $pengguna->Id,
                'Nama' => $pengguna->Nama,
                'Jabatan' => $pengguna->Jabatan,
                'BebanAktif' => (int) ($beban[$pengguna->Id] ?? 0),
            ]);
        $stok = StokSukuCadang::query()
            ->with(['sukuCadang', 'gudang'])
            ->whereColumn('JumlahTersedia', '>', 'JumlahDitahan')
            ->limit(BatasDaftar::MAKS)
            ->get()
            ->groupBy(fn (StokSukuCadang $item): string => "{$item->GudangId}:{$item->SukuCadangId}")
            ->map(fn ($baris) => [
                'GudangId' => $baris->first()->GudangId,
                'NamaGudang' => $baris->first()->gudang?->Nama,
                'SukuCadangId' => $baris->first()->SukuCadangId,
                'NamaSukuCadang' => $baris->first()->sukuCadang?->Nama,
                'KodeSukuCadang' => $baris->first()->sukuCadang?->Kode,
                'TersediaBersih' => $baris->sum(fn (StokSukuCadang $item): float => $item->jumlahTersediaBersih()),
            ])->values();

        return Inertia::render('PerintahKerja/Show', [
            'perintahKerja' => new PerintahKerjaResource($perintahKerja),
            'dapatMengelola' => $dapatMengelola,
            'dapatMengoperasikan' => Gate::allows('operate', $perintahKerja),
            'transisiDiizinkan' => collect(StatusPerintahKerja::from($perintahKerja->Status)->tujuanYangDiizinkan())
                ->filter(fn (StatusPerintahKerja $status): bool => Gate::allows('ubahStatus', [$perintahKerja, $status->value]))
                ->map(fn (StatusPerintahKerja $status): string => $status->value)->values(),
            'penugasanSaya' => $perintahKerja->penugasan->firstWhere('PenggunaId', $request->user('web')->Id),
            'teknisi' => $teknisi,
            'stok' => $stok,
            'gudang' => Gudang::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'penyedia' => Penyedia::query()->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama']),
            'kodeKegagalan' => KodeKegagalan::query()->where('Aktif', true)->orderBy('Jenis')->orderBy('Kode')->get(['Id', 'Kode', 'Nama', 'Jenis']),
        ]);
    }

    public function ubahStatus(UbahStatusPerintahKerjaRequest $request, PerintahKerja $perintahKerja, UbahStatusPerintahKerja $aksi): RedirectResponse
    {
        $this->authorize('ubahStatus', [$perintahKerja, $request->validated('Status')]);
        $aksi->jalankan(
            $perintahKerja,
            StatusPerintahKerja::from($request->validated('Status')),
            $request->validated('Catatan'),
            $request->validated('RingkasanPenyelesaian'),
            $request->integer('Versi'),
            $request->user('web')->Id,
        );

        return back()->with('sukses', 'Status perintah kerja berhasil diperbarui.');
    }
}
