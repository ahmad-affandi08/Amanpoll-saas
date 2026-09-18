<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('IntegrasiEksternal', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Kode', 80);
            $table->string('Nama', 180);
            $table->string('Jenis', 80);
            $table->text('UrlDasar')->nullable();
            $table->string('MetodeAutentikasi', 60)->nullable();
            $table->json('KonfigurasiTerenkripsi')->nullable();
            $table->string('Status', 40)->default('Aktif');
            $table->dateTime('TerakhirSinkronPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Kode'], 'UqIntegrasiEksternal');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('IntegrasiEksternal');
    }
};
