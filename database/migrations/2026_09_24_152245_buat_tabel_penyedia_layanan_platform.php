<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penyedia layanan luar yang diatur dari konsol platform (PRD 8.23): payment gateway
 * dan WhatsApp. Satu baris per penyedia per kategori, tanpa `OrganisasiId` karena
 * yang memakainya adalah Amanpoll sendiri (tagihan langganan, pesan pemasaran).
 *
 * Kredensial harus bisa dibaca ulang untuk memanggil API penyedia, jadi disimpan
 * terenkripsi (`KredensialTerenkripsi`), bukan di-hash. `SidikKredensial` adalah
 * hash SHA-256-nya: dipakai untuk menandai perubahan di audit tanpa menyimpan nilainya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PenyediaLayananPlatform', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kategori', 30);
            $table->string('Kode', 50);
            $table->boolean('Aktif')->default(false);
            $table->boolean('Utama')->default(false);
            $table->boolean('ModeUji')->default(true);
            $table->longText('KredensialTerenkripsi')->nullable();
            $table->char('SidikKredensial', 64)->nullable();
            $table->char('DiperbaruiOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent();
            $table->unique(['Kategori', 'Kode'], 'UqPenyediaLayananPlatform');
            $table->foreign('DiperbaruiOleh')->references('Id')->on('AdminPlatform')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PenyediaLayananPlatform');
    }
};
