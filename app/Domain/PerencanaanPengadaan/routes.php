<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('perencanaan-pengadaan')
    ->name('perencanaanPengadaan.')
    ->group(function (): void {
        // Route domain PerencanaanPengadaan. Aktifkan hanya endpoint yang sudah memiliki policy/use-case.
    });
