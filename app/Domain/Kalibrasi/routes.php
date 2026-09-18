<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('kalibrasi')
    ->name('kalibrasi.')
    ->group(function (): void {
        // Route domain Kalibrasi. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
