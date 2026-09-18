<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('persetujuan')
    ->name('persetujuan.')
    ->group(function (): void {
        // Route domain Persetujuan. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
