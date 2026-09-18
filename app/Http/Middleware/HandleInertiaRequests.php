<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'namaAplikasi' => config('app.name'),
            'auth' => [
                'pengguna' => $request->user(),
            ],
            'flash' => [
                'sukses' => fn () => $request->session()->get('sukses'),
                'gagal' => fn () => $request->session()->get('gagal'),
            ],
        ];
    }
}
