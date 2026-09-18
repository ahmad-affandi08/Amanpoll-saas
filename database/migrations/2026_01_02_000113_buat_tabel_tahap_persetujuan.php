<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TahapPersetujuan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AlurPersetujuanId', 26);
            $table->unsignedInteger('Urutan');
            $table->string('Nama', 160);
            $table->string('JenisPenyetuju', 50);
            $table->char('PeranId', 26)->nullable();
            $table->char('PenggunaId', 26)->nullable();
            $table->unsignedInteger('JumlahMinimumPenyetuju')->default(1);
            $table->boolean('BolehMenyetujuiSendiri')->default(0);
            $table->unsignedInteger('BatasWaktuMenit')->nullable();
            $table->json('Kondisi')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['AlurPersetujuanId', 'Urutan'], 'UqTahapPersetujuanUrutan');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AlurPersetujuanId')->references('Id')->on('AlurPersetujuan');
            $table->foreign('PeranId')->references('Id')->on('Peran');
            $table->foreign('PenggunaId')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TahapPersetujuan');
    }
};
