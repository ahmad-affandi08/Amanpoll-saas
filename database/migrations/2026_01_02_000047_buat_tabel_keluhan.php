<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Keluhan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->char('KategoriKeluhanId', 26)->nullable();
            $table->char('TingkatLayananId', 26)->nullable();
            $table->char('AsetId', 26)->nullable();
            $table->char('LokasiId', 26)->nullable();
            $table->string('Judul', 220);
            $table->text('Deskripsi');
            $table->string('Prioritas', 40)->default('Normal');
            $table->string('Status', 40)->default('Baru');
            $table->string('Sumber', 40)->default('Web');
            $table->char('PelaporId', 26)->nullable();
            $table->string('NamaPelaporEksternal', 180)->nullable();
            $table->string('KontakPelaporEksternal', 180)->nullable();
            $table->dateTime('DilaporkanPada', 6)->useCurrent();
            $table->dateTime('DiresponsPada', 6)->nullable();
            $table->dateTime('BatasResponsPada', 6)->nullable();
            $table->dateTime('BatasPenyelesaianPada', 6)->nullable();
            $table->dateTime('DiresolusikanPada', 6)->nullable();
            $table->dateTime('DitutupPada', 6)->nullable();
            $table->unsignedTinyInteger('Rating')->nullable();
            $table->text('Ulasan')->nullable();
            $table->unsignedInteger('Versi')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqKeluhanNomor');
            $table->index(['OrganisasiId', 'Status', 'Prioritas', 'DilaporkanPada'], 'IdxKeluhanStatus');
            $table->index(['AsetId', 'Status'], 'IdxKeluhanAset');
            $table->index(['OrganisasiId', 'Status', 'BatasResponsPada'], 'IdxKeluhanBatasRespons');
            $table->index(['OrganisasiId', 'Status', 'BatasPenyelesaianPada'], 'IdxKeluhanBatasPenyelesaian');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('KategoriKeluhanId')->references('Id')->on('KategoriKeluhan');
            $table->foreign('TingkatLayananId')->references('Id')->on('TingkatLayanan');
            $table->foreign('AsetId')->references('Id')->on('Aset');
            $table->foreign('LokasiId')->references('Id')->on('Lokasi');
            $table->foreign('PelaporId')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Keluhan');
    }
};
