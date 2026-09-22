<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks penunjang urutan bawaan daftar yang dipaginasi server.
 *
 * Tiap daftar menyaring dengan OrganisasiId lalu mengurutkan Nama. Tanpa indeks
 * gabungan itu, MySQL menjalankan filesort atas seluruh baris milik organisasi
 * setiap kali satu halaman dibuka -- ongkos yang dulu tidak terasa karena
 * daftarnya dipotong di 500 baris.
 *
 * Pencarian tetap tanpa indeks: LIKE '%kata%' berawalan joker tidak dapat
 * memakai indeks apa pun, dan Amanpoll sengaja tidak memasang mesin pencari
 * terpisah supaya tetap berjalan di shared hosting.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private const INDEKS = [
        'SukuCadang' => 'IdxSukuCadangUrutNama',
        'Lokasi' => 'IdxLokasiUrutNama',
        'Pengguna' => 'IdxPenggunaUrutNama',
        'ModelAset' => 'IdxModelAsetUrutNama',
        'Merek' => 'IdxMerekUrutNama',
        'Tag' => 'IdxTagUrutNama',
    ];

    public function up(): void
    {
        foreach (self::INDEKS as $tabel => $nama) {
            Schema::table($tabel, function (Blueprint $blueprint) use ($nama): void {
                $blueprint->index(['OrganisasiId', 'Nama'], $nama);
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEKS as $tabel => $nama) {
            Schema::table($tabel, function (Blueprint $blueprint) use ($nama): void {
                $blueprint->dropIndex($nama);
            });
        }
    }
};
