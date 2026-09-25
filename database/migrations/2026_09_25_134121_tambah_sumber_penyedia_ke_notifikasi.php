<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Siapa yang mengantar notifikasi WhatsApp: nomor platform atau nomor milik organisasi.
 * Email tidak diisi: penyedianya baru dipilih di transport, termasuk pengalihan saat
 * email organisasi gagal, jadi nilainya tidak dapat dipastikan saat barisnya dibuat.
 *
 * Kuota WhatsApp bawaan menghitung baris `Platform` sebulan per organisasi menurut
 * `JadwalKirimPada` (jam aplikasi, bukan jam basis data), jadi
 * hitungan itu diberi indeks sendiri; tabel Notifikasi memuat setiap kabar in-app
 * dan tumbuh jauh lebih cepat daripada baris WhatsApp-nya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Notifikasi', function (Blueprint $table): void {
            $table->string('SumberPenyedia', 20)->nullable()->after('Kanal');
            $table->index(['OrganisasiId', 'Kanal', 'JadwalKirimPada'], 'IdxNotifikasiKuota');
        });
    }

    public function down(): void
    {
        Schema::table('Notifikasi', function (Blueprint $table): void {
            $table->dropIndex('IdxNotifikasiKuota');
            $table->dropColumn('SumberPenyedia');
        });
    }
};
