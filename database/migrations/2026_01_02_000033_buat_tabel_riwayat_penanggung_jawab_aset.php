<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('RiwayatPenanggungJawabAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AsetId', 26);
            $table->char('PenggunaId', 26)->nullable();
            $table->char('UnitOrganisasiId', 26)->nullable();
            $table->dateTime('MulaiPada', 6);
            $table->dateTime('SelesaiPada', 6)->nullable();
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['AsetId', 'MulaiPada'], 'IdxPenanggungJawabAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AsetId')->references('Id')->on('Aset');
            $table->foreign('PenggunaId')->references('Id')->on('Pengguna');
            $table->foreign('UnitOrganisasiId')->references('Id')->on('UnitOrganisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RiwayatPenanggungJawabAset');
    }
};
