<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Gudang', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('LokasiId', 26)->nullable();
            $table->string('Kode', 60);
            $table->string('Nama', 160);
            $table->char('PenanggungJawabId', 26)->nullable();
            $table->string('Status', 30)->default('Aktif');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Kode'], 'UqGudangKode');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('LokasiId')->references('Id')->on('Lokasi');
            $table->foreign('PenanggungJawabId')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Gudang');
    }
};
