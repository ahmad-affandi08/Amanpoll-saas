<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('sinkronisasi')
    ->name('sinkronisasi.')
    ->group(function (): void {
        // Route domain Sinkronisasi. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
