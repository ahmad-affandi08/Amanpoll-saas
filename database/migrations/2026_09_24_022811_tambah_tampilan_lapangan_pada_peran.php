<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Peran', function (Blueprint $table): void {
            // Penanda Mode Lapangan (PRD 8.20): kosong untuk peran meja,
            // `Teknisi` atau `Pelapor` untuk peran lapangan. Tanpa indeks:
            // dibaca per pengguna lewat PenggunaPeran, bukan disaring per daftar.
            $table->string('TampilanLapangan', 20)->nullable()->after('BawaanSistem');
        });
    }

    public function down(): void
    {
        Schema::table('Peran', function (Blueprint $table): void {
            $table->dropColumn('TampilanLapangan');
        });
    }
};
