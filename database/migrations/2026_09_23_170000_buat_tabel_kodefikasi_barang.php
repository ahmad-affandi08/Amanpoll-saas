<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KodeBarang', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            // SimakBmn (PMK 29/2010) atau Simbada (Permendagri 108/2016).
            $table->string('Standar', 20);
            $table->string('Kode', 40);
            $table->string('Uraian', 255);
            $table->boolean('Aktif')->default(true);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->unique(['OrganisasiId', 'Standar', 'Kode'], 'UqKodeBarang');
            $table->index(['OrganisasiId', 'Standar', 'Uraian'], 'IdxKodeBarangUraian');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });

        Schema::create('KodeBarangAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AsetId', 26);
            $table->char('KodeBarangId', 26);
            // Didenormalkan supaya "satu aset satu kode per standar" dijaga basis
            // data, bukan hanya oleh kode program.
            $table->string('Standar', 20);
            // Nomor Urut Pendaftaran: enam angka, berurut per kode barang.
            $table->unsignedInteger('Nup');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'AsetId', 'Standar'], 'UqKodeBarangAsetStandar');
            $table->unique(['OrganisasiId', 'KodeBarangId', 'Nup'], 'UqKodeBarangAsetNup');
            $table->index(['OrganisasiId', 'Standar'], 'IdxKodeBarangAsetStandar');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AsetId')->references('Id')->on('Aset');
            $table->foreign('KodeBarangId')->references('Id')->on('KodeBarang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KodeBarangAset');
        Schema::dropIfExists('KodeBarang');
    }
};
