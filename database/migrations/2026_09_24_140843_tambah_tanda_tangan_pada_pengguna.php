<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanda tangan tersimpan di profil pengguna (PRD 8.22).
 *
 * Kolom hanya menunjuk tanda tangan yang berlaku sekarang. Berkas tanda tangan
 * lama tidak dihapus saat diganti, karena konfirmasi penerima yang sudah
 * tercatat tetap merujuk berkas yang dipakai saat itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Pengguna', function (Blueprint $table): void {
            $table->char('TandaTanganBerkasId', 26)->nullable()->after('AvatarUrl');
            $table->foreign('TandaTanganBerkasId', 'FkPenggunaTandaTangan')->references('Id')->on('Berkas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('Pengguna', function (Blueprint $table): void {
            $table->dropForeign('FkPenggunaTandaTangan');
            $table->dropColumn('TandaTanganBerkasId');
        });
    }
};
