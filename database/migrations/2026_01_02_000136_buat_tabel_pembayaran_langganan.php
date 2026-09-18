<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PembayaranLangganan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('TagihanLanggananId', 26);
            $table->string('PenyediaPembayaran', 80)->nullable();
            $table->string('ReferensiEksternal', 180)->nullable();
            $table->string('Metode', 60)->nullable();
            $table->decimal('Jumlah', 20, 2);
            $table->string('Status', 40)->default('Menunggu');
            $table->dateTime('DibayarPada', 6)->nullable();
            $table->json('MuatanData')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['TagihanLanggananId', 'Status'], 'IdxPembayaranLangganan');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('TagihanLanggananId')->references('Id')->on('TagihanLangganan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PembayaranLangganan');
    }
};
