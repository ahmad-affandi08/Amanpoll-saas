<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PastikanMemilikiIzin;
use App\Http\Middleware\TetapkanKonteksOrganisasi;
use App\Shared\Domain\Exceptions\PengecualianDomain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [HandleInertiaRequests::class]);

        $middleware->alias([
            'organisasi' => TetapkanKonteksOrganisasi::class,
            'izin' => PastikanMemilikiIzin::class,
            'kunci.api' => \App\Http\Middleware\AutentikasiKunciApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PengecualianDomain $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'pesan' => $e->getMessage(),
                    'kode_error' => $e->kodeError(),
                ], $e->kodeStatusHttp());
            }

            return Inertia::render('Error', [
                'status' => $e->kodeStatusHttp(),
                'pesan' => $e->getMessage(),
            ])->toResponse($request)->setStatusCode($e->kodeStatusHttp());
        });
    })
    ->create();
