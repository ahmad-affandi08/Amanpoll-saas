<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('NilaiKolomKustom', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('DefinisiKolomKustomId', 26);
            $table->string('JenisEntitas', 80);
            $table->char('EntitasId', 26);
            $table->json('Nilai')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['DefinisiKolomKustomId', 'JenisEntitas', 'EntitasId'], 'UqNilaiKolomKustom');
            $table->index(['OrganisasiId', 'JenisEntitas', 'EntitasId'], 'IdxNilaiKolomKustomEntitas');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('DefinisiKolomKustomId')->references('Id')->on('DefinisiKolomKustom');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('NilaiKolomKustom');
    }
};
