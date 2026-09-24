<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Login tanpa kode organisasi (PRD 8.1) mencari akun menurut email di seluruh
 * organisasi. Indeks unik `(OrganisasiId, Email)` tidak menolong pencarian yang
 * tidak menyaring organisasi, sehingga setiap login dan lupa kata sandi
 * memindai seluruh tabel pengguna semua tenant tanpa indeks ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Pengguna', function (Blueprint $table): void {
            $table->index('Email', 'IdxPenggunaEmail');
        });
    }

    public function down(): void
    {
        Schema::table('Pengguna', function (Blueprint $table): void {
            $table->dropIndex('IdxPenggunaEmail');
        });
    }
};
