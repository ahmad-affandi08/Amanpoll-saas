<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KunciApi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nama', 150);
            $table->string('AwalanKunci', 20);
            $table->string('HashKunci', 255);
            $table->json('Cakupan')->nullable();
            $table->json('AlamatIpDiizinkan')->nullable();
            $table->dateTime('KadaluarsaPada', 6)->nullable();
            $table->dateTime('TerakhirDipakaiPada', 6)->nullable();
            $table->string('Status', 30)->default('Aktif');
            $table->char('DibuatOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['AwalanKunci'], 'UqKunciApiPrefix');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('DibuatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KunciApi');
    }
};
