<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KategoriKeluhan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('IndukId', 26)->nullable();
            $table->string('Kode', 60);
            $table->string('Nama', 160);
            $table->char('TingkatLayananId', 26)->nullable();
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['OrganisasiId', 'Kode'], 'UqKategoriKeluhan');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('IndukId')->references('Id')->on('KategoriKeluhan');
            $table->foreign('TingkatLayananId')->references('Id')->on('TingkatLayanan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KategoriKeluhan');
    }
};
