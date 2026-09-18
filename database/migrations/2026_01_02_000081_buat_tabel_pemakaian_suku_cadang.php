<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PemakaianSukuCadang', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PerintahKerjaId', 26);
            $table->char('SukuCadangId', 26);
            $table->char('GudangId', 26)->nullable();
            $table->char('KelompokSukuCadangId', 26)->nullable();
            $table->decimal('Jumlah', 20, 4);
            $table->decimal('HargaSatuan', 20, 2)->nullable();
            $table->char('MutasiStokId', 26)->nullable();
            $table->char('DipakaiOleh', 26)->nullable();
            $table->dateTime('DipakaiPada', 6)->useCurrent();
            $table->index(['PerintahKerjaId', 'SukuCadangId'], 'IdxPemakaianSukuCadang');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
            $table->foreign('SukuCadangId')->references('Id')->on('SukuCadang');
            $table->foreign('GudangId')->references('Id')->on('Gudang');
            $table->foreign('KelompokSukuCadangId')->references('Id')->on('KelompokSukuCadang');
            $table->foreign('MutasiStokId')->references('Id')->on('MutasiStok');
            $table->foreign('DipakaiOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PemakaianSukuCadang');
    }
};
