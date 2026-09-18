<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DetailMutasiStok', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('MutasiStokId', 26);
            $table->char('SukuCadangId', 26);
            $table->char('KelompokSukuCadangId', 26)->nullable();
            $table->decimal('Jumlah', 20, 4);
            $table->decimal('HargaSatuan', 20, 2)->nullable();
            $table->char('LokasiGudangAsalId', 26)->nullable();
            $table->char('LokasiGudangTujuanId', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['MutasiStokId', 'SukuCadangId'], 'IdxDetailMutasiStok');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('MutasiStokId')->references('Id')->on('MutasiStok');
            $table->foreign('SukuCadangId')->references('Id')->on('SukuCadang');
            $table->foreign('KelompokSukuCadangId')->references('Id')->on('KelompokSukuCadang');
            $table->foreign('LokasiGudangAsalId')->references('Id')->on('LokasiGudang');
            $table->foreign('LokasiGudangTujuanId')->references('Id')->on('LokasiGudang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DetailMutasiStok');
    }
};
