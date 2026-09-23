<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\BuatKunciApi;
use App\Domain\Platform\Application\Actions\CabutKunciApi;
use App\Domain\Platform\Http\Requests\BuatKunciApiRequest;
use App\Domain\Platform\Http\Resources\KunciApiResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class KunciApiController extends Controller
{
    /**
     * Penyaring daftar, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<KunciApi>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        return DaftarTersaring::untuk($request, KunciApi::query())
            ->cari(['Nama', 'AwalanKunci'])
            ->urut(['Nama', 'Status', 'DibuatPada'], bawaan: 'DibuatPada', arahBawaan: 'desc')
            ->faset(['Status']);
    }

    /**
     * Berkas ini hanya berisi metadata kunci.
     *
     * `HashKunci` dan token mentahnya sengaja tidak punya kolom di sini: token
     * hanya pernah ada sekali saat pembuatan, dan hash-nya cukup untuk
     * menebak-nebak secara luring bila berkasnya berpindah tangan. `AwalanKunci`
     * ikut karena ia memang penanda publik yang sudah tampil di layar dan tidak
     * dapat dipakai untuk mengautentikasi apa pun.
     */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', KunciApi::class);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::atribut('Awalan Kunci', 'AwalanKunci'),
                KolomEkspor::dari('Cakupan', function (KunciApi $kunci): string {
                    $cakupan = $kunci->getAttribute('Cakupan');

                    return is_array($cakupan) && $cakupan !== []
                        ? implode(', ', array_map(strval(...), $cakupan))
                        : 'Akses Penuh';
                }),
                KolomEkspor::dari('Alamat IP Diizinkan', function (KunciApi $kunci): string {
                    $alamat = $kunci->getAttribute('AlamatIpDiizinkan');

                    return is_array($alamat) ? implode(', ', array_map(strval(...), $alamat)) : '';
                }),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::tanggal('Kadaluarsa', 'KadaluarsaPada', 'Y-m-d'),
                KolomEkspor::tanggal('Terakhir Dipakai', 'TerakhirDipakaiPada', 'Y-m-d H:i'),
                KolomEkspor::tanggal('Dibuat', 'DibuatPada', 'Y-m-d H:i'),
            ],
            'daftar-kunci-api',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', KunciApi::class);

        $daftar = $this->daftar($request);

        return Inertia::render('KunciApi/Index', [
            'wajib' => ['kunciApi' => AturanWajib::untuk(BuatKunciApiRequest::class)],
            'kunciApi' => KunciApiResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
        ]);
    }

    public function store(BuatKunciApiRequest $request, BuatKunciApi $aksi): RedirectResponse
    {
        $this->authorize('create', KunciApi::class);

        $data = $request->validated();

        $hasil = $aksi->jalankan(
            $request->user('web'),
            $data['Nama'],
            $data['Cakupan'] ?? null,
            isset($data['KadaluarsaPada']) ? new DateTimeImmutable($data['KadaluarsaPada']) : null,
            $data['AlamatIpDiizinkan'] ?? null,
        );

        return back()->with([
            'sukses' => 'Kunci API berhasil dibuat.',
            'tokenKunciApi' => $hasil['tokenMentah'],
        ]);
    }

    public function destroy(KunciApi $kunciApi, CabutKunciApi $aksi): RedirectResponse
    {
        $this->authorize('delete', $kunciApi);

        $aksi->jalankan($kunciApi);

        return back()->with('sukses', 'Kunci API berhasil dicabut.');
    }
}
