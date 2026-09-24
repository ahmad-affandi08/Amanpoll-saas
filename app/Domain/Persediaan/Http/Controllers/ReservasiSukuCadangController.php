<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Persediaan\Application\Actions\BuatReservasiSukuCadang;
use App\Domain\Persediaan\Application\Actions\KonsumsiReservasiSukuCadang;
use App\Domain\Persediaan\Application\Actions\LepaskanReservasiSukuCadang;
use App\Domain\Persediaan\Application\Services\LingkupGudang;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Http\Requests\SimpanReservasiSukuCadangRequest;
use App\Domain\Persediaan\Http\Resources\ReservasiSukuCadangResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReservasiSukuCadangController extends Controller
{
    public function __construct(private readonly LingkupGudang $lingkupGudang) {}

    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<ReservasiSukuCadang>
     */
    private function kueriTersaring(array $filter): Builder
    {
        // Hanya reservasi di gudang yang terlihat pengguna (PRD 8.21); ikut terbawa ke ekspor.
        return $this->lingkupGudang->saring(ReservasiSukuCadang::query(), 'ReservasiSukuCadang.GudangId')
            ->with(['gudang', 'sukuCadang', 'dibuatOleh'])
            ->when($filter['status'] ?? null, fn ($q, $v) => $q->where('Status', $v))
            ->latest('DibuatPada')
            ->orderBy('Id');
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', ReservasiSukuCadang::class);

        $filter = $request->validate(['status' => ['nullable', 'string']]);

        return $ekspor->unduh(
            $this->kueriTersaring($filter),
            [
                KolomEkspor::dari('Suku Cadang', fn (ReservasiSukuCadang $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'sukuCadang'), 'Nama')),
                KolomEkspor::dari('Gudang', fn (ReservasiSukuCadang $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'gudang'), 'Nama')),
                KolomEkspor::atribut('Jumlah', 'Jumlah'),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::dari('Dibuat Oleh', fn (ReservasiSukuCadang $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'dibuatOleh'), 'Nama')),
                KolomEkspor::tanggal('Dibuat', 'DibuatPada', 'Y-m-d H:i'),
            ],
            'daftar-reservasi-suku-cadang',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ReservasiSukuCadang::class);

        $filter = $request->validate(['status' => ['nullable', 'string']]);

        $reservasi = $this->kueriTersaring($filter)->paginate(25)->withQueryString();

        return Inertia::render('ReservasiSukuCadang/Index', [
            'wajib' => ['reservasi' => AturanWajib::untuk(SimpanReservasiSukuCadangRequest::class)],
            'reservasi' => ReservasiSukuCadangResource::collection($reservasi),
            'gudang' => Gudang::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'sukuCadang' => SukuCadang::query()->where('Status', StatusSukuCadang::Aktif->value)->orderBy('Nama')->get(['Id', 'Nama', 'Kode']),
            'filter' => $filter,
        ]);
    }

    public function store(SimpanReservasiSukuCadangRequest $request, BuatReservasiSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('create', ReservasiSukuCadang::class);

        $aksi->jalankan($request->validated(), $request->user('web')->Id);

        return back()->with('sukses', 'Reservasi suku cadang berhasil dibuat.');
    }

    public function lepaskan(ReservasiSukuCadang $reservasiSukuCadang, LepaskanReservasiSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('update', $reservasiSukuCadang);

        $aksi->jalankan($reservasiSukuCadang);

        return back()->with('sukses', 'Reservasi suku cadang berhasil dilepas.');
    }

    public function konsumsi(ReservasiSukuCadang $reservasiSukuCadang, KonsumsiReservasiSukuCadang $aksi, Request $request): RedirectResponse
    {
        $this->authorize('update', $reservasiSukuCadang);

        $aksi->jalankan($reservasiSukuCadang, $request->user('web')->Id);

        return back()->with('sukses', 'Reservasi suku cadang berhasil dipakai.');
    }
}
