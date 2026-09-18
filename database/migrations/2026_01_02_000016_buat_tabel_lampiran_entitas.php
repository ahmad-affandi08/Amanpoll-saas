<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('LampiranEntitas', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('JenisEntitas', 80);
            $table->char('EntitasId', 26);
            $table->char('BerkasId', 26);
            $table->string('Kategori', 80)->nullable();
            $table->text('Keterangan')->nullable();
            $table->char('DibuatOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['OrganisasiId', 'JenisEntitas', 'EntitasId'], 'IdxLampiranEntitas');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('BerkasId')->references('Id')->on('Berkas');
            $table->foreign('DibuatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('LampiranEntitas');
    }
};
