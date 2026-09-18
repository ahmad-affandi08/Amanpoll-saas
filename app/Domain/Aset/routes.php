<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('aset')
    ->name('aset.')
    ->group(function (): void {
        // Route domain Aset. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
