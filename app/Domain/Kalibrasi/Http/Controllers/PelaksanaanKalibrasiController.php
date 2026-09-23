<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Controllers;

use App\Domain\Aset\Domain\Enums\StatusAset;
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
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PelaksanaanKalibrasiController extends Controller
{
    public function __construct(
        private readonly KelolaPelaksanaanKalibrasi $kelolaPelaksanaan,
    ) {}

    /**
     * Penyaring daftar pelaksanaan kalibrasi, dipakai bersama halaman dan
     * ekspornya; batas tampilan tidak ikut ke ekspor.
     *
     * @return Builder<PelaksanaanKalibrasi>
     */
    private function kueriTersaring(Request $request): Builder
    {
        return PelaksanaanKalibrasi::query()
            ->with(['aset', 'jenisKalibrasi', 'penyedia', 'dilaksanakanOleh', 'diverifikasiOleh'])
            ->withCount('hasilTitikUkur')
            ->when($request->filled('hasil'), fn ($q) => $q->where('Hasil', $request->input('hasil')))
            ->when($request->filled('asetId'), fn ($q) => $q->where('AsetId', $request->input('asetId')))
            ->when($request->filled('nomor'), fn ($q) => $q->where('Nomor', 'like', "%{$request->input('nomor')}%"))
            ->latest('TanggalKalibrasi')
            ->orderBy('Id');
    }

    /** Riwayat kalibrasi beserta nomor sertifikatnya, untuk berkas akreditasi. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', PelaksanaanKalibrasi::class);

        return $ekspor->unduh(
            $this->kueriTersaring($request),
            [
                KolomEkspor::atribut('Nomor', 'Nomor'),
                KolomEkspor::dari('Kode Aset', fn (PelaksanaanKalibrasi $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'aset'), 'KodeAset')),
                KolomEkspor::dari('Aset', fn (PelaksanaanKalibrasi $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'aset'), 'Nama')),
                KolomEkspor::dari('Jenis Kalibrasi', fn (PelaksanaanKalibrasi $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'jenisKalibrasi'), 'Nama')),
                KolomEkspor::tanggal('Tanggal Kalibrasi', 'TanggalKalibrasi'),
                KolomEkspor::tanggal('Berlaku Sampai', 'TanggalBerlakuSampai'),
                KolomEkspor::atribut('Hasil', 'Hasil'),
                KolomEkspor::atribut('Nomor Sertifikat', 'NomorSertifikat'),
                KolomEkspor::atribut('Laboratorium', 'Laboratorium'),
                KolomEkspor::dari('Penyedia', fn (PelaksanaanKalibrasi $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'penyedia'), 'Nama')),
                KolomEkspor::dari('Dilaksanakan Oleh', fn (PelaksanaanKalibrasi $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'dilaksanakanOleh'), 'Nama')),
                KolomEkspor::dari('Diverifikasi Oleh', fn (PelaksanaanKalibrasi $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'diverifikasiOleh'), 'Nama')),
                KolomEkspor::atribut('Jumlah Titik Ukur', 'hasil_titik_ukur_count'),
            ],
            'riwayat-kalibrasi',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PelaksanaanKalibrasi::class);

        $organisasiId = $request->user('web')->OrganisasiId;

        $daftarPelaksanaan = PelaksanaanKalibrasi::query()
            ->with(['aset', 'jenisKalibrasi', 'penyedia', 'dilaksanakanOleh', 'diverifikasiOleh'])
            ->withCount('hasilTitikUkur')
            ->where('OrganisasiId', $organisasiId)
            ->when($request->filled('hasil'), fn ($q) => $q->where('Hasil', $request->input('hasil')))
            ->when($request->filled('asetId'), fn ($q) => $q->where('AsetId', $request->input('asetId')))
            ->when($request->filled('nomor'), fn ($q) => $q->where('Nomor', 'like', "%{$request->input('nomor')}%"))
            ->latest('TanggalKalibrasi')
            ->limit(BatasDaftar::MAKS)
            ->get();

        $asetList = Aset::query()
            ->where('OrganisasiId', $organisasiId)
            ->where('Status', StatusAset::Aktif->value)
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
            'wajib' => ['pelaksanaan' => AturanWajib::untuk(SimpanPelaksanaanKalibrasiRequest::class)],
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
            $request->user('web')->Id
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
            'wajib' => ['pelaksanaan' => AturanWajib::untuk(SimpanPelaksanaanKalibrasiRequest::class), 'hasil' => AturanWajib::untuk(SimpanHasilTitikUkurKalibrasiRequest::class)],
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
            $request->user('web')->Id
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
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Pelaksanaan kalibrasi berhasil diverifikasi dan difinalisasi.');
    }

    public function destroy(PelaksanaanKalibrasi $pelaksanaanKalibrasi, Request $request): RedirectResponse
    {
        $this->authorize('delete', $pelaksanaanKalibrasi);

        $this->kelolaPelaksanaan->hapus(
            $pelaksanaanKalibrasi,
            $request->user('web')->Id
        );

        return redirect()
            ->route('kalibrasi.pelaksanaan.index')
            ->with('sukses', 'Pelaksanaan kalibrasi berhasil dihapus.');
    }
}
