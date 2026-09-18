<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ModelAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('KategoriAsetId', 26);
            $table->char('MerekId', 26)->nullable();
            $table->string('KodeModel', 100)->nullable();
            $table->string('Nama', 180);
            $table->string('Produsen', 180)->nullable();
            $table->json('Spesifikasi')->nullable();
            $table->unsignedInteger('IntervalPemeliharaanHari')->nullable();
            $table->unsignedInteger('IntervalKalibrasiHari')->nullable();
            $table->unsignedInteger('UmurManfaatBulan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->index(['OrganisasiId', 'KategoriAsetId'], 'IdxModelAsetKategori');
            $table->index(['MerekId'], 'IdxModelAsetMerek');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('KategoriAsetId')->references('Id')->on('KategoriAset');
            $table->foreign('MerekId')->references('Id')->on('Merek');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ModelAset');
    }
};
