<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PengirimanPanggilanBalikWeb', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PanggilanBalikWebId', 26);
            $table->string('Peristiwa', 120);
            $table->json('MuatanData');
            $table->unsignedSmallInteger('StatusHttp')->nullable();
            $table->longText('Respons')->nullable();
            $table->string('Status', 40)->default('Antri');
            $table->unsignedInteger('Percobaan')->default(0);
            $table->dateTime('JadwalCobaLagiPada', 6)->nullable();
            $table->dateTime('DikirimPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['Status', 'JadwalCobaLagiPada'], 'IdxPengirimanPanggilanBalikWebAntrian');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PanggilanBalikWebId')->references('Id')->on('PanggilanBalikWeb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PengirimanPanggilanBalikWeb');
    }
};
