<?php

declare(strict_types=1);

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Http\Controllers\AuthPartnerController;
use App\Domain\Pemasaran\Http\Controllers\PortalPartnerController;
use App\Domain\Pemasaran\Http\Controllers\RobotsController;
use App\Http\Middleware\PastikanPartnerAktif;
use Illuminate\Support\Facades\Route;

// Portal partner (MARKETING.md 1, 21).
$hostPeta = app(PetaHost::class);

if ($hostPeta->portalPartnerAktif()) {
    Route::domain((string) $hostPeta->partner())
        ->middleware('web')
        ->name('partner.')
        ->group(function (): void {
            // Host partner tidak boleh dirayapi sama sekali, sama seperti host dashboard.
            Route::get('/robots.txt', [RobotsController::class, 'robotsTertutup'])->name('robots');

            Route::middleware('guest:partner')->group(function (): void {
                Route::get('/masuk', [AuthPartnerController::class, 'create'])->name('login');
                Route::post('/masuk', [AuthPartnerController::class, 'store'])
                    ->middleware('throttle:masuk')
                    ->name('login.store');
            });

            Route::middleware(['auth:partner', PastikanPartnerAktif::class])->group(function (): void {
                Route::get('/', [PortalPartnerController::class, 'beranda'])->name('beranda');
                Route::post('/lead', [PortalPartnerController::class, 'kirimLead'])
                    ->middleware('throttle:formulir')
                    ->name('lead.store');
                Route::post('/keluar', [AuthPartnerController::class, 'destroy'])->name('logout');
            });
        });
}
