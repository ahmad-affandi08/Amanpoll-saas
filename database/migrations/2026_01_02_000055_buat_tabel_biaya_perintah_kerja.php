<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('BiayaPerintahKerja', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PerintahKerjaId', 26);
            $table->string('JenisBiaya', 60);
            $table->string('Deskripsi', 255)->nullable();
            $table->decimal('Jumlah', 20, 2);
            $table->char('MataUang', 3)->default('IDR');
            $table->char('PenyediaId', 26)->nullable();
            $table->date('TanggalBiaya');
            $table->char('DibuatOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['PerintahKerjaId', 'JenisBiaya'], 'IdxBiayaPerintahKerja');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
            $table->foreign('PenyediaId')->references('Id')->on('Penyedia');
            $table->foreign('DibuatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('BiayaPerintahKerja');
    }
};
