<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('WaktuKerja', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PerintahKerjaId', 26);
            $table->char('PenggunaId', 26);
            $table->dateTime('MulaiPada', 6);
            $table->dateTime('SelesaiPada', 6)->nullable();
            $table->unsignedInteger('DurasiMenit')->nullable();
            $table->string('JenisWaktu', 40)->default('Kerja');
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['PerintahKerjaId', 'MulaiPada'], 'IdxWaktuKerjaPerintah');
            $table->index(['PenggunaId', 'MulaiPada'], 'IdxWaktuKerjaPengguna');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
            $table->foreign('PenggunaId')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('WaktuKerja');
    }
};
