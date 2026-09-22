<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Izin granular untuk admin platform (MARKETING.md 26).
 *
 * Sampai FASE 22 guard `platform` sendiri yang menjadi otorisasinya: setiap
 * admin platform sama kuat. Konsol Growth & Marketing mengubah itu — melihat
 * daftar prospek dan mengekspornya adalah dua kewenangan berbeda, dan
 * MARKETING.md menuntut izin ekspor terpisah.
 *
 * Disimpan sebagai daftar kode pada barisnya sendiri, bukan sebagai tabel peran
 * tersendiri: admin platform berjumlah sedikit dan tidak punya hierarki unit
 * seperti pengguna tenant, sehingga meniru RBAC tenant di sini hanya akan
 * menduplikasi IAM tanpa ada yang memakainya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('AdminPlatform', function (Blueprint $table): void {
            $table->json('Izin')->nullable()->after('Status');
            $table->boolean('SuperAdmin')->default(false)->after('Izin');
        });
    }

    public function down(): void
    {
        Schema::table('AdminPlatform', function (Blueprint $table): void {
            $table->dropColumn(['Izin', 'SuperAdmin']);
        });
    }
};
