<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('notifikasi')
    ->name('notifikasi.')
    ->group(function (): void {
        // Route domain Notifikasi. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
