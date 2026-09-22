<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konfigurasi domain Pemasaran (MARKETING.md 29.02, 30).
 *
 * Lintas tenant dan tanpa OrganisasiId: yang diatur di sini adalah bagaimana
 * Amanpoll memasarkan dirinya — durasi trial yang ditawarkan, aturan skor
 * prospek, jendela attribution — bukan data milik satu pelanggan.
 *
 * Rahasia penyedia tidak pernah disimpan di sini. MARKETING.md 0 dan 30
 * menempatkannya di environment, dan dashboard tidak pernah menampilkannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KonfigurasiPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kunci', 150);
            $table->json('Nilai')->nullable();
            $table->string('Keterangan', 255)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['Kunci'], 'UqKonfigurasiPemasaranKunci');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KonfigurasiPemasaran');
    }
};
