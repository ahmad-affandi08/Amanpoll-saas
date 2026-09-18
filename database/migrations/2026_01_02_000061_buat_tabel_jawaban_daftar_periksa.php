<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('JawabanDaftarPeriksa', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PelaksanaanDaftarPeriksaId', 26);
            $table->char('ButirTemplatDaftarPeriksaId', 26);
            $table->text('NilaiTeks')->nullable();
            $table->decimal('NilaiAngka', 20, 6)->nullable();
            $table->boolean('NilaiBoolean')->nullable();
            $table->dateTime('NilaiTanggal', 6)->nullable();
            $table->json('NilaiJson')->nullable();
            $table->boolean('Sesuai')->nullable();
            $table->text('Catatan')->nullable();
            $table->dateTime('DijawabPada', 6)->nullable();
            $table->unique(['PelaksanaanDaftarPeriksaId', 'ButirTemplatDaftarPeriksaId'], 'UqJawabanDaftarPeriksa');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PelaksanaanDaftarPeriksaId')->references('Id')->on('PelaksanaanDaftarPeriksa');
            $table->foreign('ButirTemplatDaftarPeriksaId')->references('Id')->on('ButirTemplatDaftarPeriksa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('JawabanDaftarPeriksa');
    }
};
