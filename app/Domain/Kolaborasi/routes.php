<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('kolaborasi')
    ->name('kolaborasi.')
    ->group(function (): void {
        // Route domain Kolaborasi. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
