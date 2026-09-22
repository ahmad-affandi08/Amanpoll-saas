<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parameter kampanye yang terbawa satu kedatangan (MARKETING.md 14, 24).
 *
 * Disimpan per sesi, bukan per pengunjung: satu orang dapat datang lewat iklan
 * hari ini dan lewat pencarian organik minggu depan, dan justru perbedaan itu
 * yang membedakan first touch dari last touch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('UtmPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('SesiPengunjungId', 26);
            $table->char('KampanyeId', 26)->nullable();
            $table->string('Source', 120)->nullable();
            $table->string('Medium', 120)->nullable();
            $table->string('Campaign', 190)->nullable();
            $table->string('Term', 190)->nullable();
            $table->string('Content', 190)->nullable();
            $table->dateTime('DirekamPada', 6)->useCurrent();
            $table->unique(['SesiPengunjungId'], 'UqUtmSesi');
            $table->index(['Source', 'Medium'], 'IdxUtmSumber');
            $table->foreign('SesiPengunjungId')->references('Id')->on('SesiPengunjung')->cascadeOnDelete();
            $table->foreign('KampanyeId')->references('Id')->on('Kampanye')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('UtmPemasaran');
    }
};
