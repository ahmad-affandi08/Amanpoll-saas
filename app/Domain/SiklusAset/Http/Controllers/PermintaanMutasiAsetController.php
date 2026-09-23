<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Controllers;

use App\Domain\Aset\Http\Resources\AsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Platform\Http\Resources\LokasiResource;
use App\Domain\Platform\Http\Resources\UnitOrganisasiResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Domain\SiklusAset\Application\Actions\BatalkanPermintaanMutasiAset;
use App\Domain\SiklusAset\Application\Actions\BuatPermintaanMutasiAset;
use App\Domain\SiklusAset\Application\Actions\EksekusiMutasiAset;
use App\Domain\SiklusAset\Application\Actions\HapusDetailMutasiAset;
use App\Domain\SiklusAset\Application\Actions\PindaiPengambilanAset;
use App\Domain\SiklusAset\Application\Actions\PutuskanDetailMutasiAset;
use App\Domain\SiklusAset\Application\Actions\SubmitPermintaanMutasiAset;
use App\Domain\SiklusAset\Application\Actions\TambahDetailMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\JenisPermintaanMutasiAset;
use App\Domain\SiklusAset\Http\Requests\PindaiPengambilanAsetRequest;
use App\Domain\SiklusAset\Http\Requests\PutuskanDetailMutasiAsetRequest;
use App\Domain\SiklusAset\Http\Requests\SimpanDetailMutasiAsetRequest;
use App\Domain\SiklusAset\Http\Requests\SimpanPermintaanMutasiAsetRequest;
use App\Domain\SiklusAset\Http\Resources\PermintaanMutasiAsetResource;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PermintaanMutasiAsetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PermintaanMutasiAset::class);

        $filter = $request->validate([
            'status' => ['nullable', 'string'],
        ]);

        $permintaan = PermintaanMutasiAset::query()
            ->with(['unitAsal', 'unitTujuan', 'lokasiAsal', 'lokasiTujuan', 'dimintaOleh'])
            ->when($filter['status'] ?? null, fn ($q, $v) => $q->where('Status', $v))
            ->latest('DimintaPada')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('MutasiAset/Index', [
            'wajib' => ['mutasi' => AturanWajib::untuk(SimpanPermintaanMutasiAsetRequest::class)],
            'permintaan' => PermintaanMutasiAsetResource::collection($permintaan),
            'filter' => $filter,
            'lokasi' => LokasiResource::collection(Lokasi::query()->orderBy('Nama')->limit(BatasDaftar::MAKS)->get()),
            'unitOrganisasi' => UnitOrganisasiResource::collection(UnitOrganisasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get()),
            'daftarJenis' => $this->daftarJenis(),
        ]);
    }

    /**
     * Pilihan jenis mutasi diambil dari enumnya supaya jenis baru tidak perlu
     * ditambahkan lagi di daftar terpisah pada frontend.
     *
     * @return list<array{nilai: string, label: string}>
     */
    private function daftarJenis(): array
    {
        $pilihan = [];

        foreach (JenisPermintaanMutasiAset::cases() as $satu) {
            $pilihan[] = ['nilai' => $satu->value, 'label' => $satu->label()];
        }

        return $pilihan;
    }

    public function show(PermintaanMutasiAset $permintaanMutasiAset): Response
    {
        $this->authorize('view', $permintaanMutasiAset);

        $permintaanMutasiAset->load([
            'unitAsal', 'unitTujuan', 'lokasiAsal', 'lokasiTujuan', 'dimintaOleh',
            'detailMutasiAset.aset', 'detailMutasiAset.diputuskanOleh', 'detailMutasiAset.dipindaiOleh',
        ]);

        return Inertia::render('MutasiAset/Show', [
            'wajib' => [
                'detail' => AturanWajib::untuk(SimpanDetailMutasiAsetRequest::class),
                'keputusan' => AturanWajib::untuk(PutuskanDetailMutasiAsetRequest::class),
                'pindai' => AturanWajib::untuk(PindaiPengambilanAsetRequest::class),
            ],
            'permintaan' => new PermintaanMutasiAsetResource($permintaanMutasiAset),
            'aset' => AsetResource::collection(Aset::query()->orderBy('Nama')->limit(BatasDaftar::MAKS)->get()),
            'unitOrganisasi' => UnitOrganisasiResource::collection(UnitOrganisasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get()),
            'lokasi' => LokasiResource::collection(Lokasi::query()->orderBy('Nama')->get()),
            'daftarJenis' => $this->daftarJenis(),
        ]);
    }

    public function store(SimpanPermintaanMutasiAsetRequest $request, BuatPermintaanMutasiAset $aksi): RedirectResponse
    {
        $this->authorize('create', PermintaanMutasiAset::class);

        $permintaan = $aksi->jalankan($request->validated(), $request->user('web')->Id);

        return redirect("/mutasi-aset/{$permintaan->Id}")->with('sukses', 'Draft permintaan mutasi berhasil dibuat.');
    }

    public function storeDetail(SimpanDetailMutasiAsetRequest $request, PermintaanMutasiAset $permintaanMutasiAset, TambahDetailMutasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanMutasiAset);

        $data = $request->validated();
        $aksi->jalankan($permintaanMutasiAset, $data['AsetId'], $data['Catatan'] ?? null);

        return back()->with('sukses', 'Aset berhasil ditambahkan ke daftar mutasi.');
    }

    public function destroyDetail(DetailMutasiAset $detailMutasiAset, HapusDetailMutasiAset $aksi): RedirectResponse
    {
        /** @var PermintaanMutasiAset $permintaan */
        $permintaan = $detailMutasiAset->permintaanMutasiAset;
        $this->authorize('update', $permintaan);

        $aksi->jalankan($permintaan, $detailMutasiAset);

        return back()->with('sukses', 'Aset berhasil dihapus dari daftar mutasi.');
    }

    public function submit(Request $request, PermintaanMutasiAset $permintaanMutasiAset, SubmitPermintaanMutasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanMutasiAset);

        $aksi->jalankan($permintaanMutasiAset, $request->user('web')->Id);

        return back()->with('sukses', 'Permintaan mutasi berhasil disubmit untuk persetujuan.');
    }

    public function batalkan(PermintaanMutasiAset $permintaanMutasiAset, BatalkanPermintaanMutasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanMutasiAset);

        $aksi->jalankan($permintaanMutasiAset);

        return back()->with('sukses', 'Permintaan mutasi berhasil dibatalkan.');
    }

    public function eksekusi(Request $request, PermintaanMutasiAset $permintaanMutasiAset, EksekusiMutasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanMutasiAset);

        $aksi->jalankan($permintaanMutasiAset, $request->user('web')->Id);

        return back()->with('sukses', 'Mutasi aset berhasil dieksekusi.');
    }

    /** Keputusan pemegang aset atas satu baris, terpisah dari persetujuan permintaannya. */
    public function putuskanDetail(
        PutuskanDetailMutasiAsetRequest $request,
        DetailMutasiAset $detailMutasiAset,
        PutuskanDetailMutasiAset $aksi,
    ): RedirectResponse {
        /** @var PermintaanMutasiAset $permintaan */
        $permintaan = $detailMutasiAset->permintaanMutasiAset;
        $this->authorize('update', $permintaan);

        $data = $request->validated();
        $disetujui = (bool) $data['Disetujui'];

        $aksi->jalankan(
            $detailMutasiAset,
            $disetujui,
            $request->user('web')->Id,
            $data['AlasanPenolakan'] ?? null,
        );

        return back()->with('sukses', $disetujui
            ? 'Aset disetujui untuk ikut dimutasi.'
            : 'Aset ditolak dan tidak akan ikut dipindahkan.');
    }

    /** Verifikasi fisik saat pengambilan; kode di luar permintaan ini ditolak. */
    public function pindai(
        PindaiPengambilanAsetRequest $request,
        PermintaanMutasiAset $permintaanMutasiAset,
        PindaiPengambilanAset $aksi,
    ): RedirectResponse {
        $this->authorize('update', $permintaanMutasiAset);

        $detail = $aksi->jalankan(
            $permintaanMutasiAset,
            (string) $request->validated('Kode'),
            $request->user('web')->Id,
        );

        $detail->loadMissing('aset');
        $kodeAset = BacaRelasi::teks(BacaRelasi::model($detail, 'aset'), 'KodeAset');

        return back()->with('sukses', sprintf(
            'Aset %s terverifikasi untuk pengambilan.',
            $kodeAset === '' ? $detail->AsetId : $kodeAset,
        ));
    }
}
