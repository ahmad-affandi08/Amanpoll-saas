<?php

use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\V1\KeluhanApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/status', StatusController::class)->name('api.status');

    Route::middleware('kunci.api')->group(function (): void {
        // Endpoint tulis wajib membawa header Idempotency-Key supaya percobaan
        // ulang dari sistem eksternal tidak menghasilkan data ganda (19.07).
        Route::post('/keluhan', [KeluhanApiController::class, 'store'])
            ->middleware(['cakupan.kunci:Keluhan.Kelola', 'idempoten'])
            ->name('api.keluhan.store');
    });
});
