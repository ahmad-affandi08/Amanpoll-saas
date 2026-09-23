<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Actions\BuatPeran;
use App\Domain\Platform\Application\Actions\HapusPeran;
use App\Domain\Platform\Application\Actions\PasangPeranAwal;
use App\Domain\Platform\Application\Actions\SinkronkanIzinPeran;
use App\Domain\Platform\Application\Actions\UbahPeran;
use App\Domain\Platform\Application\DTO\PeranData;
use App\Domain\Platform\Domain\ValueObjects\KatalogPeranAwal;
use App\Domain\Platform\Http\Requests\SimpanPeranRequest;
use App\Domain\Platform\Http\Resources\PeranResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PeranController extends Controller
{
    /**
     * Penyaring daftar, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<Peran>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        return DaftarTersaring::untuk(
            $request,
            Peran::query()->withCount(['penggunaPeran', 'peranIzin'])->with('peranIzin'),
        )
            ->cari(['Kode', 'Nama', 'Keterangan'])
            ->urut(['Nama', 'Kode'], bawaan: 'Nama');
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', Peran::class);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::atribut('Keterangan', 'Keterangan'),
                KolomEkspor::dari('Bawaan Sistem', fn (Peran $p): string => $p->BawaanSistem ? 'Ya' : 'Tidak'),
            ],
            'daftar-peran',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Peran::class);

        $daftar = $this->daftar($request);

        return Inertia::render('PeranIzin/Index', [
            'wajib' => ['peran' => AturanWajib::untuk(SimpanPeranRequest::class)],
            'peran' => PeranResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
            'bawaanBelumTerpasang' => $this->bawaanBelumTerpasang(),
        ]);
    }

    /**
     * Berapa peran bawaan yang belum dimiliki organisasi ini.
     *
     * Dihitung terhadap seluruh peran organisasi, bukan halaman yang sedang
     * tampil, supaya tombolnya tidak muncul lagi hanya karena hasil pencarian
     * kebetulan tidak memuat peran bawaannya.
     */
    private function bawaanBelumTerpasang(): int
    {
        $terpasang = Peran::query()->pluck('Kode')->all();

        return count(array_filter(
            KatalogPeranAwal::semua(),
            fn (array $contoh): bool => ! in_array($contoh['Kode'], $terpasang, true),
        ));
    }

    public function store(SimpanPeranRequest $request, BuatPeran $aksi): RedirectResponse
    {
        $this->authorize('create', Peran::class);

        $aksi->jalankan(PeranData::dariArray($request->validated()));

        return back()->with('sukses', 'Peran berhasil dibuat.');
    }

    public function update(SimpanPeranRequest $request, Peran $peran, UbahPeran $aksi): RedirectResponse
    {
        $this->authorize('update', $peran);

        $aksi->jalankan($peran, PeranData::dariArray($request->validated()));

        return back()->with('sukses', 'Peran berhasil diperbarui.');
    }

    public function destroy(Peran $peran, HapusPeran $aksi): RedirectResponse
    {
        $this->authorize('delete', $peran);

        $aksi->jalankan($peran);

        return back()->with('sukses', 'Peran berhasil dihapus.');
    }

    /**
     * Memasang peran bawaan bagi organisasi yang belum menyusun perannya.
     *
     * Organisasi yang berdiri sebelum katalog ini ada hanya punya peran
     * Pemilik, jadi tombolnya harus tersedia di halaman, bukan hanya berjalan
     * sekali saat pendaftaran trial.
     */
    public function pasangBawaan(PasangPeranAwal $aksi): RedirectResponse
    {
        $this->authorize('create', Peran::class);

        $baru = $aksi->jalankan(app(KonteksOrganisasi::class)->wajibId());

        return back()->with('sukses', $baru === []
            ? 'Seluruh peran bawaan sudah terpasang.'
            : count($baru).' peran bawaan berhasil dipasang.');
    }

    public function sinkronkanIzin(Request $request, Peran $peran, SinkronkanIzinPeran $aksi): RedirectResponse
    {
        $this->authorize('update', $peran);

        $data = $request->validate([
            'IzinId' => ['array'],
            'IzinId.*' => ['string'],
        ]);

        $aksi->jalankan($peran, $data['IzinId'] ?? []);

        return back()->with('sukses', 'Izin peran berhasil diperbarui.');
    }
}
