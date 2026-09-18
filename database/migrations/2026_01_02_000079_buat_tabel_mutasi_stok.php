<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MutasiStok', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->string('Jenis', 60);
            $table->char('GudangAsalId', 26)->nullable();
            $table->char('GudangTujuanId', 26)->nullable();
            $table->string('ReferensiJenis', 80)->nullable();
            $table->char('ReferensiId', 26)->nullable();
            $table->dateTime('Tanggal', 6)->useCurrent();
            $table->string('Status', 40)->default('Draft');
            $table->text('Catatan')->nullable();
            $table->char('DibuatOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqMutasiStokNomor');
            $table->index(['OrganisasiId', 'Tanggal', 'Jenis'], 'IdxMutasiStokTanggal');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('GudangAsalId')->references('Id')->on('Gudang');
            $table->foreign('GudangTujuanId')->references('Id')->on('Gudang');
            $table->foreign('DibuatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MutasiStok');
    }
};
