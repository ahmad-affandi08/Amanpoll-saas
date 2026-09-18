<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('GaransiAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AsetId', 26);
            $table->char('PenyediaId', 26)->nullable();
            $table->string('NomorGaransi', 160)->nullable();
            $table->string('JenisGaransi', 60)->nullable();
            $table->date('MulaiPada');
            $table->date('BerakhirPada');
            $table->text('Cakupan')->nullable();
            $table->string('Status', 30)->default('Aktif');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->index(['AsetId', 'BerakhirPada'], 'IdxGaransiAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AsetId')->references('Id')->on('Aset');
            $table->foreign('PenyediaId')->references('Id')->on('Penyedia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('GaransiAset');
    }
};
