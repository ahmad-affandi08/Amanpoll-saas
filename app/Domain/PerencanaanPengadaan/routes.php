<?php

declare(strict_types=1);

use App\Domain\PerencanaanPengadaan\Http\Controllers\AnggaranController;
use App\Domain\PerencanaanPengadaan\Http\Controllers\RencanaPengadaanController;
use App\Domain\PerencanaanPengadaan\Http\Controllers\UsulanAsetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('perencanaan-pengadaan')
    ->name('perencanaanPengadaan.')
    ->group(function (): void {
        Route::get('/anggaran', [AnggaranController::class, 'index'])->name('anggaran.index');
        Route::post('/anggaran', [AnggaranController::class, 'store'])->name('anggaran.store');
        Route::get('/anggaran/{anggaran}', [AnggaranController::class, 'show'])->name('anggaran.show');
        Route::put('/anggaran/{anggaran}', [AnggaranController::class, 'update'])->name('anggaran.update');
        Route::delete('/anggaran/{anggaran}', [AnggaranController::class, 'destroy'])->name('anggaran.destroy');
        Route::post('/anggaran/{anggaran}/ajukan', [AnggaranController::class, 'ajukan'])->name('anggaran.ajukan');
        Route::post('/anggaran/{anggaran}/pos', [AnggaranController::class, 'storePos'])->name('anggaran.pos.store');
        Route::put('/pos-anggaran/{posAnggaran}', [AnggaranController::class, 'updatePos'])->name('pos.update');
        Route::delete('/pos-anggaran/{posAnggaran}', [AnggaranController::class, 'destroyPos'])->name('pos.destroy');
        Route::post('/pos-anggaran/{posAnggaran}/transaksi', [AnggaranController::class, 'storeTransaksi'])->name('pos.transaksi.store');

        Route::get('/usulan-aset', [UsulanAsetController::class, 'index'])->name('usulan.index');
        Route::post('/usulan-aset', [UsulanAsetController::class, 'store'])->name('usulan.store');
        Route::get('/usulan-aset/{usulanAset}', [UsulanAsetController::class, 'show'])->name('usulan.show');
        Route::put('/usulan-aset/{usulanAset}', [UsulanAsetController::class, 'update'])->name('usulan.update');
        Route::delete('/usulan-aset/{usulanAset}', [UsulanAsetController::class, 'destroy'])->name('usulan.destroy');
        Route::post('/usulan-aset/{usulanAset}/submit', [UsulanAsetController::class, 'submit'])->name('usulan.submit');
        Route::post('/usulan-aset/{usulanAset}/penilaian', [UsulanAsetController::class, 'nilai'])->name('usulan.penilaian.store');
        Route::post('/usulan-aset/{usulanAset}/ajukan-persetujuan', [UsulanAsetController::class, 'ajukanPersetujuan'])->name('usulan.ajukan-persetujuan');

        Route::get('/rencana-pengadaan', [RencanaPengadaanController::class, 'index'])->name('rencana.index');
        Route::post('/rencana-pengadaan', [RencanaPengadaanController::class, 'store'])->name('rencana.store');
        Route::get('/rencana-pengadaan/{rencanaPengadaan}', [RencanaPengadaanController::class, 'show'])->name('rencana.show');
        Route::put('/rencana-pengadaan/{rencanaPengadaan}', [RencanaPengadaanController::class, 'update'])->name('rencana.update');
        Route::delete('/rencana-pengadaan/{rencanaPengadaan}', [RencanaPengadaanController::class, 'destroy'])->name('rencana.destroy');
        Route::post('/rencana-pengadaan/{rencanaPengadaan}/detail', [RencanaPengadaanController::class, 'storeDetail'])->name('rencana.detail.store');
        Route::delete('/rencana-pengadaan/{rencanaPengadaan}/detail/{detailRencanaPengadaan}', [RencanaPengadaanController::class, 'destroyDetail'])->name('rencana.detail.destroy');
        Route::post('/rencana-pengadaan/{rencanaPengadaan}/finalisasi', [RencanaPengadaanController::class, 'finalisasi'])->name('rencana.finalisasi');
    });
