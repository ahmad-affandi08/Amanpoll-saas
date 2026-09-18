<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PermintaanPembelian', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->char('UnitOrganisasiId', 26)->nullable();
            $table->char('RencanaPengadaanId', 26)->nullable();
            $table->char('PosAnggaranId', 26)->nullable();
            $table->date('TanggalPermintaan');
            $table->date('TanggalDibutuhkan')->nullable();
            $table->string('Prioritas', 40)->default('Normal');
            $table->string('Status', 40)->default('Draft');
            $table->text('Alasan')->nullable();
            $table->char('DimintaOleh', 26);
            $table->decimal('TotalEstimasi', 20, 2)->default(0);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqPermintaanPembelianNomor');
            $table->index(['OrganisasiId', 'Status', 'TanggalPermintaan'], 'IdxPermintaanPembelianStatus');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('UnitOrganisasiId')->references('Id')->on('UnitOrganisasi');
            $table->foreign('RencanaPengadaanId')->references('Id')->on('RencanaPengadaan');
            $table->foreign('PosAnggaranId')->references('Id')->on('PosAnggaran');
            $table->foreign('DimintaOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PermintaanPembelian');
    }
};
