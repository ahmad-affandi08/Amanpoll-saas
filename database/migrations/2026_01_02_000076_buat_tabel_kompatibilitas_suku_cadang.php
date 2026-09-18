<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KompatibilitasSukuCadang', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('SukuCadangId', 26);
            $table->char('KategoriAsetId', 26)->nullable();
            $table->char('ModelAsetId', 26)->nullable();
            $table->char('AsetId', 26)->nullable();
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['SukuCadangId', 'ModelAsetId', 'AsetId'], 'IdxKompatibilitasSukuCadang');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('SukuCadangId')->references('Id')->on('SukuCadang');
            $table->foreign('KategoriAsetId')->references('Id')->on('KategoriAset');
            $table->foreign('ModelAsetId')->references('Id')->on('ModelAset');
            $table->foreign('AsetId')->references('Id')->on('Aset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KompatibilitasSukuCadang');
    }
};
