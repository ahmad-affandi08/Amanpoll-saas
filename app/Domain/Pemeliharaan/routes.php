<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('pemeliharaan')
    ->name('pemeliharaan.')
    ->group(function (): void {
        // Route domain Pemeliharaan. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
