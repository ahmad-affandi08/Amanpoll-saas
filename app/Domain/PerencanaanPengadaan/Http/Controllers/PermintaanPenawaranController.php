<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Controllers;

use App\Domain\Penyedia\Domain\Enums\StatusPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPermintaanPenawaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPermintaanPenawaran;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPenawaranPenyediaRequest;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPermintaanPenawaranRequest;
use App\Domain\PerencanaanPengadaan\Http\Resources\PermintaanPenawaranResource;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPenawaran;
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

final class PermintaanPenawaranController extends Controller
{
    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<PermintaanPenawaran>
     */
    private function kueriTersaring(array $filter): Builder
    {
        return PermintaanPenawaran::query()
            ->with('permintaanPembelian')
            ->withCount(['penyediaDiundang', 'penawaran'])
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where('Nomor', 'like', "%{$cari}%"))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->latest('DibuatPada')
            ->orderBy('Id');
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', PermintaanPenawaran::class);

        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::enum(StatusPermintaanPenawaran::class)],
        ]);

        return $ekspor->unduh(
            $this->kueriTersaring($filter),
            [
                KolomEkspor::atribut('Nomor', 'Nomor'),
                KolomEkspor::dari('Nomor Permintaan Pembelian', fn (PermintaanPenawaran $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'permintaanPembelian'), 'Nomor')),
                KolomEkspor::tanggal('Dibuka', 'TanggalDibuka'),
                KolomEkspor::tanggal('Batas Penawaran', 'BatasPenawaran'),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::atribut('Penyedia Diundang', 'penyedia_diundang_count'),
                KolomEkspor::atribut('Penawaran Masuk', 'penawaran_count'),
                KolomEkspor::atribut('Catatan', 'Catatan'),
            ],
            'daftar-permintaan-penawaran',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PermintaanPenawaran::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::enum(StatusPermintaanPenawaran::class)],
        ]);

        $rfq = $this->kueriTersaring($filter)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('PermintaanPenawaran/Index', [
            'wajib' => ['permintaan' => AturanWajib::untuk(SimpanPermintaanPenawaranRequest::class)],
            'rfq' => PermintaanPenawaranResource::collection($rfq),
            'permintaanDisetujui' => PermintaanPembelian::query()
                ->where('Status', StatusPermintaanPembelian::Disetujui->value)
                ->orderBy('Nomor')
                ->get(['Id', 'Nomor', 'TotalEstimasi']),
            'penyedia' => Penyedia::query()->where('Status', StatusPenyedia::Aktif->value)->orderBy('Nama')->get(['Id', 'Kode', 'Nama']),
            'filter' => $filter,
        ]);
    }

    public function store(SimpanPermintaanPenawaranRequest $request, KelolaPermintaanPenawaran $aksi): RedirectResponse
    {
        $this->authorize('create', PermintaanPenawaran::class);
        $data = $request->validated();
        $permintaan = PermintaanPembelian::query()->whereKey($data['PermintaanPembelianId'])->firstOrFail();
        $rfq = $aksi->buat($permintaan, $data, $request->user('web')->Id);

        return redirect()
            ->route('perencanaanPengadaan.rfq.show', $rfq)
            ->with('sukses', 'Draft RFQ dibuat.');
    }

    public function show(PermintaanPenawaran $permintaanPenawaran): Response
    {
        $this->authorize('view', $permintaanPenawaran);
        $permintaanPenawaran->load([
            'permintaanPembelian.detail',
            'dibuatOleh',
            'penyediaDiundang.penyedia',
            'penawaran.penyedia',
            'penawaran.detail',
        ]);

        return Inertia::render('PermintaanPenawaran/Show', [
            'wajib' => ['penawaran' => AturanWajib::untuk(SimpanPenawaranPenyediaRequest::class)],
            'rfq' => new PermintaanPenawaranResource($permintaanPenawaran),
        ]);
    }

    public function buka(PermintaanPenawaran $permintaanPenawaran, KelolaPermintaanPenawaran $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanPenawaran);
        $aksi->buka($permintaanPenawaran);

        return back()->with('sukses', 'RFQ dibuka dan ditandai terkirim ke penyedia.');
    }

    public function storePenawaran(SimpanPenawaranPenyediaRequest $request, PermintaanPenawaran $permintaanPenawaran, KelolaPenawaranPenyedia $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanPenawaran);
        $aksi->catat($permintaanPenawaran, $request->validated());

        return back()->with('sukses', 'Penawaran penyedia dicatat dan total diverifikasi server.');
    }

    public function pilih(PermintaanPenawaran $permintaanPenawaran, PenawaranPenyedia $penawaranPenyedia, KelolaPenawaranPenyedia $aksi): RedirectResponse
    {
        $this->authorize('update', $permintaanPenawaran);
        abort_unless($penawaranPenyedia->PermintaanPenawaranId === $permintaanPenawaran->Id, 404);
        $aksi->pilih($penawaranPenyedia);

        return back()->with('sukses', 'Penawaran dipilih dan RFQ ditutup.');
    }
}
