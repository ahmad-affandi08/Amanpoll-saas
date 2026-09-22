<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** CMS konten beserta keyword manager dan clusternya (MARKETING.md 9). */
return new class extends Migration
{
    public function up(): void
    {
        // Berversi sama seperti halaman pemasaran: yang tayang adalah versi terkunci, bukan draf yang masih disunting.
        Schema::create('KontenPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Slug', 190)->unique('UnqKontenPemasaranSlug');
            $table->string('Jenis', 40);
            $table->string('Judul', 190);
            $table->string('Status', 20);
            $table->string('PenulisNama', 120)->nullable();
            $table->char('KampanyeId', 26)->nullable();

            $table->char('VersiTerbitId', 26)->nullable();
            $table->char('VersiDrafId', 26)->nullable();

            $table->boolean('NoIndex')->default(false);
            $table->dateTime('TerbitPada', 6)->nullable();
            $table->dateTime('TarikPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->index(['Status', 'TerbitPada'], 'IdxKontenPemasaranJadwal');
            $table->index(['Jenis'], 'IdxKontenPemasaranJenis');
            $table->foreign('KampanyeId')->references('Id')->on('Kampanye')->nullOnDelete();
        });

        Schema::create('VersiKontenPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('KontenPemasaranId', 26);
            $table->unsignedInteger('Nomor');

            $table->string('Judul', 190);
            $table->string('Ringkasan', 500)->nullable();
            $table->longText('IsiMarkdown');

            // Metadata SEO bagian 9; noindex sendiri melekat pada kontennya, bukan versinya.
            $table->string('MetaJudul', 190)->nullable();
            $table->string('MetaDeskripsi', 500)->nullable();
            $table->string('Kanonik', 500)->nullable();
            $table->string('OgJudul', 190)->nullable();
            $table->string('OgDeskripsi', 500)->nullable();
            $table->string('OgGambar', 500)->nullable();
            $table->string('SkemaTipe', 60)->nullable();
            $table->string('Catatan', 500)->nullable();

            $table->char('DibuatOlehPlatformId', 26)->nullable();
            $table->dateTime('DibuatPada', 6);

            $table->unique(['KontenPemasaranId', 'Nomor'], 'UnqVersiKontenNomor');
            $table->foreign('KontenPemasaranId')
                ->references('Id')->on('KontenPemasaran')->cascadeOnDelete();
            $table->foreign('DibuatOlehPlatformId')
                ->references('Id')->on('AdminPlatform')->nullOnDelete();
        });

        Schema::table('KontenPemasaran', function (Blueprint $table): void {
            $table->foreign('VersiTerbitId')->references('Id')->on('VersiKontenPemasaran')->nullOnDelete();
            $table->foreign('VersiDrafId')->references('Id')->on('VersiKontenPemasaran')->nullOnDelete();
        });

        Schema::create('ClusterSeo', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80)->unique('UnqClusterSeoKode');
            $table->string('Nama', 190);
            $table->string('Keterangan', 500)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('KeywordSeo', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Keyword', 190)->unique('UnqKeywordSeo');
            $table->char('ClusterSeoId', 26)->nullable();
            $table->string('Intent', 20);
            $table->string('TargetUrl', 500)->nullable();
            $table->string('Prioritas', 20)->default('Sedang');
            $table->string('Status', 20)->default('Ide');
            $table->string('Catatan', 500)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->index(['Intent', 'Prioritas'], 'IdxKeywordSeoIntent');
            $table->foreign('ClusterSeoId')->references('Id')->on('ClusterSeo')->nullOnDelete();
        });

        Schema::create('KontenKeywordSeo', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('KontenPemasaranId', 26);
            $table->char('KeywordSeoId', 26);

            // Satu konten punya paling banyak satu keyword utama; sisanya pendukung.
            $table->boolean('Utama')->default(false);
            $table->dateTime('DibuatPada', 6)->useCurrent();

            $table->unique(['KontenPemasaranId', 'KeywordSeoId'], 'UnqKontenKeyword');
            $table->foreign('KontenPemasaranId')
                ->references('Id')->on('KontenPemasaran')->cascadeOnDelete();
            $table->foreign('KeywordSeoId')->references('Id')->on('KeywordSeo')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KontenKeywordSeo');
        Schema::dropIfExists('KeywordSeo');
        Schema::dropIfExists('ClusterSeo');

        Schema::table('KontenPemasaran', function (Blueprint $table): void {
            $table->dropForeign(['VersiTerbitId']);
            $table->dropForeign(['VersiDrafId']);
        });

        Schema::dropIfExists('VersiKontenPemasaran');
        Schema::dropIfExists('KontenPemasaran');
    }
};
