<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('LayananKontrak', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('KontrakId', 26);
            $table->string('Nama', 180);
            $table->text('Deskripsi')->nullable();
            $table->decimal('Kuota', 20, 4)->nullable();
            $table->string('Satuan', 50)->nullable();
            $table->decimal('Terpakai', 20, 4)->default(0);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('KontrakId')->references('Id')->on('Kontrak');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('LayananKontrak');
    }
};
