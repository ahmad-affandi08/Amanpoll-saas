<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('RencanaKalibrasi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AsetId', 26);
            $table->char('JenisKalibrasiId', 26)->nullable();
            $table->char('PenyediaId', 26)->nullable();
            $table->unsignedInteger('IntervalHari');
            $table->date('TanggalMulai');
            $table->date('TanggalBerikutnya');
            $table->unsignedInteger('PeringatanHariSebelum')->default(30);
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->index(['OrganisasiId', 'TanggalBerikutnya', 'Aktif'], 'IdxRencanaKalibrasiTanggal');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AsetId')->references('Id')->on('Aset');
            $table->foreign('JenisKalibrasiId')->references('Id')->on('JenisKalibrasi');
            $table->foreign('PenyediaId')->references('Id')->on('Penyedia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RencanaKalibrasi');
    }
};
