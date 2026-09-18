<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PelaksanaanDaftarPeriksa', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('TemplatDaftarPeriksaId', 26);
            $table->char('PerintahKerjaId', 26)->nullable();
            $table->char('AsetId', 26)->nullable();
            $table->char('DilaksanakanOleh', 26)->nullable();
            $table->dateTime('MulaiPada', 6)->nullable();
            $table->dateTime('SelesaiPada', 6)->nullable();
            $table->string('Status', 40)->default('Draft');
            $table->decimal('Skor', 8, 2)->nullable();
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['PerintahKerjaId', 'AsetId', 'Status'], 'IdxPelaksanaanDaftarPeriksa');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('TemplatDaftarPeriksaId')->references('Id')->on('TemplatDaftarPeriksa');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
            $table->foreign('AsetId')->references('Id')->on('Aset');
            $table->foreign('DilaksanakanOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PelaksanaanDaftarPeriksa');
    }
};
