<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DetailPermintaanPembelian', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PermintaanPembelianId', 26);
            $table->string('JenisItem', 40);
            $table->char('AsetReferensiId', 26)->nullable();
            $table->char('SukuCadangId', 26)->nullable();
            $table->string('Deskripsi', 255);
            $table->decimal('Jumlah', 20, 4);
            $table->string('Satuan', 50);
            $table->decimal('HargaEstimasi', 20, 2)->nullable();
            $table->text('Spesifikasi')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['PermintaanPembelianId'], 'IdxDetailPermintaanPembelian');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PermintaanPembelianId')->references('Id')->on('PermintaanPembelian');
            $table->foreign('AsetReferensiId')->references('Id')->on('Aset');
            $table->foreign('SukuCadangId')->references('Id')->on('SukuCadang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DetailPermintaanPembelian');
    }
};
