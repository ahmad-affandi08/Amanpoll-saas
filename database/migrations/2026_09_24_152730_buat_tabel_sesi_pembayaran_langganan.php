<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sesi pembayaran tagihan langganan di payment gateway (PRD 8.23).
 *
 * Gateway menolak order id yang sama dua kali, jadi setiap percobaan bayar punya
 * `IdPesananPenyedia` sendiri. Klik "Bayar" berulang memakai sesi yang masih berlaku,
 * dan webhook menemukan tagihannya lewat kolom unik itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SesiPembayaranLangganan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('TagihanLanggananId', 26);
            $table->string('Penyedia', 50);
            $table->string('IdPesananPenyedia', 64)->unique('UqSesiPembayaranLanggananPesanan');
            $table->string('ReferensiPenyedia', 180)->nullable();
            $table->text('UrlPembayaran');
            $table->decimal('Jumlah', 20, 2);
            $table->dateTime('KedaluwarsaPada', 6);
            $table->string('Status', 20)->default('Menunggu');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['TagihanLanggananId', 'Penyedia', 'Status'], 'IdxSesiPembayaranLanggananTagihan');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('TagihanLanggananId')->references('Id')->on('TagihanLangganan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SesiPembayaranLangganan');
    }
};
