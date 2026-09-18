<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PermintaanMutasiAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->string('JenisMutasi', 60);
            $table->char('UnitAsalId', 26)->nullable();
            $table->char('UnitTujuanId', 26)->nullable();
            $table->char('LokasiAsalId', 26)->nullable();
            $table->char('LokasiTujuanId', 26)->nullable();
            $table->text('Alasan')->nullable();
            $table->string('Status', 40)->default('Draft');
            $table->char('DimintaOleh', 26);
            $table->dateTime('DimintaPada', 6)->useCurrent();
            $table->dateTime('DisetujuiPada', 6)->nullable();
            $table->dateTime('SelesaiPada', 6)->nullable();
            $table->unsignedInteger('Versi')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqPermintaanMutasiNomor');
            $table->index(['OrganisasiId', 'Status', 'DimintaPada'], 'IdxPermintaanMutasiStatus');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('UnitAsalId')->references('Id')->on('UnitOrganisasi');
            $table->foreign('UnitTujuanId')->references('Id')->on('UnitOrganisasi');
            $table->foreign('LokasiAsalId')->references('Id')->on('Lokasi');
            $table->foreign('LokasiTujuanId')->references('Id')->on('Lokasi');
            $table->foreign('DimintaOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PermintaanMutasiAset');
    }
};
