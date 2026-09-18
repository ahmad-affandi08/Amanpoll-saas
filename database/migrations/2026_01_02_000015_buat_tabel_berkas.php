<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Berkas', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('NamaAsli', 255);
            $table->string('NamaPenyimpanan', 255);
            $table->string('MediaPenyimpanan', 60)->default('private');
            $table->text('LokasiPenyimpanan');
            $table->string('JenisMime', 120)->nullable();
            $table->unsignedBigInteger('UkuranByte')->nullable();
            $table->char('HashSha256', 64)->nullable();
            $table->json('DataTambahan')->nullable();
            $table->char('DiunggahOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->index(['OrganisasiId', 'HashSha256'], 'IdxBerkasOrganisasiHash');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('DiunggahOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Berkas');
    }
};
