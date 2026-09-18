<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SerahTerimaAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->char('PermintaanMutasiAsetId', 26)->nullable();
            $table->string('Jenis', 60);
            $table->char('PihakMenyerahkan', 26)->nullable();
            $table->char('PihakMenerima', 26)->nullable();
            $table->dateTime('DiserahkanPada', 6)->nullable();
            $table->dateTime('DiterimaPada', 6)->nullable();
            $table->string('Status', 40)->default('Draft');
            $table->text('Catatan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqSerahTerimaNomor');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PermintaanMutasiAsetId')->references('Id')->on('PermintaanMutasiAset');
            $table->foreign('PihakMenyerahkan')->references('Id')->on('Pengguna');
            $table->foreign('PihakMenerima')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SerahTerimaAset');
    }
};
