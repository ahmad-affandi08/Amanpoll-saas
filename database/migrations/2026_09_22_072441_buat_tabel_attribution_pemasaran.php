<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * First touch dan last touch per pengunjung (MARKETING.md 14, 24).
 *
 * Satu baris per `PengenalPengunjung`, bukan per sesi: pertanyaan yang dijawab
 * attribution adalah "dari mana orang ini pertama datang, dan dari mana ia
 * datang terakhir kali" — keduanya sifat orangnya, bukan sifat satu kunjungan.
 *
 * Kolom first touch tidak pernah ditulis ulang setelah terisi. Itu aturan
 * pokok MARKETING.md 14 dan satu-satunya alasan kampanye yang mengawali sebuah
 * konversi masih dapat dikenali berminggu kemudian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AttributionPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('PengenalPengunjung', 26);

            $table->string('SumberPertama', 120)->nullable();
            $table->string('MediumPertama', 120)->nullable();
            $table->string('KampanyePertama', 190)->nullable();
            $table->char('KampanyeIdPertama', 26)->nullable();
            $table->text('LandingPertama')->nullable();
            $table->text('ReferrerPertama')->nullable();
            $table->dateTime('SentuhanPertamaPada', 6)->nullable();

            $table->string('SumberTerakhir', 120)->nullable();
            $table->string('MediumTerakhir', 120)->nullable();
            $table->string('KampanyeTerakhir', 190)->nullable();
            $table->char('KampanyeIdTerakhir', 26)->nullable();
            $table->text('LandingTerakhir')->nullable();
            $table->text('ReferrerTerakhir')->nullable();
            $table->dateTime('SentuhanTerakhirPada', 6)->nullable();

            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['PengenalPengunjung'], 'UqAttributionPengunjung');
            $table->foreign('KampanyeIdPertama')->references('Id')->on('Kampanye')->nullOnDelete();
            $table->foreign('KampanyeIdTerakhir')->references('Id')->on('Kampanye')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('AttributionPemasaran');
    }
};
