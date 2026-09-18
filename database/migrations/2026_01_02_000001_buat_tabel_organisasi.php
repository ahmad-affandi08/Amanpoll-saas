<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Organisasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 50);
            $table->string('Nama', 180);
            $table->string('NamaLegal', 220)->nullable();
            $table->string('JenisUsaha', 100)->nullable();
            $table->string('NomorIdentitasPajak', 100)->nullable();
            $table->string('Email', 180)->nullable();
            $table->string('Telepon', 50)->nullable();
            $table->text('Alamat')->nullable();
            $table->string('Negara', 100)->nullable();
            $table->string('Provinsi', 120)->nullable();
            $table->string('Kota', 120)->nullable();
            $table->string('ZonaWaktu', 64)->default('Asia/Jakarta');
            $table->text('LogoUrl')->nullable();
            $table->string('Status', 30)->default('Aktif');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->unique(['Kode'], 'UqOrganisasiKode');
            $table->index(['Status'], 'IdxOrganisasiStatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Organisasi');
    }
};
