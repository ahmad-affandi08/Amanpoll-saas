<?php

use App\Core\Host\PetaHost;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\V1\KeluhanApiController;
use Illuminate\Support\Facades\Route;

// API adalah bagian sistem, bukan situs publik, jadi ia hidup di host dashboard.
Route::domain(app(PetaHost::class)->dashboard())->group(function (): void {
    // Batas laju dipasang pada grup, bukan per rute.
    Route::middleware('throttle:api')->prefix('v1')->group(function (): void {
        Route::get('/status', StatusController::class)->name('api.status');

        Route::middleware('kunci.api')->group(function (): void {
            // Endpoint tulis wajib membawa header Idempotency-Key.
            Route::post('/keluhan', [KeluhanApiController::class, 'store'])
                ->middleware(['cakupan.kunci:Keluhan.Kelola', 'idempoten'])
                ->name('api.keluhan.store');
        });
    });
});
