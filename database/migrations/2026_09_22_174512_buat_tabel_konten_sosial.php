<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Satu konten utama dengan banyak distribusi beserta jadwalnya (MARKETING.md 18). */
return new class extends Migration
{
    public function up(): void
    {
        // Wadah bersama; yang berpindah status adalah distribusinya, bukan wadahnya.
        Schema::create('KontenSosial', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80);
            $table->string('Judul', 190);
            $table->string('Ringkasan', 500)->nullable();
            $table->string('MediaUrl', 500)->nullable();
            $table->char('HalamanId', 26)->nullable();
            $table->char('KampanyeId', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['Kode'], 'UnqKontenSosialKode');
            $table->foreign('HalamanId')->references('Id')->on('HalamanPemasaran')->nullOnDelete();
            $table->foreign('KampanyeId')->references('Id')->on('Kampanye')->nullOnDelete();
        });

        Schema::create('DistribusiKontenSosial', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('KontenSosialId', 26);
            $table->string('Channel', 40);
            $table->text('Caption');
            $table->string('MediaUrl', 500)->nullable();
            $table->string('Cta', 190)->nullable();
            $table->string('TautanTujuan', 500)->nullable();

            // utm_campaign selalu diambil dari kode kampanye induknya, jadi hanya empat sisanya disimpan.
            $table->string('UtmSource', 100)->nullable();
            $table->string('UtmMedium', 100)->nullable();
            $table->string('UtmTerm', 100)->nullable();
            $table->string('UtmContent', 100)->nullable();

            $table->string('Status', 20)->default('Draf');
            $table->string('IdPostPenyedia', 190)->nullable();
            $table->string('UrlTerbit', 500)->nullable();
            $table->string('Galat', 500)->nullable();
            $table->unsignedInteger('Percobaan')->default(0);
            $table->dateTime('DiprosesPada', 6)->nullable();
            $table->dateTime('TerbitPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            // Satu konten hanya punya satu distribusi per channel; dua caption untuk channel yang sama adalah salah ketik.
            $table->unique(['KontenSosialId', 'Channel'], 'UnqDistribusiSosialChannel');
            $table->index(['Status'], 'IdxDistribusiSosialStatus');
            $table->foreign('KontenSosialId')->references('Id')->on('KontenSosial')->cascadeOnDelete();
        });

        // Satu distribusi dapat dijadwalkan ulang berkali-kali; tiap rencana jadi barisnya sendiri.
        Schema::create('JadwalKontenSosial', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('DistribusiKontenSosialId', 26);
            $table->dateTime('JadwalPada', 6);
            $table->string('Status', 20)->default('Menunggu');
            $table->dateTime('DijalankanPada', 6)->nullable();
            $table->string('Catatan', 300)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['DistribusiKontenSosialId', 'JadwalPada'], 'UnqJadwalSosialWaktu');
            $table->index(['Status', 'JadwalPada'], 'IdxJadwalSosialMenunggu');
            $table->foreign('DistribusiKontenSosialId')
                ->references('Id')->on('DistribusiKontenSosial')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('JadwalKontenSosial');
        Schema::dropIfExists('DistribusiKontenSosial');
        Schema::dropIfExists('KontenSosial');
    }
};
