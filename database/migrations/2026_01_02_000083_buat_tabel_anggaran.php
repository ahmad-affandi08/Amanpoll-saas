<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Anggaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('UnitOrganisasiId', 26)->nullable();
            $table->string('Kode', 80);
            $table->string('Nama', 180);
            $table->unsignedSmallInteger('Tahun');
            $table->char('MataUang', 3)->default('IDR');
            $table->decimal('Jumlah', 20, 2)->default(0);
            $table->string('Status', 40)->default('Draft');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Kode', 'Tahun'], 'UqAnggaran');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('UnitOrganisasiId')->references('Id')->on('UnitOrganisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Anggaran');
    }
};
