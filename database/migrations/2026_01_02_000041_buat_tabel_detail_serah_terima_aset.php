<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DetailSerahTerimaAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('SerahTerimaAsetId', 26);
            $table->char('AsetId', 26);
            $table->string('KondisiSaatDiserahkan', 60)->nullable();
            $table->string('KondisiSaatDiterima', 60)->nullable();
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['SerahTerimaAsetId', 'AsetId'], 'UqDetailSerahTerimaAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('SerahTerimaAsetId')->references('Id')->on('SerahTerimaAset');
            $table->foreign('AsetId')->references('Id')->on('Aset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DetailSerahTerimaAset');
    }
};
