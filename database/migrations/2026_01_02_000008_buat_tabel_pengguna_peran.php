<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PenggunaPeran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PenggunaId', 26);
            $table->char('PeranId', 26);
            $table->char('UnitOrganisasiId', 26)->nullable();
            $table->char('LokasiId', 26)->nullable();
            $table->dateTime('BerlakuMulai', 6)->nullable();
            $table->dateTime('BerlakuSampai', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['PenggunaId', 'PeranId', 'UnitOrganisasiId', 'LokasiId'], 'UqPenggunaPeranScope');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PenggunaId')->references('Id')->on('Pengguna');
            $table->foreign('PeranId')->references('Id')->on('Peran');
            $table->foreign('UnitOrganisasiId')->references('Id')->on('UnitOrganisasi');
            $table->foreign('LokasiId')->references('Id')->on('Lokasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PenggunaPeran');
    }
};
