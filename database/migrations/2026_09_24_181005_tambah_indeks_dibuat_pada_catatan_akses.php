<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `catatan-akses:bersihkan` menghapus menurut `DibuatPada` saja, lintas pengguna
 * dan organisasi. Indeks yang ada, `(PenggunaId, DibuatPada)`, tidak dapat
 * dipakai untuk syarat itu karena `PenggunaId` di depannya tidak disaring,
 * sehingga setiap potongan penghapusan memindai seluruh tabel yang tumbuh tiap
 * login. Dengan indeks tunggal ini tiap potongan menjadi pindaian rentang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('CatatanAkses', function (Blueprint $table): void {
            $table->index('DibuatPada', 'IdxCatatanAksesDibuatPada');
        });
    }

    public function down(): void
    {
        Schema::table('CatatanAkses', function (Blueprint $table): void {
            $table->dropIndex('IdxCatatanAksesDibuatPada');
        });
    }
};
