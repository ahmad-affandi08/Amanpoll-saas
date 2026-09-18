<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AnalisisKegagalan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PerintahKerjaId', 26);
            $table->char('KodeMasalahId', 26)->nullable();
            $table->char('KodePenyebabId', 26)->nullable();
            $table->char('KodeTindakanId', 26)->nullable();
            $table->text('AkarMasalah')->nullable();
            $table->text('TindakanKorektif')->nullable();
            $table->text('TindakanPencegahan')->nullable();
            $table->char('DibuatOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['PerintahKerjaId'], 'UqAnalisisKegagalanPerintah');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
            $table->foreign('KodeMasalahId')->references('Id')->on('KodeKegagalan');
            $table->foreign('KodePenyebabId')->references('Id')->on('KodeKegagalan');
            $table->foreign('KodeTindakanId')->references('Id')->on('KodeKegagalan');
            $table->foreign('DibuatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('AnalisisKegagalan');
    }
};
