<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SertifikasiAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AsetId', 26);
            $table->string('JenisSertifikasi', 120);
            $table->string('NomorSertifikat', 180)->nullable();
            $table->string('Penerbit', 180)->nullable();
            $table->date('TerbitPada')->nullable();
            $table->date('BerlakuSampai')->nullable();
            $table->string('Status', 40)->default('Aktif');
            $table->char('BerkasId', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['AsetId', 'BerlakuSampai', 'Status'], 'IdxSertifikasiAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AsetId')->references('Id')->on('Aset');
            $table->foreign('BerkasId')->references('Id')->on('Berkas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SertifikasiAset');
    }
};
