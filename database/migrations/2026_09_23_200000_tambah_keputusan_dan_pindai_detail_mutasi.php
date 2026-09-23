<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('DetailMutasiAset', function (Blueprint $table): void {
            // Keputusan per aset: satu permintaan dapat disetujui sebagian.
            $table->char('DiputuskanOleh', 26)->nullable()->after('Status');
            $table->dateTime('DiputuskanPada', 6)->nullable()->after('DiputuskanOleh');
            $table->string('AlasanPenolakan', 500)->nullable()->after('DiputuskanPada');
            // Bukti fisik pengambilan: QR aset dipindai saat barang benar-benar diambil.
            $table->char('DipindaiOleh', 26)->nullable()->after('AlasanPenolakan');
            $table->dateTime('DipindaiPada', 6)->nullable()->after('DipindaiOleh');
            $table->foreign('DiputuskanOleh')->references('Id')->on('Pengguna');
            $table->foreign('DipindaiOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::table('DetailMutasiAset', function (Blueprint $table): void {
            $table->dropForeign(['DiputuskanOleh']);
            $table->dropForeign(['DipindaiOleh']);
            $table->dropColumn(['DiputuskanOleh', 'DiputuskanPada', 'AlasanPenolakan', 'DipindaiOleh', 'DipindaiPada']);
        });
    }
};
