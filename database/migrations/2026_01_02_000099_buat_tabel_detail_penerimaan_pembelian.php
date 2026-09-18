<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DetailPenerimaanPembelian', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PenerimaanPembelianId', 26);
            $table->char('DetailPesananPembelianId', 26)->nullable();
            $table->char('SukuCadangId', 26)->nullable();
            $table->decimal('JumlahDipesan', 20, 4);
            $table->decimal('JumlahDiterima', 20, 4);
            $table->decimal('JumlahDitolak', 20, 4)->default(0);
            $table->string('Kondisi', 40)->nullable();
            $table->json('NomorSeriJson')->nullable();
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PenerimaanPembelianId')->references('Id')->on('PenerimaanPembelian');
            $table->foreign('DetailPesananPembelianId')->references('Id')->on('DetailPesananPembelian');
            $table->foreign('SukuCadangId')->references('Id')->on('SukuCadang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DetailPenerimaanPembelian');
    }
};
