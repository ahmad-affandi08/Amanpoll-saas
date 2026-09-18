<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('HasilTitikUkurKalibrasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PelaksanaanKalibrasiId', 26);
            $table->char('TitikUkurKalibrasiId', 26)->nullable();
            $table->string('NamaTitik', 160)->nullable();
            $table->decimal('NilaiReferensi', 20, 8)->nullable();
            $table->decimal('NilaiTerukur', 20, 8)->nullable();
            $table->decimal('Koreksi', 20, 8)->nullable();
            $table->decimal('Ketidakpastian', 20, 8)->nullable();
            $table->string('Satuan', 50)->nullable();
            $table->string('Hasil', 40)->nullable();
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['PelaksanaanKalibrasiId'], 'IdxHasilKalibrasi');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PelaksanaanKalibrasiId')->references('Id')->on('PelaksanaanKalibrasi');
            $table->foreign('TitikUkurKalibrasiId')->references('Id')->on('TitikUkurKalibrasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('HasilTitikUkurKalibrasi');
    }
};
