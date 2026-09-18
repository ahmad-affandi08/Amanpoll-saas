<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('LokasiGudang', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('GudangId', 26);
            $table->char('IndukId', 26)->nullable();
            $table->string('Kode', 60);
            $table->string('Nama', 120);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['GudangId', 'Kode'], 'UqLokasiGudang');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('GudangId')->references('Id')->on('Gudang');
            $table->foreign('IndukId')->references('Id')->on('LokasiGudang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('LokasiGudang');
    }
};
