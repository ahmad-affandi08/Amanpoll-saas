<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Merek', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26)->nullable();
            $table->string('Nama', 160);
            $table->string('NegaraAsal', 100)->nullable();
            $table->string('Website', 255)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['Nama'], 'IdxMerekNama');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Merek');
    }
};
