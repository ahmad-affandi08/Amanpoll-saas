<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Izin\PemeriksaIzin;
use App\Core\Izin\PemeriksaIzinPlatform;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly PemeriksaIzin $pemeriksaIzin,
        private readonly PemeriksaEntitlement $pemeriksaEntitlement,
        private readonly PemeriksaIzinPlatform $pemeriksaIzinPlatform,
        private readonly PenentuModeLapangan $penentuModeLapangan,
    ) {}

    public function share(Request $request): array
    {
        // Guard disebut eksplisit: sejak konsol platform punya guard sendiri.
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
            // Mode Lapangan (PRD 8.20): mode yang dipakai, apakah pengguna lapangan
            // murni, dan apakah ia boleh beralih antara dasbor dan Mode Lapangan.
            'lapangan' => fn (): array => [
                'mode' => $pengguna ? $this->penentuModeLapangan->mode($pengguna)?->value : null,
                'murni' => $pengguna !== null && $this->penentuModeLapangan->lapanganMurni($pengguna),
                'bisaBeralih' => $pengguna !== null && $this->penentuModeLapangan->bisaBeralih($pengguna),
            ],
            // Kewenangan konsol platform dibagikan terpisah.
            'platform' => function () use ($request): array {
                $admin = $request->user('platform');

                return $admin === null ? [] : [
                    'Nama' => $admin->Nama,
                    'SuperAdmin' => $admin->SuperAdmin === true,
                    'Izin' => $this->pemeriksaIzinPlatform->daftarKode($admin),
                ];
            },
            // Entitlement dibagikan supaya UI dapat menyembunyikan menu dan menonaktifkan tombol.
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
