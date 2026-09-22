<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Eksekusi otomasi beserta log per langkahnya (MARKETING.md 17). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('EksekusiOtomasiPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OtomasiPemasaranId', 26);
            // Versi dikunci saat eksekusi lahir: menyunting otomasi tidak mengubah yang sudah berjalan.
            $table->char('VersiOtomasiPemasaranId', 26);
            $table->char('EventPemasaranId', 26)->nullable();
            $table->char('ProspekId', 26)->nullable();
            $table->char('OrganisasiId', 26)->nullable();

            // Kunci dari versi dan peristiwanya: peristiwa yang sama tidak dapat melahirkan eksekusi kedua.
            $table->string('KunciIdempotensi', 190)->unique('UnqEksekusiOtomasiIdempotensi');

            $table->string('Status', 20);
            $table->unsignedInteger('LangkahBerikutnya')->default(0);
            $table->unsignedInteger('Percobaan')->default(0);
            $table->dateTime('LanjutPada', 6)->nullable();
            $table->string('Galat', 500)->nullable();
            $table->dateTime('DimulaiPada', 6);
            $table->dateTime('SelesaiPada', 6)->nullable();
            $table->dateTime('DiperbaruiPada', 6);

            $table->index(['Status', 'LanjutPada'], 'IdxEksekusiOtomasiAntre');
            $table->index(['OtomasiPemasaranId', 'Status'], 'IdxEksekusiOtomasiPerOtomasi');
            $table->foreign('OtomasiPemasaranId')->references('Id')->on('OtomasiPemasaran')->cascadeOnDelete();
            $table->foreign('VersiOtomasiPemasaranId')
                ->references('Id')->on('VersiOtomasiPemasaran')->cascadeOnDelete();
            $table->foreign('EventPemasaranId')->references('Id')->on('EventPemasaran')->nullOnDelete();
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->nullOnDelete();
        });

        // Hanya-tambah: log ini yang menjawab "apa yang sudah benar-benar dijalankan" saat dicoba ulang.
        Schema::create('LogEksekusiOtomasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('EksekusiOtomasiPemasaranId', 26);
            $table->char('LangkahOtomasiPemasaranId', 26)->nullable();
            $table->unsignedInteger('Urutan');
            $table->string('Jenis', 20);
            $table->string('Hasil', 30);
            $table->string('Ringkasan', 500)->nullable();
            $table->dateTime('TerjadiPada', 6);

            $table->index(['EksekusiOtomasiPemasaranId', 'Urutan'], 'IdxLogEksekusiLangkah');
            $table->foreign('EksekusiOtomasiPemasaranId')
                ->references('Id')->on('EksekusiOtomasiPemasaran')->cascadeOnDelete();
            $table->foreign('LangkahOtomasiPemasaranId')
                ->references('Id')->on('LangkahOtomasiPemasaran')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('LogEksekusiOtomasi');
        Schema::dropIfExists('EksekusiOtomasiPemasaran');
    }
};
