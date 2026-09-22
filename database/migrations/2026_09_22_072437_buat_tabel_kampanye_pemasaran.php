<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kampanye pemasaran (MARKETING.md 13, 24). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Kampanye', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 100);
            $table->string('Nama', 180);
            $table->string('Objective', 40);
            $table->string('Status', 30)->default('Draf');
            $table->date('MulaiPada')->nullable();
            $table->date('SelesaiPada')->nullable();
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['Kode'], 'UqKampanyeKode');
            $table->index(['Status', 'MulaiPada'], 'IdxKampanyeStatus');
        });

        Schema::create('KampanyeChannel', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('KampanyeId', 26);
            $table->string('Channel', 40);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['KampanyeId', 'Channel'], 'UqKampanyeChannel');
            $table->foreign('KampanyeId')->references('Id')->on('Kampanye')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KampanyeChannel');
        Schema::dropIfExists('Kampanye');
    }
};
