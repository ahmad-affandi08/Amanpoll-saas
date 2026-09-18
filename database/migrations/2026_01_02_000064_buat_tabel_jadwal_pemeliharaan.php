<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('JadwalPemeliharaan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('RencanaPemeliharaanAsetId', 26);
            $table->char('PerintahKerjaId', 26)->nullable();
            $table->date('TanggalJadwal');
            $table->string('Status', 40)->default('Terjadwal');
            $table->boolean('DihasilkanOtomatis')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['RencanaPemeliharaanAsetId', 'TanggalJadwal'], 'UqJadwalPemeliharaan');
            $table->index(['OrganisasiId', 'TanggalJadwal', 'Status'], 'IdxJadwalPemeliharaanTanggal');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('RencanaPemeliharaanAsetId')->references('Id')->on('RencanaPemeliharaanAset');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('JadwalPemeliharaan');
    }
};
