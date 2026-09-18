<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('langganan')
    ->name('langganan.')
    ->group(function (): void {
        // Route domain Langganan. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
