<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Controllers;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kalibrasi\Application\Actions\KelolaRencanaKalibrasi;
use App\Domain\Kalibrasi\Application\Services\LayananPeringatanKalibrasi;
use App\Domain\Kalibrasi\Http\Requests\SimpanRencanaKalibrasiRequest;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Application\Services\OpsiUnitPengelola;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RencanaKalibrasiController extends Controller
{
    public function __construct(
        private readonly KelolaRencanaKalibrasi $kelolaRencana,
        private readonly LayananPeringatanKalibrasi $layananPeringatan,
        private readonly KalenderOrganisasi $kalender,
    ) {}

    /**
     * Penyaring daftar rencana kalibrasi, dipakai bersama halaman dan ekspornya.
     *
     * Batas BatasDaftar::MAKS sengaja tidak ikut: halaman memotong diam-diam
     * demi kecepatan, sedangkan ekspor ada justru supaya yang terpotong itu
     * tetap dapat dibaca.
     *
     * @return Builder<RencanaKalibrasi>
     */
    private function kueriTersaring(Request $request): Builder
    {
        return RencanaKalibrasi::query()
            ->with(['aset', 'jenisKalibrasi', 'penyedia', 'unitPengelola:Id,Kode,Nama'])
            ->withCount('pelaksanaanKalibrasi')
            ->when($request->filled('asetId'), fn ($q) => $q->where('AsetId', $request->input('asetId')))
            ->when($request->filled('unitPengelolaId'), fn ($q) => $q->where('UnitPengelolaId', $request->input('unitPengelolaId')))
            ->when($request->filled('jenisKalibrasiId'), fn ($q) => $q->where('JenisKalibrasiId', $request->input('jenisKalibrasiId')))
            ->when($request->has('aktif'), fn ($q) => $q->where('Aktif', $request->boolean('aktif')))
            ->orderBy('TanggalBerikutnya')
            ->orderBy('Id');
    }

    /** Jadwal kalibrasi seperti yang tampil di layar, tanpa pemotongan daftarnya. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', RencanaKalibrasi::class);

        return $ekspor->unduh(
            $this->kueriTersaring($request),
            [
                KolomEkspor::dari('Kode Aset', fn (RencanaKalibrasi $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'aset'), 'KodeAset')),
                KolomEkspor::dari('Aset', fn (RencanaKalibrasi $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'aset'), 'Nama')),
                KolomEkspor::dari('Jenis Kalibrasi', fn (RencanaKalibrasi $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'jenisKalibrasi'), 'Nama')),
                KolomEkspor::dari('Penyedia', fn (RencanaKalibrasi $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'penyedia'), 'Nama')),
                ...(OpsiUnitPengelola::dipakai()
                    ? [KolomEkspor::dari('Unit Pengelola', fn (RencanaKalibrasi $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'unitPengelola'), 'Nama'))]
                    : []),
                KolomEkspor::atribut('Interval (hari)', 'IntervalHari'),
                KolomEkspor::tanggal('Mulai', 'TanggalMulai'),
                KolomEkspor::tanggal('Jatuh Tempo Berikutnya', 'TanggalBerikutnya'),
                KolomEkspor::atribut('Peringatan (hari sebelum)', 'PeringatanHariSebelum'),
                KolomEkspor::dari('Aktif', fn (RencanaKalibrasi $r): string => $r->Aktif ? 'Ya' : 'Tidak'),
                KolomEkspor::atribut('Jumlah Pelaksanaan', 'pelaksanaan_kalibrasi_count'),
            ],
            'jadwal-kalibrasi',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RencanaKalibrasi::class);

        $organisasiId = $request->user('web')->OrganisasiId;
        $hariIni = $this->kalender->hariIni($organisasiId);

        $daftarRencana = RencanaKalibrasi::query()
            ->with(['aset', 'jenisKalibrasi', 'penyedia', 'unitPengelola:Id,Kode,Nama'])
            ->withCount('pelaksanaanKalibrasi')
            ->where('OrganisasiId', $organisasiId)
            ->when($request->filled('asetId'), fn ($q) => $q->where('AsetId', $request->input('asetId')))
            ->when($request->filled('unitPengelolaId'), fn ($q) => $q->where('UnitPengelolaId', $request->input('unitPengelolaId')))
            ->when($request->filled('jenisKalibrasiId'), fn ($q) => $q->where('JenisKalibrasiId', $request->input('jenisKalibrasiId')))
            ->when($request->has('aktif'), fn ($q) => $q->where('Aktif', $request->boolean('aktif')))
            ->orderBy('TanggalBerikutnya')
            ->limit(BatasDaftar::MAKS)
            ->get()
            ->map(function ($rk) use ($hariIni) {
                $tglBerikutnya = Carbon::parse($rk->TanggalBerikutnya);
                $batasPeringatan = (clone $hariIni)->addDays((int) $rk->PeringatanHariSebelum);

                if (! $rk->Aktif) {
                    $status = 'TidakAktif';
                } elseif ($tglBerikutnya->lt($hariIni)) {
                    $status = 'Terlambat';
                } elseif ($tglBerikutnya->lte($batasPeringatan)) {
                    $status = 'SegeraJatuhTempo';
                } else {
                    $status = 'Valid';
                }

                $rk->StatusKalibrasi = $status;
                $rk->SisaHari = (int) $hariIni->diffInDays($tglBerikutnya, false);

                return $rk;
            });

        // Filter status kalibrasi di collection jika dipilih
        if ($request->filled('status')) {
            $statusFilter = $request->input('status');
            $daftarRencana = $daftarRencana->filter(fn ($rk) => $rk->StatusKalibrasi === $statusFilter)->values();
        }

        $asetList = Aset::query()
            ->where('OrganisasiId', $organisasiId)
            ->where('Status', StatusAset::Aktif->value)
            ->orderBy('Nama')
            ->get(['Id', 'KodeAset', 'Nama', 'UnitPengelolaId']);

        $jenisList = JenisKalibrasi::query()
            ->where('OrganisasiId', $organisasiId)
            ->where('Aktif', true)
            ->orderBy('Nama')
            ->get(['Id', 'Kode', 'Nama']);

        $penyediaList = Penyedia::query()
            ->where('OrganisasiId', $organisasiId)
            ->orderBy('Nama')
            ->get(['Id', 'Kode', 'Nama']);

        return Inertia::render('Kalibrasi/Rencana/Index', [
            'wajib' => ['rencana' => AturanWajib::untuk(SimpanRencanaKalibrasiRequest::class)],
            'rencanaKalibrasi' => $daftarRencana,
            'aset' => $asetList,
            'jenisKalibrasi' => $jenisList,
            'penyedia' => $penyediaList,
            'filter' => [
                'asetId' => $request->input('asetId'),
                'jenisKalibrasiId' => $request->input('jenisKalibrasiId'),
                'status' => $request->input('status'),
                'unitPengelolaId' => $request->input('unitPengelolaId'),
            ],
            'unitPengelolaDipakai' => OpsiUnitPengelola::dipakai(),
            'saringanUnitPengelola' => OpsiUnitPengelola::daftar(termasukNonaktif: true),
            'pilihanUnitPengelola' => OpsiUnitPengelola::daftar(),
        ]);
    }

    public function store(SimpanRencanaKalibrasiRequest $request): RedirectResponse
    {
        $this->authorize('create', RencanaKalibrasi::class);

        $this->kelolaRencana->buat(
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Rencana kalibrasi berhasil dibuat.');
    }

    public function show(RencanaKalibrasi $rencanaKalibrasi): Response
    {
        $this->authorize('view', $rencanaKalibrasi);

        $rencanaKalibrasi->load([
            'aset.lokasi',
            'unitPengelola:Id,Kode,Nama',
            'jenisKalibrasi.titikUkur',
            'penyedia',
            'pelaksanaanKalibrasi' => fn ($q) => $q->with(['dilaksanakanOleh', 'diverifikasiOleh'])->latest('TanggalKalibrasi'),
        ]);

        $hariIni = $this->kalender->hariIni($rencanaKalibrasi->OrganisasiId);
        $tglBerikutnya = Carbon::parse($rencanaKalibrasi->TanggalBerikutnya);
        $batasPeringatan = (clone $hariIni)->addDays((int) $rencanaKalibrasi->PeringatanHariSebelum);

        if (! $rencanaKalibrasi->Aktif) {
            $status = 'TidakAktif';
        } elseif ($tglBerikutnya->lt($hariIni)) {
            $status = 'Terlambat';
        } elseif ($tglBerikutnya->lte($batasPeringatan)) {
            $status = 'SegeraJatuhTempo';
        } else {
            $status = 'Valid';
        }

        $rencanaKalibrasi->StatusKalibrasi = $status;
        $rencanaKalibrasi->SisaHari = (int) $hariIni->diffInDays($tglBerikutnya, false);

        $organisasiId = $rencanaKalibrasi->OrganisasiId;

        $jenisList = JenisKalibrasi::query()->where('OrganisasiId', $organisasiId)->where('Aktif', true)->get(['Id', 'Kode', 'Nama']);
        $penyediaList = Penyedia::query()->where('OrganisasiId', $organisasiId)->get(['Id', 'Kode', 'Nama']);

        return Inertia::render('Kalibrasi/Rencana/Show', [
            'rencana' => $rencanaKalibrasi,
            'jenisKalibrasi' => $jenisList,
            'penyedia' => $penyediaList,
        ]);
    }

    public function update(SimpanRencanaKalibrasiRequest $request, RencanaKalibrasi $rencanaKalibrasi): RedirectResponse
    {
        $this->authorize('update', $rencanaKalibrasi);

        $this->kelolaRencana->perbarui(
            $rencanaKalibrasi,
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Rencana kalibrasi berhasil diperbarui.');
    }

    public function destroy(RencanaKalibrasi $rencanaKalibrasi, Request $request): RedirectResponse
    {
        $this->authorize('delete', $rencanaKalibrasi);

        $this->kelolaRencana->hapus(
            $rencanaKalibrasi,
            $request->user('web')->Id
        );

        return redirect()->route('kalibrasi.rencana.index')->with('sukses', 'Rencana kalibrasi berhasil dihapus.');
    }

    public function jalankanPengingat(Request $request): RedirectResponse
    {
        $this->authorize('create', RencanaKalibrasi::class);

        $hasil = $this->layananPeringatan->kirimPeringatan($request->user('web')->OrganisasiId);

        return back()->with('sukses', "Pengingat kalibrasi selesai diperiksa. Segera jatuh tempo: {$hasil['segeraJatuhTempo']}, Terlambat: {$hasil['terlambat']}, Dilewati (sudah dikirim hari ini): {$hasil['dilewati']}.");
    }
}
