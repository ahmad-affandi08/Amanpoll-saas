<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kalibrasi\Application\Actions\KelolaPelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Http\Requests\SimpanHasilTitikUkurKalibrasiRequest;
use App\Domain\Kalibrasi\Http\Requests\SimpanPelaksanaanKalibrasiRequest;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PelaksanaanKalibrasiController extends Controller
{
    public function __construct(
        private readonly KelolaPelaksanaanKalibrasi $kelolaPelaksanaan,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PelaksanaanKalibrasi::class);

        $organisasiId = $request->user()->OrganisasiId;

        $daftarPelaksanaan = PelaksanaanKalibrasi::query()
            ->with(['aset', 'jenisKalibrasi', 'penyedia', 'dilaksanakanOleh', 'diverifikasiOleh'])
            ->withCount('hasilTitikUkur')
            ->where('OrganisasiId', $organisasiId)
            ->when($request->filled('hasil'), fn ($q) => $q->where('Hasil', $request->input('hasil')))
            ->when($request->filled('asetId'), fn ($q) => $q->where('AsetId', $request->input('asetId')))
            ->when($request->filled('nomor'), fn ($q) => $q->where('Nomor', 'like', "%{$request->input('nomor')}%"))
            ->latest('TanggalKalibrasi')
            ->get();

        $asetList = Aset::query()
            ->where('OrganisasiId', $organisasiId)
            ->where('Status', Aset::STATUS_AKTIF)
            ->orderBy('Nama')
            ->get(['Id', 'KodeAset', 'Nama']);

        $jenisList = JenisKalibrasi::query()
            ->where('OrganisasiId', $organisasiId)
            ->where('Aktif', true)
            ->orderBy('Nama')
            ->get(['Id', 'Kode', 'Nama']);

        $penyediaList = Penyedia::query()
            ->where('OrganisasiId', $organisasiId)
            ->orderBy('Nama')
            ->get(['Id', 'Kode', 'Nama']);

        $rencanaList = RencanaKalibrasi::query()
            ->with('aset')
            ->where('OrganisasiId', $organisasiId)
            ->where('Aktif', true)
            ->get(['Id', 'AsetId', 'JenisKalibrasiId', 'IntervalHari', 'TanggalBerikutnya']);

        $teknisiList = Pengguna::query()
            ->where('Status', 'Aktif')
            ->orderBy('Nama')
            ->get(['Id', 'Nama']);

        return Inertia::render('Kalibrasi/Pelaksanaan/Index', [
            'pelaksanaanKalibrasi' => $daftarPelaksanaan,
            'aset' => $asetList,
            'jenisKalibrasi' => $jenisList,
            'penyedia' => $penyediaList,
            'rencanaKalibrasi' => $rencanaList,
            'teknisi' => $teknisiList,
            'filter' => [
                'hasil' => $request->input('hasil'),
                'asetId' => $request->input('asetId'),
                'nomor' => $request->input('nomor'),
            ],
        ]);
    }

    public function store(SimpanPelaksanaanKalibrasiRequest $request): RedirectResponse
    {
        $this->authorize('create', PelaksanaanKalibrasi::class);

        $pelaksanaan = $this->kelolaPelaksanaan->jadwalkan(
            $request->validated(),
            $request->user()->Id
        );

        return redirect()
            ->route('kalibrasi.pelaksanaan.show', $pelaksanaan->Id)
            ->with('sukses', 'Pelaksanaan kalibrasi berhasil dijadwalkan.');
    }

    public function show(PelaksanaanKalibrasi $pelaksanaanKalibrasi): Response
    {
        $this->authorize('view', $pelaksanaanKalibrasi);

        $pelaksanaanKalibrasi->load([
            'aset.lokasi',
            'jenisKalibrasi.titikUkur',
            'penyedia',
            'perintahKerja',
            'dilaksanakanOleh',
            'diverifikasiOleh',
            'rencanaKalibrasi',
            'hasilTitikUkur.titikUkurKalibrasi',
        ]);

        $organisasiId = $pelaksanaanKalibrasi->OrganisasiId;

        $teknisiList = Pengguna::query()
            ->where('Status', 'Aktif')
            ->orderBy('Nama')
            ->get(['Id', 'Nama']);

        $penyediaList = Penyedia::query()
            ->where('OrganisasiId', $organisasiId)
            ->get(['Id', 'Kode', 'Nama']);

        return Inertia::render('Kalibrasi/Pelaksanaan/Show', [
            'pelaksanaan' => $pelaksanaanKalibrasi,
            'teknisi' => $teknisiList,
            'penyedia' => $penyediaList,
        ]);
    }

    public function simpanHasilTitikUkur(
        SimpanHasilTitikUkurKalibrasiRequest $request,
        PelaksanaanKalibrasi $pelaksanaanKalibrasi
    ): RedirectResponse {
        $this->authorize('update', $pelaksanaanKalibrasi);

        $this->kelolaPelaksanaan->simpanHasilTitikUkur(
            $pelaksanaanKalibrasi,
            $request->validated()['hasil'],
            $request->user()->Id
        );

        return back()->with('sukses', 'Hasil pengukuran titik ukur berhasil disimpan.');
    }

    public function finalisasi(Request $request, PelaksanaanKalibrasi $pelaksanaanKalibrasi): RedirectResponse
    {
        $this->authorize('update', $pelaksanaanKalibrasi);

        $validated = $request->validate([
            'Hasil' => ['required', 'string', 'in:Lolos,Gagal,LolosDenganCatatan'],
            'NomorSertifikat' => ['required', 'string', 'max:180'],
            'TanggalKalibrasi' => ['required', 'date'],
            'TanggalBerlakuSampai' => ['nullable', 'date'],
            'Laboratorium' => ['nullable', 'string', 'max:200'],
            'Catatan' => ['nullable', 'string'],
            'KondisiLingkungan' => ['nullable', 'array'],
        ]);

        $this->kelolaPelaksanaan->finalisasi(
            $pelaksanaanKalibrasi,
            $validated,
            $request->user()->Id
        );

        return back()->with('sukses', 'Pelaksanaan kalibrasi berhasil diverifikasi dan difinalisasi.');
    }

    public function destroy(PelaksanaanKalibrasi $pelaksanaanKalibrasi, Request $request): RedirectResponse
    {
        $this->authorize('delete', $pelaksanaanKalibrasi);

        $this->kelolaPelaksanaan->hapus(
            $pelaksanaanKalibrasi,
            $request->user()->Id
        );

        return redirect()
            ->route('kalibrasi.pelaksanaan.index')
            ->with('sukses', 'Pelaksanaan kalibrasi berhasil dihapus.');
    }
}
