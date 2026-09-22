<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Controllers;

use App\Domain\IntegrasiAudit\Domain\Enums\StatusKotakKeluarPeristiwa;
use App\Domain\IntegrasiAudit\Domain\Enums\StatusPengirimanPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KotakKeluarPeristiwa;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PengirimanPanggilanBalikWeb;
use App\Domain\Kepatuhan\Application\Actions\KelolaIntegrasiEksternal;
use App\Domain\Kepatuhan\Application\Actions\KelolaPemetaanDataEksternal;
use App\Domain\Kepatuhan\Application\Services\LayananSinkronisasiEksternal;
use App\Domain\Kepatuhan\Domain\Enums\ArahSinkronisasiEksternal;
use App\Domain\Kepatuhan\Domain\Enums\StatusIntegrasiEksternal;
use App\Domain\Kepatuhan\Http\Requests\SimpanIntegrasiEksternalRequest;
use App\Domain\Kepatuhan\Http\Requests\SimpanPemetaanDataEksternalRequest;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\PemetaanDataEksternal;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class IntegrasiEksternalController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', IntegrasiEksternal::class);

        return Inertia::render('Integrasi/Index', [
            'integrasi' => IntegrasiEksternal::query()
                ->withCount(['pemetaan', 'sinkronisasi'])
                ->orderBy('Kode')
                ->limit(BatasDaftar::MAKS)
                ->get()
                ->map(fn (IntegrasiEksternal $item): array => $this->ringkas($item))
                ->all(),
            'webhook' => PanggilanBalikWeb::query()
                ->withCount('pengiriman')
                ->orderBy('Nama')
                ->get()
                ->map(fn (PanggilanBalikWeb $item): array => [
                    'Id' => $item->Id,
                    'Nama' => $item->Nama,
                    'Url' => $item->Url,
                    'Peristiwa' => $item->Peristiwa,
                    'Aktif' => $item->Aktif,
                    'JumlahPengiriman' => $item->pengiriman_count,
                ])
                ->all(),
            'antrianPeristiwa' => [
                'menunggu' => KotakKeluarPeristiwa::query()->where('Status', StatusKotakKeluarPeristiwa::Menunggu->value)->count(),
                'gagal' => KotakKeluarPeristiwa::query()->where('Status', StatusKotakKeluarPeristiwa::Gagal->value)->count(),
                'pengirimanGagal' => PengirimanPanggilanBalikWeb::query()
                    ->whereIn('Status', [
                        StatusPengirimanPanggilanBalikWeb::Gagal->value,
                        StatusPengirimanPanggilanBalikWeb::GagalPermanen->value,
                    ])
                    ->count(),
            ],
        ]);
    }

    public function store(SimpanIntegrasiEksternalRequest $request, KelolaIntegrasiEksternal $aksi): RedirectResponse
    {
        $this->authorize('create', IntegrasiEksternal::class);
        $integrasi = $aksi->buat($request->validated());

        return redirect()
            ->route('integrasi.show', $integrasi)
            ->with('sukses', 'Integrasi eksternal dibuat.');
    }

    public function show(IntegrasiEksternal $integrasi): Response
    {
        $this->authorize('view', $integrasi);
        $integrasi->loadCount(['pemetaan', 'sinkronisasi']);

        return Inertia::render('Integrasi/Show', [
            'integrasi' => $this->ringkas($integrasi),
            'pemetaan' => $integrasi->pemetaan()->orderByDesc('DiperbaruiPada')->limit(100)->get()
                ->map(fn (PemetaanDataEksternal $item): array => [
                    'Id' => $item->Id,
                    'JenisEntitas' => $item->JenisEntitas,
                    'EntitasId' => $item->EntitasId,
                    'KodeEksternal' => $item->KodeEksternal,
                    'Konflik' => (bool) ($item->DataTambahan['Konflik'] ?? false),
                    'AlasanKonflik' => $item->DataTambahan['AlasanKonflik'] ?? null,
                    'DiperbaruiPada' => $item->DiperbaruiPada->toIso8601String(),
                ])->all(),
            'sinkronisasi' => $integrasi->sinkronisasi()->orderByDesc('MulaiPada')->limit(20)->get()
                ->map(fn ($item): array => [
                    'Id' => $item->Id,
                    'JenisProses' => $item->JenisProses,
                    'Arah' => $item->Arah,
                    'Status' => $item->Status,
                    'JumlahData' => $item->JumlahData,
                    'JumlahBerhasil' => $item->JumlahBerhasil,
                    'JumlahGagal' => $item->JumlahGagal,
                    'PesanKesalahan' => $item->PesanKesalahan,
                    'MulaiPada' => $item->MulaiPada->toIso8601String(),
                    'SelesaiPada' => $item->SelesaiPada?->toIso8601String(),
                ])->all(),
        ]);
    }

    public function update(SimpanIntegrasiEksternalRequest $request, IntegrasiEksternal $integrasi, KelolaIntegrasiEksternal $aksi): RedirectResponse
    {
        $this->authorize('update', $integrasi);
        $aksi->ubah($integrasi, $request->validated());

        return back()->with('sukses', 'Integrasi eksternal diperbarui.');
    }

    public function ubahStatus(Request $request, IntegrasiEksternal $integrasi, KelolaIntegrasiEksternal $aksi): RedirectResponse
    {
        $this->authorize('update', $integrasi);
        $data = $request->validate(['Status' => ['required', Rule::enum(StatusIntegrasiEksternal::class)]]);
        $aksi->ubahStatus($integrasi, $data['Status']);

        return back()->with('sukses', 'Status integrasi diperbarui.');
    }

    public function destroy(IntegrasiEksternal $integrasi, KelolaIntegrasiEksternal $aksi): RedirectResponse
    {
        $this->authorize('delete', $integrasi);
        $aksi->hapus($integrasi);

        return redirect()->route('integrasi.index')->with('sukses', 'Integrasi eksternal dihapus.');
    }

    public function ujiKoneksi(IntegrasiEksternal $integrasi, LayananSinkronisasiEksternal $layanan): RedirectResponse
    {
        $this->authorize('update', $integrasi);
        $hasil = $layanan->ujiKoneksi($integrasi);

        return back()->with($hasil['berhasil'] ? 'sukses' : 'galat', $hasil['pesan']);
    }

    public function sinkronkan(Request $request, IntegrasiEksternal $integrasi, LayananSinkronisasiEksternal $layanan): RedirectResponse
    {
        $this->authorize('update', $integrasi);
        $data = $request->validate([
            'JenisProses' => ['required', 'string', 'max:80'],
            'Arah' => ['required', Rule::enum(ArahSinkronisasiEksternal::class)],
        ]);

        $layanan->antrikan($integrasi, $data['JenisProses'], $data['Arah']);

        return back()->with('sukses', 'Sinkronisasi dimasukkan ke antrean.');
    }

    public function storePemetaan(SimpanPemetaanDataEksternalRequest $request, IntegrasiEksternal $integrasi, KelolaPemetaanDataEksternal $aksi): RedirectResponse
    {
        $this->authorize('update', $integrasi);
        $pemetaan = $aksi->petakan($integrasi, $request->validated());

        return back()->with(
            $aksi->berkonflik($pemetaan) ? 'galat' : 'sukses',
            $aksi->berkonflik($pemetaan)
                ? 'Pemetaan disimpan tetapi berkonflik dengan pemetaan lain dan perlu diselesaikan.'
                : 'Pemetaan data eksternal disimpan.',
        );
    }

    public function selesaikanKonflik(Request $request, IntegrasiEksternal $integrasi, PemetaanDataEksternal $pemetaanDataEksternal, KelolaPemetaanDataEksternal $aksi): RedirectResponse
    {
        $this->authorize('update', $integrasi);
        $data = $request->validate(['Pertahankan' => ['required', 'boolean']]);
        $aksi->selesaikanKonflik($pemetaanDataEksternal, (bool) $data['Pertahankan']);

        return back()->with('sukses', 'Konflik pemetaan diselesaikan.');
    }

    public function destroyPemetaan(IntegrasiEksternal $integrasi, PemetaanDataEksternal $pemetaanDataEksternal, KelolaPemetaanDataEksternal $aksi): RedirectResponse
    {
        $this->authorize('update', $integrasi);
        $aksi->lepas($pemetaanDataEksternal);

        return back()->with('sukses', 'Pemetaan dilepas.');
    }

    /**
     * Ringkasan integrasi tanpa kredensial; hanya nama kunci konfigurasi yang
     * ditampilkan supaya isinya tidak pernah kembali ke klien.
     *
     * @return array<string, mixed>
     */
    private function ringkas(IntegrasiEksternal $integrasi): array
    {
        return [
            'Id' => $integrasi->Id,
            'Kode' => $integrasi->Kode,
            'Nama' => $integrasi->Nama,
            'Jenis' => $integrasi->Jenis,
            'UrlDasar' => $integrasi->UrlDasar,
            'MetodeAutentikasi' => $integrasi->MetodeAutentikasi,
            'KunciKonfigurasi' => array_keys($integrasi->KonfigurasiTerenkripsi ?? []),
            'Status' => $integrasi->Status,
            'TerakhirSinkronPada' => $integrasi->TerakhirSinkronPada?->toIso8601String(),
            'JumlahPemetaan' => $integrasi->pemetaan_count ?? 0,
            'JumlahSinkronisasi' => $integrasi->sinkronisasi_count ?? 0,
        ];
    }
}
