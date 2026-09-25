<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email dan WhatsApp milik organisasi untuk notifikasi stafnya (PRD 8.23).
 *
 * Kembaran `PenyediaLayananPlatform` per organisasi. Tanpa baris aktif di sini,
 * notifikasi organisasi berangkat lewat penyedia platform seperti sebelumnya.
 * Kolom `Terakhir*` dan `GalatTerakhir` merekam kesehatan pengiriman supaya admin
 * organisasi melihat penyedianya bermasalah sebelum stafnya mengeluh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PenyediaLayananOrganisasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Kategori', 30);
            $table->string('Kode', 50);
            $table->boolean('Aktif')->default(false);
            $table->boolean('ModeUji')->default(false);
            $table->longText('KredensialTerenkripsi')->nullable();
            $table->char('SidikKredensial', 64)->nullable();
            $table->dateTime('TerakhirBerhasilPada', 6)->nullable();
            $table->dateTime('TerakhirGagalPada', 6)->nullable();
            $table->string('GalatTerakhir', 300)->nullable();
            $table->char('DiperbaruiOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent();
            $table->unique(['OrganisasiId', 'Kategori', 'Kode'], 'UqPenyediaLayananOrganisasi');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi')->cascadeOnDelete();
            $table->foreign('DiperbaruiOleh')->references('Id')->on('Pengguna')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PenyediaLayananOrganisasi');
    }
};
