<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PelaksanaanKalibrasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->char('RencanaKalibrasiId', 26)->nullable();
            $table->char('AsetId', 26);
            $table->char('JenisKalibrasiId', 26)->nullable();
            $table->char('PenyediaId', 26)->nullable();
            $table->char('PerintahKerjaId', 26)->nullable();
            $table->date('TanggalKalibrasi');
            $table->date('TanggalBerlakuSampai')->nullable();
            $table->string('Hasil', 40);
            $table->string('NomorSertifikat', 180)->nullable();
            $table->string('Laboratorium', 200)->nullable();
            $table->json('KondisiLingkungan')->nullable();
            $table->text('Catatan')->nullable();
            $table->char('DilaksanakanOleh', 26)->nullable();
            $table->char('DiverifikasiOleh', 26)->nullable();
            $table->dateTime('DiverifikasiPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqPelaksanaanKalibrasiNomor');
            $table->index(['AsetId', 'TanggalKalibrasi'], 'IdxPelaksanaanKalibrasiAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('RencanaKalibrasiId')->references('Id')->on('RencanaKalibrasi');
            $table->foreign('AsetId')->references('Id')->on('Aset');
            $table->foreign('JenisKalibrasiId')->references('Id')->on('JenisKalibrasi');
            $table->foreign('PenyediaId')->references('Id')->on('Penyedia');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
            $table->foreign('DilaksanakanOleh')->references('Id')->on('Pengguna');
            $table->foreign('DiverifikasiOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PelaksanaanKalibrasi');
    }
};
