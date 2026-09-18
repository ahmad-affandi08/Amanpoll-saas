<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('UsulanAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->char('UnitOrganisasiId', 26);
            $table->char('KategoriAsetId', 26)->nullable();
            $table->char('ModelAsetId', 26)->nullable();
            $table->string('NamaKebutuhan', 220);
            $table->decimal('Jumlah', 14, 4)->default(1);
            $table->decimal('EstimasiHargaSatuan', 20, 2)->nullable();
            $table->text('Alasan');
            $table->string('JenisKebutuhan', 60)->nullable();
            $table->unsignedSmallInteger('TahunKebutuhan')->nullable();
            $table->string('Prioritas', 40)->default('Normal');
            $table->string('Status', 40)->default('Draft');
            $table->char('DiajukanOleh', 26);
            $table->dateTime('DiajukanPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqUsulanAsetNomor');
            $table->index(['OrganisasiId', 'Status', 'TahunKebutuhan'], 'IdxUsulanAsetStatus');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('UnitOrganisasiId')->references('Id')->on('UnitOrganisasi');
            $table->foreign('KategoriAsetId')->references('Id')->on('KategoriAset');
            $table->foreign('ModelAsetId')->references('Id')->on('ModelAset');
            $table->foreign('DiajukanOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('UsulanAset');
    }
};
