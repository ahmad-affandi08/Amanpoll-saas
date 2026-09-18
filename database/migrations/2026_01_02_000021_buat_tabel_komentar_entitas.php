<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KomentarEntitas', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('JenisEntitas', 80);
            $table->char('EntitasId', 26);
            $table->char('IndukKomentarId', 26)->nullable();
            $table->text('Isi');
            $table->char('DibuatOleh', 26);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->index(['OrganisasiId', 'JenisEntitas', 'EntitasId', 'DibuatPada'], 'IdxKomentarEntitas');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('IndukKomentarId')->references('Id')->on('KomentarEntitas');
            $table->foreign('DibuatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KomentarEntitas');
    }
};
