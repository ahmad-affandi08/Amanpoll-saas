<?php

declare(strict_types=1);

use App\Domain\PerencanaanPengadaan\Http\Controllers\AnggaranController;
use App\Domain\PerencanaanPengadaan\Http\Controllers\PenerimaanPembelianController;
use App\Domain\PerencanaanPengadaan\Http\Controllers\PermintaanPembelianController;
use App\Domain\PerencanaanPengadaan\Http\Controllers\PermintaanPenawaranController;
use App\Domain\PerencanaanPengadaan\Http\Controllers\PesananPembelianController;
use App\Domain\PerencanaanPengadaan\Http\Controllers\RencanaPengadaanController;
use App\Domain\PerencanaanPengadaan\Http\Controllers\TagihanPenyediaController;
use App\Domain\PerencanaanPengadaan\Http\Controllers\UsulanAsetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('perencanaan-pengadaan')
    ->name('perencanaanPengadaan.')
    ->group(function (): void {
        Route::get('/anggaran', [AnggaranController::class, 'index'])->name('anggaran.index');
        Route::get('/anggaran/ekspor', [AnggaranController::class, 'ekspor'])->middleware('throttle:ekspor')->name('anggaran.ekspor');
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
        Route::get('/usulan-aset/ekspor', [UsulanAsetController::class, 'ekspor'])->middleware('throttle:ekspor')->name('usulan.ekspor');
        Route::post('/usulan-aset', [UsulanAsetController::class, 'store'])->name('usulan.store');
        Route::get('/usulan-aset/{usulanAset}', [UsulanAsetController::class, 'show'])->name('usulan.show');
        Route::put('/usulan-aset/{usulanAset}', [UsulanAsetController::class, 'update'])->name('usulan.update');
        Route::delete('/usulan-aset/{usulanAset}', [UsulanAsetController::class, 'destroy'])->name('usulan.destroy');
        Route::post('/usulan-aset/{usulanAset}/submit', [UsulanAsetController::class, 'submit'])->name('usulan.submit');
        Route::post('/usulan-aset/{usulanAset}/penilaian', [UsulanAsetController::class, 'nilai'])->name('usulan.penilaian.store');
        Route::post('/usulan-aset/{usulanAset}/ajukan-persetujuan', [UsulanAsetController::class, 'ajukanPersetujuan'])->name('usulan.ajukan-persetujuan');

        Route::get('/rencana-pengadaan', [RencanaPengadaanController::class, 'index'])->name('rencana.index');
        Route::get('/rencana-pengadaan/ekspor', [RencanaPengadaanController::class, 'ekspor'])->middleware('throttle:ekspor')->name('rencana.ekspor');
        Route::post('/rencana-pengadaan', [RencanaPengadaanController::class, 'store'])->name('rencana.store');
        Route::get('/rencana-pengadaan/{rencanaPengadaan}', [RencanaPengadaanController::class, 'show'])->name('rencana.show');
        Route::put('/rencana-pengadaan/{rencanaPengadaan}', [RencanaPengadaanController::class, 'update'])->name('rencana.update');
        Route::delete('/rencana-pengadaan/{rencanaPengadaan}', [RencanaPengadaanController::class, 'destroy'])->name('rencana.destroy');
        Route::post('/rencana-pengadaan/{rencanaPengadaan}/detail', [RencanaPengadaanController::class, 'storeDetail'])->name('rencana.detail.store');
        Route::delete('/rencana-pengadaan/{rencanaPengadaan}/detail/{detailRencanaPengadaan}', [RencanaPengadaanController::class, 'destroyDetail'])->name('rencana.detail.destroy');
        Route::post('/rencana-pengadaan/{rencanaPengadaan}/finalisasi', [RencanaPengadaanController::class, 'finalisasi'])->name('rencana.finalisasi');

        Route::get('/permintaan-pembelian', [PermintaanPembelianController::class, 'index'])->name('permintaan.index');
        Route::get('/permintaan-pembelian/ekspor', [PermintaanPembelianController::class, 'ekspor'])->middleware('throttle:ekspor')->name('permintaan.ekspor');
        Route::post('/permintaan-pembelian', [PermintaanPembelianController::class, 'store'])->name('permintaan.store');
        Route::get('/permintaan-pembelian/{permintaanPembelian}', [PermintaanPembelianController::class, 'show'])->name('permintaan.show');
        Route::post('/permintaan-pembelian/{permintaanPembelian}/detail', [PermintaanPembelianController::class, 'storeDetail'])->name('permintaan.detail.store');
        Route::delete('/permintaan-pembelian/{permintaanPembelian}/detail/{detailPermintaanPembelian}', [PermintaanPembelianController::class, 'destroyDetail'])->name('permintaan.detail.destroy');
        Route::post('/permintaan-pembelian/{permintaanPembelian}/submit', [PermintaanPembelianController::class, 'submit'])->name('permintaan.submit');

        Route::get('/permintaan-penawaran', [PermintaanPenawaranController::class, 'index'])->name('rfq.index');
        Route::get('/permintaan-penawaran/ekspor', [PermintaanPenawaranController::class, 'ekspor'])->middleware('throttle:ekspor')->name('rfq.ekspor');
        Route::post('/permintaan-penawaran', [PermintaanPenawaranController::class, 'store'])->name('rfq.store');
        Route::get('/permintaan-penawaran/{permintaanPenawaran}', [PermintaanPenawaranController::class, 'show'])->name('rfq.show');
        Route::post('/permintaan-penawaran/{permintaanPenawaran}/buka', [PermintaanPenawaranController::class, 'buka'])->name('rfq.buka');
        Route::post('/permintaan-penawaran/{permintaanPenawaran}/penawaran', [PermintaanPenawaranController::class, 'storePenawaran'])->name('rfq.penawaran.store');
        Route::post('/permintaan-penawaran/{permintaanPenawaran}/penawaran/{penawaranPenyedia}/pilih', [PermintaanPenawaranController::class, 'pilih'])->name('rfq.penawaran.pilih');

        Route::get('/pesanan-pembelian', [PesananPembelianController::class, 'index'])->name('po.index');
        Route::get('/pesanan-pembelian/ekspor', [PesananPembelianController::class, 'ekspor'])->middleware('throttle:ekspor')->name('po.ekspor');
        Route::post('/penawaran/{penawaranPenyedia}/pesanan-pembelian', [PesananPembelianController::class, 'store'])->name('po.store');
        Route::get('/pesanan-pembelian/{pesananPembelian}', [PesananPembelianController::class, 'show'])->name('po.show');
        Route::post('/pesanan-pembelian/{pesananPembelian}/ajukan', [PesananPembelianController::class, 'ajukan'])->name('po.ajukan');
        Route::post('/pesanan-pembelian/{pesananPembelian}/kirim', [PesananPembelianController::class, 'kirim'])->name('po.kirim');
        Route::post('/pesanan-pembelian/{pesananPembelian}/penerimaan', [PenerimaanPembelianController::class, 'store'])->name('penerimaan.store');
        Route::post('/pesanan-pembelian/{pesananPembelian}/tagihan', [TagihanPenyediaController::class, 'store'])->name('tagihan.store');

        Route::get('/penerimaan-pembelian', [PenerimaanPembelianController::class, 'index'])->name('penerimaan.index');
        Route::get('/penerimaan-pembelian/ekspor', [PenerimaanPembelianController::class, 'ekspor'])->middleware('throttle:ekspor')->name('penerimaan.ekspor');
        Route::get('/tagihan-penyedia', [TagihanPenyediaController::class, 'index'])->name('tagihan.index');
        Route::get('/tagihan-penyedia/ekspor', [TagihanPenyediaController::class, 'ekspor'])->middleware('throttle:ekspor')->name('tagihan.ekspor');
        Route::get('/tagihan-penyedia/{tagihanPenyedia}', [TagihanPenyediaController::class, 'show'])->name('tagihan.show');
        Route::post('/tagihan-penyedia/{tagihanPenyedia}/pembayaran', [TagihanPenyediaController::class, 'bayar'])->name('tagihan.bayar');
    });
