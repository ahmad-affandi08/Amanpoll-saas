<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TagihanLangganan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('LanggananId', 26);
            $table->string('Nomor', 120);
            $table->date('PeriodeMulai');
            $table->date('PeriodeSelesai');
            $table->date('JatuhTempo');
            $table->decimal('Subtotal', 20, 2)->default(0);
            $table->decimal('Pajak', 20, 2)->default(0);
            $table->decimal('Total', 20, 2)->default(0);
            $table->string('Status', 40)->default('BelumDibayar');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['Nomor'], 'UqTagihanLanggananNomor');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('LanggananId')->references('Id')->on('Langganan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TagihanLangganan');
    }
};
