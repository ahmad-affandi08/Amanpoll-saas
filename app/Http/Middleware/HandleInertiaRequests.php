<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly PemeriksaIzin $pemeriksaIzin,
        private readonly PemeriksaEntitlement $pemeriksaEntitlement,
    ) {}

    public function share(Request $request): array
    {
        // Guard disebut eksplisit: sejak konsol platform punya guard sendiri,
        // $request->user() dapat mengembalikan admin platform, yang tidak punya
        // organisasi maupun izin tenant.
        $pengguna = $request->user('web');

        return [
            ...parent::share($request),
            'namaAplikasi' => config('app.name'),
            'auth' => [
                'pengguna' => $pengguna ? [
                    'Id' => $pengguna->Id,
                    'Nama' => $pengguna->Nama,
                    'Email' => $pengguna->Email,
                    'OrganisasiId' => $pengguna->OrganisasiId,
                    'AvatarUrl' => $pengguna->AvatarUrl,
                    'Jabatan' => $pengguna->Jabatan,
                    'organisasi' => $pengguna->organisasi ? [
                        'Id' => $pengguna->organisasi->Id,
                        'Nama' => $pengguna->organisasi->Nama,
                        'Kode' => $pengguna->organisasi->Kode,
                    ] : null,
                ] : null,
            ],
            'izin' => fn (): array => $pengguna ? $this->pemeriksaIzin->daftarKodeIzin((string) $pengguna->Id) : [],
            // Entitlement dibagikan supaya UI dapat menyembunyikan menu dan
            // menonaktifkan tombol. Ini semata demi kenyamanan: penegakannya
            // tetap di backend, dan prop ini membaca sumber yang sama persis
            // sehingga UI tidak pernah menjanjikan apa yang backend tolak.
            'entitlement' => fn (): array => $pengguna
                ? $this->pemeriksaEntitlement->sekarang()->keArray()
                : [],
            'flash' => [
                'sukses' => fn () => $request->session()->get('sukses'),
                'gagal' => fn () => $request->session()->get('gagal'),
                'tokenKunciApi' => fn () => $request->session()->get('tokenKunciApi'),
                'instruksiPembayaran' => fn () => $request->session()->get('instruksiPembayaran'),
            ],
        ];
    }
}
