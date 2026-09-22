<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tahap pipeline prospek (MARKETING.md 5.3, 24). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TahapPipeline', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 50);
            $table->string('Nama', 120);
            $table->unsignedSmallInteger('Urutan')->default(0);
            $table->boolean('TahapAkhir')->default(false);
            $table->boolean('DianggapMenang')->default(false);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['Kode'], 'UqTahapPipelineKode');
            $table->index(['Urutan'], 'IdxTahapPipelineUrutan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TahapPipeline');
    }
};
