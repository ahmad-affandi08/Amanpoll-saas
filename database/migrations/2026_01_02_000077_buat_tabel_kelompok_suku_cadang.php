<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KelompokSukuCadang', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('SukuCadangId', 26);
            $table->string('NomorBatch', 120);
            $table->date('TanggalProduksi')->nullable();
            $table->date('TanggalKadaluarsa')->nullable();
            $table->decimal('HargaPerolehan', 20, 2)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['SukuCadangId', 'NomorBatch'], 'UqKelompokSukuCadang');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('SukuCadangId')->references('Id')->on('SukuCadang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KelompokSukuCadang');
    }
};
