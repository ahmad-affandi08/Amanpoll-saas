<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PenandaSinkronisasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PerangkatPenggunaId', 26);
            $table->string('JenisEntitas', 80);
            $table->string('TokenSinkronisasi', 255)->nullable();
            $table->dateTime('TerakhirSinkronPada', 6)->nullable();
            $table->unique(['PerangkatPenggunaId', 'JenisEntitas'], 'UqPenandaSinkronisasi');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PerangkatPenggunaId')->references('Id')->on('PerangkatPengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PenandaSinkronisasi');
    }
};
