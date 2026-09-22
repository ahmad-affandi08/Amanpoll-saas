<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Satu buku konsen untuk email dan WhatsApp, bukan dua daftar terpisah (MARKETING.md 16, 27). */
return new class extends Migration
{
    public function up(): void
    {
        // Kolom bernama Email yang berisi nomor telepon adalah jebakan bagi pembaca berikutnya.
        Schema::table('KonsenPemasaran', function (Blueprint $table): void {
            $table->dropIndex('IdxKonsenEmail');
            $table->renameColumn('Email', 'Kontak');
            $table->string('Kanal', 20)->default('Email')->after('ProspekId');
        });

        Schema::table('KonsenPemasaran', function (Blueprint $table): void {
            $table->index(['Kanal', 'Kontak', 'DicatatPada'], 'IdxKonsenKontak');
        });

        Schema::table('DaftarSupresi', function (Blueprint $table): void {
            $table->dropUnique('UnqDaftarSupresiHash');
            $table->renameColumn('EmailHash', 'KontakHash');
            $table->renameColumn('Email', 'Kontak');
            $table->string('Kanal', 20)->default('Email')->after('Id');
        });

        Schema::table('DaftarSupresi', function (Blueprint $table): void {
            // Berhenti dari WhatsApp tidak otomatis berarti berhenti dari email; keduanya disupresi terpisah.
            $table->unique(['Kanal', 'KontakHash'], 'UnqDaftarSupresiKanalHash');
        });
    }

    public function down(): void
    {
        Schema::table('DaftarSupresi', function (Blueprint $table): void {
            $table->dropUnique('UnqDaftarSupresiKanalHash');
            $table->dropColumn('Kanal');
            $table->renameColumn('KontakHash', 'EmailHash');
            $table->renameColumn('Kontak', 'Email');
        });

        Schema::table('DaftarSupresi', function (Blueprint $table): void {
            $table->unique(['EmailHash'], 'UnqDaftarSupresiHash');
        });

        Schema::table('KonsenPemasaran', function (Blueprint $table): void {
            $table->dropIndex('IdxKonsenKontak');
            $table->dropColumn('Kanal');
            $table->renameColumn('Kontak', 'Email');
        });

        Schema::table('KonsenPemasaran', function (Blueprint $table): void {
            $table->index(['Email', 'DicatatPada'], 'IdxKonsenEmail');
        });
    }
};
