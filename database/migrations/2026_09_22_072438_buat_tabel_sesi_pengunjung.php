<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sesi pengunjung anonim (MARKETING.md 24, 1.1).
 *
 * `PengenalPengunjung` adalah identitas yang bertahan — nilai cookie berdomain
 * induk — sedangkan satu baris di sini adalah satu kunjungan. Keduanya dipisah
 * karena perjalanan calon pelanggan berlangsung berhari-hari dan melintasi dua
 * host: yang pertama melekat pada orangnya, yang kedua pada kedatangannya.
 *
 * Tanpa OrganisasiId: pengunjung situs pemasaran belum menjadi tenant mana pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SesiPengunjung', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('PengenalPengunjung', 26);
            $table->string('AlamatIp', 64)->nullable();
            $table->text('AgenPengguna')->nullable();
            $table->string('Perangkat', 30)->nullable();
            $table->text('Referrer')->nullable();
            $table->text('LandingUrl')->nullable();
            $table->string('Host', 190)->nullable();
            $table->dateTime('DimulaiPada', 6)->useCurrent();
            $table->dateTime('TerakhirAktifPada', 6)->useCurrent();
            $table->index(['PengenalPengunjung', 'DimulaiPada'], 'IdxSesiPengunjungPengenal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SesiPengunjung');
    }
};
