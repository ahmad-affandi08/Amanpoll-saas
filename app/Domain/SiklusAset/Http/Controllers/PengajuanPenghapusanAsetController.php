<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Controllers;

use App\Domain\Aset\Http\Resources\AsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\SiklusAset\Application\Actions\BatalkanPengajuanPenghapusanAset;
use App\Domain\SiklusAset\Application\Actions\BuatPengajuanPenghapusanAset;
use App\Domain\SiklusAset\Application\Actions\EksekusiPenghapusanAset;
use App\Domain\SiklusAset\Application\Actions\HapusDetailPenghapusanAset;
use App\Domain\SiklusAset\Application\Actions\SubmitPengajuanPenghapusanAset;
use App\Domain\SiklusAset\Application\Actions\TambahDetailPenghapusanAset;
use App\Domain\SiklusAset\Http\Requests\SimpanDetailPenghapusanAsetRequest;
use App\Domain\SiklusAset\Http\Requests\SimpanPengajuanPenghapusanAsetRequest;
use App\Domain\SiklusAset\Http\Resources\PengajuanPenghapusanAsetResource;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailPenghapusanAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PengajuanPenghapusanAsetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PengajuanPenghapusanAset::class);

        $filter = $request->validate(['status' => ['nullable', 'string']]);

        $pengajuan = PengajuanPenghapusanAset::query()
            ->with('diajukanOleh')
            ->when($filter['status'] ?? null, fn ($q, $v) => $q->where('Status', $v))
            ->latest('DiajukanPada')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('PenghapusanAset/Index', [
            'wajib' => ['pengajuan' => AturanWajib::untuk(SimpanPengajuanPenghapusanAsetRequest::class)],
            'pengajuan' => PengajuanPenghapusanAsetResource::collection($pengajuan),
            'filter' => $filter,
        ]);
    }

    public function show(PengajuanPenghapusanAset $pengajuanPenghapusanAset): Response
    {
        $this->authorize('view', $pengajuanPenghapusanAset);

        $pengajuanPenghapusanAset->load(['diajukanOleh', 'detailPenghapusanAset.aset' => fn ($q) => $q->withTrashed()]);

        return Inertia::render('PenghapusanAset/Show', [
            'wajib' => ['detail' => AturanWajib::untuk(SimpanDetailPenghapusanAsetRequest::class)],
            'pengajuan' => new PengajuanPenghapusanAsetResource($pengajuanPenghapusanAset),
            'aset' => AsetResource::collection(Aset::query()->orderBy('Nama')->limit(BatasDaftar::MAKS)->get()),
        ]);
    }

    public function store(SimpanPengajuanPenghapusanAsetRequest $request, BuatPengajuanPenghapusanAset $aksi): RedirectResponse
    {
        $this->authorize('create', PengajuanPenghapusanAset::class);

        $pengajuan = $aksi->jalankan($request->validated(), $request->user('web')->Id);

        return redirect("/penghapusan-aset/{$pengajuan->Id}")->with('sukses', 'Draft pengajuan penghapusan berhasil dibuat.');
    }

    public function storeDetail(SimpanDetailPenghapusanAsetRequest $request, PengajuanPenghapusanAset $pengajuanPenghapusanAset, TambahDetailPenghapusanAset $aksi): RedirectResponse
    {
        $this->authorize('update', $pengajuanPenghapusanAset);

        $data = $request->validated();
        $aksi->jalankan(
            $pengajuanPenghapusanAset,
            $data['AsetId'],
            isset($data['NilaiBukuSaatPenghapusan']) ? (float) $data['NilaiBukuSaatPenghapusan'] : null,
            isset($data['HasilPelepasan']) ? (float) $data['HasilPelepasan'] : null,
            $data['Catatan'] ?? null,
        );

        return back()->with('sukses', 'Aset berhasil ditambahkan ke pengajuan penghapusan.');
    }

    public function destroyDetail(DetailPenghapusanAset $detailPenghapusanAset, HapusDetailPenghapusanAset $aksi): RedirectResponse
    {
        /** @var PengajuanPenghapusanAset $pengajuan */
        $pengajuan = $detailPenghapusanAset->pengajuanPenghapusanAset;
        $this->authorize('update', $pengajuan);

        $aksi->jalankan($pengajuan, $detailPenghapusanAset);

        return back()->with('sukses', 'Aset berhasil dihapus dari pengajuan penghapusan.');
    }

    public function submit(PengajuanPenghapusanAset $pengajuanPenghapusanAset, SubmitPengajuanPenghapusanAset $aksi, Request $request): RedirectResponse
    {
        $this->authorize('update', $pengajuanPenghapusanAset);

        $aksi->jalankan($pengajuanPenghapusanAset, $request->user('web')->Id);

        return back()->with('sukses', 'Pengajuan penghapusan berhasil disubmit untuk persetujuan.');
    }

    public function batalkan(PengajuanPenghapusanAset $pengajuanPenghapusanAset, BatalkanPengajuanPenghapusanAset $aksi): RedirectResponse
    {
        $this->authorize('update', $pengajuanPenghapusanAset);

        $aksi->jalankan($pengajuanPenghapusanAset);

        return back()->with('sukses', 'Pengajuan penghapusan berhasil dibatalkan.');
    }

    public function eksekusi(PengajuanPenghapusanAset $pengajuanPenghapusanAset, EksekusiPenghapusanAset $aksi): RedirectResponse
    {
        $this->authorize('update', $pengajuanPenghapusanAset);

        $aksi->jalankan($pengajuanPenghapusanAset);

        return back()->with('sukses', 'Penghapusan aset berhasil dieksekusi.');
    }
}
