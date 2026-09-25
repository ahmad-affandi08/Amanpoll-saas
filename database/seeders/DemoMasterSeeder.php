<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Aset\Application\Actions\BuatAset;
use App\Domain\Aset\Application\Actions\CatatPembacaanMeter;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use App\Domain\Persediaan\Application\Actions\BuatMutasiStok;
use App\Domain\Persediaan\Application\Actions\PostingMutasiStok;
use App\Domain\Persediaan\Application\Actions\TambahDetailMutasiStok;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Master operasional perusahaan demo: kategori, merek, model, penyedia, 60-an aset
 * di tiga situs, gudang, suku cadang, dan saldo stok awal.
 *
 * Aset dan saldo stok dibuat lewat Action (BuatAset, mutasi stok Penerimaan yang
 * diposting), dengan jam dimundurkan ke tanggal perolehan masing-masing, supaya
 * riwayat lokasi, kode QR, nilai residu, dan buku stok konsisten dengan aplikasi.
 */
final class DemoMasterSeeder extends Seeder
{
    use KonteksDemo;

    /**
     * Kode, nama (menentukan ikon 3D), umur manfaat bulan, residu %, butuh kalibrasi.
     *
     * @var list<array{0: string, 1: string, 2: int, 3: float, 4: bool}>
     */
    private const KATEGORI_ASET = [
        ['KAT-GENSET', 'Genset & UPS', 180, 10, false],
        ['KAT-KOMP', 'Kompresor Udara', 120, 10, false],
        ['KAT-AC', 'AC & Pendingin', 96, 5, false],
        ['KAT-MESIN', 'Mesin Produksi', 144, 10, false],
        ['KAT-LIFT', 'Lift & Eskalator', 240, 10, false],
        ['KAT-PANEL', 'Panel & Kelistrikan', 240, 5, false],
        ['KAT-APAR', 'APAR & Pemadam Kebakaran', 60, 0, false],
        ['KAT-POMPA', 'Pompa & Air Bersih', 120, 5, false],
        ['KAT-FORK', 'Forklift & Kendaraan', 96, 15, false],
        ['KAT-IT', 'Komputer & Jaringan', 48, 5, false],
        ['KAT-PRINT', 'Printer & Pencetak', 48, 0, false],
        ['KAT-CCTV', 'CCTV & Keamanan', 60, 0, false],
        ['KAT-UKUR', 'Alat Ukur & Laboratorium', 96, 0, true],
    ];

    /** @var list<array{0: string, 1: string}> */
    private const MEREK = [
        ['Cummins', 'Amerika Serikat'], ['Perkins', 'Inggris'], ['APC', 'Amerika Serikat'],
        ['Atlas Copco', 'Swedia'], ['Daikin', 'Jepang'], ['York', 'Amerika Serikat'],
        ['Haitian', 'Tiongkok'], ['Jomar', 'Amerika Serikat'], ['Schindler', 'Swiss'],
        ['Schneider Electric', 'Prancis'], ['Grundfos', 'Denmark'], ['Toyota', 'Jepang'],
        ['Hino', 'Jepang'], ['Dell', 'Amerika Serikat'], ['Cisco', 'Amerika Serikat'],
        ['Fortinet', 'Amerika Serikat'], ['HP', 'Amerika Serikat'], ['Zebra', 'Amerika Serikat'],
        ['Hikvision', 'Tiongkok'], ['Mettler Toledo', 'Swiss'], ['Mitutoyo', 'Jepang'],
        ['Fluke', 'Amerika Serikat'], ['Yamato', 'Jepang'], ['Instron', 'Amerika Serikat'],
    ];

    /**
     * Kode model, nama, kode kategori, merek, interval pemeliharaan hari, interval kalibrasi hari.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: int|null, 5: int|null}>
     */
    private const MODEL = [
        ['C1000D5', 'Genset Cummins C1000 D5 1000 kVA', 'KAT-GENSET', 'Cummins', 90, null],
        ['P500-1', 'Genset Perkins 500 kVA', 'KAT-GENSET', 'Perkins', 90, null],
        ['SRT40K', 'UPS APC Smart-UPS SRT 40 kVA', 'KAT-GENSET', 'APC', 180, null],
        ['GA75VSD', 'Kompresor Atlas Copco GA75 VSD+', 'KAT-KOMP', 'Atlas Copco', 60, null],
        ['FTKC50', 'AC Split Daikin 2 PK', 'KAT-AC', 'Daikin', 90, null],
        ['YK300', 'Chiller York YK 300 TR', 'KAT-AC', 'York', 90, null],
        ['MA3800', 'Mesin Injeksi Haitian Mars MA3800', 'KAT-MESIN', 'Haitian', 30, null],
        ['IBM85', 'Mesin Blow Molding Jomar IBM 85', 'KAT-MESIN', 'Jomar', 30, null],
        ['S3300', 'Lift Penumpang Schindler 3300', 'KAT-LIFT', 'Schindler', 30, null],
        ['OKKEN', 'Panel LVMDP Schneider Okken', 'KAT-PANEL', 'Schneider Electric', 180, null],
        ['CR32', 'Pompa Grundfos CR 32', 'KAT-POMPA', 'Grundfos', 90, null],
        ['8FD25', 'Forklift Toyota 8FD25 2,5 Ton', 'KAT-FORK', 'Toyota', 60, null],
        ['R750', 'Server Dell PowerEdge R750', 'KAT-IT', 'Dell', 180, null],
        ['C9300', 'Core Switch Cisco Catalyst 9300', 'KAT-IT', 'Cisco', 180, null],
        ['M404', 'Printer HP LaserJet Pro M404', 'KAT-PRINT', 'HP', 120, null],
        ['XPI224', 'Timbangan Analitik Mettler XPR 224', 'KAT-UKUR', 'Mettler Toledo', null, 365],
        ['CD-15', 'Jangka Sorong Digital Mitutoyo CD-15APX', 'KAT-UKUR', 'Mitutoyo', null, 365],
        ['FLK87V', 'Multimeter Fluke 87V', 'KAT-UKUR', 'Fluke', null, 365],
    ];

