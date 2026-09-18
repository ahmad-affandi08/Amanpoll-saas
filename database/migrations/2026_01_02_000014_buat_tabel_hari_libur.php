<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('HariLibur', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('LokasiId', 26)->nullable();
            $table->date('Tanggal');
            $table->string('Nama', 180);
            $table->boolean('BerulangTahunan')->default(0);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['OrganisasiId', 'LokasiId', 'Tanggal', 'Nama'], 'UqHariLibur');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('LokasiId')->references('Id')->on('Lokasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('HariLibur');
    }
};
