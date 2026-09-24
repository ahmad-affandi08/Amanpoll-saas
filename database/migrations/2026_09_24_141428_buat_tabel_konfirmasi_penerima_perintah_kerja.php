<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan konfirmasi penerima pekerjaan (PRD 8.22).
 *
 * Satu baris per jawaban penerima: siapa, lewat cara apa, kapan, dan tanda tangan
 * yang dicap saat itu. `TandaTanganBerkasId` merujuk berkas, bukan salinan, jadi
 * mengganti tanda tangan profil tidak mengubah konfirmasi lama. `Berlaku` menandai
 * konfirmasi "Diterima" milik siklus penyelesaian yang sedang berjalan; dicabut saat
 * perintah kerja kembali ke Dikerjakan.
 *
 * Indeks `(PerintahKerjaId, Berlaku)` melayani satu-satunya kueri panas: "apakah
 * perintah kerja ini sudah dikonfirmasi", dibaca di detail, daftar, dan saat
 * verifikasi. `KunciPerangkat` unik per organisasi supaya kiriman ulang dari HP
 * teknisi (cara 3, antrean offline) tidak menggandakan konfirmasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KonfirmasiPenerimaPerintahKerja', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PerintahKerjaId', 26);
            $table->string('Metode', 30);
            $table->string('Hasil', 30);
            $table->char('PenggunaId', 26)->nullable();
            $table->char('DicatatOleh', 26)->nullable();
            $table->string('NamaPenerima', 150);
            $table->string('JabatanPenerima', 150)->nullable();
            $table->char('TandaTanganBerkasId', 26)->nullable();
            $table->text('Alasan')->nullable();
            $table->text('Ulasan')->nullable();
            $table->unsignedTinyInteger('Penilaian')->nullable();
            $table->boolean('Berlaku')->default(false);
            $table->string('KunciPerangkat', 64)->nullable();
            $table->dateTime('DikonfirmasiPada', 6);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['PerintahKerjaId', 'Berlaku'], 'IdxKonfirmasiPenerimaPerintahKerja');
            $table->unique(['OrganisasiId', 'KunciPerangkat'], 'UqKonfirmasiPenerimaKunciPerangkat');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PerintahKerjaId')->references('Id')->on('PerintahKerja');
            $table->foreign('PenggunaId')->references('Id')->on('Pengguna');
            $table->foreign('DicatatOleh')->references('Id')->on('Pengguna');
            $table->foreign('TandaTanganBerkasId')->references('Id')->on('Berkas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KonfirmasiPenerimaPerintahKerja');
    }
};
