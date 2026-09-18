<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KontrakAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('KontrakId', 26);
            $table->char('AsetId', 26);
            $table->date('MulaiPada')->nullable();
            $table->date('BerakhirPada')->nullable();
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['KontrakId', 'AsetId'], 'UqKontrakAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('KontrakId')->references('Id')->on('Kontrak');
            $table->foreign('AsetId')->references('Id')->on('Aset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KontrakAset');
    }
};
