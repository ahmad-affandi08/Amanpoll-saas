<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Kontrak', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PenyediaId', 26)->nullable();
            $table->string('Nomor', 120);
            $table->string('Nama', 220);
            $table->string('Jenis', 60);
            $table->date('MulaiPada');
            $table->date('BerakhirPada');
            $table->decimal('Nilai', 20, 2)->nullable();
            $table->char('MataUang', 3)->default('IDR');
            $table->char('TingkatLayananId', 26)->nullable();
            $table->unsignedInteger('PeringatanHariSebelum')->default(30);
            $table->string('Status', 40)->default('Aktif');
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqKontrakNomor');
            $table->index(['OrganisasiId', 'BerakhirPada', 'Status'], 'IdxKontrakBerakhir');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PenyediaId')->references('Id')->on('Penyedia');
            $table->foreign('TingkatLayananId')->references('Id')->on('TingkatLayanan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Kontrak');
    }
};
