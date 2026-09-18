<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MeterAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AsetId', 26);
            $table->string('Nama', 120);
            $table->string('Satuan', 50);
            $table->string('Jenis', 40)->default('Kumulatif');
            $table->decimal('NilaiAwal', 20, 4)->default(0);
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['AsetId', 'Aktif'], 'IdxMeterAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AsetId')->references('Id')->on('Aset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MeterAset');
    }
};
