<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KodeKegagalan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('KategoriAsetId', 26)->nullable();
            $table->string('Kode', 60);
            $table->string('Nama', 180);
            $table->string('Jenis', 60);
            $table->text('Keterangan')->nullable();
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['OrganisasiId', 'Kode'], 'UqKodeKegagalan');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('KategoriAsetId')->references('Id')->on('KategoriAset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KodeKegagalan');
    }
};
