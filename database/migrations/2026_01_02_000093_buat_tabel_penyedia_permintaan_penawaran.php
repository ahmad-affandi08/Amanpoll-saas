<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PenyediaPermintaanPenawaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PermintaanPenawaranId', 26);
            $table->char('PenyediaId', 26);
            $table->dateTime('DikirimPada', 6)->nullable();
            $table->dateTime('DilihatPada', 6)->nullable();
            $table->string('Status', 40)->default('Diundang');
            $table->unique(['PermintaanPenawaranId', 'PenyediaId'], 'UqPenyediaPermintaanPenawaran');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PermintaanPenawaranId')->references('Id')->on('PermintaanPenawaran');
            $table->foreign('PenyediaId')->references('Id')->on('Penyedia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PenyediaPermintaanPenawaran');
    }
};
