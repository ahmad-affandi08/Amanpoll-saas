<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('persediaan')
    ->name('persediaan.')
    ->group(function (): void {
        // Route domain Persediaan. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
