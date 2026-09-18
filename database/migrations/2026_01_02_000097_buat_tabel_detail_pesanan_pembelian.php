<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DetailPesananPembelian', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PesananPembelianId', 26);
            $table->string('JenisItem', 40);
            $table->char('SukuCadangId', 26)->nullable();
            $table->string('Deskripsi', 255);
            $table->decimal('Jumlah', 20, 4);
            $table->string('Satuan', 50);
            $table->decimal('HargaSatuan', 20, 2);
            $table->decimal('Diskon', 20, 2)->default(0);
            $table->decimal('Pajak', 20, 2)->default(0);
            $table->decimal('Total', 20, 2);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['PesananPembelianId'], 'IdxDetailPesananPembelian');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PesananPembelianId')->references('Id')->on('PesananPembelian');
            $table->foreign('SukuCadangId')->references('Id')->on('SukuCadang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DetailPesananPembelian');
    }
};
