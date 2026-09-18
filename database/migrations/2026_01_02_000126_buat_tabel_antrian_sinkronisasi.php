<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AntrianSinkronisasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PerangkatPenggunaId', 26);
            $table->string('KunciOperasi', 255);
            $table->string('JenisEntitas', 80);
            $table->char('EntitasId', 26)->nullable();
            $table->string('Operasi', 30);
            $table->unsignedInteger('VersiKlien')->nullable();
            $table->json('MuatanData');
            $table->string('Status', 40)->default('Menunggu');
            $table->json('Konflik')->nullable();
            $table->unsignedInteger('Percobaan')->default(0);
            $table->dateTime('DiterimaPada', 6)->useCurrent();
            $table->dateTime('DiprosesPada', 6)->nullable();
            $table->unique(['PerangkatPenggunaId', 'KunciOperasi'], 'UqAntrianSinkronisasiOperasi');
            $table->index(['OrganisasiId', 'Status', 'DiterimaPada'], 'IdxAntrianSinkronisasi');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PerangkatPenggunaId')->references('Id')->on('PerangkatPengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('AntrianSinkronisasi');
    }
};
