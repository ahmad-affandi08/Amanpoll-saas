<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TemplatInspeksi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Kode', 80);
            $table->string('Nama', 180);
            $table->char('KategoriAsetId', 26)->nullable();
            $table->char('TemplatDaftarPeriksaId', 26);
            $table->unsignedInteger('IntervalHari')->nullable();
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['OrganisasiId', 'Kode'], 'UqTemplatInspeksi');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('KategoriAsetId')->references('Id')->on('KategoriAset');
            $table->foreign('TemplatDaftarPeriksaId')->references('Id')->on('TemplatDaftarPeriksa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TemplatInspeksi');
    }
};
