<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kalibrasi\Application\Actions\KelolaRencanaKalibrasi;
use App\Domain\Kalibrasi\Application\Services\LayananPeringatanKalibrasi;
use App\Domain\Kalibrasi\Http\Requests\SimpanRencanaKalibrasiRequest;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RencanaKalibrasiController extends Controller
{
    public function __construct(
        private readonly KelolaRencanaKalibrasi $kelolaRencana,
        private readonly LayananPeringatanKalibrasi $layananPeringatan,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RencanaKalibrasi::class);

        $organisasiId = $request->user()->OrganisasiId;
        $hariIni = Carbon::today();

        $daftarRencana = RencanaKalibrasi::query()
            ->with(['aset', 'jenisKalibrasi', 'penyedia'])
            ->withCount('pelaksanaanKalibrasi')
            ->where('OrganisasiId', $organisasiId)
            ->when($request->filled('asetId'), fn ($q) => $q->where('AsetId', $request->input('asetId')))
            ->when($request->filled('jenisKalibrasiId'), fn ($q) => $q->where('JenisKalibrasiId', $request->input('jenisKalibrasiId')))
            ->when($request->has('aktif'), fn ($q) => $q->where('Aktif', $request->boolean('aktif')))
            ->orderBy('TanggalBerikutnya')
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

        return Inertia::render('Kalibrasi/Rencana/Index', [
            'rencanaKalibrasi' => $daftarRencana,
            'aset' => $asetList,
            'jenisKalibrasi' => $jenisList,
            'penyedia' => $penyediaList,
            'filter' => [
                'asetId' => $request->input('asetId'),
                'jenisKalibrasiId' => $request->input('jenisKalibrasiId'),
                'status' => $request->input('status'),
            ],
        ]);
    }

    public function store(SimpanRencanaKalibrasiRequest $request): RedirectResponse
    {
        $this->authorize('create', RencanaKalibrasi::class);

        $this->kelolaRencana->buat(
            $request->validated(),
            $request->user()->Id
        );

        return back()->with('sukses', 'Rencana kalibrasi berhasil dibuat.');
    }

    public function show(RencanaKalibrasi $rencanaKalibrasi): Response
    {
        $this->authorize('view', $rencanaKalibrasi);

        $rencanaKalibrasi->load([
            'aset.lokasi',
            'jenisKalibrasi.titikUkur',
            'penyedia',
            'pelaksanaanKalibrasi' => fn ($q) => $q->with(['dilaksanakanOleh', 'diverifikasiOleh'])->latest('TanggalKalibrasi'),
        ]);

        $hariIni = Carbon::today();
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
            $request->user()->Id
        );

        return back()->with('sukses', 'Rencana kalibrasi berhasil diperbarui.');
    }

    public function destroy(RencanaKalibrasi $rencanaKalibrasi, Request $request): RedirectResponse
    {
        $this->authorize('delete', $rencanaKalibrasi);

        $this->kelolaRencana->hapus(
            $rencanaKalibrasi,
            $request->user()->Id
        );

        return redirect()->route('kalibrasi.rencana.index')->with('sukses', 'Rencana kalibrasi berhasil dihapus.');
    }

    public function jalankanPengingat(Request $request): RedirectResponse
    {
        $this->authorize('create', RencanaKalibrasi::class);

        $hasil = $this->layananPeringatan->kirimPeringatan($request->user()->OrganisasiId);

        return back()->with('sukses', "Pengingat kalibrasi selesai diperiksa. Segera jatuh tempo: {$hasil['segeraJatuhTempo']}, Terlambat: {$hasil['terlambat']}, Dilewati (sudah dikirim hari ini): {$hasil['dilewati']}.");
    }
}
