<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('RelasiAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AsetIndukId', 26);
            $table->char('AsetAnakId', 26);
            $table->string('JenisRelasi', 60)->default('Komponen');
            $table->decimal('Jumlah', 14, 4)->default(1);
            $table->date('MulaiPada')->nullable();
            $table->date('SelesaiPada')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['AsetIndukId', 'AsetAnakId', 'JenisRelasi'], 'UqRelasiAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AsetIndukId')->references('Id')->on('Aset');
            $table->foreign('AsetAnakId')->references('Id')->on('Aset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RelasiAset');
    }
};
