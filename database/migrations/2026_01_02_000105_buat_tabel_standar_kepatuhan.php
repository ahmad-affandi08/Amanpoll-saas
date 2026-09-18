<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('StandarKepatuhan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26)->nullable();
            $table->string('Kode', 80);
            $table->string('Nama', 220);
            $table->string('Penerbit', 180)->nullable();
            $table->string('VersiStandar', 80)->nullable();
            $table->string('JenisIndustri', 120)->nullable();
            $table->text('Deskripsi')->nullable();
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['Kode', 'Aktif'], 'IdxStandarKepatuhan');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('StandarKepatuhan');
    }
};
