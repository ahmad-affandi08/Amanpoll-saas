<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TitikUkurKalibrasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('JenisKalibrasiId', 26)->nullable();
            $table->char('KategoriAsetId', 26)->nullable();
            $table->string('Nama', 160);
            $table->string('Satuan', 50)->nullable();
            $table->decimal('NilaiReferensi', 20, 8)->nullable();
            $table->decimal('ToleransiMinus', 20, 8)->nullable();
            $table->decimal('ToleransiPlus', 20, 8)->nullable();
            $table->integer('Urutan')->default(0);
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('JenisKalibrasiId')->references('Id')->on('JenisKalibrasi');
            $table->foreign('KategoriAsetId')->references('Id')->on('KategoriAset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TitikUkurKalibrasi');
    }
};
