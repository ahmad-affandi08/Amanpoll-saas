<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PerintahKerjaAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PerintahKerjaId', 26);
            $table->char('AsetId', 26);
            $table->boolean('Utama')->default(0);
            $table->string('KondisiAwal', 60)->nullable();
            $table->string('KondisiAkhir', 60)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['PerintahKerjaId', 'AsetId'], 'UqPerintahKerjaAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
            $table->foreign('AsetId')->references('Id')->on('Aset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PerintahKerjaAset');
    }
};
