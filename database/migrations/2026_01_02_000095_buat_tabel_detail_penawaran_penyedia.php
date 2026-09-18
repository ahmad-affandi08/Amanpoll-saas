<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DetailPenawaranPenyedia', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PenawaranPenyediaId', 26);
            $table->char('DetailPermintaanPembelianId', 26)->nullable();
            $table->string('Deskripsi', 255);
            $table->decimal('Jumlah', 20, 4);
            $table->decimal('HargaSatuan', 20, 2);
            $table->decimal('Diskon', 20, 2)->default(0);
            $table->decimal('Pajak', 20, 2)->default(0);
            $table->decimal('Total', 20, 2);
            $table->unsignedInteger('WaktuPengirimanHari')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PenawaranPenyediaId')->references('Id')->on('PenawaranPenyedia');
            $table->foreign('DetailPermintaanPembelianId')->references('Id')->on('DetailPermintaanPembelian');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DetailPenawaranPenyedia');
    }
};
