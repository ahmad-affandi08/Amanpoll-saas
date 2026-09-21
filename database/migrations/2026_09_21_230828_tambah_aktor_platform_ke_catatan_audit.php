<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelaku dari konsol platform pada catatan audit (22.02).
 *
 * PenggunaId ber-foreign key ke tabel Pengguna, sehingga tidak dapat menampung
 * identitas admin platform. Tanpa kolom tersendiri, perubahan paket dan
 * langganan — yang berdampak uang — akan tercatat tanpa pelaku sama sekali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('CatatanAudit', function (Blueprint $table): void {
            $table->char('AktorPlatformId', 26)->nullable()->after('PenggunaId');
            $table->index(['AktorPlatformId', 'DibuatPada'], 'IdxCatatanAuditAktorPlatform');
            $table->foreign('AktorPlatformId')->references('Id')->on('AdminPlatform');
        });
    }

    public function down(): void
    {
        Schema::table('CatatanAudit', function (Blueprint $table): void {
            $table->dropForeign(['AktorPlatformId']);
            $table->dropIndex('IdxCatatanAuditAktorPlatform');
            $table->dropColumn('AktorPlatformId');
        });
    }
};
