<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KategoriAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('IndukId', 26)->nullable();
            $table->string('Kode', 60);
            $table->string('Nama', 160);
            $table->unsignedInteger('UmurManfaatBulan')->nullable();
            $table->string('MetodePenyusutanBawaan', 40)->nullable();
            $table->decimal('PersentaseNilaiResidu', 8, 4)->nullable();
            $table->boolean('MemerlukanKalibrasi')->default(0);
            $table->boolean('MemerlukanPemeliharaan')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->unique(['OrganisasiId', 'Kode'], 'UqKategoriAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('IndukId')->references('Id')->on('KategoriAset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KategoriAset');
    }
};
