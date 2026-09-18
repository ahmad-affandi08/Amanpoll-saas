<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('pelaporan')
    ->name('pelaporan.')
    ->group(function (): void {
        // Route domain Pelaporan. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
