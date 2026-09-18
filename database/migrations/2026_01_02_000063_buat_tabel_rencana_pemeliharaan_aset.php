<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('RencanaPemeliharaanAset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('RencanaPemeliharaanId', 26);
            $table->char('AsetId', 26);
            $table->date('TanggalMulai');
            $table->date('TanggalBerikutnya')->nullable();
            $table->decimal('NilaiMeterBerikutnya', 20, 4)->nullable();
            $table->dateTime('TerakhirDilaksanakanPada', 6)->nullable();
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['RencanaPemeliharaanId', 'AsetId'], 'UqRencanaPemeliharaanAset');
            $table->index(['OrganisasiId', 'TanggalBerikutnya', 'Aktif'], 'IdxRencanaPemeliharaanAsetTanggal');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('RencanaPemeliharaanId')->references('Id')->on('RencanaPemeliharaan');
            $table->foreign('AsetId')->references('Id')->on('Aset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RencanaPemeliharaanAset');
    }
};
