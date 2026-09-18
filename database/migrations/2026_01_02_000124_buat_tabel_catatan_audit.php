<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('CatatanAudit', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26)->nullable();
            $table->char('PenggunaId', 26)->nullable();
            $table->string('Aksi', 100);
            $table->string('JenisEntitas', 100);
            $table->char('EntitasId', 26)->nullable();
            $table->json('DataSebelum')->nullable();
            $table->json('DataSesudah')->nullable();
            $table->string('AlamatIp', 64)->nullable();
            $table->text('AgenPengguna')->nullable();
            $table->string('KorelasiId', 100)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['OrganisasiId', 'JenisEntitas', 'EntitasId', 'DibuatPada'], 'IdxCatatanAuditEntitas');
            $table->index(['PenggunaId', 'DibuatPada'], 'IdxCatatanAuditPengguna');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PenggunaId')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CatatanAudit');
    }
};
