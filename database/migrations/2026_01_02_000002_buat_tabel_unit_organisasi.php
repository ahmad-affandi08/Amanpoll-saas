<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('UnitOrganisasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('IndukId', 26)->nullable();
            $table->string('Kode', 50);
            $table->string('Nama', 180);
            $table->string('Jenis', 60)->default('Unit');
            $table->string('Email', 180)->nullable();
            $table->string('Telepon', 50)->nullable();
            $table->string('Status', 30)->default('Aktif');
            $table->integer('Urutan')->default(0);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->unique(['OrganisasiId', 'Kode'], 'UqUnitOrganisasiKode');
            $table->index(['IndukId'], 'IdxUnitOrganisasiInduk');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('IndukId')->references('Id')->on('UnitOrganisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('UnitOrganisasi');
    }
};
