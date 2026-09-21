<?php

use App\Http\Middleware\AutentikasiKunciApi;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PastikanCakupanKunciApi;
use App\Http\Middleware\PastikanIdempoten;
use App\Http\Middleware\PastikanMemilikiIzin;
use App\Http\Middleware\TetapkanKonteksOrganisasi;
use App\Http\Middleware\TetapkanKorelasiId;
use App\Shared\Domain\Exceptions\PengecualianDomain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
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
        $middleware->web(prepend: [TetapkanKorelasiId::class]);
        $middleware->api(prepend: [TetapkanKorelasiId::class]);

        $middleware->alias([
            'organisasi' => TetapkanKonteksOrganisasi::class,
            'izin' => PastikanMemilikiIzin::class,
            'kunci.api' => AutentikasiKunciApi::class,
            'cakupan.kunci' => PastikanCakupanKunciApi::class,
            'idempoten' => PastikanIdempoten::class,
        ]);

        // Konteks organisasi wajib ditetapkan sebelum route model binding di-resolve,
        // supaya binding tenant-scoped (mis. Route::get('/aset/{aset}')) benar-benar
        // membatasi ke organisasi yang sedang login/API key, bukan resolve dulu baru dicek.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: TetapkanKonteksOrganisasi::class,
        );
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: AutentikasiKunciApi::class,
        );
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
