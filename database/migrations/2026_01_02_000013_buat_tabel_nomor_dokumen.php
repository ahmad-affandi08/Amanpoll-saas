<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('NomorDokumen', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('JenisDokumen', 80);
            $table->string('Awalan', 40)->nullable();
            $table->string('FormatNomor', 160);
            $table->unsignedBigInteger('NomorTerakhir')->default(0);
            $table->string('ResetPeriode', 30)->default('Tahunan');
            $table->string('PeriodeAktif', 20)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'JenisDokumen'], 'UqNomorDokumen');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('NomorDokumen');
    }
};
