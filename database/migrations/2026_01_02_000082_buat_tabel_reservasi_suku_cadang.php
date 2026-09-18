<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ReservasiSukuCadang', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PerintahKerjaId', 26)->nullable();
            $table->char('GudangId', 26);
            $table->char('SukuCadangId', 26);
            $table->decimal('Jumlah', 20, 4);
            $table->string('Status', 40)->default('Aktif');
            $table->dateTime('KadaluarsaPada', 6)->nullable();
            $table->char('DibuatOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['SukuCadangId', 'GudangId', 'Status'], 'IdxReservasiSukuCadang');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
            $table->foreign('GudangId')->references('Id')->on('Gudang');
            $table->foreign('SukuCadangId')->references('Id')->on('SukuCadang');
            $table->foreign('DibuatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ReservasiSukuCadang');
    }
};
