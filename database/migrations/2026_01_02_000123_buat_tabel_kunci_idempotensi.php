<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KunciIdempotensi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26)->nullable();
            $table->string('Kunci', 255);
            $table->string('Rute', 255);
            $table->char('HashPermintaan', 64)->nullable();
            $table->unsignedSmallInteger('StatusHttp')->nullable();
            $table->longText('Respons')->nullable();
            $table->dateTime('KadaluarsaPada', 6);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['OrganisasiId', 'Kunci', 'Rute'], 'UqKunciIdempotensi');
            $table->index(['KadaluarsaPada'], 'IdxKunciIdempotensiKadaluarsa');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KunciIdempotensi');
    }
};
