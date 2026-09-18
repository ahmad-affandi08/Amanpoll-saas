<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PermintaanPenawaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->char('PermintaanPembelianId', 26)->nullable();
            $table->dateTime('TanggalDibuka', 6);
            $table->dateTime('BatasPenawaran', 6)->nullable();
            $table->string('Status', 40)->default('Draft');
            $table->text('Catatan')->nullable();
            $table->char('DibuatOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqPermintaanPenawaranNomor');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PermintaanPembelianId')->references('Id')->on('PermintaanPembelian');
            $table->foreign('DibuatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PermintaanPenawaran');
    }
};
