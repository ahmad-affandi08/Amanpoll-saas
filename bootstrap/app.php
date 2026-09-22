<?php

use App\Core\Host\PetaHost;
use App\Http\Middleware\AutentikasiKunciApi;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PastikanAkunMasihAktif;
use App\Http\Middleware\PastikanCakupanKunciApi;
use App\Http\Middleware\PastikanFiturPaketAktif;
use App\Http\Middleware\PastikanFiturPlatformAktif;
use App\Http\Middleware\PastikanIdempoten;
use App\Http\Middleware\PastikanIzinPlatform;
use App\Http\Middleware\PastikanLanggananMengizinkanTulis;
use App\Http\Middleware\PastikanMemilikiIzin;
use App\Http\Middleware\TandaiHostTidakTerindeks;
use App\Http\Middleware\TetapkanKonteksOrganisasi;
use App\Http\Middleware\TetapkanKorelasiId;
use App\Http\Middleware\TetapkanSesiPengunjung;
use App\Shared\Domain\Exceptions\PengecualianDomain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require __DIR__.'/../routes/publik.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [HandleInertiaRequests::class]);
        $middleware->web(prepend: [TetapkanKorelasiId::class]);
        $middleware->api(prepend: [TetapkanKorelasiId::class]);

        // Status akun diperiksa ulang tiap permintaan, bukan hanya saat masuk:
        // sesi berumur panjang dan PemeriksaIzin hanya membaca peran, sehingga
        // tanpa ini penonaktifan pengguna atau organisasi tidak segera berlaku.
        $middleware->web(append: [PastikanAkunMasihAktif::class]);

        // Identitas pengunjung juga dibawa di host dashboard: pendaftaran trial
        // terjadi di sini, dan tanpa pengenalnya seluruh konversi akan tercatat
        // sebagai `direct` (MARKETING.md 1.1).
        $middleware->web(append: [TetapkanSesiPengunjung::class]);

        // Hanya host publik yang boleh diindeks (MARKETING.md 1.2).
        $middleware->web(append: [TandaiHostTidakTerindeks::class]);
        $middleware->api(append: [TandaiHostTidakTerindeks::class]);

        // Penjaga langganan dipasang pada grup, bukan per rute, supaya tidak ada
        // rute yang dapat lupa dijaga — termasuk rute API yang ditembak langsung
        // tanpa melewati UI (Gate 22).
        $middleware->web(append: [PastikanLanggananMengizinkanTulis::class]);
        $middleware->api(append: [PastikanLanggananMengizinkanTulis::class]);

        // Konsol platform punya halaman masuk sendiri. Tanpa ini, admin platform
        // yang sesinya habis akan dilempar ke halaman masuk tenant, yang meminta
        // kode organisasi — kredensial yang memang tidak ia miliki.
        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is('admin-platform', 'admin-platform/*')
                ? route('adminPlatform.login')
                : route('login'),
        );
        $middleware->redirectUsersTo(
            fn (Request $request): string => $request->is('admin-platform', 'admin-platform/*')
                ? route('adminPlatform.paket.index')
                : route('dashboard'),
        );

        $middleware->alias([
            'organisasi' => TetapkanKonteksOrganisasi::class,
            'fitur' => PastikanFiturPaketAktif::class,
            'izin' => PastikanMemilikiIzin::class,
            'izin.platform' => PastikanIzinPlatform::class,
            'fitur.platform' => PastikanFiturPlatformAktif::class,
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

        // Berjalan setelah konteks organisasi ada — pencatatan akses menulis ke
        // tabel bertenant — tetapi sebelum route model binding, supaya akun yang
        // dinonaktifkan tidak pernah menyentuh data.
        $middleware->appendToPriorityList(
            after: TetapkanKonteksOrganisasi::class,
            append: PastikanAkunMasihAktif::class,
        );

        // Penjaga langganan harus berjalan setelah konteks organisasi ada dan
        // setelah route model binding, karena ia membaca nama rute untuk
        // mengecualikan jalur pembayaran.
        $middleware->appendToPriorityList(
            after: SubstituteBindings::class,
            append: PastikanLanggananMengizinkanTulis::class,
        );
        $middleware->appendToPriorityList(
            after: PastikanLanggananMengizinkanTulis::class,
            append: PastikanFiturPaketAktif::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Respons yang lahir dari pengecualian — pengalihan `auth`, 404, 500 —
        // tidak melewati fase balik middleware, sehingga penanda noindex-nya
        // dipasang di sini agar tidak ada halaman sistem yang lolos ke indeks.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request): Response {
            if (! app(PetaHost::class)->adalahHostPublik($request->getHost())) {
                $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
            }

            return $response;
        });

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
