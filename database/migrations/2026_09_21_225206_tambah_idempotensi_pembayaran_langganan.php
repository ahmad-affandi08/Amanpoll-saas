<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotensi webhook pembayaran (22.06).
 *
 * Penyedia pembayaran menjamin "at least once", jadi peristiwa yang sama akan
 * datang berkali-kali. Keunikan ditegakkan di basis data, bukan hanya dicek di
 * aplikasi, supaya dua proses yang memproses ulang peristiwa yang sama pada
 * saat bersamaan tetap hanya menghasilkan satu pembayaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('PembayaranLangganan', function (Blueprint $table): void {
            $table->string('IdPeristiwaPenyedia', 180)->nullable()->after('ReferensiEksternal');
            $table->unique(
                ['PenyediaPembayaran', 'IdPeristiwaPenyedia'],
                'UqPembayaranLanggananPeristiwa',
            );
        });
    }

    public function down(): void
    {
        Schema::table('PembayaranLangganan', function (Blueprint $table): void {
            $table->dropUnique('UqPembayaranLanggananPeristiwa');
            $table->dropColumn('IdPeristiwaPenyedia');
        });
    }
};
