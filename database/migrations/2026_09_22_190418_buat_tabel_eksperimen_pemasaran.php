<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Eksperimen A/B beserta varian, penetapan pengunjung, dan hasilnya (MARKETING.md 22). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('EksperimenPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80);
            $table->string('Nama', 190);
            $table->string('Target', 40);
            $table->string('Hipotesis', 500)->nullable();
            $table->string('Status', 20)->default('Draf');
            $table->string('MetrikUtama', 40);

            // Ambang sampel disimpan bersama eksperimennya, bukan hanya di setelan global.
            $table->unsignedInteger('MinimumSampel');

            $table->char('PemenangVarianId', 26)->nullable();
            $table->string('AlasanKeputusan', 500)->nullable();
            $table->dateTime('DiputuskanPada', 6)->nullable();
            $table->dateTime('MulaiPada', 6)->nullable();
            $table->dateTime('SelesaiPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['Kode'], 'UnqEksperimenKode');
            $table->index(['Status'], 'IdxEksperimenStatus');
        });

        Schema::create('VarianEksperimen', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('EksperimenPemasaranId', 26);
            $table->string('Kode', 20);
            $table->string('Nama', 190);
            $table->unsignedInteger('Bobot')->default(1);
            $table->boolean('Kontrol')->default(false);
            $table->json('Konfigurasi')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['EksperimenPemasaranId', 'Kode'], 'UnqVarianEksperimenKode');
            $table->foreign('EksperimenPemasaranId')
                ->references('Id')->on('EksperimenPemasaran')->cascadeOnDelete();
        });

        Schema::table('EksperimenPemasaran', function (Blueprint $table): void {
            $table->foreign('PemenangVarianId')->references('Id')->on('VarianEksperimen')->nullOnDelete();
        });

        // Indeks unik inilah penjamin varian tetap: satu pengunjung satu varian, selamanya.
        Schema::create('PartisipasiEksperimen', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('EksperimenPemasaranId', 26);
            $table->char('VarianEksperimenId', 26);
            $table->char('PengenalPengunjung', 26);
            $table->dateTime('DitetapkanPada', 6);

            $table->unique(['EksperimenPemasaranId', 'PengenalPengunjung'], 'UnqPartisipasiPengunjung');
            $table->index(['VarianEksperimenId'], 'IdxPartisipasiVarian');
            $table->foreign('EksperimenPemasaranId')
                ->references('Id')->on('EksperimenPemasaran')->cascadeOnDelete();
            $table->foreign('VarianEksperimenId')
                ->references('Id')->on('VarianEksperimen')->cascadeOnDelete();
        });

        Schema::create('HasilEksperimen', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('EksperimenPemasaranId', 26);
            $table->char('VarianEksperimenId', 26);
            $table->string('Metrik', 40);

            // Pembilang dan penyebut disimpan apa adanya; rasio yang tanpa keduanya tidak dapat ditelusuri.
            $table->unsignedInteger('Penyebut')->default(0);
            $table->unsignedInteger('Pembilang')->default(0);
            $table->decimal('Rasio', 8, 4)->default(0);
            $table->dateTime('DihitungPada', 6);

            $table->unique(['EksperimenPemasaranId', 'VarianEksperimenId', 'Metrik'], 'UnqHasilEksperimen');
            $table->foreign('EksperimenPemasaranId')
                ->references('Id')->on('EksperimenPemasaran')->cascadeOnDelete();
            $table->foreign('VarianEksperimenId')
                ->references('Id')->on('VarianEksperimen')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('HasilEksperimen');
        Schema::dropIfExists('PartisipasiEksperimen');

        Schema::table('EksperimenPemasaran', function (Blueprint $table): void {
            $table->dropForeign(['PemenangVarianId']);
        });

        Schema::dropIfExists('VarianEksperimen');
        Schema::dropIfExists('EksperimenPemasaran');
    }
};
