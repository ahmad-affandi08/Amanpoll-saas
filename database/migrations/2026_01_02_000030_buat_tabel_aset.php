<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Aset', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('UnitOrganisasiId', 26)->nullable();
            $table->char('LokasiId', 26)->nullable();
            $table->char('KategoriAsetId', 26);
            $table->char('ModelAsetId', 26)->nullable();
            $table->char('PenyediaId', 26)->nullable();
            $table->string('KodeAset', 100);
            $table->string('Nama', 200);
            $table->string('NomorSeri', 160)->nullable();
            $table->string('NomorInventaris', 160)->nullable();
            $table->string('NomorRegistrasiEksternal', 160)->nullable();
            $table->date('TanggalPerolehan')->nullable();
            $table->date('TanggalMulaiOperasi')->nullable();
            $table->date('TanggalAkhirOperasi')->nullable();
            $table->decimal('HargaPerolehan', 20, 2)->nullable();
            $table->decimal('NilaiResidu', 20, 2)->nullable();
            $table->char('MataUang', 3)->default('IDR');
            $table->string('SumberDana', 120)->nullable();
            $table->string('MetodePenyusutan', 40)->nullable();
            $table->unsignedInteger('UmurManfaatBulan')->nullable();
            $table->string('Status', 40)->default('Aktif');
            $table->string('Kondisi', 40)->default('Baik');
            $table->string('TingkatKritis', 30)->default('Normal');
            $table->string('KodeQr', 255)->nullable();
            $table->string('NfcUid', 255)->nullable();
            $table->string('KodeBatang', 255)->nullable();
            $table->text('Catatan')->nullable();
            $table->unsignedInteger('Versi')->default(1);
            $table->char('DibuatOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->unique(['OrganisasiId', 'KodeAset'], 'UqAsetKode');
            $table->index(['OrganisasiId', 'NomorSeri'], 'IdxAsetNomorSeri');
            $table->index(['OrganisasiId', 'LokasiId', 'Status'], 'IdxAsetLokasi');
            $table->index(['OrganisasiId', 'KategoriAsetId', 'Status'], 'IdxAsetKategori');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('UnitOrganisasiId')->references('Id')->on('UnitOrganisasi');
            $table->foreign('LokasiId')->references('Id')->on('Lokasi');
            $table->foreign('KategoriAsetId')->references('Id')->on('KategoriAset');
            $table->foreign('ModelAsetId')->references('Id')->on('ModelAset');
            $table->foreign('PenyediaId')->references('Id')->on('Penyedia');
            $table->foreign('DibuatOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Aset');
    }
};
