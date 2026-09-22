<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Berkas lead magnet menempel pada formulirnya, bukan tabel baru (MARKETING.md 10). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('FormulirPemasaran', function (Blueprint $table): void {
            // Disimpan di disk privat; tidak pernah punya URL publik yang dapat ditebak.
            $table->string('BerkasLokasi', 500)->nullable()->after('UrlWebhook');
            $table->string('BerkasNamaAsli', 190)->nullable()->after('BerkasLokasi');
            $table->string('BerkasMime', 120)->nullable()->after('BerkasNamaAsli');
            $table->unsignedBigInteger('BerkasUkuranByte')->nullable()->after('BerkasMime');
        });
    }

    public function down(): void
    {
        Schema::table('FormulirPemasaran', function (Blueprint $table): void {
            $table->dropColumn(['BerkasLokasi', 'BerkasNamaAsli', 'BerkasMime', 'BerkasUkuranByte']);
        });
    }
};
