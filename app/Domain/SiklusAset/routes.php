<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('siklus-aset')
    ->name('siklusAset.')
    ->group(function (): void {
        // Route domain SiklusAset. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
