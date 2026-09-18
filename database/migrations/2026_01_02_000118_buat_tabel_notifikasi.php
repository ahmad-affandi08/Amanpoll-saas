<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Notifikasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PenggunaId', 26)->nullable();
            $table->string('Kanal', 40);
            $table->string('JenisPeristiwa', 100);
            $table->string('Judul', 255)->nullable();
            $table->longText('Isi');
            $table->string('JenisEntitas', 80)->nullable();
            $table->char('EntitasId', 26)->nullable();
            $table->string('Status', 40)->default('Antri');
            $table->dateTime('JadwalKirimPada', 6)->nullable();
            $table->dateTime('DikirimPada', 6)->nullable();
            $table->dateTime('DibacaPada', 6)->nullable();
            $table->unsignedInteger('Percobaan')->default(0);
            $table->text('KesalahanTerakhir')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['PenggunaId', 'Status', 'DibuatPada'], 'IdxNotifikasiPengguna');
            $table->index(['OrganisasiId', 'Status', 'JadwalKirimPada'], 'IdxNotifikasiAntrian');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PenggunaId')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Notifikasi');
    }
};
