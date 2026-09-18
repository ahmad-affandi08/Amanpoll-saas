<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PenilaianUsulanAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('UsulanAsetId', 26);
            $table->string('Kriteria', 160);
            $table->decimal('Bobot', 8, 4)->default(1);
            $table->decimal('Nilai', 8, 4);
            $table->decimal('Skor', 12, 4);
            $table->char('DinilaiOleh', 26)->nullable();
            $table->dateTime('DinilaiPada', 6)->useCurrent();
            $table->index(['UsulanAsetId'], 'IdxPenilaianUsulanAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('UsulanAsetId')->references('Id')->on('UsulanAset');
            $table->foreign('DinilaiOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PenilaianUsulanAset');
    }
};
