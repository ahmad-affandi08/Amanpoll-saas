<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DefinisiKolomKustom', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('JenisEntitas', 80);
            $table->string('Kode', 80);
            $table->string('Label', 160);
            $table->string('TipeData', 40);
            $table->boolean('Wajib')->default(0);
            $table->json('Pilihan')->nullable();
            $table->json('AturanValidasi')->nullable();
            $table->json('NilaiBawaan')->nullable();
            $table->integer('Urutan')->default(0);
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'JenisEntitas', 'Kode'], 'UqKolomKustom');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DefinisiKolomKustom');
    }
};
