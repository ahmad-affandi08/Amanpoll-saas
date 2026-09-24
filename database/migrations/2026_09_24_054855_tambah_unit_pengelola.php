<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unit pengelola (PRD 8.21): bagian yang memelihara aset, mis. IPSRS dan IT.
 *
 * Bukan tabel baru: unit pengelola adalah UnitOrganisasi bertanda
 * `MengelolaAset`. Seluruh kolom `UnitPengelolaId` nullable dan tidak diisi di
 * sini -- organisasi dengan satu bagian pemeliharaan tetap berperilaku seperti
 * sebelumnya, dan data lama hanya diisi lewat perintah artisan eksplisit.
 *
 * Indeks `(OrganisasiId, UnitPengelolaId)` melayani antrian, daftar, dan
 * laporan yang menyaring organisasi lalu unit pengelola.
 *
 * Tiap tabel ditulis harfiah, bukan lewat perulangan: Larastan membaca
 * migrasi untuk mengenali kolom model, dan nama tabel dari variabel tidak
 * terbaca olehnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('UnitOrganisasi', function (Blueprint $table): void {
            $table->boolean('MengelolaAset')->default(false)->after('Status');
        });

        Schema::table('Aset', function (Blueprint $table): void {
            $table->char('UnitPengelolaId', 26)->nullable()->after('UnitOrganisasiId');
            $table->index(['OrganisasiId', 'UnitPengelolaId'], 'IdxAsetUnitPengelola');
            $table->foreign('UnitPengelolaId')->references('Id')->on('UnitOrganisasi');
        });

        Schema::table('KategoriKeluhan', function (Blueprint $table): void {
            $table->char('UnitPengelolaId', 26)->nullable()->after('PeranPenanggungJawabId');
            $table->index(['OrganisasiId', 'UnitPengelolaId'], 'IdxKategoriKeluhanUnitPengelola');
            $table->foreign('UnitPengelolaId')->references('Id')->on('UnitOrganisasi');
        });

        Schema::table('Keluhan', function (Blueprint $table): void {
            $table->char('UnitPengelolaId', 26)->nullable()->after('LokasiId');
            $table->index(['OrganisasiId', 'UnitPengelolaId'], 'IdxKeluhanUnitPengelola');
            $table->foreign('UnitPengelolaId')->references('Id')->on('UnitOrganisasi');
        });

        Schema::table('PerintahKerja', function (Blueprint $table): void {
            $table->char('UnitPengelolaId', 26)->nullable()->after('UnitOrganisasiId');
            $table->index(['OrganisasiId', 'UnitPengelolaId'], 'IdxPerintahKerjaUnitPengelola');
            $table->foreign('UnitPengelolaId')->references('Id')->on('UnitOrganisasi');
        });

        Schema::table('Gudang', function (Blueprint $table): void {
            $table->char('UnitPengelolaId', 26)->nullable()->after('LokasiId');
            $table->index(['OrganisasiId', 'UnitPengelolaId'], 'IdxGudangUnitPengelola');
            $table->foreign('UnitPengelolaId')->references('Id')->on('UnitOrganisasi');
        });

        Schema::table('RencanaPemeliharaan', function (Blueprint $table): void {
            $table->char('UnitPengelolaId', 26)->nullable()->after('TemplatDaftarPeriksaId');
            $table->index(['OrganisasiId', 'UnitPengelolaId'], 'IdxRencanaPemeliharaanUnitPengelola');
            $table->foreign('UnitPengelolaId')->references('Id')->on('UnitOrganisasi');
        });

        Schema::table('RencanaKalibrasi', function (Blueprint $table): void {
            $table->char('UnitPengelolaId', 26)->nullable()->after('AsetId');
            $table->index(['OrganisasiId', 'UnitPengelolaId'], 'IdxRencanaKalibrasiUnitPengelola');
            $table->foreign('UnitPengelolaId')->references('Id')->on('UnitOrganisasi');
        });
    }

    public function down(): void
    {
        Schema::table('Aset', function (Blueprint $table): void {
            $table->dropForeign(['UnitPengelolaId']);
            $table->dropIndex('IdxAsetUnitPengelola');
            $table->dropColumn('UnitPengelolaId');
        });

        Schema::table('KategoriKeluhan', function (Blueprint $table): void {
            $table->dropForeign(['UnitPengelolaId']);
            $table->dropIndex('IdxKategoriKeluhanUnitPengelola');
            $table->dropColumn('UnitPengelolaId');
        });

        Schema::table('Keluhan', function (Blueprint $table): void {
            $table->dropForeign(['UnitPengelolaId']);
            $table->dropIndex('IdxKeluhanUnitPengelola');
            $table->dropColumn('UnitPengelolaId');
        });

        Schema::table('PerintahKerja', function (Blueprint $table): void {
            $table->dropForeign(['UnitPengelolaId']);
            $table->dropIndex('IdxPerintahKerjaUnitPengelola');
            $table->dropColumn('UnitPengelolaId');
        });

        Schema::table('Gudang', function (Blueprint $table): void {
            $table->dropForeign(['UnitPengelolaId']);
            $table->dropIndex('IdxGudangUnitPengelola');
            $table->dropColumn('UnitPengelolaId');
        });

        Schema::table('RencanaPemeliharaan', function (Blueprint $table): void {
            $table->dropForeign(['UnitPengelolaId']);
            $table->dropIndex('IdxRencanaPemeliharaanUnitPengelola');
            $table->dropColumn('UnitPengelolaId');
        });

        Schema::table('RencanaKalibrasi', function (Blueprint $table): void {
            $table->dropForeign(['UnitPengelolaId']);
            $table->dropIndex('IdxRencanaKalibrasiUnitPengelola');
            $table->dropColumn('UnitPengelolaId');
        });

        Schema::table('UnitOrganisasi', function (Blueprint $table): void {
            $table->dropColumn('MengelolaAset');
        });
    }
};
