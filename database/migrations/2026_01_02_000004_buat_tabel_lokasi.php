<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Lokasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('UnitOrganisasiId', 26)->nullable();
            $table->char('KategoriLokasiId', 26)->nullable();
            $table->char('IndukId', 26)->nullable();
            $table->string('Kode', 60);
            $table->string('Nama', 180);
            $table->text('Alamat')->nullable();
            $table->string('Lantai', 30)->nullable();
            $table->decimal('Latitude', 10, 7)->nullable();
            $table->decimal('Longitude', 10, 7)->nullable();
            $table->string('ZonaWaktu', 64)->nullable();
            $table->string('Status', 30)->default('Aktif');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->unique(['OrganisasiId', 'Kode'], 'UqLokasiKode');
            $table->index(['IndukId'], 'IdxLokasiInduk');
            $table->index(['UnitOrganisasiId'], 'IdxLokasiUnit');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('UnitOrganisasiId')->references('Id')->on('UnitOrganisasi');
            $table->foreign('KategoriLokasiId')->references('Id')->on('KategoriLokasi');
            $table->foreign('IndukId')->references('Id')->on('Lokasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Lokasi');
    }
};
