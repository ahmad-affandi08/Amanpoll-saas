<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KepatuhanAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AsetId', 26);
            $table->char('PersyaratanKepatuhanId', 26);
            $table->string('Status', 40)->default('BelumDiperiksa');
            $table->date('TanggalPemeriksaan')->nullable();
            $table->date('BerlakuSampai')->nullable();
            $table->text('Catatan')->nullable();
            $table->char('DiperiksaOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['AsetId', 'PersyaratanKepatuhanId'], 'UqKepatuhanAset');
            $table->index(['OrganisasiId', 'Status', 'BerlakuSampai'], 'IdxKepatuhanAsetStatus');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AsetId')->references('Id')->on('Aset');
            $table->foreign('PersyaratanKepatuhanId')->references('Id')->on('PersyaratanKepatuhan');
            $table->foreign('DiperiksaOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KepatuhanAset');
    }
};
