<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('WaktuHentiAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AsetId', 26);
            $table->char('PerintahKerjaId', 26)->nullable();
            $table->dateTime('MulaiPada', 6);
            $table->dateTime('SelesaiPada', 6)->nullable();
            $table->unsignedInteger('DurasiMenit')->nullable();
            $table->string('Jenis', 50)->default('TidakTerencana');
            $table->text('Alasan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['AsetId', 'MulaiPada'], 'IdxWaktuHentiAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AsetId')->references('Id')->on('Aset');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('WaktuHentiAset');
    }
};
