<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PembacaanMeterAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('MeterAsetId', 26);
            $table->decimal('Nilai', 20, 4);
            $table->dateTime('DibacaPada', 6);
            $table->string('Sumber', 40)->default('Manual');
            $table->char('DicatatOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['MeterAsetId', 'DibacaPada'], 'IdxPembacaanMeter');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('MeterAsetId')->references('Id')->on('MeterAset');
            $table->foreign('DicatatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PembacaanMeterAset');
    }
};
