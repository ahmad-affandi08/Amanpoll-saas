<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain;

use App\Domain\Pelaporan\Domain\Enums\KelompokKpi;
use App\Domain\Pelaporan\Domain\Enums\SatuanKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\DefinisiKpi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;

/** Katalog seluruh KPI Amanpoll beserta rumusnya (Gate 21). */
final class KatalogKpi
{
    /** @var array<string, DefinisiKpi>|null */
    private static ?array $cache = null;

    /** @return array<string, DefinisiKpi> */
    public static function semua(): array
    {
        return self::$cache ??= self::bangun();
    }

    public static function ada(string $kunci): bool
    {
        return isset(self::semua()[$kunci]);
    }

    public static function ambil(string $kunci): DefinisiKpi
    {
        return self::semua()[$kunci]
            ?? throw new DataTidakDitemukan("KPI {$kunci} tidak dikenal.");
    }

    /** @return list<string> */
    public static function kunci(): array
    {
        return array_keys(self::semua());
    }

    /** @return list<DefinisiKpi> */
    public static function untukKelompok(KelompokKpi $kelompok): array
    {
        return array_values(array_filter(
            self::semua(),
            fn (DefinisiKpi $definisi): bool => $definisi->kelompok === $kelompok,
        ));
    }

    /** @return array<string, DefinisiKpi> */
    private static function bangun(): array
    {
        $daftar = [
            // 21.01 Asset counts & condition -------------------------------
            new DefinisiKpi(
                'aset.jumlah', 'Jumlah Aset', KelompokKpi::Aset, SatuanKpi::Jumlah,
                'COUNT(Aset) yang belum dihapus, dipecah per Status.',
                'Aset',
                'Aset.Lihat',
            ),
            new DefinisiKpi(
                'aset.nilai_perolehan', 'Nilai Perolehan Aset', KelompokKpi::Aset, SatuanKpi::Uang,
                'SUM(Aset.HargaPerolehan) atas aset yang belum dihapus.',
                'Aset',
                'Aset.Lihat',
            ),
            new DefinisiKpi(
                'aset.kondisi', 'Distribusi Kondisi Aset', KelompokKpi::Aset, SatuanKpi::Persen,
                'Persentase aset berkondisi Baik = COUNT(Kondisi=Baik) / COUNT(Aset dengan kondisi terisi) x 100. Rincian memuat jumlah per kondisi.',
                'Aset',
                'Aset.Lihat',
                naikItuBaik: true,
                satuanRincian: SatuanKpi::Jumlah,
            ),

            // 21.01 Complaint ----------------------------------------------
            new DefinisiKpi(
                'keluhan.terbuka', 'Keluhan Terbuka', KelompokKpi::Keluhan, SatuanKpi::Jumlah,
                'COUNT(Keluhan) yang statusnya belum Selesai maupun Ditutup, tanpa memandang rentang tanggal karena keluhan lama yang masih menggantung tetap harus terlihat.',
                'Keluhan',
                'Keluhan.Kelola',
                naikItuBaik: false,
            ),
            new DefinisiKpi(
                'keluhan.masuk', 'Keluhan Masuk', KelompokKpi::Keluhan, SatuanKpi::Jumlah,
                'COUNT(Keluhan) dengan DilaporkanPada di dalam rentang, dipecah per hari dan per prioritas.',
                'Keluhan',
                'Keluhan.Kelola',
            ),
            new DefinisiKpi(
                'keluhan.waktu_respons', 'Rata-rata Waktu Respons Keluhan', KelompokKpi::Keluhan, SatuanKpi::Menit,
                'AVG(selisih menit DilaporkanPada sampai DiresponsPada) atas keluhan yang sudah direspons di dalam rentang.',
                'Keluhan',
                'Keluhan.Kelola',
                naikItuBaik: false,
            ),

            // 21.01 Work order ---------------------------------------------
            new DefinisiKpi(
                'perintah_kerja.aktif', 'Perintah Kerja Aktif', KelompokKpi::PerintahKerja, SatuanKpi::Jumlah,
                'COUNT(PerintahKerja) yang belum Selesai, Ditutup, atau Dibatalkan. Rincian memuat jumlah per status.',
                'PerintahKerja',
                null,
                naikItuBaik: false,
            ),
            new DefinisiKpi(
                'perintah_kerja.selesai', 'Perintah Kerja Selesai', KelompokKpi::PerintahKerja, SatuanKpi::Jumlah,
                'COUNT(PerintahKerja) dengan DiselesaikanPada di dalam rentang, dipecah per hari.',
                'PerintahKerja',
                null,
                naikItuBaik: true,
            ),
            new DefinisiKpi(
                'perintah_kerja.terlambat', 'Perintah Kerja Terlambat', KelompokKpi::PerintahKerja, SatuanKpi::Jumlah,
                'COUNT(PerintahKerja) yang belum selesai dan BatasPenyelesaianPada sudah lewat saat ini.',
                'PerintahKerja',
                null,
                naikItuBaik: false,
            ),
            new DefinisiKpi(
                'perintah_kerja.terencana', 'Porsi Pekerjaan Terencana', KelompokKpi::PerintahKerja, SatuanKpi::Persen,
                'COUNT(PerintahKerja berjenis Preventif, Inspeksi, atau Kalibrasi) / COUNT(seluruh PerintahKerja dalam rentang) x 100.',
                'PerintahKerja',
                null,
                naikItuBaik: true,
                satuanRincian: SatuanKpi::Jumlah,
            ),

            // 21.01 SLA ------------------------------------------------------
            new DefinisiKpi(
                'sla.kepatuhan_penyelesaian', 'Kepatuhan SLA Penyelesaian', KelompokKpi::TingkatLayanan, SatuanKpi::Persen,
                'COUNT(PerintahKerja yang DiselesaikanPada <= BatasPenyelesaianPada) / COUNT(PerintahKerja selesai yang punya batas penyelesaian) x 100, atas pekerjaan yang selesai di dalam rentang.',
                'PerintahKerja',
                null,
                naikItuBaik: true,
                satuanRincian: SatuanKpi::Jumlah,
            ),
            new DefinisiKpi(
                'sla.kepatuhan_respons', 'Kepatuhan SLA Respons', KelompokKpi::TingkatLayanan, SatuanKpi::Persen,
                'COUNT(PerintahKerja yang DiterimaPada <= BatasResponsPada) / COUNT(PerintahKerja yang sudah diterima dan punya batas respons) x 100, atas pekerjaan yang dibuat di dalam rentang.',
                'PerintahKerja',
                null,
                naikItuBaik: true,
                satuanRincian: SatuanKpi::Jumlah,
            ),
            new DefinisiKpi(
                'sla.berisiko', 'SLA Berisiko', KelompokKpi::TingkatLayanan, SatuanKpi::Jumlah,
                'COUNT(PerintahKerja belum selesai yang BatasPenyelesaianPada jatuh dalam 24 jam ke depan).',
                'PerintahKerja',
                null,
                naikItuBaik: false,
            ),

            // 21.01 Downtime, MTTR, MTBF ------------------------------------
            new DefinisiKpi(
                'downtime.total_jam', 'Total Downtime', KelompokKpi::Keandalan, SatuanKpi::Jam,
                'SUM(WaktuHentiAset.DurasiMenit) / 60 atas downtime yang mulai di dalam rentang. Sesi yang belum berakhir dihitung sampai batas akhir rentang.',
                'WaktuHentiAset',
                null,
                naikItuBaik: false,
            ),
            new DefinisiKpi(
                'downtime.ketersediaan', 'Ketersediaan Aset', KelompokKpi::Keandalan, SatuanKpi::Persen,
                '(1 - total menit downtime / (jumlah aset terdampak x menit dalam rentang)) x 100. Dihitung hanya atas aset yang pernah mengalami downtime, sehingga tidak diencerkan aset yang tidak pernah dipantau.',
                'WaktuHentiAset',
                null,
                naikItuBaik: true,
                satuanRincian: SatuanKpi::Jam,
            ),
            new DefinisiKpi(
                'keandalan.mttr', 'MTTR', KelompokKpi::Keandalan, SatuanKpi::Jam,
                'Mean Time To Repair = SUM(WaktuHentiAset.DurasiMenit tak terencana yang sudah berakhir) / COUNT(sesi downtime tersebut) / 60.',
                'WaktuHentiAset',
                null,
                naikItuBaik: false,
            ),
            new DefinisiKpi(
                'keandalan.mtbf', 'MTBF', KelompokKpi::Keandalan, SatuanKpi::Jam,
                'Mean Time Between Failures = (total menit operasional - total menit downtime tak terencana) / COUNT(kegagalan) / 60, dengan total menit operasional = jumlah aset terdampak x menit dalam rentang.',
                'WaktuHentiAset',
                null,
                naikItuBaik: true,
            ),

            // 21.01 Cost -----------------------------------------------------
            new DefinisiKpi(
                'biaya.pemeliharaan', 'Biaya Pemeliharaan', KelompokKpi::Biaya, SatuanKpi::Uang,
                'SUM(BiayaPerintahKerja.Jumlah) dengan TanggalBiaya di dalam rentang, dipecah per JenisBiaya dan per bulan.',
                'BiayaPerintahKerja',
                'Laporan.Lihat',
                naikItuBaik: false,
            ),
            new DefinisiKpi(
                'biaya.per_aset', 'Biaya Pemeliharaan per Aset', KelompokKpi::Biaya, SatuanKpi::Uang,
                'SUM(BiayaPerintahKerja.Jumlah) / COUNT(DISTINCT aset yang dikerjakan) di dalam rentang.',
                'BiayaPerintahKerja',
                'Laporan.Lihat',
                naikItuBaik: false,
            ),

            // 21.01 Stock ----------------------------------------------------
            new DefinisiKpi(
                'stok.nilai', 'Nilai Persediaan', KelompokKpi::Stok, SatuanKpi::Uang,
                'SUM(StokSukuCadang.JumlahTersedia x SukuCadang.HargaRataRata) atas suku cadang yang belum dihapus.',
                'StokSukuCadang',
                'Stok.Kelola',
            ),
            new DefinisiKpi(
                'stok.di_bawah_minimum', 'Suku Cadang di Bawah Minimum', KelompokKpi::Stok, SatuanKpi::Jumlah,
                'COUNT(SukuCadang yang StokMinimum > 0 dan total JumlahTersedia di seluruh gudang < StokMinimum).',
                'StokSukuCadang',
                'Stok.Kelola',
                naikItuBaik: false,
            ),

            // 21.01 Calibration ----------------------------------------------
            new DefinisiKpi(
                'kalibrasi.jatuh_tempo', 'Kalibrasi Jatuh Tempo', KelompokKpi::Kalibrasi, SatuanKpi::Jumlah,
                'COUNT(RencanaKalibrasi aktif yang TanggalBerikutnya <= hari ini + PeringatanHariSebelum). Rincian memisahkan yang sudah terlambat dari yang segera jatuh tempo.',
                'RencanaKalibrasi',
                'Kalibrasi.Kelola',
                naikItuBaik: false,
            ),
            new DefinisiKpi(
                'kalibrasi.kepatuhan', 'Kepatuhan Kalibrasi', KelompokKpi::Kalibrasi, SatuanKpi::Persen,
                'COUNT(RencanaKalibrasi aktif yang TanggalBerikutnya >= hari ini) / COUNT(RencanaKalibrasi aktif) x 100.',
                'RencanaKalibrasi',
                'Kalibrasi.Kelola',
                naikItuBaik: true,
                satuanRincian: SatuanKpi::Jumlah,
            ),

            // 21.01 Preventive -------------------------------------------------
            new DefinisiKpi(
                'preventif.jatuh_tempo', 'Preventif Jatuh Tempo', KelompokKpi::Preventif, SatuanKpi::Jumlah,
                'COUNT(JadwalPemeliharaan berstatus Terjadwal yang TanggalJadwal <= hari ini).',
                'JadwalPemeliharaan',
                'Pemeliharaan.Kelola',
                naikItuBaik: false,
            ),
            new DefinisiKpi(
                'preventif.kepatuhan', 'Kepatuhan Preventif', KelompokKpi::Preventif, SatuanKpi::Persen,
                'COUNT(JadwalPemeliharaan berstatus Selesai) / COUNT(JadwalPemeliharaan dengan TanggalJadwal di dalam rentang) x 100.',
                'JadwalPemeliharaan',
                'Pemeliharaan.Kelola',
                naikItuBaik: true,
                satuanRincian: SatuanKpi::Jumlah,
            ),

            // 21.01 Procurement -------------------------------------------------
            new DefinisiKpi(
                'pengadaan.nilai_pesanan', 'Nilai Pesanan Pembelian', KelompokKpi::Pengadaan, SatuanKpi::Uang,
                'SUM(PesananPembelian.Total) dengan TanggalPesanan di dalam rentang dan status bukan Draft maupun Dibatalkan, dipecah per status.',
                'PesananPembelian',
                'Pengadaan.Kelola',
            ),
            new DefinisiKpi(
                'pengadaan.jumlah_pesanan', 'Jumlah Pesanan Pembelian', KelompokKpi::Pengadaan, SatuanKpi::Jumlah,
                'COUNT(PesananPembelian) dengan TanggalPesanan di dalam rentang, dipecah per status.',
                'PesananPembelian',
                'Pengadaan.Kelola',
            ),

            // 21.01 Budget --------------------------------------------------------
            new DefinisiKpi(
                'anggaran.serapan', 'Serapan Anggaran', KelompokKpi::Anggaran, SatuanKpi::Persen,
                'SUM(PosAnggaran.Terpakai) / SUM(PosAnggaran.Jumlah) x 100 atas anggaran tahun berjalan pada rentang. Rincian memuat pagu, terpakai, dan ditahan per anggaran.',
                'PosAnggaran',
                'Pengadaan.Kelola',
                satuanRincian: SatuanKpi::Uang,
            ),
            new DefinisiKpi(
                'anggaran.sisa', 'Sisa Anggaran', KelompokKpi::Anggaran, SatuanKpi::Uang,
                'SUM(PosAnggaran.Jumlah) - SUM(PosAnggaran.Terpakai) - SUM(PosAnggaran.Ditahan) atas anggaran tahun berjalan pada rentang.',
                'PosAnggaran',
                'Pengadaan.Kelola',
                naikItuBaik: true,
            ),

            // 21.01 Contract ---------------------------------------------------------
            new DefinisiKpi(
                'kontrak.akan_berakhir', 'Kontrak Akan Berakhir', KelompokKpi::Kontrak, SatuanKpi::Jumlah,
                'COUNT(Kontrak berstatus Aktif yang BerakhirPada <= hari ini + PeringatanHariSebelum).',
                'Kontrak',
                'Kontrak.Kelola',
                naikItuBaik: false,
            ),
            new DefinisiKpi(
                'kontrak.nilai_aktif', 'Nilai Kontrak Aktif', KelompokKpi::Kontrak, SatuanKpi::Uang,
                'SUM(Kontrak.Nilai) atas kontrak berstatus Aktif yang masa berlakunya beririsan dengan rentang.',
                'Kontrak',
                'Kontrak.Kelola',
            ),

            // 21.01 Compliance -----------------------------------------------------------
            new DefinisiKpi(
                'kepatuhan.tingkat', 'Tingkat Kepatuhan Aset', KelompokKpi::Kepatuhan, SatuanKpi::Persen,
                'COUNT(KepatuhanAset berstatus Patuh) / COUNT(KepatuhanAset yang sudah diperiksa) x 100. Rincian memuat jumlah per status.',
                'KepatuhanAset',
                'Kepatuhan.Kelola',
                naikItuBaik: true,
                satuanRincian: SatuanKpi::Jumlah,
            ),
            new DefinisiKpi(
                'kepatuhan.akan_kedaluwarsa', 'Kepatuhan Akan Kedaluwarsa', KelompokKpi::Kepatuhan, SatuanKpi::Jumlah,
                'COUNT(KepatuhanAset yang BerlakuSampai <= hari ini + 30 hari dan belum berstatus TidakPatuh).',
                'KepatuhanAset',
                'Kepatuhan.Kelola',
                naikItuBaik: false,
            ),
        ];

        $terindeks = [];
        foreach ($daftar as $definisi) {
            $terindeks[$definisi->kunci] = $definisi;
        }

        return $terindeks;
    }
}
