<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Inspeksi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->char('TemplatInspeksiId', 26);
            $table->char('AsetId', 26);
            $table->char('PelaksanaanDaftarPeriksaId', 26)->nullable();
            $table->dateTime('DijadwalkanPada', 6)->nullable();
            $table->dateTime('DilaksanakanPada', 6)->nullable();
            $table->string('Status', 40)->default('Terjadwal');
            $table->string('Hasil', 40)->nullable();
            $table->text('Temuan')->nullable();
            $table->text('TindakLanjut')->nullable();
            $table->char('PerintahKerjaId', 26)->nullable();
            $table->char('DilaksanakanOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqInspeksiNomor');
            $table->index(['AsetId', 'DijadwalkanPada', 'Status'], 'IdxInspeksiAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('TemplatInspeksiId')->references('Id')->on('TemplatInspeksi');
            $table->foreign('AsetId')->references('Id')->on('Aset');
            $table->foreign('PelaksanaanDaftarPeriksaId')->references('Id')->on('PelaksanaanDaftarPeriksa');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
            $table->foreign('DilaksanakanOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Inspeksi');
    }
};
