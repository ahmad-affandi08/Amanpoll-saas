<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function (): void {
        // Route domain Platform. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
