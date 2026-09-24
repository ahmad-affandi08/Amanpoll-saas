<?php

declare(strict_types=1);

use App\Domain\Kolaborasi\Http\Controllers\BerkasController;
use App\Domain\Kolaborasi\Http\Controllers\DefinisiKolomKustomController;
use App\Domain\Kolaborasi\Http\Controllers\EntitasTagController;
use App\Domain\Kolaborasi\Http\Controllers\KomentarEntitasController;
use App\Domain\Kolaborasi\Http\Controllers\LampiranEntitasController;
use App\Domain\Kolaborasi\Http\Controllers\NilaiKolomKustomController;
use App\Domain\Kolaborasi\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('kolaborasi')
    ->name('kolaborasi.')
    ->group(function (): void {
        Route::post('/berkas', [BerkasController::class, 'store'])->name('berkas.store');
        Route::get('/berkas/{berkas}/unduh', [BerkasController::class, 'unduh'])->name('berkas.unduh');
        Route::get('/berkas/{berkas}/thumbnail', [BerkasController::class, 'thumbnail'])->name('berkas.thumbnail');
        Route::delete('/berkas/{berkas}', [BerkasController::class, 'destroy'])->name('berkas.destroy');

        Route::get('/lampiran', [LampiranEntitasController::class, 'index'])->name('lampiran.index');
        Route::post('/lampiran', [LampiranEntitasController::class, 'store'])->name('lampiran.store');
        Route::delete('/lampiran/{lampiranEntitas}', [LampiranEntitasController::class, 'destroy'])->name('lampiran.destroy');

        Route::get('/kolom-kustom', [DefinisiKolomKustomController::class, 'halaman'])->name('definisiKolomKustom.halaman');

        Route::get('/tag', [TagController::class, 'index'])->name('tag.index');
        Route::get('/tag/ekspor', [TagController::class, 'ekspor'])->middleware('throttle:ekspor')->name('tag.ekspor');
        Route::post('/tag', [TagController::class, 'store'])->name('tag.store');
        Route::put('/tag/{tag}', [TagController::class, 'update'])->name('tag.update');
        Route::delete('/tag/{tag}', [TagController::class, 'destroy'])->name('tag.destroy');

        Route::get('/entitas-tag', [EntitasTagController::class, 'index'])->name('entitasTag.index');
        Route::post('/entitas-tag', [EntitasTagController::class, 'store'])->name('entitasTag.store');
        Route::delete('/entitas-tag/{entitasTag}', [EntitasTagController::class, 'destroy'])->name('entitasTag.destroy');

        Route::get('/definisi-kolom-kustom', [DefinisiKolomKustomController::class, 'index'])->name('definisiKolomKustom.index');
        Route::get('/definisi-kolom-kustom/ekspor', [DefinisiKolomKustomController::class, 'ekspor'])->middleware('throttle:ekspor')->name('definisiKolomKustom.ekspor');
        Route::post('/definisi-kolom-kustom', [DefinisiKolomKustomController::class, 'store'])->name('definisiKolomKustom.store');
        Route::put('/definisi-kolom-kustom/{definisiKolomKustom}', [DefinisiKolomKustomController::class, 'update'])->name('definisiKolomKustom.update');
        Route::delete('/definisi-kolom-kustom/{definisiKolomKustom}', [DefinisiKolomKustomController::class, 'destroy'])->name('definisiKolomKustom.destroy');

        Route::get('/nilai-kolom-kustom', [NilaiKolomKustomController::class, 'index'])->name('nilaiKolomKustom.index');
        Route::post('/nilai-kolom-kustom', [NilaiKolomKustomController::class, 'store'])->name('nilaiKolomKustom.store');

        Route::get('/komentar', [KomentarEntitasController::class, 'index'])->name('komentar.index');
        Route::post('/komentar', [KomentarEntitasController::class, 'store'])->name('komentar.store');
        Route::put('/komentar/{komentarEntitas}', [KomentarEntitasController::class, 'update'])->name('komentar.update');
        Route::delete('/komentar/{komentarEntitas}', [KomentarEntitasController::class, 'destroy'])->name('komentar.destroy');
    });
