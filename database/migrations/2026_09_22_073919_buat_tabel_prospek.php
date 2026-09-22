<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prospek dan perusahaannya (MARKETING.md 5.2, 24).
 *
 * Tanpa OrganisasiId: prospek belum menjadi tenant. Ketika trialnya menghasilkan
 * workspace, `OrganisasiId` diisi sebagai tautan — bukan sebagai scope — supaya
 * attribution perjalanannya tetap dapat ditelusuri setelah ia menjadi pelanggan
 * (MARKETING.md 25).
 *
 * `PengenalPengunjung` adalah jembatan ke attribution: satu prospek yang lahir
 * dari formulir membawa serta seluruh riwayat kunjungan anonimnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('OrganisasiProspek', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Nama', 190);
            $table->string('Industri', 120)->nullable();
            $table->unsignedInteger('JumlahLokasi')->nullable();
            $table->unsignedInteger('EstimasiAset')->nullable();
            $table->unsignedInteger('EstimasiTeknisi')->nullable();
            $table->string('Kota', 120)->nullable();
            $table->string('Negara', 120)->nullable();
            $table->string('Situs', 190)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->index(['Nama'], 'IdxOrganisasiProspekNama');
        });

        Schema::create('Prospek', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiProspekId', 26)->nullable();
            $table->char('PengenalPengunjung', 26)->nullable();
            $table->char('OrganisasiId', 26)->nullable();

            $table->string('Nama', 190);
            $table->string('Email', 190)->nullable();
            $table->string('Telepon', 60)->nullable();
            $table->string('WhatsApp', 60)->nullable();
            $table->string('Jabatan', 120)->nullable();

            $table->string('Sumber', 40);
            $table->char('KampanyeId', 26)->nullable();
            $table->char('TahapPipelineId', 26)->nullable();
            $table->integer('Skor')->default(0);
            $table->text('Catatan')->nullable();

            $table->dateTime('AktivitasTerakhirPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->index(['Email'], 'IdxProspekEmail');
            $table->index(['TahapPipelineId', 'Skor'], 'IdxProspekTahap');
            $table->index(['PengenalPengunjung'], 'IdxProspekPengunjung');
            $table->foreign('OrganisasiProspekId')->references('Id')->on('OrganisasiProspek')->nullOnDelete();
            $table->foreign('KampanyeId')->references('Id')->on('Kampanye')->nullOnDelete();
            $table->foreign('TahapPipelineId')->references('Id')->on('TahapPipeline')->nullOnDelete();
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Prospek');
        Schema::dropIfExists('OrganisasiProspek');
    }
};
