<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PenugasanPerintahKerja', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PerintahKerjaId', 26);
            $table->char('PenggunaId', 26);
            $table->string('PeranTugas', 60)->default('Teknisi');
            $table->char('DitugaskanOleh', 26)->nullable();
            $table->dateTime('DitugaskanPada', 6)->useCurrent();
            $table->dateTime('DiterimaPada', 6)->nullable();
            $table->dateTime('SelesaiPada', 6)->nullable();
            $table->string('Status', 40)->default('Ditugaskan');
            $table->index(['PenggunaId', 'Status', 'DitugaskanPada'], 'IdxPenugasanPerintahKerja');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
            $table->foreign('PenggunaId')->references('Id')->on('Pengguna');
            $table->foreign('DitugaskanOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PenugasanPerintahKerja');
    }
};
