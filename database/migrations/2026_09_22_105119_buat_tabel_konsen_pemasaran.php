<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Consent, suppression list, dan permintaan penghapusan data (MARKETING.md 27). */
return new class extends Migration
{
    public function up(): void
    {
        // Hanya-tambah: bukti yang dapat disunting belakangan bukan bukti, jadi pencabutan ditulis sebagai baris baru.
        Schema::create('KonsenPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProspekId', 26)->nullable();
            $table->string('Email', 190);
            $table->boolean('Diberikan');
            $table->string('Sumber', 40);
            $table->string('VersiKebijakan', 40);
            $table->string('AlamatIp', 45)->nullable();
            $table->string('AgenPengguna', 500)->nullable();
            $table->dateTime('DicatatPada', 6);

            $table->index(['Email', 'DicatatPada'], 'IdxKonsenEmail');
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->nullOnDelete();
        });

        // Dikunci pada hash: permintaan penghapusan boleh membuang alamatnya tanpa membuka kembali pintu kirim.
        Schema::create('DaftarSupresi', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('EmailHash', 64)->unique('UnqDaftarSupresiHash');
            $table->string('Email', 190)->nullable();
            $table->string('Alasan', 40);
            $table->string('Catatan', 500)->nullable();
            $table->dateTime('DitambahkanPada', 6);
        });

        Schema::create('PermintaanDataProspek', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProspekId', 26)->nullable();
            $table->char('EmailHash', 64);
            $table->string('Email', 190)->nullable();
            $table->string('Jenis', 30);
            $table->string('Catatan', 500)->nullable();
            $table->dateTime('DimintaPada', 6);
            $table->dateTime('DiprosesPada', 6)->nullable();

            $table->index(['DiprosesPada'], 'IdxPermintaanDataMenunggu');
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PermintaanDataProspek');
        Schema::dropIfExists('DaftarSupresi');
        Schema::dropIfExists('KonsenPemasaran');
    }
};
