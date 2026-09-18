<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PreferensiNotifikasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PenggunaId', 26);
            $table->string('JenisPeristiwa', 100);
            $table->string('Kanal', 40);
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['PenggunaId', 'JenisPeristiwa', 'Kanal'], 'UqPreferensiNotifikasi');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PenggunaId')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PreferensiNotifikasi');
    }
};
