<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PengajuanPenghapusanAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->text('Alasan');
            $table->string('MetodePenghapusan', 60)->nullable();
            $table->string('Status', 40)->default('Draft');
            $table->char('DiajukanOleh', 26);
            $table->dateTime('DiajukanPada', 6)->useCurrent();
            $table->dateTime('DiselesaikanPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqPengajuanPenghapusanNomor');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('DiajukanOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PengajuanPenghapusanAset');
    }
};
