<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TagihanPenyedia', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PenyediaId', 26);
            $table->char('PesananPembelianId', 26)->nullable();
            $table->string('NomorTagihan', 120);
            $table->date('TanggalTagihan');
            $table->date('JatuhTempo')->nullable();
            $table->decimal('Subtotal', 20, 2)->default(0);
            $table->decimal('Pajak', 20, 2)->default(0);
            $table->decimal('Total', 20, 2)->default(0);
            $table->decimal('Sisa', 20, 2)->default(0);
            $table->string('Status', 40)->default('BelumDibayar');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['OrganisasiId', 'PenyediaId', 'NomorTagihan'], 'UqTagihanPenyedia');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PenyediaId')->references('Id')->on('Penyedia');
            $table->foreign('PesananPembelianId')->references('Id')->on('PesananPembelian');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TagihanPenyedia');
    }
};
