<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('kepatuhan')
    ->name('kepatuhan.')
    ->group(function (): void {
        // Route domain Kepatuhan. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
