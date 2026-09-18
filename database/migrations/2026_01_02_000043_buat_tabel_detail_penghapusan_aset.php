<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DetailPenghapusanAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PengajuanPenghapusanAsetId', 26);
            $table->char('AsetId', 26);
            $table->decimal('NilaiBukuSaatPenghapusan', 20, 2)->nullable();
            $table->decimal('HasilPelepasan', 20, 2)->nullable();
            $table->string('Status', 40)->default('Menunggu');
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['PengajuanPenghapusanAsetId', 'AsetId'], 'UqDetailPenghapusanAset');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PengajuanPenghapusanAsetId')->references('Id')->on('PengajuanPenghapusanAset');
            $table->foreign('AsetId')->references('Id')->on('Aset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DetailPenghapusanAset');
    }
};
