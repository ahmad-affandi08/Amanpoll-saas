<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Keluhan', function (Blueprint $table): void {
            // Urgensi berbahasa awam yang dipilih pelapor di Mode Lapangan (PRD 8.20):
            // hanya usulan, bukan Prioritas. Tanpa indeks: dibaca per keluhan,
            // tidak pernah disaring di daftar.
            $table->string('UsulanUrgensi', 40)->nullable()->after('Prioritas');
        });
    }

    public function down(): void
    {
        Schema::table('Keluhan', function (Blueprint $table): void {
            $table->dropColumn('UsulanUrgensi');
        });
    }
};
