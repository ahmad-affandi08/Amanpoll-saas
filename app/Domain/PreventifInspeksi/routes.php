<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('preventif-inspeksi')
    ->name('preventifInspeksi.')
    ->group(function (): void {
        // Route domain PreventifInspeksi. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
