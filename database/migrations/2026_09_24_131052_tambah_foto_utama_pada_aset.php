<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto utama aset (PRD 8.4 "Foto Aset"): salah satu lampiran berkategori
 * `FotoAset` yang tampil di daftar aset, detail aset, dan layar Mode Lapangan.
 *
 * Kolomnya nullable: aset tanpa foto menampilkan ikon 3D kategorinya. Tidak
 * diberi indeks tersendiri karena tidak pernah disaring atau diurutkan;
 * kunci asingnya sudah membuat indeks yang dibutuhkan MariaDB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Aset', function (Blueprint $table): void {
            $table->char('FotoUtamaBerkasId', 26)->nullable()->after('KodeBatang');
            $table->foreign('FotoUtamaBerkasId')->references('Id')->on('Berkas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('Aset', function (Blueprint $table): void {
            $table->dropForeign(['FotoUtamaBerkasId']);
            $table->dropColumn('FotoUtamaBerkasId');
        });
    }
};
