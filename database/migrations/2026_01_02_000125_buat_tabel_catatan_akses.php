<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('CatatanAkses', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26)->nullable();
            $table->char('PenggunaId', 26)->nullable();
            $table->string('Jenis', 80);
            $table->string('AlamatIp', 64)->nullable();
            $table->text('AgenPengguna')->nullable();
            $table->boolean('Berhasil')->default(1);
            $table->string('AlasanGagal', 255)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['PenggunaId', 'DibuatPada'], 'IdxCatatanAksesPengguna');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PenggunaId')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CatatanAkses');
    }
};
