<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('StokSukuCadang', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('GudangId', 26);
            $table->char('LokasiGudangId', 26)->nullable();
            $table->char('SukuCadangId', 26);
            $table->char('KelompokSukuCadangId', 26)->nullable();
            $table->decimal('JumlahTersedia', 20, 4)->default(0);
            $table->decimal('JumlahDipesan', 20, 4)->default(0);
            $table->decimal('JumlahDitahan', 20, 4)->default(0);
            $table->unsignedInteger('Versi')->default(1);
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['GudangId', 'LokasiGudangId', 'SukuCadangId', 'KelompokSukuCadangId'], 'UqStokSukuCadang');
            $table->index(['OrganisasiId', 'SukuCadangId', 'GudangId'], 'IdxStokSukuCadang');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('GudangId')->references('Id')->on('Gudang');
            $table->foreign('LokasiGudangId')->references('Id')->on('LokasiGudang');
            $table->foreign('SukuCadangId')->references('Id')->on('SukuCadang');
            $table->foreign('KelompokSukuCadangId')->references('Id')->on('KelompokSukuCadang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('StokSukuCadang');
    }
};
