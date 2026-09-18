<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PenerimaanPembelian', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->char('PesananPembelianId', 26);
            $table->char('GudangId', 26)->nullable();
            $table->dateTime('TanggalTerima', 6);
            $table->string('NomorSuratJalan', 120)->nullable();
            $table->char('DiterimaOleh', 26)->nullable();
            $table->string('Status', 40)->default('Diterima');
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqPenerimaanPembelianNomor');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PesananPembelianId')->references('Id')->on('PesananPembelian');
            $table->foreign('GudangId')->references('Id')->on('Gudang');
            $table->foreign('DiterimaOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PenerimaanPembelian');
    }
};
