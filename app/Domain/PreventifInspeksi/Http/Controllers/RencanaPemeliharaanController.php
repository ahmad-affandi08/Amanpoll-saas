<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Controllers;

use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Platform\Application\Services\OpsiUnitPengelola;
use App\Domain\PreventifInspeksi\Application\Actions\JadwalkanPemeliharaanPreventif;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaRencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanRencanaPemeliharaanAsetRequest;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanRencanaPemeliharaanRequest;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RencanaPemeliharaanController extends Controller
{
    public function __construct(
        private readonly KelolaRencanaPemeliharaan $kelolaRencana,
        private readonly JadwalkanPemeliharaanPreventif $penjadwalPreventif,
    ) {}

    /** Jadwal preventif beserta aset yang tercakup di tiap rencana. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', RencanaPemeliharaan::class);

        return $ekspor->unduh(
            RencanaPemeliharaan::query()
                ->with(['templatDaftarPeriksa', 'unitPengelola:Id,Kode,Nama'])
                ->withCount('aset')
                ->orderBy('Nama')
                ->orderBy('Id'),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::atribut('Jenis', 'Jenis'),
                KolomEkspor::atribut('Prioritas', 'Prioritas'),
                KolomEkspor::atribut('Strategi Jadwal', 'StrategiJadwal'),
                KolomEkspor::atribut('Interval', 'IntervalNilai'),
                KolomEkspor::atribut('Satuan Interval', 'IntervalSatuan'),
                KolomEkspor::atribut('Toleransi (hari)', 'ToleransiHari'),
                KolomEkspor::dari('Daftar Periksa', fn (RencanaPemeliharaan $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'templatDaftarPeriksa'), 'Nama')),
                KolomEkspor::atribut('Jumlah Aset', 'aset_count'),
                ...(OpsiUnitPengelola::dipakai()
                    ? [KolomEkspor::dari('Unit Pengelola', fn (RencanaPemeliharaan $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'unitPengelola'), 'Nama'))]
                    : []),
                KolomEkspor::dari('Aktif', fn (RencanaPemeliharaan $r): string => $r->Aktif ? 'Ya' : 'Tidak'),
            ],
            'jadwal-preventif',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RencanaPemeliharaan::class);

        $daftarRencana = RencanaPemeliharaan::query()
            ->with(['templatDaftarPeriksa', 'unitPengelola:Id,Kode,Nama'])
            ->withCount('aset')
            ->orderBy('Nama')
            ->get();

        $templatList = TemplatDaftarPeriksa::query()
            ->where('Aktif', true)
            ->orderBy('Nama')
            ->get(['Id', 'Nama', 'Kode']);

        return Inertia::render('RencanaPemeliharaan/Index', [
            'wajib' => ['rencana' => AturanWajib::untuk(SimpanRencanaPemeliharaanRequest::class)],
            'rencana' => $daftarRencana,
            'templatDaftarPeriksa' => $templatList,
            'unitPengelolaDipakai' => OpsiUnitPengelola::dipakai(),
            'pilihanUnitPengelola' => OpsiUnitPengelola::daftar(),
            'saringanUnitPengelola' => OpsiUnitPengelola::daftar(termasukNonaktif: true),
        ]);
    }

    public function store(SimpanRencanaPemeliharaanRequest $request): RedirectResponse
    {
        $this->authorize('create', RencanaPemeliharaan::class);

        $rencana = $this->kelolaRencana->buat(
            $request->validated(),
            $request->user('web')->Id
        );

        return redirect()->route('preventifInspeksi.rencana-pemeliharaan.show', $rencana->Id)
            ->with('sukses', 'Rencana pemeliharaan preventif berhasil dibuat.');
    }

    public function show(RencanaPemeliharaan $rencanaPemeliharaan): Response
    {
        $this->authorize('view', $rencanaPemeliharaan);

        $rencanaPemeliharaan->load([
            'templatDaftarPeriksa',
            'unitPengelola:Id,Kode,Nama',
            'aset' => fn ($q) => $q->with(['aset.lokasi', 'jadwal' => fn ($j) => $j->latest('TanggalJadwal')->limit(5)]),
        ]);

        $asetTersedia = Aset::query()
            ->where('Status', StatusAset::Aktif->value)
            ->orderBy('Nama')
            ->get(['Id', 'KodeAset', 'Nama', 'LokasiId']);

        $templatList = TemplatDaftarPeriksa::query()
            ->where('Aktif', true)
            ->orderBy('Nama')
            ->get(['Id', 'Nama', 'Kode']);

        return Inertia::render('RencanaPemeliharaan/Show', [
            'wajib' => ['aset' => AturanWajib::untuk(SimpanRencanaPemeliharaanAsetRequest::class), 'rencana' => AturanWajib::untuk(SimpanRencanaPemeliharaanRequest::class)],
            'rencana' => $rencanaPemeliharaan,
            'asetTersedia' => $asetTersedia,
            'templatDaftarPeriksa' => $templatList,
            'unitPengelolaDipakai' => OpsiUnitPengelola::dipakai(),
            'pilihanUnitPengelola' => OpsiUnitPengelola::daftar($rencanaPemeliharaan->UnitPengelolaId),
            'saranUnitPengelolaId' => $this->kelolaRencana->saranUnitPengelola($rencanaPemeliharaan),
        ]);
    }

    public function update(
        SimpanRencanaPemeliharaanRequest $request,
        RencanaPemeliharaan $rencanaPemeliharaan
    ): RedirectResponse {
        $this->authorize('update', $rencanaPemeliharaan);

        $this->kelolaRencana->perbarui(
            $rencanaPemeliharaan,
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Rencana pemeliharaan berhasil diperbarui.');
    }

    public function tetapkanAset(
        SimpanRencanaPemeliharaanAsetRequest $request,
        RencanaPemeliharaan $rencanaPemeliharaan
    ): RedirectResponse {
        $this->authorize('update', $rencanaPemeliharaan);

        $this->kelolaRencana->tetapkanAset(
            $rencanaPemeliharaan,
            $request->validated('AsetId'),
            $request->validated('TanggalMulai'),
            $request->validated('TanggalBerikutnya'),
        );

        return back()->with('sukses', 'Aset berhasil didaftarkan ke dalam rencana pemeliharaan.');
    }

    public function lepasAset(
        Request $request,
        RencanaPemeliharaan $rencanaPemeliharaan,
        string $asetId
    ): RedirectResponse {
        $this->authorize('update', $rencanaPemeliharaan);

        $this->kelolaRencana->lepasAset($rencanaPemeliharaan, $asetId);

        return back()->with('sukses', 'Aset berhasil dilepas dari rencana pemeliharaan.');
    }

    public function jalankanScheduler(Request $request): RedirectResponse
    {
        $this->authorize('create', RencanaPemeliharaan::class);

        $hasil = $this->penjadwalPreventif->jalankan(
            organisasiId: $request->user('web')->OrganisasiId,
            penggunaId: $request->user('web')->Id
        );

        return back()->with('sukses', "Penjadwalan selesai. Jadwal dibuat: {$hasil['jadwalDibuat']}, Perintah Kerja dibuat: {$hasil['perintahKerjaDibuat']}, Dilewati: {$hasil['dilewati']}.");
    }
}
