<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PermintaanPersetujuan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AlurPersetujuanId', 26);
            $table->string('JenisEntitas', 80);
            $table->char('EntitasId', 26);
            $table->unsignedInteger('TahapSaatIni')->default(1);
            $table->string('Status', 40)->default('Menunggu');
            $table->char('DimintaOleh', 26);
            $table->dateTime('DimintaPada', 6)->useCurrent();
            $table->dateTime('SelesaiPada', 6)->nullable();
            $table->json('DataTambahan')->nullable();
            $table->index(['OrganisasiId', 'JenisEntitas', 'EntitasId', 'Status'], 'IdxPermintaanPersetujuanEntitas');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AlurPersetujuanId')->references('Id')->on('AlurPersetujuan');
            $table->foreign('DimintaOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PermintaanPersetujuan');
    }
};
