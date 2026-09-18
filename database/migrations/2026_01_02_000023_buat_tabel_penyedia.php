<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Penyedia', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Kode', 60);
            $table->string('Nama', 200);
            $table->string('NamaLegal', 240)->nullable();
            $table->string('NomorIdentitasPajak', 100)->nullable();
            $table->string('Email', 180)->nullable();
            $table->string('Telepon', 60)->nullable();
            $table->string('Website', 255)->nullable();
            $table->text('Alamat')->nullable();
            $table->string('Kota', 120)->nullable();
            $table->string('Provinsi', 120)->nullable();
            $table->string('Negara', 100)->nullable();
            $table->string('Status', 30)->default('Aktif');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->unique(['OrganisasiId', 'Kode'], 'UqPenyediaKode');
            $table->index(['OrganisasiId', 'Nama'], 'IdxPenyediaNama');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Penyedia');
    }
};
