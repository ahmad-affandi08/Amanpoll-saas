<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Langganan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PaketLanggananId', 26);
            $table->string('Siklus', 30)->default('Bulanan');
            $table->date('MulaiPada');
            $table->date('BerakhirPada')->nullable();
            $table->date('UjiCobaSampai')->nullable();
            $table->string('Status', 40)->default('Aktif');
            $table->dateTime('BatalPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->index(['OrganisasiId', 'Status'], 'IdxLanggananOrganisasi');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PaketLanggananId')->references('Id')->on('PaketLangganan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Langganan');
    }
};
