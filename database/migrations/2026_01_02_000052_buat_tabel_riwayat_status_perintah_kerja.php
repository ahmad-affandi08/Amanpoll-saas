<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('RiwayatStatusPerintahKerja', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PerintahKerjaId', 26);
            $table->string('StatusSebelum', 40)->nullable();
            $table->string('StatusSesudah', 40);
            $table->text('Catatan')->nullable();
            $table->char('DiubahOleh', 26)->nullable();
            $table->dateTime('DiubahPada', 6)->useCurrent();
            $table->index(['PerintahKerjaId', 'DiubahPada'], 'IdxRiwayatStatusPerintahKerja');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
            $table->foreign('DiubahOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RiwayatStatusPerintahKerja');
    }
};
