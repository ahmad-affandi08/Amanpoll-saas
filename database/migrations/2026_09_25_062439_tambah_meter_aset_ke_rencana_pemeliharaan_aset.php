<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rencana preventif berbasis meter perlu tahu meter mana yang dibaca: satu aset
 * boleh punya beberapa meter (jam mesin, kilometer). `NilaiMeterBerikutnya` sudah
 * ada sejak awal tetapi tanpa rujukan meter, sehingga ambangnya tidak pernah
 * dapat dievaluasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('RencanaPemeliharaanAset', function (Blueprint $table): void {
            $table->char('MeterAsetId', 26)->nullable()->after('AsetId');
            $table->foreign('MeterAsetId', 'FkRencanaPemeliharaanAsetMeter')->references('Id')->on('MeterAset');
        });
    }

    public function down(): void
    {
        Schema::table('RencanaPemeliharaanAset', function (Blueprint $table): void {
            $table->dropForeign('FkRencanaPemeliharaanAsetMeter');
            $table->dropColumn('MeterAsetId');
        });
    }
};
