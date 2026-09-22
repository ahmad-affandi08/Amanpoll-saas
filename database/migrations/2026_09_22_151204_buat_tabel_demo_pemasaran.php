<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Demo produk beserta sesi dan peristiwanya (MARKETING.md 11, FASE 38.03). */
return new class extends Migration
{
    public function up(): void
    {
        // Penanda di tenantnya sendiri: reset menuntut dua fakta sepakat, bukan satu kolom di tabel pemasaran.
        Schema::table('Organisasi', function (Blueprint $table): void {
            $table->boolean('Demo')->default(false)->after('Status');
            $table->index(['Demo'], 'IdxOrganisasiDemo');
        });

        Schema::create('DemoPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80);
            $table->string('Nama', 180);
            $table->boolean('Aktif')->default(false);
            $table->string('Dataset', 80);
            $table->char('OrganisasiDemoId', 26)->nullable();
            $table->unsignedInteger('ResetIntervalMenit')->default(1440);
            $table->json('ModulTampil')->nullable();
            $table->json('FiturDibatasi')->nullable();
            $table->string('CtaLabel', 120)->nullable();
            $table->string('CtaUrl', 500)->nullable();
            // Dua batas yang berbeda: berapa lama satu sesi boleh hidup, dan berapa sesi boleh hidup bersamaan.
            $table->unsignedInteger('MaksDurasiMenit')->default(30);
            $table->unsignedInteger('MaksSesiSerentak')->default(5);
            $table->dateTime('TerakhirResetPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['Kode'], 'UnqDemoPemasaranKode');
            $table->foreign('OrganisasiDemoId')->references('Id')->on('Organisasi')->nullOnDelete();
        });

        Schema::create('SesiDemo', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('DemoPemasaranId', 26);
            $table->char('PengenalPengunjung', 26);
            $table->char('SesiPengunjungId', 26)->nullable();
            $table->string('Status', 20);
            $table->dateTime('MulaiPada', 6);
            $table->dateTime('KedaluwarsaPada', 6);
            $table->dateTime('SelesaiPada', 6)->nullable();
            $table->string('AlasanSelesai', 190)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->index(['DemoPemasaranId', 'Status'], 'IdxSesiDemoStatus');
            $table->index(['PengenalPengunjung'], 'IdxSesiDemoPengunjung');
            $table->index(['KedaluwarsaPada'], 'IdxSesiDemoKedaluwarsa');
            $table->foreign('DemoPemasaranId')->references('Id')->on('DemoPemasaran')->cascadeOnDelete();
        });

        Schema::create('EventDemo', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('SesiDemoId', 26);
            $table->string('Jenis', 40);
            $table->string('Modul', 40)->nullable();
            $table->json('Rincian')->nullable();
            $table->dateTime('TerjadiPada', 6);

            $table->index(['SesiDemoId', 'TerjadiPada'], 'IdxEventDemoSesi');
            $table->index(['Jenis'], 'IdxEventDemoJenis');
            $table->foreign('SesiDemoId')->references('Id')->on('SesiDemo')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('EventDemo');
        Schema::dropIfExists('SesiDemo');
        Schema::dropIfExists('DemoPemasaran');

        Schema::table('Organisasi', function (Blueprint $table): void {
            $table->dropIndex('IdxOrganisasiDemo');
            $table->dropColumn('Demo');
        });
    }
};
