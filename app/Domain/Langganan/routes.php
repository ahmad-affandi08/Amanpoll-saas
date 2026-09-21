<?php

declare(strict_types=1);

use App\Domain\Langganan\Http\Controllers\AuthPlatformController;
use App\Domain\Langganan\Http\Controllers\LanggananPlatformController;
use App\Domain\Langganan\Http\Controllers\LanggananTenantController;
use App\Domain\Langganan\Http\Controllers\PaketPlatformController;
use App\Domain\Langganan\Http\Controllers\WebhookPembayaranController;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use Illuminate\Support\Facades\Route;

/**
 * Admin platform bekerja lintas tenant, jadi bindingnya dibuat eksplisit lepas
 * dari global scope organisasi. Namanya dibedakan dari parameter tenant supaya
 * tidak ada rute tenant yang tanpa sengaja ikut memakai binding tanpa batas
 * ini.
 */
Route::bind(
    'langgananPlatform',
    fn (string $id): Langganan => Langganan::query()->withoutGlobalScopes()->findOrFail($id),
);

// Webhook penyedia pembayaran (22.06). Tanpa sesi dan tanpa tenant: keabsahannya
// dibuktikan oleh tanda tangan penyedia, bukan oleh pengguna yang masuk.
Route::middleware('api')
    ->post('/webhook/pembayaran/{penyedia}', WebhookPembayaranController::class)
    ->name('langganan.webhook.pembayaran');

// Prefix dibedakan dari /platform milik pengaturan tenant: keduanya memakai
// guard yang berbeda, dan URL yang sama dengan arti berbeda adalah sumber salah
// paham yang mahal.
Route::middleware('web')->prefix('admin-platform')->name('adminPlatform.')->group(function (): void {
    Route::middleware('guest:platform')->group(function (): void {
        Route::get('/login', [AuthPlatformController::class, 'create'])->name('login');
        Route::post('/login', [AuthPlatformController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:platform')->group(function (): void {
        Route::post('/logout', [AuthPlatformController::class, 'destroy'])->name('logout');

        Route::get('/paket', [PaketPlatformController::class, 'index'])->name('paket.index');
        Route::post('/paket', [PaketPlatformController::class, 'store'])->name('paket.store');
        Route::put('/paket/{paketLangganan}', [PaketPlatformController::class, 'update'])->name('paket.update');
        Route::delete('/paket/{paketLangganan}', [PaketPlatformController::class, 'destroy'])->name('paket.destroy');

        Route::get('/langganan', [LanggananPlatformController::class, 'index'])->name('langganan.index');
        Route::post('/langganan', [LanggananPlatformController::class, 'store'])->name('langganan.store');
        Route::post('/langganan/{langgananPlatform}/perpanjang', [LanggananPlatformController::class, 'perpanjang'])
            ->name('langganan.perpanjang');
        Route::post('/langganan/{langgananPlatform}/batalkan', [LanggananPlatformController::class, 'batalkan'])
            ->name('langganan.batalkan');
        Route::post('/langganan/{langgananPlatform}/tagihan', [LanggananPlatformController::class, 'terbitkanTagihan'])
            ->name('langganan.tagihan');
    });
});

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('langganan')
    ->name('langganan.')
    ->group(function (): void {
        Route::get('/', [LanggananTenantController::class, 'index'])->name('index');

        // Dikecualikan dari pemblokiran tulis: justru lewat sini tenant yang
        // kedaluwarsa memulihkan langganannya.
        Route::post('/tagihan/{tagihan}/bayar', [LanggananTenantController::class, 'bayar'])
            ->name('tagihan.bayar');
    });
