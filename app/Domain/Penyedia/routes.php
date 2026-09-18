<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('penyedia')
    ->name('penyedia.')
    ->group(function (): void {
        // Route domain Penyedia. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
