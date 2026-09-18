<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Izin\PemeriksaIzin;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(private readonly PemeriksaIzin $pemeriksaIzin) {}

    public function share(Request $request): array
    {
        $pengguna = $request->user();

        return [
            ...parent::share($request),
            'namaAplikasi' => config('app.name'),
            'auth' => [
                'pengguna' => $pengguna,
            ],
            'izin' => fn (): array => $pengguna ? $this->pemeriksaIzin->daftarKodeIzin((string) $pengguna->Id) : [],
            'flash' => [
                'sukses' => fn () => $request->session()->get('sukses'),
                'gagal' => fn () => $request->session()->get('gagal'),
                'tokenKunciApi' => fn () => $request->session()->get('tokenKunciApi'),
            ],
        ];
    }
}
