<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SinkronisasiEksternal', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('IntegrasiEksternalId', 26);
            $table->string('JenisProses', 80);
            $table->string('Arah', 30);
            $table->string('Status', 40)->default('Diproses');
            $table->unsignedInteger('JumlahData')->default(0);
            $table->unsignedInteger('JumlahBerhasil')->default(0);
            $table->unsignedInteger('JumlahGagal')->default(0);
            $table->text('PesanKesalahan')->nullable();
            $table->dateTime('MulaiPada', 6)->useCurrent();
            $table->dateTime('SelesaiPada', 6)->nullable();
            $table->index(['IntegrasiEksternalId', 'MulaiPada', 'Status'], 'IdxSinkronisasiEksternal');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('IntegrasiEksternalId')->references('Id')->on('IntegrasiEksternal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SinkronisasiEksternal');
    }
};
