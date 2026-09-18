<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TemplatDaftarPeriksa', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Kode', 80);
            $table->string('Nama', 180);
            $table->string('Jenis', 60);
            $table->char('KategoriAsetId', 26)->nullable();
            $table->char('ModelAsetId', 26)->nullable();
            $table->unsignedInteger('VersiTemplat')->default(1);
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Kode', 'VersiTemplat'], 'UqTemplatDaftarPeriksa');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('KategoriAsetId')->references('Id')->on('KategoriAset');
            $table->foreign('ModelAsetId')->references('Id')->on('ModelAset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TemplatDaftarPeriksa');
    }
};
