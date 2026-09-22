<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks yang mengikuti kueri daftar yang benar-benar dijalankan (FASE 25.01).
 *
 * Tiap daftar utama menyaring pada kolom tertentu lalu mengurutkan pada kolom
 * lain. Indeks yang sudah ada menutup penyaringnya tetapi berhenti sebelum
 * kolom pengurutnya, sehingga pengurutan jatuh ke filesort: seluruh baris yang
 * lolos saringan harus diurutkan di memori sebelum dua puluh lima yang tampil
 * dapat diambil. Kolom pengurut ditaruh paling belakang supaya satu indeks
 * melayani penyaringan sekaligus pengurutan.
 *
 * Keluhan sengaja tidak disentuh: `IdxKeluhanStatus` sudah berakhir pada
 * `DilaporkanPada`, persis kolom yang dipakai daftarnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Daftar perintah kerja menyaring Status lalu mengurutkan DibuatPada,
        // sedangkan IdxPerintahKerjaStatus berakhir pada DijadwalkanMulaiPada.
        Schema::table('PerintahKerja', function (Blueprint $table): void {
            $table->index(['OrganisasiId', 'Status', 'DibuatPada'], 'IdxPerintahKerjaDaftar');
        });

        // Inspeksi belum punya indeks apa pun yang diawali OrganisasiId untuk daftarnya.
        Schema::table('Inspeksi', function (Blueprint $table): void {
            $table->index(['OrganisasiId', 'Status', 'Hasil', 'DijadwalkanPada'], 'IdxInspeksiDaftar');
        });

        // IdxMutasiStokTanggal diawali Tanggal, bukan Status yang dipakai penyaring daftar.
        Schema::table('MutasiStok', function (Blueprint $table): void {
            $table->index(['OrganisasiId', 'Status', 'Jenis', 'DibuatPada'], 'IdxMutasiStokDaftar');
        });

        // Reservasi hanya punya indeks kunci asing pada OrganisasiId.
        Schema::table('ReservasiSukuCadang', function (Blueprint $table): void {
            $table->index(['OrganisasiId', 'Status', 'DibuatPada'], 'IdxReservasiSukuCadangDaftar');
        });
    }

    public function down(): void
    {
        Schema::table('PerintahKerja', function (Blueprint $table): void {
            $table->dropIndex('IdxPerintahKerjaDaftar');
        });

        Schema::table('Inspeksi', function (Blueprint $table): void {
            $table->dropIndex('IdxInspeksiDaftar');
        });

        Schema::table('MutasiStok', function (Blueprint $table): void {
            $table->dropIndex('IdxMutasiStokDaftar');
        });

        Schema::table('ReservasiSukuCadang', function (Blueprint $table): void {
            $table->dropIndex('IdxReservasiSukuCadangDaftar');
        });
    }
};
