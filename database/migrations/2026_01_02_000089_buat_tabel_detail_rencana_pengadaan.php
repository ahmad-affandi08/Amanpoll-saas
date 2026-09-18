<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DetailRencanaPengadaan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('RencanaPengadaanId', 26);
            $table->char('UsulanAsetId', 26)->nullable();
            $table->char('SukuCadangId', 26)->nullable();
            $table->string('Deskripsi', 255);
            $table->decimal('Jumlah', 20, 4);
            $table->string('Satuan', 50);
            $table->decimal('HargaEstimasi', 20, 2)->nullable();
            $table->unsignedTinyInteger('BulanRencana')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['RencanaPengadaanId'], 'IdxDetailRencanaPengadaan');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('RencanaPengadaanId')->references('Id')->on('RencanaPengadaan');
            $table->foreign('UsulanAsetId')->references('Id')->on('UsulanAset');
            $table->foreign('SukuCadangId')->references('Id')->on('SukuCadang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DetailRencanaPengadaan');
    }
};
