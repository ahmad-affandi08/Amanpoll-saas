<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('LaporanTersimpan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nama', 180);
            $table->string('Jenis', 80);
            $table->json('Konfigurasi');
            $table->boolean('Pribadi')->default(0);
            $table->char('PemilikId', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PemilikId')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('LaporanTersimpan');
    }
};
