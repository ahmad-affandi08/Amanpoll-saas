<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Form builder dan pengirimannya (MARKETING.md 10). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('FormulirPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80)->unique('UnqFormulirPemasaranKode');
            $table->string('Nama', 190);
            $table->text('PesanSukses')->nullable();
            $table->string('UrlRedirect', 500)->nullable();
            $table->string('Sumber', 40);
            $table->char('KampanyeId', 26)->nullable();
            $table->json('Tag')->nullable();

            // Pemicu otomasi disimpan sebagai kode, bukan foreign key.
            $table->string('PemicuOtomasi', 120)->nullable();
            $table->string('UrlWebhook', 500)->nullable();

            $table->boolean('WajibPersetujuan')->default(true);
            $table->boolean('CaptchaAktif')->default(false);
            $table->boolean('Aktif')->default(true);

            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->foreign('KampanyeId')->references('Id')->on('Kampanye')->nullOnDelete();
        });

        Schema::create('FieldFormulirPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('FormulirPemasaranId', 26);
            $table->string('Kode', 80);
            $table->string('Label', 190);
            $table->string('Jenis', 40);
            $table->boolean('Wajib')->default(false);
            $table->unsignedInteger('Urutan')->default(0);
            $table->json('Pilihan')->nullable();
            $table->string('Placeholder', 190)->nullable();
            $table->string('Bantuan', 500)->nullable();

            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['FormulirPemasaranId', 'Kode'], 'UnqFieldFormulirKode');
            $table->index(['FormulirPemasaranId', 'Urutan'], 'IdxFieldFormulirUrutan');
            $table->foreign('FormulirPemasaranId')
                ->references('Id')->on('FormulirPemasaran')->cascadeOnDelete();
        });

        Schema::create('PengirimanFormulir', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('FormulirPemasaranId', 26);
            $table->char('ProspekId', 26)->nullable();
            $table->char('PengenalPengunjung', 26)->nullable();
            $table->json('Data');
            $table->boolean('Persetujuan')->default(false);
            $table->string('AlamatIp', 45)->nullable();
            $table->string('AgenPengguna', 500)->nullable();
            $table->dateTime('DikirimPada', 6);

            $table->index(['FormulirPemasaranId', 'DikirimPada'], 'IdxPengirimanFormulirWaktu');
            $table->index(['PengenalPengunjung'], 'IdxPengirimanFormulirPengunjung');
            $table->foreign('FormulirPemasaranId')
                ->references('Id')->on('FormulirPemasaran')->cascadeOnDelete();
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PengirimanFormulir');
        Schema::dropIfExists('FieldFormulirPemasaran');
        Schema::dropIfExists('FormulirPemasaran');
    }
};
