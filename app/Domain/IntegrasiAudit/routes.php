<?php

declare(strict_types=1);

use App\Domain\IntegrasiAudit\Http\Controllers\CatatanAuditController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('integrasi-audit')
    ->name('integrasiAudit.')
    ->group(function (): void {
        Route::get('/audit', [CatatanAuditController::class, 'index'])->name('audit.index');
    });
