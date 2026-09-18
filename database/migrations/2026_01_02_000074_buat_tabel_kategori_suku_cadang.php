<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KategoriSukuCadang', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('IndukId', 26)->nullable();
            $table->string('Kode', 60);
            $table->string('Nama', 160);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['OrganisasiId', 'Kode'], 'UqKategoriSukuCadang');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('IndukId')->references('Id')->on('KategoriSukuCadang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KategoriSukuCadang');
    }
};
