<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Peran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26)->nullable();
            $table->string('Kode', 80);
            $table->string('Nama', 120);
            $table->text('Keterangan')->nullable();
            $table->boolean('BawaanSistem')->default(0);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->index(['OrganisasiId', 'Kode'], 'IdxPeranOrganisasiKode');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Peran');
    }
};
