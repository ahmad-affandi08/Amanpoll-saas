<?php

declare(strict_types=1);

use App\Domain\Kontrak\Http\Controllers\KontrakController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('kontrak')
    ->name('kontrak.')
    ->group(function (): void {
        Route::get('/', [KontrakController::class, 'index'])->name('index');
        Route::get('/ekspor', [KontrakController::class, 'ekspor'])->middleware('throttle:ekspor')->name('ekspor');
        Route::post('/', [KontrakController::class, 'store'])->name('store');
        Route::get('/{kontrak}', [KontrakController::class, 'show'])->name('show');
        Route::put('/{kontrak}', [KontrakController::class, 'update'])->name('update');
        Route::delete('/{kontrak}', [KontrakController::class, 'destroy'])->name('destroy');
        Route::post('/{kontrak}/batalkan', [KontrakController::class, 'batalkan'])->name('batalkan');

        Route::post('/{kontrak}/aset', [KontrakController::class, 'storeAset'])->name('aset.store');
        Route::delete('/{kontrak}/aset/{kontrakAset}', [KontrakController::class, 'destroyAset'])->name('aset.destroy');

        Route::post('/{kontrak}/layanan', [KontrakController::class, 'storeLayanan'])->name('layanan.store');
        Route::delete('/{kontrak}/layanan/{layananKontrak}', [KontrakController::class, 'destroyLayanan'])->name('layanan.destroy');
        Route::post('/{kontrak}/layanan/{layananKontrak}/pemakaian', [KontrakController::class, 'catatPemakaian'])->name('layanan.pemakaian');
    });
