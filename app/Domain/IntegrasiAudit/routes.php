<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('integrasi-audit')
    ->name('integrasiAudit.')
    ->group(function (): void {
        // Route domain IntegrasiAudit. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
