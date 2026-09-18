<?php

use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/status', StatusController::class)->name('api.status');

    Route::middleware('kunci.api')->group(function (): void {
        // Endpoint integrasi Amanpoll yang membutuhkan API key ditempatkan di sini.
    });
});
