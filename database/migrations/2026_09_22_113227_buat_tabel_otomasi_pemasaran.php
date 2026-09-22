<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Otomasi pemasaran berversi: Trigger → Condition → Delay → Action (MARKETING.md 17). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('OtomasiPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80)->unique('UnqOtomasiKode');
            $table->string('Nama', 190);
            $table->string('Keterangan', 500)->nullable();
            $table->string('Pemicu', 80);
            $table->boolean('Aktif')->default(false);
            $table->char('VersiAktifId', 26)->nullable();
            $table->dateTime('DibuatPada', 6);
            $table->dateTime('DiperbaruiPada', 6);

            $table->index(['Pemicu', 'Aktif'], 'IdxOtomasiPemicuAktif');
        });

        Schema::create('VersiOtomasiPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OtomasiPemasaranId', 26);
            $table->unsignedInteger('Nomor');
            $table->string('Status', 20);
            $table->char('DiterbitkanOlehId', 26)->nullable();
            $table->dateTime('DiterbitkanPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6);
            $table->dateTime('DiperbaruiPada', 6);

            $table->unique(['OtomasiPemasaranId', 'Nomor'], 'UnqVersiOtomasiNomor');
            $table->foreign('OtomasiPemasaranId')->references('Id')->on('OtomasiPemasaran')->cascadeOnDelete();
        });

        // Versi aktif ditunjuk setelah tabelnya ada; keduanya saling merujuk.
        Schema::table('OtomasiPemasaran', function (Blueprint $table): void {
            $table->foreign('VersiAktifId')->references('Id')->on('VersiOtomasiPemasaran')->nullOnDelete();
        });

        Schema::create('LangkahOtomasiPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('VersiOtomasiPemasaranId', 26);
            $table->unsignedInteger('Urutan');
            $table->string('Jenis', 20);
            // Bentuk konfigurasinya berbeda tiap jenis langkah, jadi disimpan sebagai JSON dan divalidasi katalognya.
            $table->json('Konfigurasi');
            $table->dateTime('DibuatPada', 6);

            $table->unique(['VersiOtomasiPemasaranId', 'Urutan'], 'UnqLangkahOtomasiUrutan');
            $table->foreign('VersiOtomasiPemasaranId')
                ->references('Id')->on('VersiOtomasiPemasaran')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('OtomasiPemasaran', function (Blueprint $table): void {
            $table->dropForeign(['VersiAktifId']);
        });

        Schema::dropIfExists('LangkahOtomasiPemasaran');
        Schema::dropIfExists('VersiOtomasiPemasaran');
        Schema::dropIfExists('OtomasiPemasaran');
    }
};
