<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PemetaanDataEksternal', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('IntegrasiEksternalId', 26);
            $table->string('JenisEntitas', 80);
            $table->char('EntitasId', 26);
            $table->string('KodeEksternal', 255);
            $table->json('DataTambahan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['IntegrasiEksternalId', 'JenisEntitas', 'EntitasId'], 'UqPemetaanDataEksternal');
            $table->index(['IntegrasiEksternalId', 'KodeEksternal'], 'IdxPemetaanKodeEksternal');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('IntegrasiEksternalId')->references('Id')->on('IntegrasiEksternal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PemetaanDataEksternal');
    }
};
