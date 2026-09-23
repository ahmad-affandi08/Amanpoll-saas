<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AlkesAspak', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->string('Kode', 60);
            $table->string('Nama', 255);
            $table->string('Kelompok', 160)->nullable();
            $table->string('Satuan', 40)->nullable();
            $table->boolean('Aktif')->default(true);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            // Kode alkes adalah kunci yang dipakai ASPAK, jadi harus tunggal per organisasi.
            $table->unique(['OrganisasiId', 'Kode'], 'UqAlkesAspakKode');
            $table->index(['OrganisasiId', 'Nama'], 'IdxAlkesAspakNama');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
        });

        Schema::create('PemetaanAspak', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AlkesAspakId', 26);
            $table->char('KategoriAsetId', 26)->nullable();
            $table->char('ModelAsetId', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            // Satu kategori atau model hanya boleh menunjuk satu kode alkes; kalau
            // tidak, ekspornya akan melaporkan aset yang sama sebagai dua alat.
            $table->unique(['OrganisasiId', 'KategoriAsetId'], 'UqPemetaanAspakKategori');
            $table->unique(['OrganisasiId', 'ModelAsetId'], 'UqPemetaanAspakModel');
            $table->index(['OrganisasiId', 'AlkesAspakId'], 'IdxPemetaanAspakAlkes');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AlkesAspakId')->references('Id')->on('AlkesAspak');
            $table->foreign('KategoriAsetId')->references('Id')->on('KategoriAset');
            $table->foreign('ModelAsetId')->references('Id')->on('ModelAset');
        });

        Schema::table('Lokasi', function (Blueprint $table): void {
            // ASPAK melaporkan alat per ruang, sedangkan lokasi kita dinamai bebas.
            $table->string('KodeRuangAspak', 60)->nullable()->after('Kode');
        });
    }

    public function down(): void
    {
        Schema::table('Lokasi', function (Blueprint $table): void {
            $table->dropColumn('KodeRuangAspak');
        });

        Schema::dropIfExists('PemetaanAspak');
        Schema::dropIfExists('AlkesAspak');
    }
};
