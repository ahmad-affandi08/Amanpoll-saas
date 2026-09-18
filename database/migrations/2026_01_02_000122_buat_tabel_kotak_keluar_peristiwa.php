<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KotakKeluarPeristiwa', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26)->nullable();
            $table->string('NamaPeristiwa', 160);
            $table->string('JenisAgregat', 100)->nullable();
            $table->char('AgregatId', 26)->nullable();
            $table->json('MuatanData');
            $table->string('Status', 40)->default('Menunggu');
            $table->unsignedInteger('Percobaan')->default(0);
            $table->dateTime('TersediaPada', 6)->useCurrent();
            $table->dateTime('DiprosesPada', 6)->nullable();
            $table->text('KesalahanTerakhir')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['Status', 'TersediaPada'], 'IdxKotakKeluarPeristiwa');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KotakKeluarPeristiwa');
    }
};
