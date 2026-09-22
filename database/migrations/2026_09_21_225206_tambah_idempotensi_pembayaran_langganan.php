<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Idempotensi webhook pembayaran (22.06). */
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
