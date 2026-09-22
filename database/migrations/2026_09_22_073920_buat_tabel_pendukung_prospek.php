<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kontak, tag, aktivitas, riwayat tahap, dan rincian skor (MARKETING.md 24). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KontakProspek', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProspekId', 26);
            $table->string('Nama', 190);
            $table->string('Email', 190)->nullable();
            $table->string('Telepon', 60)->nullable();
            $table->string('Jabatan', 120)->nullable();
            $table->boolean('Utama')->default(false);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['ProspekId'], 'IdxKontakProspek');
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->cascadeOnDelete();
        });

        Schema::create('TagProspek', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Nama', 80);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['Nama'], 'UqTagProspekNama');
        });

        Schema::create('ProspekTag', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProspekId', 26);
            $table->char('TagProspekId', 26);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['ProspekId', 'TagProspekId'], 'UqProspekTag');
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->cascadeOnDelete();
            $table->foreign('TagProspekId')->references('Id')->on('TagProspek')->cascadeOnDelete();
        });

        // Perubahan tahap wajib tercatat (MARKETING.md 5.3).
        Schema::create('RiwayatTahapProspek', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProspekId', 26);
            $table->char('TahapSebelumId', 26)->nullable();
            $table->char('TahapSesudahId', 26);
            $table->char('AktorPlatformId', 26)->nullable();
            $table->string('Alasan', 255)->nullable();
            $table->dateTime('BerpindahPada', 6)->useCurrent();
            $table->index(['ProspekId', 'BerpindahPada'], 'IdxRiwayatTahapProspek');
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->cascadeOnDelete();
            $table->foreign('TahapSebelumId')->references('Id')->on('TahapPipeline')->nullOnDelete();
            $table->foreign('TahapSesudahId')->references('Id')->on('TahapPipeline')->cascadeOnDelete();
            $table->foreign('AktorPlatformId')->references('Id')->on('AdminPlatform')->nullOnDelete();
        });

        Schema::create('AktivitasProspek', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProspekId', 26);
            $table->char('AktorPlatformId', 26)->nullable();
            $table->string('Jenis', 60);
            $table->string('Judul', 190);
            $table->text('Isi')->nullable();
            $table->dateTime('TerjadiPada', 6)->useCurrent();
            $table->index(['ProspekId', 'TerjadiPada'], 'IdxAktivitasProspek');
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->cascadeOnDelete();
            $table->foreign('AktorPlatformId')->references('Id')->on('AdminPlatform')->nullOnDelete();
        });

        // Rincian skor, bukan hanya totalnya.
        Schema::create('SkorProspek', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProspekId', 26);
            $table->string('Peristiwa', 80);
            $table->integer('Bobot');
            $table->dateTime('DihitungPada', 6)->useCurrent();
            $table->unique(['ProspekId', 'Peristiwa'], 'UqSkorProspekPeristiwa');
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SkorProspek');
        Schema::dropIfExists('AktivitasProspek');
        Schema::dropIfExists('RiwayatTahapProspek');
        Schema::dropIfExists('ProspekTag');
        Schema::dropIfExists('TagProspek');
        Schema::dropIfExists('KontakProspek');
    }
};
