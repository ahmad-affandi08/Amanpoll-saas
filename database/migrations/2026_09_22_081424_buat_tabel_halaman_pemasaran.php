<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landing page builder (MARKETING.md 8).
 *
 * Isi halaman tidak disimpan pada barisnya sendiri melainkan pada versi.
 * `HalamanPemasaran` hanya memegang identitas — slug, tipe, status, jadwal —
 * dan menunjuk satu versi yang sedang terbit. Dengan bentuk ini revision
 * history, rollback, dan terbit terjadwal menjadi satu mekanisme yang sama:
 * mengganti versi mana yang ditunjuk. Tidak ada jalur lain yang mengubah isi
 * halaman terbit, sehingga tidak ada cara menerbitkan sesuatu tanpa
 * meninggalkan versinya.
 *
 * Versi dan bloknya tidak pernah diubah setelah dibuat. Menyunting draf
 * melahirkan versi baru, bukan menimpa yang lama — riwayat yang dapat ditulis
 * ulang tidak dapat dipakai untuk rollback.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('HalamanPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Slug', 190)->unique('UnqHalamanPemasaranSlug');
            $table->string('Tipe', 40);
            $table->string('Judul', 190);
            $table->string('Status', 20);
            $table->string('Segmen', 60)->nullable();
            $table->char('KampanyeId', 26)->nullable();

            $table->char('VersiTerbitId', 26)->nullable();
            $table->char('VersiDrafId', 26)->nullable();

            /*
             * Jadwal disimpan sebagai niat, bukan sebagai hasil: penjadwal yang
             * terlambat berjalan tetap menerbitkan halaman yang waktunya sudah
             * lewat, alih-alih melewatkannya diam-diam.
             */
            $table->dateTime('TerbitPada', 6)->nullable();
            $table->dateTime('TarikPada', 6)->nullable();

            $table->boolean('NoIndex')->default(false);

            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->index(['Status', 'TerbitPada'], 'IdxHalamanPemasaranJadwal');
            $table->index(['Tipe', 'Segmen'], 'IdxHalamanPemasaranTipe');
            $table->foreign('KampanyeId')->references('Id')->on('Kampanye')->nullOnDelete();
        });

        Schema::create('VersiHalamanPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('HalamanPemasaranId', 26);
            $table->unsignedInteger('Nomor');

            $table->string('Judul', 190);
            $table->string('MetaJudul', 190)->nullable();
            $table->string('MetaDeskripsi', 500)->nullable();
            $table->string('Kanonik', 500)->nullable();
            $table->string('OgJudul', 190)->nullable();
            $table->string('OgDeskripsi', 500)->nullable();
            $table->string('OgGambar', 500)->nullable();
            $table->string('SkemaTipe', 60)->nullable();
            $table->string('Catatan', 500)->nullable();

            $table->char('DibuatOlehPlatformId', 26)->nullable();
            $table->dateTime('DibuatPada', 6);

            $table->unique(['HalamanPemasaranId', 'Nomor'], 'UnqVersiHalamanNomor');
            $table->foreign('HalamanPemasaranId')
                ->references('Id')->on('HalamanPemasaran')->cascadeOnDelete();
            $table->foreign('DibuatOlehPlatformId')
                ->references('Id')->on('AdminPlatform')->nullOnDelete();
        });

        Schema::create('BlokHalamanPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('VersiHalamanPemasaranId', 26);
            $table->string('Jenis', 40);
            $table->unsignedInteger('Urutan');
            $table->json('Isi');
            $table->char('FormulirPemasaranId', 26)->nullable();

            $table->index(['VersiHalamanPemasaranId', 'Urutan'], 'IdxBlokHalamanUrutan');
            $table->foreign('VersiHalamanPemasaranId')
                ->references('Id')->on('VersiHalamanPemasaran')->cascadeOnDelete();
            $table->foreign('FormulirPemasaranId')
                ->references('Id')->on('FormulirPemasaran')->nullOnDelete();
        });

        // Ditambahkan terpisah: kedua tabel saling menunjuk, sehingga salah satu
        // kuncinya baru dapat dipasang setelah keduanya ada.
        Schema::table('HalamanPemasaran', function (Blueprint $table): void {
            $table->foreign('VersiTerbitId')
                ->references('Id')->on('VersiHalamanPemasaran')->nullOnDelete();
            $table->foreign('VersiDrafId')
                ->references('Id')->on('VersiHalamanPemasaran')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('HalamanPemasaran', function (Blueprint $table): void {
            $table->dropForeign(['VersiTerbitId']);
            $table->dropForeign(['VersiDrafId']);
        });

        Schema::dropIfExists('BlokHalamanPemasaran');
        Schema::dropIfExists('VersiHalamanPemasaran');
        Schema::dropIfExists('HalamanPemasaran');
    }
};
