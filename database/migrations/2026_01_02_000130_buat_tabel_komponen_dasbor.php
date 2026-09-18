<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KomponenDasbor', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('DasborTersimpanId', 26);
            $table->string('JenisKomponen', 80);
            $table->string('Judul', 180)->nullable();
            $table->json('Konfigurasi');
            $table->integer('PosisiX')->default(0);
            $table->integer('PosisiY')->default(0);
            $table->integer('Lebar')->default(4);
            $table->integer('Tinggi')->default(3);
            $table->integer('Urutan')->default(0);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('DasborTersimpanId')->references('Id')->on('DasborTersimpan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KomponenDasbor');
    }
};
