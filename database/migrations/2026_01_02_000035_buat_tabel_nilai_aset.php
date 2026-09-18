<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('NilaiAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AsetId', 26);
            $table->date('TanggalNilai');
            $table->decimal('NilaiBuku', 20, 2);
            $table->decimal('AkumulasiPenyusutan', 20, 2)->default(0);
            $table->decimal('BebanPenyusutanPeriode', 20, 2)->default(0);
            $table->string('Metode', 40)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['AsetId', 'TanggalNilai'], 'UqNilaiAsetTanggal');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AsetId')->references('Id')->on('Aset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('NilaiAset');
    }
};
