<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\ValueObjects;

use App\Domain\Platform\Domain\Enums\ModeLapangan;

/**
 * Peran siap pakai untuk organisasi yang baru berdiri.
 *
 * Tanpa ini sebuah organisasi hanya punya satu peran serba-bisa (Pemilik),
 * sehingga orang pertama yang perlu diberi akses terbatas harus menyusun
 * kombinasi izinnya sendiri dari nol. Katalog ini menerjemahkan 21 kode Izin
 * menjadi jabatan yang dikenal di lapangan, lalu tenant boleh mengubah atau
 * menghapusnya sesukanya.
 *
 * Dua izin sengaja tidak ikut di peran mana pun: `Stok.Override` (menyetujui
 * stok menjadi negatif) dan `Anggaran.Sesuaikan` (mengubah pagu yang sudah
 * ditetapkan). Keduanya menembus kontrol yang justru dijaga modulnya, jadi
 * harus diberikan sadar-sadar per orang, bukan menempel pada jabatan.
 *
 * Peran lapangan (bertanda `TampilanLapangan`) tidak memegang izin Kelola atas
 * keluhan maupun perintah kerja (PRD 8.20). Teknisi yang ditugaskan sudah boleh
 * melihat dan mengerjakan tiketnya lewat `PerintahKerjaPolicy`, dan membuat
 * keluhan tidak butuh izin apa pun; izin Kelola justru membuka seluruh tiket
 * dan keluhan organisasi kepada mereka.
 *
 * Katalog hanya dibaca saat peran dipasang. Tenant lama diselaraskan lewat
 * perintah `platform:terapkan-peran-lapangan` yang eksplisit dan teraudit.
 */
final class KatalogPeranAwal
{
    /**
     * Izin berisiko tinggi yang tidak boleh menempel pada peran bawaan.
     *
     * @var list<string>
     */
    public const IZIN_DIKECUALIKAN = [
        'Stok.Override',
        'Anggaran.Sesuaikan',
    ];

    /**
     * Izin yang dicabut dari peran lapangan bawaan pada FASE 39.
     *
     * Dipakai perintah artisan untuk menyelaraskan peran tenant lama yang lahir
     * dari katalog sebelumnya. Sengaja berupa daftar cabut, bukan penyamaan
     * penuh dengan katalog: izin lain yang ditambahkan tenant sendiri ke
     * perannya tidak boleh ikut hilang.
     *
     * @var array<string, list<string>>
     */
    public const IZIN_DICABUT_DARI_PERAN_LAPANGAN = [
        'TEKNISI' => ['PerintahKerja.Kelola', 'Keluhan.Kelola'],
        'PELAPOR' => ['Keluhan.Kelola'],
    ];

    /**
     * @return list<array{Kode: string, Nama: string, Keterangan: string, Izin: list<string>, TampilanLapangan: ModeLapangan|null}>
     */
    public static function semua(): array
    {
        return [
            [
                'Kode' => 'ADMIN-SISTEM',
                'Nama' => 'Administrator Sistem',
                'Keterangan' => 'Mengelola pengguna, peran, unit, lokasi, penomoran, dan integrasi. Tidak menyentuh data operasional.',
                'Izin' => ['Aset.Lihat', 'Pengguna.Kelola', 'Pengaturan.Kelola', 'Integrasi.Kelola', 'Audit.Lihat'],
                'TampilanLapangan' => null,
            ],
            [
                'Kode' => 'MANAJER-ASET',
                'Nama' => 'Manajer Aset',
                'Keterangan' => 'Pemilik data aset: mendaftar, memutasi, menghapus, serta memegang kontrak dan kepatuhannya.',
                'Izin' => [
                    'Aset.Lihat', 'Aset.Buat', 'Aset.Ubah', 'Aset.Hapus',
                    'Kontrak.Kelola', 'Kepatuhan.Kelola', 'Aspak.Kelola', 'Laporan.Lihat', 'Audit.Lihat',
                ],
                'TampilanLapangan' => null,
            ],
            [
                'Kode' => 'KOORDINATOR-PEMELIHARAAN',
                'Nama' => 'Koordinator Pemeliharaan',
                'Keterangan' => 'Membagi perintah kerja, menyusun rencana pemeliharaan dan kalibrasi, serta menutup keluhan.',
                'Izin' => [
                    'Aset.Lihat', 'Keluhan.Kelola', 'PerintahKerja.Kelola',
                    'Pemeliharaan.Kelola', 'Kalibrasi.Kelola', 'Laporan.Lihat',
                ],
                'TampilanLapangan' => null,
            ],
            [
                'Kode' => 'TEKNISI',
                'Nama' => 'Teknisi',
                'Keterangan' => 'Mengerjakan perintah kerja yang ditugaskan kepadanya dan inspeksi di lapangan. Suku cadang diminta ke gudang, bukan diubah sendiri.',
                'Izin' => ['Aset.Lihat', 'Pemeliharaan.Kelola'],
                'TampilanLapangan' => ModeLapangan::Teknisi,
            ],
            [
                'Kode' => 'PETUGAS-KALIBRASI',
                'Nama' => 'Petugas Kalibrasi',
                'Keterangan' => 'Menjalankan rencana kalibrasi, mencatat hasil ukur, dan menjaga sertifikasi tetap berlaku.',
                'Izin' => ['Aset.Lihat', 'Kalibrasi.Kelola', 'Kepatuhan.Kelola', 'Laporan.Lihat'],
                'TampilanLapangan' => null,
            ],
            [
                'Kode' => 'OPERATOR-GUDANG',
                'Nama' => 'Operator Gudang',
                'Keterangan' => 'Menerima, mengeluarkan, dan menghitung stok suku cadang beserta reservasinya.',
                'Izin' => ['Aset.Lihat', 'Stok.Kelola', 'Laporan.Lihat'],
                'TampilanLapangan' => null,
            ],
            [
                'Kode' => 'STAF-PENGADAAN',
                'Nama' => 'Staf Pengadaan',
                'Keterangan' => 'Menyusun rencana dan usulan pengadaan, mengelola penyedia serta kontraknya.',
                'Izin' => ['Aset.Lihat', 'Pengadaan.Kelola', 'Penyedia.Kelola', 'Kontrak.Kelola', 'Laporan.Lihat'],
                'TampilanLapangan' => null,
            ],
            [
                'Kode' => 'PENYETUJU',
                'Nama' => 'Penyetuju',
                'Keterangan' => 'Menyetujui atau menolak pengajuan yang masuk ke alur persetujuan. Tidak membuat pengajuannya sendiri.',
                'Izin' => ['Aset.Lihat', 'Persetujuan.Kelola', 'Laporan.Lihat'],
                'TampilanLapangan' => null,
            ],
            [
                'Kode' => 'AUDITOR',
                'Nama' => 'Auditor',
                'Keterangan' => 'Akses baca menyeluruh untuk pemeriksaan: aset, laporan, dan jejak audit. Tidak dapat mengubah apa pun.',
                'Izin' => ['Aset.Lihat', 'Laporan.Lihat', 'Audit.Lihat'],
                'TampilanLapangan' => null,
            ],
            [
                'Kode' => 'PELAPOR',
                'Nama' => 'Pelapor Keluhan',
                'Keterangan' => 'Peran paling sempit untuk staf unit: melihat aset unitnya, melaporkan kerusakan, dan memantau laporannya sendiri.',
                'Izin' => ['Aset.Lihat'],
                'TampilanLapangan' => ModeLapangan::Pelapor,
            ],
        ];
    }
}