    /**
     * Kode, nama, kota, email, kategori, kontak utama.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string}>
     */
    private const PENYEDIA = [
        ['VND-001', 'PT Tenaga Prima Energi', 'Jakarta Utara', 'service@tenagaprima.test', 'JASA-SERVIS', 'Hadi Kusuma'],
        ['VND-002', 'PT Atlas Kompresindo Nusantara', 'Bekasi', 'cs@atlaskompresindo.test', 'JASA-SERVIS', 'Yohanes Tan'],
        ['VND-003', 'CV Sejuk Mandiri Teknik', 'Bekasi', 'order@sejukmandiri.test', 'JASA-SERVIS', 'Wawan Hermawan'],
        ['VND-004', 'PT Lift Indo Jaya', 'Jakarta Selatan', 'maintenance@liftindo.test', 'JASA-SERVIS', 'Gunawan Putra'],
        ['VND-005', 'PT Sumber Suku Cadang Industri', 'Tangerang', 'sales@sumbersuku.test', 'SUKU-CADANG', 'Lina Marlina'],
        ['VND-006', 'PT Mitra Datacom Solusi', 'Jakarta Pusat', 'support@mitradatacom.test', 'TI', 'Kevin Wirawan'],
        ['VND-007', 'PT Kalibrasi Presisi Indonesia', 'Bandung', 'lab@kalibrasipresisi.test', 'KALIBRASI', 'Dr. Arif Rahman'],
        ['VND-008', 'PT Proteksi Api Sentosa', 'Bekasi', 'info@proteksiapi.test', 'K3', 'Samsul Bahri'],
        ['VND-009', 'PT Haitian Mesin Plastik Indonesia', 'Cikarang', 'service@haitian-id.test', 'SUKU-CADANG', 'Chen Wei'],
        ['VND-010', 'PT Toyota Material Handling Indonesia', 'Jakarta Timur', 'service@tmhi.test', 'JASA-SERVIS', 'Andreas Sitompul'],
    ];

