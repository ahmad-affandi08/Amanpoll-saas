<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Peristiwa pemasaran (MARKETING.md 23, 24). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('EventPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('PengenalPengunjung', 26)->nullable();
            $table->char('SesiPengunjungId', 26)->nullable();
            $table->string('Jenis', 80);
            $table->text('Url')->nullable();
            $table->json('DataTambahan')->nullable();
            $table->dateTime('TerjadiPada', 6)->useCurrent();
            $table->index(['PengenalPengunjung', 'TerjadiPada'], 'IdxEventPengunjung');
            $table->index(['Jenis', 'TerjadiPada'], 'IdxEventJenis');
            $table->foreign('SesiPengunjungId')->references('Id')->on('SesiPengunjung')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('EventPemasaran');
    }
};
