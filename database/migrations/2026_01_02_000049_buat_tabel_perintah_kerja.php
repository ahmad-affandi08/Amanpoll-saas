<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PerintahKerja', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Nomor', 100);
            $table->char('KeluhanId', 26)->nullable();
            $table->char('TingkatLayananId', 26)->nullable();
            $table->string('Jenis', 50);
            $table->string('Judul', 220);
            $table->text('Deskripsi')->nullable();
            $table->string('Prioritas', 40)->default('Normal');
            $table->string('Status', 40)->default('Draft');
            $table->char('LokasiId', 26)->nullable();
            $table->char('UnitOrganisasiId', 26)->nullable();
            $table->dateTime('DijadwalkanMulaiPada', 6)->nullable();
            $table->dateTime('DijadwalkanSelesaiPada', 6)->nullable();
            $table->dateTime('DiterimaPada', 6)->nullable();
            $table->dateTime('DimulaiPada', 6)->nullable();
            $table->dateTime('DiselesaikanPada', 6)->nullable();
            $table->dateTime('DitutupPada', 6)->nullable();
            $table->dateTime('BatasResponsPada', 6)->nullable();
            $table->dateTime('BatasPenyelesaianPada', 6)->nullable();
            $table->decimal('PersentaseSelesai', 5, 2)->default(0);
            $table->boolean('MembutuhkanWaktuHenti')->default(0);
            $table->boolean('MembutuhkanPersetujuan')->default(0);
            $table->text('RingkasanPenyelesaian')->nullable();
            $table->char('DibuatOleh', 26)->nullable();
            $table->unsignedInteger('Versi')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->unique(['OrganisasiId', 'Nomor'], 'UqPerintahKerjaNomor');
            $table->index(['OrganisasiId', 'Status', 'Prioritas', 'DijadwalkanMulaiPada'], 'IdxPerintahKerjaStatus');
            $table->index(['KeluhanId'], 'IdxPerintahKerjaKeluhan');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('KeluhanId')->references('Id')->on('Keluhan');
            $table->foreign('TingkatLayananId')->references('Id')->on('TingkatLayanan');
            $table->foreign('LokasiId')->references('Id')->on('Lokasi');
            $table->foreign('UnitOrganisasiId')->references('Id')->on('UnitOrganisasi');
            $table->foreign('DibuatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PerintahKerja');
    }
};
