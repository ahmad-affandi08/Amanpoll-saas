<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Izin dasar platform Amanpoll (bukan data tenant). Idempotent lewat upsert.
 */
final class IzinSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('Izin')->upsert(
            [
                ['Id' => '01JAMANPOLL000000000000001', 'Kode' => 'Aset.Lihat', 'Nama' => 'Lihat Aset', 'Modul' => 'Aset'],
                ['Id' => '01JAMANPOLL000000000000002', 'Kode' => 'Aset.Buat', 'Nama' => 'Buat Aset', 'Modul' => 'Aset'],
                ['Id' => '01JAMANPOLL000000000000003', 'Kode' => 'Aset.Ubah', 'Nama' => 'Ubah Aset', 'Modul' => 'Aset'],
                ['Id' => '01JAMANPOLL000000000000004', 'Kode' => 'Aset.Hapus', 'Nama' => 'Hapus Aset', 'Modul' => 'Aset'],
                ['Id' => '01JAMANPOLL000000000000005', 'Kode' => 'Keluhan.Kelola', 'Nama' => 'Kelola Keluhan', 'Modul' => 'Keluhan'],
                ['Id' => '01JAMANPOLL000000000000006', 'Kode' => 'PerintahKerja.Kelola', 'Nama' => 'Kelola Perintah Kerja', 'Modul' => 'PerintahKerja'],
                ['Id' => '01JAMANPOLL000000000000007', 'Kode' => 'Pemeliharaan.Kelola', 'Nama' => 'Kelola Pemeliharaan', 'Modul' => 'Pemeliharaan'],
                ['Id' => '01JAMANPOLL000000000000008', 'Kode' => 'Kalibrasi.Kelola', 'Nama' => 'Kelola Kalibrasi', 'Modul' => 'Kalibrasi'],
                ['Id' => '01JAMANPOLL000000000000009', 'Kode' => 'Stok.Kelola', 'Nama' => 'Kelola Stok dan Suku Cadang', 'Modul' => 'Persediaan'],
                ['Id' => '01JAMANPOLL000000000000010', 'Kode' => 'Pengadaan.Kelola', 'Nama' => 'Kelola Pengadaan', 'Modul' => 'Pengadaan'],
                ['Id' => '01JAMANPOLL000000000000011', 'Kode' => 'Penyedia.Kelola', 'Nama' => 'Kelola Penyedia', 'Modul' => 'Penyedia'],
                ['Id' => '01JAMANPOLL000000000000012', 'Kode' => 'Kontrak.Kelola', 'Nama' => 'Kelola Kontrak', 'Modul' => 'Kontrak'],
                ['Id' => '01JAMANPOLL000000000000013', 'Kode' => 'Persetujuan.Kelola', 'Nama' => 'Kelola Persetujuan', 'Modul' => 'Persetujuan'],
                ['Id' => '01JAMANPOLL000000000000014', 'Kode' => 'Laporan.Lihat', 'Nama' => 'Lihat Laporan', 'Modul' => 'Laporan'],
                ['Id' => '01JAMANPOLL000000000000015', 'Kode' => 'Pengguna.Kelola', 'Nama' => 'Kelola Pengguna', 'Modul' => 'IAM'],
                ['Id' => '01JAMANPOLL000000000000016', 'Kode' => 'Pengaturan.Kelola', 'Nama' => 'Kelola Pengaturan', 'Modul' => 'Sistem'],
                ['Id' => '01JAMANPOLL000000000000017', 'Kode' => 'Audit.Lihat', 'Nama' => 'Lihat Audit', 'Modul' => 'Audit'],
                ['Id' => '01JAMANPOLL000000000000018', 'Kode' => 'Integrasi.Kelola', 'Nama' => 'Kelola Integrasi', 'Modul' => 'Integrasi'],
                ['Id' => '01JAMANPOLL000000000000019', 'Kode' => 'Stok.Override', 'Nama' => 'Izinkan Penyesuaian Stok Negatif', 'Modul' => 'Persediaan'],
                ['Id' => '01JAMANPOLL000000000000020', 'Kode' => 'Anggaran.Sesuaikan', 'Nama' => 'Lakukan Penyesuaian Anggaran', 'Modul' => 'Pengadaan'],
            ],
            uniqueBy: ['Id'],
            update: ['Kode', 'Nama', 'Modul'],
        );
    }
}