    /**
     * Kode aset, nama, kategori, model (kode) atau null, lokasi, unit pengelola, penyedia,
     * bulan sejak diperoleh, harga, status, kondisi, tingkat kritis.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string|null, 4: string, 5: string, 6: string|null, 7: int, 8: int, 9: string, 10: string, 11: string}>
     */
    private const ASET = [
        // Utilitas pabrik
        ['UTL-GEN-01', 'Genset Cummins 1000 kVA Utama', 'KAT-GENSET', 'C1000D5', 'CKR-UTL', 'TEKFAS', 'VND-001', 60, 2850000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['UTL-GEN-02', 'Genset Perkins 500 kVA Cadangan', 'KAT-GENSET', 'P500-1', 'CKR-UTL', 'TEKFAS', 'VND-001', 84, 1250000000, 'Aktif', 'PerluPerhatian', 'Tinggi'],
        ['UTL-KMP-01', 'Kompresor Atlas Copco GA75 #1', 'KAT-KOMP', 'GA75VSD', 'CKR-UTL', 'TEKFAS', 'VND-002', 40, 780000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['UTL-KMP-02', 'Kompresor Atlas Copco GA75 #2', 'KAT-KOMP', 'GA75VSD', 'CKR-UTL', 'TEKFAS', 'VND-002', 40, 780000000, 'Aktif', 'Baik', 'Tinggi'],
        ['UTL-CHL-01', 'Chiller York 300 TR', 'KAT-AC', 'YK300', 'CKR-UTL', 'TEKFAS', 'VND-003', 72, 1950000000, 'Aktif', 'PerluPerhatian', 'SangatTinggi'],
        ['UTL-PNL-01', 'Panel LVMDP Pabrik Cikarang', 'KAT-PANEL', 'OKKEN', 'CKR-UTL', 'TEKFAS', null, 96, 920000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['UTL-PNL-02', 'Panel SDP Gedung Produksi A', 'KAT-PANEL', null, 'CKR-PRDA', 'TEKFAS', null, 96, 185000000, 'Aktif', 'Baik', 'Tinggi'],
        ['UTL-TRF-01', 'Trafo Distribusi 2000 kVA', 'KAT-PANEL', null, 'CKR-UTL', 'TEKFAS', null, 96, 640000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['UTL-PMP-01', 'Pompa Air Bersih Grundfos CR32 #1', 'KAT-POMPA', 'CR32', 'CKR-UTL', 'TEKFAS', null, 50, 96000000, 'Aktif', 'Baik', 'Normal'],
        ['UTL-PMP-02', 'Pompa Air Bersih Grundfos CR32 #2', 'KAT-POMPA', 'CR32', 'CKR-UTL', 'TEKFAS', null, 50, 96000000, 'Rusak', 'Rusak', 'Normal'],
        ['UTL-PMP-03', 'Pompa Hydrant Diesel 750 GPM', 'KAT-POMPA', null, 'CKR-UTL', 'TEKFAS', 'VND-008', 70, 410000000, 'Aktif', 'Baik', 'SangatTinggi'],
        // Produksi
        ['PRD-INJ-01', 'Mesin Injeksi Haitian MA3800 #1', 'KAT-MESIN', 'MA3800', 'CKR-PRDA-INJ', 'TEKFAS', 'VND-009', 54, 1480000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['PRD-INJ-02', 'Mesin Injeksi Haitian MA3800 #2', 'KAT-MESIN', 'MA3800', 'CKR-PRDA-INJ', 'TEKFAS', 'VND-009', 54, 1480000000, 'Aktif', 'PerluPerhatian', 'SangatTinggi'],
        ['PRD-INJ-03', 'Mesin Injeksi Haitian MA3800 #3', 'KAT-MESIN', 'MA3800', 'CKR-PRDA-INJ', 'TEKFAS', 'VND-009', 30, 1560000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['PRD-INJ-04', 'Mesin Injeksi Haitian MA3800 #4', 'KAT-MESIN', 'MA3800', 'CKR-PRDA-INJ', 'TEKFAS', 'VND-009', 18, 1590000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['PRD-BLW-01', 'Mesin Blow Molding Jomar IBM 85 #1', 'KAT-MESIN', 'IBM85', 'CKR-PRDA-BLW', 'TEKFAS', null, 66, 2150000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['PRD-BLW-02', 'Mesin Blow Molding Jomar IBM 85 #2', 'KAT-MESIN', 'IBM85', 'CKR-PRDA-BLW', 'TEKFAS', null, 66, 2150000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['PRD-CRS-01', 'Mesin Crusher Plastik 30 HP', 'KAT-MESIN', null, 'CKR-PRDA', 'TEKFAS', null, 80, 165000000, 'Aktif', 'PerluPerhatian', 'Normal'],
        ['PRD-MTC-01', 'Mould Temperature Controller 9 kW', 'KAT-MESIN', null, 'CKR-PRDA-INJ', 'TEKFAS', null, 36, 48000000, 'Aktif', 'Baik', 'Tinggi'],
        ['PRD-AC-01', 'AC Split Daikin 2 PK Ruang Kontrol Produksi', 'KAT-AC', 'FTKC50', 'CKR-PRDA', 'TEKFAS', 'VND-003', 26, 11500000, 'Aktif', 'Baik', 'Normal'],
        // Gudang & logistik
        ['GDL-FRK-01', 'Forklift Toyota 8FD25 #1', 'KAT-FORK', '8FD25', 'CKR-GBB', 'TEKFAS', 'VND-010', 44, 385000000, 'Aktif', 'Baik', 'Tinggi'],
        ['GDL-FRK-02', 'Forklift Toyota 8FD25 #2', 'KAT-FORK', '8FD25', 'CKR-GBB', 'TEKFAS', 'VND-010', 44, 385000000, 'Aktif', 'PerluPerhatian', 'Tinggi'],
        ['GDL-FRK-03', 'Forklift Toyota 8FD25 Surabaya', 'KAT-FORK', '8FD25', 'SBY', 'TEKFAS', 'VND-010', 20, 398000000, 'Aktif', 'Baik', 'Tinggi'],
        ['GDL-TRK-01', 'Truk Box Hino Dutro 130 HD', 'KAT-FORK', null, 'SBY', 'TEKFAS', null, 38, 465000000, 'Aktif', 'Baik', 'Normal'],
        ['GDL-AC-01', 'AC Split Daikin 2 PK Kantor Gudang Surabaya', 'KAT-AC', 'FTKC50', 'SBY', 'TEKFAS', 'VND-003', 20, 11500000, 'Aktif', 'Baik', 'Normal'],
        // Gedung kantor pusat
        ['GDG-LFT-01', 'Lift Penumpang Menara Sinar #1', 'KAT-LIFT', 'S3300', 'JKT', 'TEKFAS', 'VND-004', 110, 1350000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['GDG-LFT-02', 'Lift Penumpang Menara Sinar #2', 'KAT-LIFT', 'S3300', 'JKT', 'TEKFAS', 'VND-004', 110, 1350000000, 'Aktif', 'PerluPerhatian', 'SangatTinggi'],
        ['GDG-LFT-03', 'Lift Barang Pabrik Cikarang', 'KAT-LIFT', null, 'CKR-PRDA', 'TEKFAS', 'VND-004', 88, 980000000, 'Aktif', 'Baik', 'Tinggi'],
        ['GDG-AC-01', 'AC Split Daikin 2 PK Ruang Rapat Lt. 12', 'KAT-AC', 'FTKC50', 'JKT-L12-RR', 'TEKFAS', 'VND-003', 30, 11500000, 'Aktif', 'Baik', 'Normal'],
        ['GDG-AC-02', 'AC Split Daikin 2 PK Ruang Direksi', 'KAT-AC', 'FTKC50', 'JKT-L12', 'TEKFAS', 'VND-003', 30, 11500000, 'Aktif', 'Baik', 'Normal'],
        ['GDG-AC-03', 'AC Split Daikin 2 PK Ruang Keuangan', 'KAT-AC', 'FTKC50', 'JKT-L12', 'TEKFAS', 'VND-003', 30, 11500000, 'Aktif', 'PerluPerhatian', 'Normal'],
        ['GDG-AC-04', 'AC Presisi Ruang Server Lt. 15', 'KAT-AC', null, 'JKT-L15-SRV', 'IT', 'VND-003', 34, 285000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['GDG-UPS-01', 'UPS APC 40 kVA Ruang Server', 'KAT-GENSET', 'SRT40K', 'JKT-L15-SRV', 'IT', 'VND-006', 34, 215000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['GDG-PMP-01', 'Pompa Transfer Air Menara Sinar', 'KAT-POMPA', 'CR32', 'JKT', 'TEKFAS', null, 110, 88000000, 'Aktif', 'Baik', 'Normal'],
        // K3
        ['K3-APR-01', 'APAR CO2 5 kg Gedung Produksi A (set 12)', 'KAT-APAR', null, 'CKR-PRDA', 'TEKFAS', 'VND-008', 22, 36000000, 'Aktif', 'Baik', 'Tinggi'],
        ['K3-APR-02', 'APAR Powder 6 kg Gudang Bahan Baku (set 8)', 'KAT-APAR', null, 'CKR-GBB', 'TEKFAS', 'VND-008', 22, 19200000, 'Aktif', 'Baik', 'Tinggi'],
        ['K3-APR-03', 'APAR CO2 3 kg Menara Sinar (set 16)', 'KAT-APAR', null, 'JKT', 'TEKFAS', 'VND-008', 14, 38400000, 'Aktif', 'Baik', 'Tinggi'],
        ['K3-HYD-01', 'Sistem Hydrant Pabrik Cikarang', 'KAT-APAR', null, 'CKR', 'TEKFAS', 'VND-008', 96, 1250000000, 'Aktif', 'Baik', 'SangatTinggi'],
        // TI
        ['IT-SRV-01', 'Server Dell PowerEdge R750 (ERP)', 'KAT-IT', 'R750', 'JKT-L15-SRV', 'IT', 'VND-006', 28, 265000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['IT-SRV-02', 'Server Dell PowerEdge R750 (File & Backup)', 'KAT-IT', 'R750', 'JKT-L15-SRV', 'IT', 'VND-006', 28, 248000000, 'Aktif', 'Baik', 'Tinggi'],
        ['IT-NET-01', 'Core Switch Cisco Catalyst 9300', 'KAT-IT', 'C9300', 'JKT-L15-SRV', 'IT', 'VND-006', 28, 185000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['IT-NET-02', 'Firewall FortiGate 200F', 'KAT-IT', null, 'JKT-L15-SRV', 'IT', 'VND-006', 20, 142000000, 'Aktif', 'Baik', 'SangatTinggi'],
        ['IT-NET-03', 'Switch Distribusi Pabrik Cikarang', 'KAT-IT', null, 'CKR-KTR', 'IT', 'VND-006', 44, 64000000, 'Aktif', 'PerluPerhatian', 'Tinggi'],
        ['IT-NAS-01', 'NAS Synology RS2421 Backup', 'KAT-IT', null, 'JKT-L15-SRV', 'IT', 'VND-006', 16, 98000000, 'Aktif', 'Baik', 'Tinggi'],
        ['IT-PC-01', 'PC Workstation Engineering CAD', 'KAT-IT', null, 'CKR-KTR', 'IT', null, 36, 42000000, 'Aktif', 'Baik', 'Normal'],
        ['IT-PC-02', 'Laptop Dell Latitude Direktur Operasional', 'KAT-IT', null, 'JKT-L12', 'IT', null, 12, 28500000, 'Aktif', 'Baik', 'Normal'],
        ['IT-PC-03', 'Laptop Dell Latitude Keuangan', 'KAT-IT', null, 'JKT-L12', 'IT', null, 40, 21000000, 'Nonaktif', 'Rusak', 'Normal'],
        ['IT-PRN-01', 'Printer HP LaserJet Lt. 12', 'KAT-PRINT', 'M404', 'JKT-L12', 'IT', null, 32, 6800000, 'Aktif', 'Baik', 'Normal'],
        ['IT-PRN-02', 'Printer HP LaserJet Kantor Pabrik', 'KAT-PRINT', 'M404', 'CKR-KTR', 'IT', null, 32, 6800000, 'Aktif', 'PerluPerhatian', 'Normal'],
        ['IT-PRN-03', 'Printer Label Zebra ZT411 Gudang', 'KAT-PRINT', null, 'CKR-GBB', 'IT', null, 24, 38500000, 'Aktif', 'Baik', 'Tinggi'],
        ['IT-CCT-01', 'Sistem CCTV Pabrik 32 Kanal', 'KAT-CCTV', null, 'CKR-KTR', 'IT', 'VND-006', 48, 175000000, 'Aktif', 'Baik', 'Tinggi'],
        ['IT-CCT-02', 'Sistem CCTV Menara Sinar 24 Kanal', 'KAT-CCTV', null, 'JKT', 'IT', 'VND-006', 60, 128000000, 'Aktif', 'Baik', 'Tinggi'],
        // Laboratorium & alat ukur (terkalibrasi)
        ['LAB-TMB-01', 'Timbangan Analitik Mettler XPR 224', 'KAT-UKUR', 'XPI224', 'CKR-LAB', 'TEKFAS', 'VND-007', 38, 145000000, 'Aktif', 'Baik', 'Tinggi'],
        ['LAB-TMB-02', 'Timbangan Lantai 3 Ton Gudang', 'KAT-UKUR', null, 'CKR-GBB', 'TEKFAS', 'VND-007', 62, 42000000, 'Aktif', 'Baik', 'Tinggi'],
        ['LAB-JSR-01', 'Jangka Sorong Digital Mitutoyo #1', 'KAT-UKUR', 'CD-15', 'CKR-LAB', 'TEKFAS', 'VND-007', 30, 3200000, 'Aktif', 'Baik', 'Normal'],
        ['LAB-JSR-02', 'Jangka Sorong Digital Mitutoyo #2', 'KAT-UKUR', 'CD-15', 'CKR-PRDA', 'TEKFAS', 'VND-007', 30, 3200000, 'Aktif', 'Baik', 'Normal'],
        ['LAB-MLT-01', 'Multimeter Fluke 87V Tim Elektrikal', 'KAT-UKUR', 'FLK87V', 'CKR-UTL', 'TEKFAS', 'VND-007', 26, 9800000, 'Aktif', 'Baik', 'Normal'],
        ['LAB-OVN-01', 'Oven Laboratorium Yamato DX402', 'KAT-UKUR', null, 'CKR-LAB', 'TEKFAS', 'VND-007', 48, 68000000, 'Aktif', 'Baik', 'Normal'],
        ['LAB-TNS-01', 'Tensile Tester Instron 3345', 'KAT-UKUR', null, 'CKR-LAB', 'TEKFAS', 'VND-007', 44, 520000000, 'Aktif', 'PerluPerhatian', 'Tinggi'],
        ['LAB-PRG-01', 'Pressure Gauge Master 0–25 bar', 'KAT-UKUR', null, 'CKR-UTL', 'TEKFAS', 'VND-007', 34, 7400000, 'Aktif', 'Baik', 'Normal'],
    ];

    /**
     * Kode, nama, kategori, satuan, stok minimum, titik pesan ulang, harga satuan,
     * gudang, jumlah saldo awal, kategori aset yang cocok.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: int, 5: int, 6: int, 7: string, 8: int, 9: string|null}>
     */
    private const SUKU_CADANG = [
        ['SC-FLT-001', 'Filter Oli Genset Cummins LF9009', 'SC-FILTER', 'pcs', 4, 6, 685000, 'GDG-CKR', 14, 'KAT-GENSET'],
        ['SC-FLT-002', 'Filter Solar Genset FS1000', 'SC-FILTER', 'pcs', 4, 6, 540000, 'GDG-CKR', 10, 'KAT-GENSET'],
        ['SC-FLT-003', 'Filter Udara Kompresor GA75', 'SC-FILTER', 'pcs', 2, 3, 1250000, 'GDG-CKR', 5, 'KAT-KOMP'],
        ['SC-FLT-004', 'Separator Oli Kompresor GA75', 'SC-FILTER', 'pcs', 1, 2, 4850000, 'GDG-CKR', 2, 'KAT-KOMP'],
        ['SC-OLI-001', 'Oli Mesin Diesel SAE 15W-40 (drum 209 L)', 'SC-PELUMAS', 'drum', 1, 2, 9800000, 'GDG-CKR', 4, 'KAT-GENSET'],
        ['SC-OLI-002', 'Oli Hidrolik ISO VG 46 (pail 20 L)', 'SC-PELUMAS', 'pail', 6, 10, 1150000, 'GDG-CKR', 24, 'KAT-MESIN'],
        ['SC-OLI-003', 'Oli Kompresor Roto-Inject (20 L)', 'SC-PELUMAS', 'pail', 2, 4, 3650000, 'GDG-CKR', 6, 'KAT-KOMP'],
        ['SC-GRS-001', 'Grease EP2 (kaleng 16 kg)', 'SC-PELUMAS', 'kaleng', 2, 4, 1450000, 'GDG-CKR', 8, null],
        ['SC-BRG-001', 'Bearing SKF 6205-2RS', 'SC-MEKANIKAL', 'pcs', 10, 15, 95000, 'GDG-CKR', 40, null],
        ['SC-BRG-002', 'Bearing SKF 6310-2Z', 'SC-MEKANIKAL', 'pcs', 6, 10, 385000, 'GDG-CKR', 18, 'KAT-POMPA'],
        ['SC-SEL-001', 'Mechanical Seal Pompa CR32', 'SC-MEKANIKAL', 'pcs', 2, 3, 2350000, 'GDG-CKR', 3, 'KAT-POMPA'],
        ['SC-BLT-001', 'V-Belt B-68', 'SC-MEKANIKAL', 'pcs', 6, 10, 145000, 'GDG-CKR', 20, null],
        ['SC-HTR-001', 'Heater Band Barrel Injeksi 220V 1,2 kW', 'SC-PRODUKSI', 'pcs', 8, 12, 425000, 'GDG-CKR', 16, 'KAT-MESIN'],
        ['SC-TC-001', 'Thermocouple Tipe J', 'SC-PRODUKSI', 'pcs', 10, 15, 165000, 'GDG-CKR', 30, 'KAT-MESIN'],
        ['SC-NZL-001', 'Nozzle Tip Injeksi MA3800', 'SC-PRODUKSI', 'pcs', 2, 3, 3200000, 'GDG-CKR', 4, 'KAT-MESIN'],
        ['SC-SOL-001', 'Solenoid Valve Hidrolik 24 VDC', 'SC-PRODUKSI', 'pcs', 2, 4, 2750000, 'GDG-CKR', 5, 'KAT-MESIN'],
        ['SC-KNT-001', 'Kontaktor Schneider LC1D32', 'SC-ELEKTRIKAL', 'pcs', 4, 6, 1180000, 'GDG-CKR', 10, 'KAT-PANEL'],
        ['SC-MCB-001', 'MCB 3P 32A Schneider', 'SC-ELEKTRIKAL', 'pcs', 6, 10, 485000, 'GDG-CKR', 15, 'KAT-PANEL'],
        ['SC-LMP-001', 'Lampu LED High Bay 150 W', 'SC-ELEKTRIKAL', 'pcs', 8, 12, 1350000, 'GDG-CKR', 20, null],
        ['SC-AKI-001', 'Aki Starter Genset 12V 200Ah', 'SC-ELEKTRIKAL', 'pcs', 2, 2, 4250000, 'GDG-CKR', 4, 'KAT-GENSET'],
        ['SC-FRN-001', 'Freon R-32 (tabung 10 kg)', 'SC-HVAC', 'tabung', 2, 3, 1850000, 'GDG-CKR', 6, 'KAT-AC'],
        ['SC-KAP-001', 'Kapasitor AC 45 µF', 'SC-HVAC', 'pcs', 4, 6, 185000, 'GDG-JKT', 10, 'KAT-AC'],
        ['SC-FLT-005', 'Filter AC Split Daikin', 'SC-HVAC', 'pcs', 8, 12, 95000, 'GDG-JKT', 24, 'KAT-AC'],
        ['SC-TNR-001', 'Toner HP 76A', 'SC-TI', 'pcs', 4, 6, 1450000, 'GDG-JKT', 12, 'KAT-PRINT'],
        ['SC-HDD-001', 'Hard Disk SAS 2,4 TB 10K', 'SC-TI', 'pcs', 2, 3, 6850000, 'GDG-JKT', 4, 'KAT-IT'],
        ['SC-SFP-001', 'Modul SFP+ 10G SR', 'SC-TI', 'pcs', 2, 4, 2150000, 'GDG-JKT', 6, 'KAT-IT'],
        ['SC-RBN-001', 'Ribbon Printer Label Zebra 110 mm', 'SC-TI', 'roll', 10, 20, 285000, 'GDG-CKR', 36, 'KAT-PRINT'],
        ['SC-BAN-001', 'Ban Solid Forklift 7.00-12', 'SC-MEKANIKAL', 'pcs', 2, 4, 3650000, 'GDG-SBY', 6, 'KAT-FORK'],
        ['SC-APR-001', 'Tabung Isi Ulang APAR CO2 5 kg', 'SC-K3', 'pcs', 4, 6, 450000, 'GDG-CKR', 8, 'KAT-APAR'],
        ['SC-TLI-001', 'Tali Baja Lift 10 mm (meter)', 'SC-MEKANIKAL', 'meter', 50, 80, 125000, 'GDG-JKT', 120, 'KAT-LIFT'],
    ];

    public function run(): void
    {
        $this->masukKonteks();
        $organisasiId = $this->organisasiId();

        $kategoriAset = $this->semaiKategoriAset($organisasiId);
        $merek = $this->semaiMerek($organisasiId);
        $model = $this->semaiModel($organisasiId, $kategoriAset, $merek);
        $penyedia = $this->semaiPenyedia($organisasiId);
        $this->semaiAset($organisasiId, $kategoriAset, $model, $penyedia);
        $this->semaiMeter($organisasiId);
        $this->semaiGaransi($organisasiId, $penyedia);

        $gudang = $this->semaiGudang($organisasiId);
        $sukuCadang = $this->semaiSukuCadang($organisasiId, $kategoriAset);
        $this->semaiSaldoAwalStok($organisasiId, $gudang, $sukuCadang);
    }

    /** @return array<string, string> */
    private function semaiKategoriAset(string $organisasiId): array
    {
        $id = [];
        foreach (self::KATEGORI_ASET as [$kode, $nama, $umur, $residu, $kalibrasi]) {
            $id[$kode] = $this->simpan('KategoriAset', ['OrganisasiId' => $organisasiId, 'Kode' => $kode], [
                'Nama' => $nama,
                'UmurManfaatBulan' => $umur,
                'MetodePenyusutanBawaan' => 'GarisLurus',
                'PersentaseNilaiResidu' => $residu,
                'MemerlukanKalibrasi' => $kalibrasi ? 1 : 0,
                'MemerlukanPemeliharaan' => 1,
                'DiperbaruiPada' => now(),
            ]);
        }

        return $id;
    }

    /** @return array<string, string> */
    private function semaiMerek(string $organisasiId): array
    {
        $id = [];
        foreach (self::MEREK as [$nama, $negara]) {
            $id[$nama] = $this->simpan('Merek', ['OrganisasiId' => $organisasiId, 'Nama' => $nama], [
                'NegaraAsal' => $negara,
            ]);
        }

        return $id;
    }

    /**
     * @param  array<string, string>  $kategoriAset
     * @param  array<string, string>  $merek
     * @return array<string, string>
     */
    private function semaiModel(string $organisasiId, array $kategoriAset, array $merek): array
    {
        $id = [];
        foreach (self::MODEL as [$kode, $nama, $kategori, $namaMerek, $intervalPemeliharaan, $intervalKalibrasi]) {
            $id[$kode] = $this->simpan('ModelAset', ['OrganisasiId' => $organisasiId, 'KodeModel' => $kode], [
                'KategoriAsetId' => $kategoriAset[$kategori],
                'MerekId' => $merek[$namaMerek],
                'Nama' => $nama,
                'Produsen' => $namaMerek,
                'IntervalPemeliharaanHari' => $intervalPemeliharaan,
                'IntervalKalibrasiHari' => $intervalKalibrasi,
                'DiperbaruiPada' => now(),
            ]);
        }

        return $id;
    }

    /** @return array<string, string> */
    private function semaiPenyedia(string $organisasiId): array
    {
        $kategori = [];
        foreach ([
            'JASA-SERVIS' => 'Jasa Servis & Perawatan',
            'SUKU-CADANG' => 'Pemasok Suku Cadang',
            'TI' => 'Teknologi Informasi',
            'KALIBRASI' => 'Laboratorium Kalibrasi',
            'K3' => 'Peralatan K3',
        ] as $kode => $nama) {
            $kategori[$kode] = $this->simpan('KategoriPenyedia', ['OrganisasiId' => $organisasiId, 'Kode' => $kode], ['Nama' => $nama]);
        }

        $id = [];
        foreach (self::PENYEDIA as $urutan => [$kode, $nama, $kota, $email, $kodeKategori, $kontak]) {
            $id[$kode] = $this->simpan('Penyedia', ['OrganisasiId' => $organisasiId, 'Kode' => $kode], [
                'Nama' => $nama,
                'NamaLegal' => $nama,
                'NomorIdentitasPajak' => sprintf('02.%03d.456.7-%03d.000', 100 + $urutan, 400 + $urutan),
                'Email' => $email,
                'Telepon' => sprintf('021-%07d', 5550100 + $urutan * 37),
                'Kota' => $kota,
                'Provinsi' => str_contains($kota, 'Jakarta') ? 'DKI Jakarta' : ($kota === 'Tangerang' ? 'Banten' : 'Jawa Barat'),
                'Negara' => 'Indonesia',
                'Status' => 'Aktif',
                'DiperbaruiPada' => now(),
            ]);

            $this->simpan('PenyediaKategori', ['PenyediaId' => $id[$kode], 'KategoriPenyediaId' => $kategori[$kodeKategori]], []);
            $this->simpan('KontakPenyedia', ['OrganisasiId' => $organisasiId, 'PenyediaId' => $id[$kode], 'Nama' => $kontak], [
                'Jabatan' => 'Account Manager',
                'Email' => $email,
                'Telepon' => sprintf('0813%08d', 21000000 + $urutan * 1379),
                'Utama' => 1,
                'DiperbaruiPada' => now(),
            ]);
        }

        return $id;
    }

    /**
     * @param  array<string, string>  $kategoriAset
     * @param  array<string, string>  $model
     * @param  array<string, string>  $penyedia
     */
    private function semaiAset(string $organisasiId, array $kategoriAset, array $model, array $penyedia): void
    {
        $lokasi = DB::table('Lokasi')->where('OrganisasiId', $organisasiId)->pluck('Id', 'Kode');
        $lokasiUnit = DB::table('Lokasi')->where('OrganisasiId', $organisasiId)->pluck('UnitOrganisasiId', 'Kode');
        $unit = DB::table('UnitOrganisasi')->where('OrganisasiId', $organisasiId)->pluck('Id', 'Kode');
        $admin = $this->pengguna('manajer.aset@amanpoll.test');
        $buatAset = app(BuatAset::class);

        foreach (self::ASET as $urutan => [$kode, $nama, $kategori, $kodeModel, $kodeLokasi, $pengelola, $kodePenyedia, $bulan, $harga, $status, $kondisi, $kritis]) {
            if (DB::table('Aset')->where('OrganisasiId', $organisasiId)->where('KodeAset', $kode)->exists()) {
                continue;
            }

            $diperoleh = now()->subMonths($bulan)->startOfMonth()->addDays($urutan % 20)->setTime(10, 0);

            $this->padaWaktu($diperoleh, fn () => $buatAset->jalankan([
                'OrganisasiId' => $organisasiId,
                'KodeAset' => $kode,
                'Nama' => $nama,
                'KategoriAsetId' => $kategoriAset[$kategori],
                'ModelAsetId' => $kodeModel === null ? null : $model[$kodeModel],
                'PenyediaId' => $kodePenyedia === null ? null : $penyedia[$kodePenyedia],
                'LokasiId' => $lokasi[$kodeLokasi],
                // Pemilik aset mengikuti unit pemilik lokasinya; yang memelihara adalah bagian pengelola.
                'UnitOrganisasiId' => $lokasiUnit[$kodeLokasi],
                'UnitPengelolaId' => $unit[$pengelola],
                'NomorSeri' => sprintf('%s-%s-%05d', substr($kode, 0, 3), $diperoleh->format('y'), 1200 + $urutan * 17),
                'NomorInventaris' => sprintf('INV/SNI/%s/%04d', $diperoleh->format('Y'), $urutan + 1),
                'TanggalPerolehan' => $diperoleh->toDateString(),
                'TanggalMulaiOperasi' => $diperoleh->addDays(14)->toDateString(),
                'HargaPerolehan' => $harga,
                'MataUang' => 'IDR',
                'SumberDana' => $harga >= 500000000 ? 'Belanja Modal' : 'Anggaran Operasional',
                'Status' => $status,
                'Kondisi' => $kondisi,
                'TingkatKritis' => $kritis,
            ], $admin));
        }
    }

    /** Meter jam operasi untuk aset yang dipelihara berdasarkan pemakaian, dengan pembacaan bulanan. */
    private function semaiMeter(string $organisasiId): void
    {
        $meter = [
            // kode aset, nama meter, satuan, nilai awal, kenaikan per bulan
            ['UTL-GEN-01', 'Jam operasi mesin', 'jam', 8200, 95],
            ['UTL-GEN-02', 'Jam operasi mesin', 'jam', 11850, 40],
            ['UTL-KMP-01', 'Jam operasi', 'jam', 18400, 610],
            ['UTL-KMP-02', 'Jam operasi', 'jam', 17950, 540],
            ['GDL-FRK-01', 'Hour meter', 'jam', 6120, 165],
            ['GDL-FRK-02', 'Hour meter', 'jam', 6480, 172],
            ['GDL-FRK-03', 'Hour meter', 'jam', 5890, 158],
            ['PRD-INJ-01', 'Jumlah siklus injeksi', 'siklus', 1850000, 92000],
        ];

        $catat = app(CatatPembacaanMeter::class);
        $petugas = $this->pengguna('teknisi.teknik@amanpoll.test');

        foreach ($meter as [$kodeAset, $nama, $satuan, $awal, $perBulan]) {
            $asetId = $this->idDari('Aset', ['KodeAset' => $kodeAset]);

            if (DB::table('MeterAset')->where('AsetId', $asetId)->exists()) {
                continue;
            }

            $meterAset = MeterAset::query()->create([
                'OrganisasiId' => $organisasiId,
                'AsetId' => $asetId,
                'Nama' => $nama,
                'Satuan' => $satuan,
                'Jenis' => 'Kumulatif',
                'NilaiAwal' => $awal,
                'Aktif' => true,
            ]);

            for ($bulanLalu = 11; $bulanLalu >= 0; $bulanLalu--) {
                $nilai = $awal + $perBulan * (12 - $bulanLalu);
                $this->padaWaktu($this->hariLalu($bulanLalu * 30 + 2, 8), fn () => $catat->jalankan($meterAset, [
                    'OrganisasiId' => $organisasiId,
                    'Nilai' => $nilai,
                    'DibacaPada' => now(),
                    'Sumber' => 'Manual',
                ], $petugas));
            }
        }
    }

    /** Garansi pabrikan untuk aset yang umurnya masih di bawah tiga tahun. */
    private function semaiGaransi(string $organisasiId, array $penyedia): void
    {
        foreach (self::ASET as [$kode, , , , , , $kodePenyedia, $bulan]) {
            if ($bulan > 36 || $kodePenyedia === null) {
                continue;
            }

            $aset = Aset::query()->where('KodeAset', $kode)->firstOrFail();
            $mulai = $aset->TanggalPerolehan ?? now();

            $this->simpan('GaransiAset', ['OrganisasiId' => $organisasiId, 'AsetId' => $aset->Id], [
                'PenyediaId' => $penyedia[$kodePenyedia],
                'NomorGaransi' => 'GRN-'.str_replace('-', '', $kode),
                'JenisGaransi' => 'Pabrikan',
                'MulaiPada' => $mulai->toDateString(),
                'BerakhirPada' => $mulai->copy()->addMonths(36)->toDateString(),
                'Cakupan' => 'Suku cadang dan jasa perbaikan akibat cacat produksi.',
                'Status' => $mulai->copy()->addMonths(36)->isPast() ? 'Kedaluwarsa' : 'Aktif',
                'DiperbaruiPada' => now(),
            ]);
        }
    }

    /** @return array<string, string> */
    private function semaiGudang(string $organisasiId): array
    {
        $unit = DB::table('UnitOrganisasi')->where('OrganisasiId', $organisasiId)->pluck('Id', 'Kode');
        $gudang = [
            ['GDG-CKR', 'Gudang Suku Cadang Cikarang', 'CKR-GSC', 'TEKFAS', 'gudang@amanpoll.test'],
            ['GDG-JKT', 'Gudang Kecil Menara Sinar', 'JKT-L15', 'IT', 'koordinator.it@amanpoll.test'],
            ['GDG-SBY', 'Gudang Suku Cadang Surabaya', 'SBY', 'TEKFAS', 'gudang@amanpoll.test'],
        ];

        $id = [];
        foreach ($gudang as [$kode, $nama, $kodeLokasi, $pengelola, $penanggungJawab]) {
            $id[$kode] = $this->simpan('Gudang', ['OrganisasiId' => $organisasiId, 'Kode' => $kode], [
                'Nama' => $nama,
                'LokasiId' => $this->idDari('Lokasi', ['Kode' => $kodeLokasi]),
                'UnitPengelolaId' => $unit[$pengelola],
                'PenanggungJawabId' => $this->pengguna($penanggungJawab),
                'Status' => 'Aktif',
                'DiperbaruiPada' => now(),
            ]);
        }

        foreach (['A' => 'Rak A — Filter & Pelumas', 'B' => 'Rak B — Mekanikal', 'C' => 'Rak C — Elektrikal', 'D' => 'Rak D — Produksi'] as $kode => $nama) {
            $this->simpan('LokasiGudang', ['OrganisasiId' => $organisasiId, 'GudangId' => $id['GDG-CKR'], 'Kode' => "RAK-{$kode}"], ['Nama' => $nama]);
        }

        return $id;
    }

    /**
     * @param  array<string, string>  $kategoriAset
     * @return array<string, string>
     */
    private function semaiSukuCadang(string $organisasiId, array $kategoriAset): array
    {
        $kategori = [];
        foreach ([
            'SC-FILTER' => 'Filter', 'SC-PELUMAS' => 'Pelumas & Oli', 'SC-MEKANIKAL' => 'Mekanikal',
            'SC-PRODUKSI' => 'Komponen Mesin Produksi', 'SC-ELEKTRIKAL' => 'Elektrikal', 'SC-HVAC' => 'HVAC',
            'SC-TI' => 'Perangkat TI', 'SC-K3' => 'Perlengkapan K3',
        ] as $kode => $nama) {
            $kategori[$kode] = $this->simpan('KategoriSukuCadang', ['OrganisasiId' => $organisasiId, 'Kode' => $kode], ['Nama' => $nama]);
        }

        $id = [];
        foreach (self::SUKU_CADANG as [$kode, $nama, $kodeKategori, $satuan, $minimum, $pesanUlang, $harga, , , $kategoriCocok]) {
            $id[$kode] = $this->simpan('SukuCadang', ['OrganisasiId' => $organisasiId, 'Kode' => $kode], [
                'KategoriSukuCadangId' => $kategori[$kodeKategori],
                'Nama' => $nama,
                'NomorBagian' => strtoupper(substr(md5($kode), 0, 8)),
                'SatuanDasar' => $satuan,
                'StokMinimum' => $minimum,
                'StokMaksimum' => $pesanUlang * 4,
                'TitikPesanUlang' => $pesanUlang,
                'HargaRataRata' => $harga,
                'MemakaiBatch' => 0,
                'MemakaiKadaluarsa' => 0,
                'Status' => 'Aktif',
                'DiperbaruiPada' => now(),
            ]);

            if ($kategoriCocok !== null) {
                $this->simpan('KompatibilitasSukuCadang', [
                    'OrganisasiId' => $organisasiId,
                    'SukuCadangId' => $id[$kode],
                    'KategoriAsetId' => $kategoriAset[$kategoriCocok],
                ], []);
            }
        }

        return $id;
    }

    /**
     * Saldo awal lewat satu mutasi Penerimaan per gudang yang diposting setahun lalu,
     * sehingga StokSukuCadang dan buku mutasinya berasal dari jalur yang sama dengan aplikasi.
     *
     * @param  array<string, string>  $gudang
     * @param  array<string, string>  $sukuCadang
     */
    private function semaiSaldoAwalStok(string $organisasiId, array $gudang, array $sukuCadang): void
    {
        if (DB::table('MutasiStok')->where('OrganisasiId', $organisasiId)->exists()) {
            return;
        }

        $petugas = $this->pengguna('gudang@amanpoll.test');

        foreach ($gudang as $kodeGudang => $gudangId) {
            $baris = array_filter(self::SUKU_CADANG, fn (array $satu): bool => $satu[7] === $kodeGudang);
            if ($baris === []) {
                continue;
            }

            $this->padaWaktu($this->hariLalu(365, 8), function () use ($organisasiId, $gudangId, $baris, $sukuCadang, $petugas, $kodeGudang): void {
                $mutasi = app(BuatMutasiStok::class)->jalankan([
                    'OrganisasiId' => $organisasiId,
                    'Jenis' => 'Penerimaan',
                    'GudangTujuanId' => $gudangId,
                    'Catatan' => "Saldo awal stok {$kodeGudang} saat Amanpoll mulai dipakai.",
                ], $petugas);

                foreach ($baris as [$kode, , , , , , $harga, , $jumlah]) {
                    app(TambahDetailMutasiStok::class)->jalankan($mutasi, [
                        'SukuCadangId' => $sukuCadang[$kode],
                        'Jumlah' => $jumlah,
                        'HargaSatuan' => $harga,
                    ]);
                }

                app(PostingMutasiStok::class)->jalankan($mutasi->refresh(), $petugas);
            }, 'gudang@amanpoll.test');
        }
    }
}
