<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KategoriLokasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Kode', 50);
            $table->string('Nama', 120);
            $table->text('Keterangan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->unique(['OrganisasiId', 'Kode'], 'UqKategoriLokasiKode');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KategoriLokasi');
    }
};
