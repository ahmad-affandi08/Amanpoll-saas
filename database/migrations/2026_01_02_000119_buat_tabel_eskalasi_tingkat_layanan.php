<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('EskalasiTingkatLayanan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('TingkatLayananId', 26);
            $table->unsignedInteger('Tahap');
            $table->unsignedInteger('SetelahMenit');
            $table->char('PeranId', 26)->nullable();
            $table->char('PenggunaId', 26)->nullable();
            $table->json('Kanal')->nullable();
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['TingkatLayananId', 'Tahap'], 'UqEskalasiTingkatLayanan');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('TingkatLayananId')->references('Id')->on('TingkatLayanan');
            $table->foreign('PeranId')->references('Id')->on('Peran');
            $table->foreign('PenggunaId')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('EskalasiTingkatLayanan');
    }
};
