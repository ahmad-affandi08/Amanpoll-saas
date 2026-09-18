<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('RencanaPemeliharaan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Kode', 80);
            $table->string('Nama', 200);
            $table->string('Jenis', 50)->default('Preventif');
            $table->char('TemplatDaftarPeriksaId', 26)->nullable();
            $table->string('Prioritas', 40)->default('Normal');
            $table->string('StrategiJadwal', 50)->default('Kalender');
            $table->unsignedInteger('IntervalNilai')->nullable();
            $table->string('IntervalSatuan', 30)->nullable();
            $table->boolean('BerdasarkanMeter')->default(0);
            $table->decimal('AmbangMeter', 20, 4)->nullable();
            $table->unsignedInteger('ToleransiHari')->default(0);
            $table->unsignedInteger('BuatPerintahKerjaHariSebelum')->default(7);
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Kode'], 'UqRencanaPemeliharaan');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('TemplatDaftarPeriksaId')->references('Id')->on('TemplatDaftarPeriksa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RencanaPemeliharaan');
    }
};
