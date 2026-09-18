<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('RiwayatLokasiAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AsetId', 26);
            $table->char('LokasiAsalId', 26)->nullable();
            $table->char('LokasiTujuanId', 26)->nullable();
            $table->string('JenisPerpindahan', 60);
            $table->string('ReferensiJenis', 80)->nullable();
            $table->char('ReferensiId', 26)->nullable();
            $table->text('Alasan')->nullable();
            $table->char('DipindahkanOleh', 26)->nullable();
            $table->dateTime('DipindahkanPada', 6);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['AsetId', 'DipindahkanPada'], 'IdxRiwayatLokasiAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AsetId')->references('Id')->on('Aset');
            $table->foreign('LokasiAsalId')->references('Id')->on('Lokasi');
            $table->foreign('LokasiTujuanId')->references('Id')->on('Lokasi');
            $table->foreign('DipindahkanOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RiwayatLokasiAset');
    }
};
