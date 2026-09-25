<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\PerencanaanPengadaan\Application\Actions\CatatPembayaranPenyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\CatatPenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Application\Actions\CatatTransaksiAnggaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaAnggaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPermintaanPenawaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPesananPembelian;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPosAnggaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaRencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaTagihanPenyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaUsulanAset;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPenawaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\RencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
use App\Domain\Persediaan\Application\Actions\BatalkanMutasiStok;
use App\Domain\Persediaan\Application\Actions\BuatMutasiStok;
use App\Domain\Persediaan\Application\Actions\PostingMutasiStok;
use App\Domain\Persediaan\Application\Actions\TambahDetailMutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Persetujuan\Application\Actions\AktifkanAlurPersetujuan;
use App\Domain\Persetujuan\Application\Actions\BuatAlurPersetujuan;
use App\Domain\Persetujuan\Application\Actions\BuatTahapPersetujuan;
use App\Domain\Persetujuan\Application\Actions\SetujuiPermintaanPersetujuan;
use App\Domain\Persetujuan\Application\Actions\TolakPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\ValueObjects\Uang;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Riwayat pengadaan dan persediaan PT Sinar Nusantara Industri selama ± 12 bulan:
 * alur persetujuan, anggaran beserta pos dan ledgernya, usulan aset, rencana
 * pengadaan, siklus PP → RFQ → penawaran → PO → penerimaan → tagihan → pembayaran,
 * serta mutasi stok di luar perintah kerja (transfer, stok opname, pengeluaran).
 *
 * Setiap langkah dijadwalkan pada waktunya sendiri lalu seluruh jadwal dijalankan
 * berurutan waktu lewat Action resmi, sehingga nomor dokumen, status, ledger
 * anggaran, saldo stok, notifikasi, dan jejak audit terbentuk persis seperti bila
 * pengguna mengerjakannya di aplikasi. Langkah yang jatuh setelah hari ini tidak
 * dijalankan; dari situlah dokumen yang "sedang berjalan" hari ini terbentuk.
 */
final class DemoPengadaanSeeder extends Seeder
{
    use KonteksDemo;

    private const PENGADAAN = 'pengadaan@amanpoll.test';

    private const PENYETUJU = 'penyetuju@amanpoll.test';

    private const MANAJER_ASET = 'manajer.aset@amanpoll.test';

    private const GUDANG = 'gudang@amanpoll.test';

    private const ADMIN = 'admin@amanpoll.test';

    private const ZONA = 'Asia/Jakarta';

    /** @var array<string, string> Koordinator yang memverifikasi PP unitnya (tahap berbasis unit). */
    private const KOORDINATOR_UNIT = [
        'TEKFAS' => 'koordinator.teknik@amanpoll.test',
        'IT' => 'koordinator.it@amanpoll.test',
    ];

    /**
     * Alur persetujuan per jenis dokumen yang didukung mesin persetujuan.
     * Tahap: nama, jenis penyetuju (Pengguna/Peran/Unit), email atau kode peran, batas waktu menit,
     * dan ambang nilai opsional (tahap hanya berlaku untuk dokumen senilai itu ke atas).
     *
     * @var list<array{0: string, 1: string, 2: string, 3: list<array{0: string, 1: string, 2: string, 3: int, 4?: int}>}>
     */
    private const ALUR_PERSETUJUAN = [
        ['ALR-ANGGARAN', 'Persetujuan Anggaran Tahunan', 'Anggaran', [
            ['Persetujuan Direktur Operasional', 'Peran', 'PENYETUJU', 4320],
        ]],
        ['ALR-USULAN-ASET', 'Persetujuan Usulan Aset', 'UsulanAset', [
            ['Kajian Manajer Aset & Fasilitas', 'Pengguna', self::MANAJER_ASET, 2880],
            ['Persetujuan Direktur Operasional', 'Peran', 'PENYETUJU', 2880],
        ]],
        // PP di bawah Rp 25 juta cukup diverifikasi koordinator; di atasnya naik ke direktur.
        ['ALR-PP', 'Persetujuan Permintaan Pembelian', 'PermintaanPembelian', [
            ['Verifikasi Koordinator Unit Peminta', 'Unit', 'KOORDINATOR-PEMELIHARAAN', 1440],
            ['Persetujuan Direktur Operasional', 'Peran', 'PENYETUJU', 2880, 25_000_000],
        ]],
        ['ALR-PO', 'Persetujuan Pesanan Pembelian', 'PesananPembelian', [
            ['Persetujuan Direktur Operasional', 'Peran', 'PENYETUJU', 1440],
        ]],
        ['ALR-MUTASI-ASET', 'Persetujuan Mutasi Aset', 'PermintaanMutasiAset', [
            ['Persetujuan Manajer Aset & Fasilitas', 'Pengguna', self::MANAJER_ASET, 2880],
        ]],
        ['ALR-HAPUS-ASET', 'Persetujuan Penghapusan Aset', 'PengajuanPenghapusanAset', [
            ['Kajian Manajer Aset & Fasilitas', 'Pengguna', self::MANAJER_ASET, 4320],
            ['Persetujuan Direktur Operasional', 'Peran', 'PENYETUJU', 4320],
        ]],
    ];

    /**
     * Struktur pos anggaran: kode, nama, kode induk, dan nilai (juta rupiah) untuk
     * tahun lalu, tahun berjalan, dan usulan tahun depan.
     *
     * @var list<array{0: string, 1: string, 2: string|null, 3: array{0: int, 1: int, 2: int}}>
     */
    private const POS_ANGGARAN = [
        ['PMH', 'Pemeliharaan & Jasa Servis', null, [1700, 1900, 2050]],
        ['PMH-UTL', 'Servis Utilitas: Genset, Kompresor, Chiller', 'PMH', [850, 950, 1000]],
        ['PMH-GDG', 'Servis Gedung, Lift & Forklift', 'PMH', [550, 600, 650]],
        ['PMH-K3', 'Sarana K3 & Kalibrasi Eksternal', 'PMH', [300, 350, 400]],
        ['SC', 'Suku Cadang & Material Pemeliharaan', null, [1250, 1400, 1500]],
        ['MODAL', 'Belanja Modal Aset', null, [2900, 3200, 3600]],
        ['TI', 'Teknologi Informasi', null, [700, 800, 900]],
        ['TI-HW', 'Perangkat Keras & Suku Cadang TI', 'TI', [450, 520, 580]],
        ['TI-LYN', 'Lisensi & Layanan TI', 'TI', [250, 280, 320]],
    ];

    /** @var array{0: int, 1: int, 2: int} Pagu anggaran (juta rupiah): tahun lalu, berjalan, depan. */
    private const PAGU_ANGGARAN = [6800, 7500, 8300];

    /**
     * Usulan aset: kunci, hari lalu, unit, kebutuhan, kategori, model, jumlah, estimasi harga,
     * jenis kebutuhan, untuk tahun depan, prioritas, nilai 4 kriteria, akhir cerita, alasan.
     * Akhir: Disetujui, Ditolak, MenungguPenyetuju, MenungguManajer, Dinilai, Draft.
     *
     * @var list<array{0: string, 1: int, 2: string, 3: string, 4: string, 5: string|null, 6: int, 7: int, 8: string, 9: bool, 10: string, 11: array{0: int, 1: int, 2: int, 3: int}, 12: string, 13: string}>
     */
    private const USULAN_ASET = [
        ['U1', 300, 'PROD', 'Mould temperature controller 9 kW tambahan untuk lini injeksi 3–4', 'KAT-MESIN', null, 2, 48000000, 'Penambahan', true, 'Tinggi', [85, 80, 75, 90], 'Disetujui',
            'Lini injeksi 3 dan 4 masih berbagi satu MTC sehingga suhu cetakan tidak stabil dan reject short-shot naik ke 3,8%. MTC terpisah per lini menurunkan reject dan waktu set-up.'],
        ['U2', 296, 'TEKFAS', 'Pompa air bersih pengganti Grundfos CR32 #2', 'KAT-POMPA', 'CR32', 1, 98000000, 'Penggantian', true, 'Kritis', [95, 85, 80, 85], 'Disetujui',
            'Pompa #2 rusak berat (impeller aus dan motor terbakar); biaya rewinding dan impeller baru mendekati 70% harga unit baru. Pabrik saat ini hanya bertumpu pada satu pompa.'],
        ['U3', 293, 'GDL', 'Forklift diesel 2,5 ton tambahan gudang Surabaya', 'KAT-FORK', '8FD25', 1, 395000000, 'Penambahan', true, 'Tinggi', [80, 70, 75, 80], 'Disetujui',
            'Volume pengiriman gudang Surabaya naik 35% sejak kontrak distributor Jawa Timur; satu forklift tidak cukup saat bongkar dua truk bersamaan dan sewa harian sudah melebihi Rp18 juta per bulan.'],
        ['U4', 290, 'IT', 'Switch distribusi pabrik pengganti (end of support)', 'KAT-IT', null, 1, 68000000, 'Penggantian', true, 'Tinggi', [85, 60, 80, 90], 'Disetujui',
            'Switch distribusi Cikarang sudah end-of-support dari pabrikan, tidak lagi mendapat patch keamanan, dan tiga kali hang pada kuartal ini sehingga sistem timbangan dan printer label gudang terputus.'],
        ['U5', 287, 'HRGA', 'Kendaraan operasional direksi (MPV)', 'KAT-FORK', null, 1, 650000000, 'Penambahan', true, 'Normal', [40, 20, 35, 30], 'Ditolak',
            'Kendaraan direksi saat ini berumur 9 tahun dan sering masuk bengkel; diusulkan pengadaan MPV baru untuk kunjungan pelanggan dan pabrik.'],
        ['U6', 124, 'PROD', 'Mesin crusher plastik 30 HP pengganti', 'KAT-MESIN', null, 1, 185000000, 'Penggantian', false, 'Tinggi', [85, 75, 80, 70], 'Disetujui',
            'Crusher lama berumur 7 tahun, pisau cepat tumpul, sering macet, dan kebisingannya 96 dB melebihi NAB. Unit baru menaikkan kapasitas daur ulang regrind dari 150 ke 250 kg/jam.'],
        ['U7', 45, 'QA', 'Oven laboratorium tambahan untuk uji ketahanan panas kemasan', 'KAT-UKUR', null, 1, 72000000, 'Penambahan', true, 'Normal', [70, 75, 70, 65], 'MenungguPenyetuju',
            'Permintaan uji ketahanan panas dari pelanggan makanan naik; satu oven membuat antrean uji mencapai 4 hari dan menahan rilis lot produksi.'],
        ['U8', 20, 'QA', 'Tensile tester pengganti Instron 3345 (umur lebih dari 10 tahun)', 'KAT-UKUR', null, 1, 540000000, 'Penggantian', true, 'Tinggi', [80, 85, 70, 60], 'MenungguManajer',
            'Load cell tensile tester sering drift sehingga dua kali gagal kalibrasi ulang; suku cadang controller sudah tidak diproduksi.'],
        ['U9', 12, 'IT', 'Server backup tambahan dan perluasan NAS', 'KAT-IT', 'R750', 1, 310000000, 'Penambahan', true, 'Normal', [75, 60, 70, 60], 'Dinilai',
            'Kapasitas backup harian ERP dan file server sudah 87%; retensi backup terpaksa dipangkas dari 30 menjadi 14 hari.'],
        ['U10', 6, 'TEKFAS', 'Genset 500 kVA pengganti (umur lebih dari 7 tahun, jam operasi tinggi)', 'KAT-GENSET', 'P500-1', 1, 1350000000, 'Penggantian', true, 'Tinggi', [85, 80, 60, 50], 'Draft',
            'Genset cadangan Perkins 500 kVA sudah lebih dari 12.000 jam operasi, konsumsi oli meningkat, dan dua kali gagal start saat uji beban bulanan.'],
    ];

    /** @var list<string> Kriteria penilaian usulan beserta bobotnya (urutan sama dengan nilai usulan). */
    private const KRITERIA_PENILAIAN = [
        'Urgensi operasional',
        'Dampak K3 & mutu produk',
        'Efisiensi biaya siklus hidup',
        'Kesiapan anggaran',
    ];

    /** @var list<int> */
    private const BOBOT_KRITERIA = [40, 25, 20, 15];

    /**
     * Rencana pengadaan: kunci, hari lalu, nama (dengan {tahun}), geser tahun, pos, usulan,
     * baris tambahan [deskripsi, kode suku cadang, jumlah, satuan, harga, bulan], difinalisasi.
     *
     * @var list<array{0: string, 1: int, 2: string, 3: int, 4: string|null, 5: list<string>, 6: list<array{0: string, 1: string|null, 2: int, 3: string, 4: int, 5: int}>, 7: bool}>
     */
    private const RENCANA_PENGADAAN = [
        ['R1', 272, 'Rencana Pengadaan Aset Tahun {tahun}', 0, 'MODAL', ['U1', 'U2', 'U3'], [], true],
        ['R2', 270, 'Rencana Pengadaan Perangkat TI Tahun {tahun}', 0, 'TI-HW', ['U4'], [
            ['Laptop pengganti staf keuangan', null, 3, 'unit', 21000000, 4],
            ['Hard disk cadangan server dan NAS', 'SC-HDD-001', 6, 'pcs', 6850000, 9],
        ], true],
        ['R3', 104, 'Rencana Pengadaan Aset Semester II {tahun}', 0, 'MODAL', ['U6'], [
            ['Pompa jockey hydrant cadangan', null, 1, 'unit', 45000000, 10],
        ], true],
        ['R4', 8, 'Rencana Pengadaan Aset Tahun {tahun}', 1, null, [], [
            ['Genset 500 kVA pengganti', null, 1, 'unit', 1350000000, 3],
            ['Tensile tester pengganti', null, 1, 'unit', 540000000, 5],
            ['Server backup tambahan dan perluasan NAS', null, 1, 'unit', 310000000, 2],
        ], false],
    ];

    /**
     * Permintaan pembelian. Item: [jenis, kode suku cadang/aset referensi, deskripsi, jumlah, satuan, harga estimasi].
     * `berhenti` menahan siklus di satu titik untuk pekerjaan yang sedang berjalan hari ini.
     *
     * @var list<array{kunci: string, hari: int, unit: string, pos: string, prioritas: string, alasan: string, item: list<array{0: string, 1: string|null, 2: string, 3: int, 4: string, 5: int}>, penyedia: list<string>, gudang?: string, kirim?: int, tempo?: int, batas?: int, rencana?: string, berhenti?: string, sebagian?: list<int>, tolak?: string}>
     */
    private const PERMINTAAN_PEMBELIAN = [
        ['kunci' => 'PP01', 'hari' => 342, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Normal',
            'alasan' => 'Pengisian ulang stok filter genset menjelang servis 500 jam kuartal IV; stok filter oli tinggal 5 pcs, mendekati titik pesan ulang.',
            'item' => [['SukuCadang', 'SC-FLT-001', 'Filter oli genset Cummins LF9009', 8, 'pcs', 685000], ['SukuCadang', 'SC-FLT-002', 'Filter solar genset FS1000', 6, 'pcs', 540000]],
            'penyedia' => ['VND-005', 'VND-001'], 'gudang' => 'GDG-CKR', 'kirim' => 7],
        ['kunci' => 'PP02', 'hari' => 333, 'unit' => 'TEKFAS', 'pos' => 'PMH-UTL', 'prioritas' => 'Tinggi',
            'alasan' => 'Kompresor GA75 #2 mengalami kenaikan suhu elemen dan konsumsi oli; perlu overhaul 40.000 jam sesuai rekomendasi pabrikan.',
            'item' => [['Jasa', null, 'Jasa overhaul elemen kompresor GA75 #2 (40.000 jam)', 1, 'paket', 38500000], ['SukuCadang', 'SC-FLT-004', 'Separator oli kompresor GA75', 2, 'pcs', 4850000]],
            'penyedia' => ['VND-002'], 'gudang' => 'GDG-CKR', 'kirim' => 12],
        ['kunci' => 'PP03', 'hari' => 319, 'unit' => 'IT', 'pos' => 'TI-HW', 'prioritas' => 'Normal',
            'alasan' => 'Cadangan disk server ERP dan file server; dua disk RAID menunjukkan status predictive failure.',
            'item' => [['SukuCadang', 'SC-HDD-001', 'Hard disk SAS 2,4 TB 10K', 4, 'pcs', 6850000], ['SukuCadang', 'SC-SFP-001', 'Modul SFP+ 10G SR', 4, 'pcs', 2150000]],
            'penyedia' => ['VND-006'], 'gudang' => 'GDG-JKT', 'kirim' => 10],
        ['kunci' => 'PP04', 'hari' => 306, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Tinggi',
            'alasan' => 'Pemakaian heater band dan thermocouple lini injeksi meningkat setelah penambahan shift; stok di bawah titik pesan ulang.',
            'item' => [['SukuCadang', 'SC-HTR-001', 'Heater band barrel injeksi 220V 1,2 kW', 12, 'pcs', 425000], ['SukuCadang', 'SC-TC-001', 'Thermocouple tipe J', 20, 'pcs', 165000], ['SukuCadang', 'SC-NZL-001', 'Nozzle tip injeksi MA3800', 2, 'pcs', 3200000]],
            'penyedia' => ['VND-009', 'VND-005'], 'gudang' => 'GDG-CKR', 'kirim' => 14],
        ['kunci' => 'PP05', 'hari' => 297, 'unit' => 'TEKFAS', 'pos' => 'PMH-GDG', 'prioritas' => 'Normal',
            'alasan' => 'Servis berkala lift penumpang Menara Sinar semester II sesuai kontrak pemeliharaan dan persyaratan SILO.',
            'item' => [['Jasa', null, 'Jasa servis berkala lift penumpang Menara Sinar #1 & #2 (Juli–Desember)', 1, 'paket', 64000000]],
            'penyedia' => ['VND-004'], 'kirim' => 5],
        ['kunci' => 'PP06', 'hari' => 289, 'unit' => 'TEKFAS', 'pos' => 'MODAL', 'prioritas' => 'Normal',
            'alasan' => 'Penambahan AC split untuk ruang kontrol lini blow molding dan ruang QC yang suhunya melebihi 28°C pada siang hari.',
            'item' => [['Aset', 'GDG-AC-01', 'AC split Daikin 2 PK inverter', 2, 'unit', 11500000]],
            'penyedia' => ['VND-003'], 'kirim' => 10],
        ['kunci' => 'PP07', 'hari' => 282, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Normal',
            'alasan' => 'Stok akhir tahun oli hidrolik dan grease untuk pelumasan mesin injeksi selama libur panjang Natal–Tahun Baru.',
            'item' => [['SukuCadang', 'SC-OLI-002', 'Oli hidrolik ISO VG 46 (pail 20 L)', 20, 'pail', 1150000], ['SukuCadang', 'SC-GRS-001', 'Grease EP2 (kaleng 16 kg)', 6, 'kaleng', 1450000]],
            'penyedia' => ['VND-005', 'VND-009'], 'gudang' => 'GDG-CKR', 'kirim' => 6],
        ['kunci' => 'PP08', 'hari' => 258, 'unit' => 'TEKFAS', 'pos' => 'PMH-UTL', 'prioritas' => 'Tinggi',
            'alasan' => 'Kontrak servis preventif genset 1000 kVA dan 500 kVA tahun berjalan (4 kunjungan dan tanggap darurat 24 jam).',
            'item' => [['Jasa', null, 'Kontrak servis preventif genset Cummins 1000 kVA & Perkins 500 kVA (4 kunjungan)', 1, 'paket', 186000000]],
            'penyedia' => ['VND-001'], 'kirim' => 20],
        ['kunci' => 'PP09', 'hari' => 249, 'unit' => 'IT', 'pos' => 'TI-LYN', 'prioritas' => 'Tinggi',
            'alasan' => 'Lisensi UTM firewall FortiGate 200F berakhir bulan depan; tanpa perpanjangan, filter web dan IPS berhenti bekerja.',
            'item' => [['Lainnya', null, 'Perpanjangan lisensi FortiGuard UTM Bundle FortiGate 200F (1 tahun)', 1, 'lisensi', 68000000]],
            'penyedia' => ['VND-006'], 'kirim' => 3],
        ['kunci' => 'PP10', 'hari' => 235, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Normal',
            'alasan' => 'Persiapan overhaul pompa dan conveyor sebelum libur Lebaran; bearing dan v-belt mendekati titik pesan ulang.',
            'item' => [['SukuCadang', 'SC-BRG-001', 'Bearing SKF 6205-2RS', 30, 'pcs', 95000], ['SukuCadang', 'SC-BRG-002', 'Bearing SKF 6310-2Z', 10, 'pcs', 385000], ['SukuCadang', 'SC-BLT-001', 'V-belt B-68', 16, 'pcs', 145000], ['SukuCadang', 'SC-SEL-001', 'Mechanical seal pompa CR32', 2, 'pcs', 2350000]],
            'penyedia' => ['VND-005', 'VND-009'], 'gudang' => 'GDG-CKR', 'kirim' => 8],
        ['kunci' => 'PP11', 'hari' => 226, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Normal',
            'alasan' => 'Penggantian komponen panel dan lampu high bay Gedung Produksi A hasil temuan pemeriksaan thermografi.',
            'item' => [['SukuCadang', 'SC-KNT-001', 'Kontaktor Schneider LC1D32', 6, 'pcs', 1180000], ['SukuCadang', 'SC-MCB-001', 'MCB 3P 32A Schneider', 10, 'pcs', 485000], ['SukuCadang', 'SC-LMP-001', 'Lampu LED high bay 150 W', 12, 'pcs', 1350000]],
            'penyedia' => ['VND-005'], 'gudang' => 'GDG-CKR', 'kirim' => 9],
        ['kunci' => 'PP12', 'hari' => 212, 'unit' => 'TEKFAS', 'pos' => 'MODAL', 'prioritas' => 'Tinggi', 'rencana' => 'R1',
            'alasan' => 'Realisasi usulan penambahan MTC untuk lini injeksi 3–4 sesuai rencana pengadaan aset tahunan.',
            'item' => [['Aset', 'PRD-MTC-01', 'Mould temperature controller 9 kW', 2, 'unit', 48000000]],
            'penyedia' => ['VND-009', 'VND-005'], 'kirim' => 21],
        ['kunci' => 'PP13', 'hari' => 199, 'unit' => 'TEKFAS', 'pos' => 'PMH-GDG', 'prioritas' => 'Normal',
            'alasan' => 'Servis 1.000 jam forklift gudang Surabaya dan penggantian ban solid yang sudah aus.',
            'item' => [['Jasa', null, 'Jasa servis 1.000 jam forklift Toyota 8FD25 Surabaya', 1, 'paket', 18500000], ['SukuCadang', 'SC-BAN-001', 'Ban solid forklift 7.00-12', 4, 'pcs', 3650000]],
            'penyedia' => ['VND-010'], 'gudang' => 'GDG-SBY', 'kirim' => 7],
        ['kunci' => 'PP14', 'hari' => 185, 'unit' => 'IT', 'pos' => 'TI-HW', 'prioritas' => 'Normal', 'rencana' => 'R2',
            'alasan' => 'Penggantian laptop staf keuangan yang rusak dan kebutuhan toner printer kantor pusat.',
            'item' => [['Aset', 'IT-PC-02', 'Laptop Dell Latitude 5450 i5/16GB/512GB', 3, 'unit', 21000000], ['SukuCadang', 'SC-TNR-001', 'Toner HP 76A', 10, 'pcs', 1450000]],
            'penyedia' => ['VND-006'], 'gudang' => 'GDG-JKT', 'kirim' => 10],
        ['kunci' => 'PP15', 'hari' => 171, 'unit' => 'TEKFAS', 'pos' => 'PMH-UTL', 'prioritas' => 'Tinggi',
            'alasan' => 'Overhaul chiller York 300 TR: efisiensi turun dan arus kompresor tinggi.',
            'item' => [['Jasa', null, 'Jasa overhaul chiller York YK 300 TR', 1, 'paket', 245000000]],
            'penyedia' => ['VND-003'], 'berhenti' => 'Ditolak',
            'tolak' => 'Nilai terlalu besar tanpa pembanding. Lengkapi laporan diagnosa dan minimal dua penawaran, ajukan ulang setelah puncak produksi.'],
        ['kunci' => 'PP16', 'hari' => 159, 'unit' => 'TEKFAS', 'pos' => 'PMH-K3', 'prioritas' => 'Normal',
            'alasan' => 'Isi ulang APAR CO2 yang dipakai simulasi tanggap darurat dan inspeksi tahunan sistem hydrant.',
            'item' => [['SukuCadang', 'SC-APR-001', 'Tabung isi ulang APAR CO2 5 kg', 10, 'pcs', 450000], ['Jasa', null, 'Jasa inspeksi dan uji tekanan sistem hydrant pabrik', 1, 'paket', 22000000]],
            'penyedia' => ['VND-008'], 'gudang' => 'GDG-CKR', 'kirim' => 6],
        ['kunci' => 'PP17', 'hari' => 145, 'unit' => 'TEKFAS', 'pos' => 'MODAL', 'prioritas' => 'Mendesak', 'rencana' => 'R1',
            'alasan' => 'Pompa air bersih #2 rusak berat (impeller dan motor terbakar); pabrik hanya bertumpu pada satu pompa.',
            'item' => [['Aset', 'UTL-PMP-02', 'Pompa air bersih Grundfos CR 32-4 lengkap motor 11 kW', 1, 'unit', 98000000]],
            'penyedia' => ['VND-005', 'VND-002'], 'kirim' => 14],
        ['kunci' => 'PP18', 'hari' => 131, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Tinggi',
            'alasan' => 'Persiapan overhaul tengah tahun mesin injeksi #1–#4 (turun mesin terjadwal minggu ketiga Juni).',
            'item' => [['SukuCadang', 'SC-SOL-001', 'Solenoid valve hidrolik 24 VDC', 4, 'pcs', 2750000], ['SukuCadang', 'SC-HTR-001', 'Heater band barrel injeksi 220V 1,2 kW', 10, 'pcs', 425000], ['SukuCadang', 'SC-TC-001', 'Thermocouple tipe J', 15, 'pcs', 165000], ['SukuCadang', 'SC-OLI-002', 'Oli hidrolik ISO VG 46 (pail 20 L)', 12, 'pail', 1150000]],
            'penyedia' => ['VND-009', 'VND-005'], 'gudang' => 'GDG-CKR', 'kirim' => 10],
        ['kunci' => 'PP19', 'hari' => 117, 'unit' => 'TEKFAS', 'pos' => 'PMH-UTL', 'prioritas' => 'Tinggi',
            'alasan' => 'Pengajuan ulang overhaul chiller York dilengkapi laporan diagnosa vibrasi dan analisis oli kompresor.',
            'item' => [['Jasa', null, 'Jasa overhaul chiller York YK 300 TR termasuk penggantian bearing kompresor', 1, 'paket', 228000000]],
            'penyedia' => ['VND-003'], 'kirim' => 25],
        ['kunci' => 'PP20', 'hari' => 103, 'unit' => 'IT', 'pos' => 'TI-HW', 'prioritas' => 'Tinggi', 'rencana' => 'R2',
            'alasan' => 'Switch distribusi pabrik sudah end-of-support dan sering hang; pengganti sesuai usulan aset yang disetujui.',
            'item' => [['Aset', 'IT-NET-03', 'Switch distribusi Cisco Catalyst 9200L 48 port PoE', 1, 'unit', 68000000], ['SukuCadang', 'SC-SFP-001', 'Modul SFP+ 10G SR', 4, 'pcs', 2150000]],
            'penyedia' => ['VND-006'], 'gudang' => 'GDG-JKT', 'kirim' => 14],
        ['kunci' => 'PP21', 'hari' => 87, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Normal',
            'alasan' => 'Servis 4.000 jam kompresor GA75 #1 dan #2; filter udara dan oli kompresor di bawah titik pesan ulang.',
            'item' => [['SukuCadang', 'SC-FLT-003', 'Filter udara kompresor GA75', 4, 'pcs', 1250000], ['SukuCadang', 'SC-OLI-003', 'Oli kompresor Roto-Inject (20 L)', 4, 'pail', 3650000], ['SukuCadang', 'SC-FLT-004', 'Separator oli kompresor GA75', 1, 'pcs', 4850000]],
            'penyedia' => ['VND-002', 'VND-005'], 'gudang' => 'GDG-CKR', 'kirim' => 8],
        ['kunci' => 'PP22', 'hari' => 73, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Normal',
            'alasan' => 'Perawatan AC split kantor pusat menjelang musim kemarau; stok filter dan kapasitor AC menipis.',
            'item' => [['SukuCadang', 'SC-FRN-001', 'Freon R-32 (tabung 10 kg)', 4, 'tabung', 1850000], ['SukuCadang', 'SC-KAP-001', 'Kapasitor AC 45 µF', 10, 'pcs', 185000], ['SukuCadang', 'SC-FLT-005', 'Filter AC split Daikin', 20, 'pcs', 95000]],
            'penyedia' => ['VND-003', 'VND-005'], 'gudang' => 'GDG-JKT', 'kirim' => 7, 'berhenti' => 'DibayarSebagian'],
        ['kunci' => 'PP23', 'hari' => 62, 'unit' => 'TEKFAS', 'pos' => 'MODAL', 'prioritas' => 'Tinggi', 'rencana' => 'R1',
            'alasan' => 'Tambahan forklift untuk gudang distribusi Surabaya seiring kenaikan volume pengiriman (usulan aset disetujui).',
            'item' => [['Aset', 'GDL-FRK-03', 'Forklift diesel Toyota 8FD25 2,5 ton', 1, 'unit', 395000000]],
            'penyedia' => ['VND-010'], 'kirim' => 21, 'tempo' => 45, 'berhenti' => 'Ditagih'],
        ['kunci' => 'PP24', 'hari' => 50, 'unit' => 'TEKFAS', 'pos' => 'PMH-GDG', 'prioritas' => 'Normal',
            'alasan' => 'Servis berkala lift penumpang Menara Sinar semester II sesuai kontrak pemeliharaan.',
            'item' => [['Jasa', null, 'Jasa servis berkala lift penumpang Menara Sinar #1 & #2 (Juli–Desember)', 1, 'paket', 66000000]],
            'penyedia' => ['VND-004'], 'kirim' => 5, 'tempo' => 14, 'berhenti' => 'Ditagih'],
        ['kunci' => 'PP25', 'hari' => 41, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Tinggi',
            'alasan' => 'Penggantian aki starter genset (umur 3 tahun, tegangan cranking turun) dan filter untuk servis 250 jam.',
            'item' => [['SukuCadang', 'SC-AKI-001', 'Aki starter genset 12V 200Ah', 2, 'pcs', 4250000], ['SukuCadang', 'SC-FLT-001', 'Filter oli genset Cummins LF9009', 6, 'pcs', 685000], ['SukuCadang', 'SC-FLT-002', 'Filter solar genset FS1000', 6, 'pcs', 540000]],
            'penyedia' => ['VND-005', 'VND-001'], 'gudang' => 'GDG-CKR', 'kirim' => 8, 'berhenti' => 'DiterimaSebagian', 'sebagian' => [1, 2]],
        ['kunci' => 'PP26', 'hari' => 31, 'unit' => 'IT', 'pos' => 'TI-HW', 'prioritas' => 'Normal',
            'alasan' => 'Cadangan hard disk untuk NAS backup dan server file (kapasitas terpakai 87%).',
            'item' => [['SukuCadang', 'SC-HDD-001', 'Hard disk SAS 2,4 TB 10K', 6, 'pcs', 6850000]],
            'penyedia' => ['VND-006'], 'gudang' => 'GDG-JKT', 'kirim' => 10, 'berhenti' => 'Dikirim'],
        ['kunci' => 'PP27', 'hari' => 14, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Normal',
            'alasan' => 'Stok bearing, v-belt, dan grease untuk PM bulanan mendekati titik pesan ulang.',
            'item' => [['SukuCadang', 'SC-BRG-001', 'Bearing SKF 6205-2RS', 20, 'pcs', 95000], ['SukuCadang', 'SC-BLT-001', 'V-belt B-68', 10, 'pcs', 145000], ['SukuCadang', 'SC-GRS-001', 'Grease EP2 (kaleng 16 kg)', 4, 'kaleng', 1450000]],
            'penyedia' => ['VND-005', 'VND-009'], 'gudang' => 'GDG-CKR', 'berhenti' => 'PoMenunggu'],
        ['kunci' => 'PP28', 'hari' => 16, 'unit' => 'TEKFAS', 'pos' => 'PMH-UTL', 'prioritas' => 'Tinggi',
            'alasan' => 'Servis besar genset 1000 kVA 6.000 jam: penggantian injector, pemeriksaan turbo, dan uji beban.',
            'item' => [['Jasa', null, 'Jasa servis besar 6.000 jam genset Cummins 1000 kVA termasuk load bank test', 1, 'paket', 42000000]],
            'penyedia' => ['VND-001', 'VND-002'], 'batas' => 21, 'berhenti' => 'RfqSebagian'],
        ['kunci' => 'PP29', 'hari' => 9, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Normal',
            'alasan' => 'Penggantian tali baja lift barang pabrik yang mulai getas (temuan inspeksi SILO).',
            'item' => [['SukuCadang', 'SC-TLI-001', 'Tali baja lift 10 mm', 80, 'meter', 125000]],
            'penyedia' => ['VND-004', 'VND-005'], 'gudang' => 'GDG-CKR', 'batas' => 10, 'berhenti' => 'RfqMenunggu'],
        ['kunci' => 'PP30', 'hari' => 5, 'unit' => 'TEKFAS', 'pos' => 'MODAL', 'prioritas' => 'Tinggi', 'rencana' => 'R3',
            'alasan' => 'Crusher plastik 30 HP pengganti sesuai usulan aset yang disetujui; unit lama sering macet dan bising.',
            'item' => [['Aset', 'PRD-CRS-01', 'Mesin crusher plastik 30 HP', 1, 'unit', 185000000]],
            'penyedia' => ['VND-009'], 'berhenti' => 'MenungguPenyetuju'],
        ['kunci' => 'PP31', 'hari' => 4, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Normal',
            'alasan' => 'Mechanical seal dan bearing untuk pompa hydrant jockey; stok seal tinggal satu.',
            'item' => [['SukuCadang', 'SC-SEL-001', 'Mechanical seal pompa CR32', 3, 'pcs', 2350000], ['SukuCadang', 'SC-BRG-002', 'Bearing SKF 6310-2Z', 6, 'pcs', 385000]],
            'penyedia' => ['VND-005'], 'gudang' => 'GDG-CKR', 'berhenti' => 'MenungguPenyetuju'],
        ['kunci' => 'PP32', 'hari' => 3, 'unit' => 'IT', 'pos' => 'TI-HW', 'prioritas' => 'Normal',
            'alasan' => 'UPS rak jaringan pabrik sering alarm baterai; perlu penggantian unit dan tambahan stok toner.',
            'item' => [['Aset', 'GDG-UPS-01', 'UPS APC Smart-UPS 3 kVA rack mount', 2, 'unit', 18500000], ['SukuCadang', 'SC-TNR-001', 'Toner HP 76A', 6, 'pcs', 1450000]],
            'penyedia' => ['VND-006'], 'gudang' => 'GDG-JKT', 'berhenti' => 'MenungguKoordinator'],
        ['kunci' => 'PP33', 'hari' => 1, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Tinggi',
            'alasan' => 'Stok heater band dan thermocouple kembali mendekati titik pesan ulang setelah overhaul mesin injeksi.',
            'item' => [['SukuCadang', 'SC-HTR-001', 'Heater band barrel injeksi 220V 1,2 kW', 8, 'pcs', 425000], ['SukuCadang', 'SC-TC-001', 'Thermocouple tipe J', 10, 'pcs', 165000]],
            'penyedia' => ['VND-009', 'VND-005'], 'gudang' => 'GDG-CKR', 'berhenti' => 'MenungguKoordinator'],
        ['kunci' => 'PP34', 'hari' => 0, 'unit' => 'TEKFAS', 'pos' => 'SC', 'prioritas' => 'Normal',
            'alasan' => 'Lampu LED high bay pengganti untuk area Gudang Bahan Baku.',
            'item' => [['SukuCadang', 'SC-LMP-001', 'Lampu LED high bay 150 W', 10, 'pcs', 1350000]],
            'penyedia' => ['VND-005'], 'gudang' => 'GDG-CKR', 'berhenti' => 'Draft'],
    ];

    /**
     * Transaksi anggaran di luar PO (pembayaran langsung/kontrak berjalan):
     * hari lalu, pos, jenis, jumlah, keterangan.
     *
     * @var list<array{0: int, 1: string, 2: string, 3: int, 4: string}>
     */
    private const TRANSAKSI_ANGGARAN = [
        [330, 'TI-LYN', 'Realisasi', 42000000, 'Langganan Microsoft 365 Business Standard 60 pengguna (Oktober–Desember).'],
        [254, 'TI-LYN', 'Realisasi', 168000000, 'Langganan Microsoft 365 Business Standard 60 pengguna (Januari–Desember).'],
        [150, 'PMH-K3', 'Realisasi', 38500000, 'Kalibrasi eksternal alat ukur laboratorium QC oleh PT Kalibrasi Presisi Indonesia.'],
        [95, 'PMH-UTL', 'Realisasi', 7850000, 'Perbaikan darurat modul ATS genset (pembelian langsung kas kecil pabrik).'],
        [69, 'PMH-GDG', 'Realisasi', 12500000, 'Pengurasan dan disinfeksi tangki air bersih Menara Sinar.'],
        [27, 'PMH-GDG', 'Komitmen', 24000000, 'SPK pengecatan ulang marka jalur forklift Gudang Bahan Baku (dikerjakan Oktober).'],
    ];

    /**
     * Mutasi stok di luar perintah kerja: hari lalu, jam, jenis, gudang asal, gudang tujuan,
     * catatan, baris [kode suku cadang, jumlah], status akhir.
     *
     * @var list<array{0: int, 1: int, 2: string, 3: string|null, 4: string|null, 5: string, 6: list<array{0: string, 1: int}>, 7: string}>
     */
    private const MUTASI_STOK = [
        [321, 10, 'Transfer', 'GDG-CKR', 'GDG-SBY', 'Pengiriman stok pelumas dan komponen mekanikal untuk perawatan forklift dan truk di gudang Surabaya.',
            [['SC-OLI-002', 3], ['SC-GRS-001', 1], ['SC-BRG-001', 6], ['SC-BLT-001', 4]], 'Diposting'],
        [301, 14, 'Pengeluaran', 'GDG-CKR', null, 'Pengeluaran lampu LED untuk proyek penggantian penerangan Gudang Bahan Baku (dikerjakan tim HRGA, tanpa perintah kerja).',
            [['SC-LMP-001', 6]], 'Diposting'],
        [273, 15, 'Adjustment', 'GDG-CKR', null, 'Stok opname akhir tahun: selisih hitung fisik bearing dan thermocouple, v-belt lebih satu (berita acara opname Desember).',
            [['SC-BRG-001', -2], ['SC-TC-001', -1], ['SC-BLT-001', 1]], 'Diposting'],
        [272, 10, 'Adjustment', 'GDG-JKT', null, 'Stok opname akhir tahun Gudang Menara Sinar: filter AC lebih 2 pcs, toner rusak 1 pcs dimusnahkan.',
            [['SC-FLT-005', 2], ['SC-TNR-001', -1]], 'Diposting'],
        [241, 9, 'Pengeluaran', 'GDG-CKR', null, 'Pemakaian rutin ribbon printer label untuk penandaan palet bahan baku bulan Januari.',
            [['SC-RBN-001', 8]], 'Diposting'],
        [208, 10, 'Transfer', 'GDG-CKR', 'GDG-SBY', 'Replenishment stok gudang Surabaya: oli hidrolik, filter oli, dan grease untuk truk box.',
            [['SC-OLI-002', 3], ['SC-FLT-001', 2], ['SC-GRS-001', 1]], 'Diposting'],
        [189, 13, 'Pengeluaran', 'GDG-CKR', null, 'Grease untuk pelumasan conveyor lini blow molding oleh operator produksi (perawatan mandiri).',
            [['SC-GRS-001', 1]], 'Diposting'],
        [181, 15, 'Adjustment', 'GDG-CKR', null, 'Stok opname kuartal I: selisih v-belt dan MCB setelah pencocokan kartu stok.',
            [['SC-BLT-001', -1], ['SC-MCB-001', -1]], 'Diposting'],
        [152, 10, 'Pengeluaran', 'GDG-CKR', null, 'Penggantian tabung APAR yang dipakai simulasi tanggap darurat K3 tahunan.',
            [['SC-APR-001', 2]], 'Diposting'],
        [139, 14, 'Return', null, 'GDG-CKR', 'Pengembalian sisa bearing dan v-belt dari pekerjaan kontraktor overhaul conveyor yang selesai lebih awal.',
            [['SC-BRG-001', 2], ['SC-BLT-001', 1]], 'Diposting'],
        [121, 11, 'Pengeluaran', 'GDG-JKT', null, 'Permintaan toner printer lantai 12 (bagian Keuangan) untuk tutup buku semester.',
            [['SC-TNR-001', 2]], 'Diposting'],
        [94, 9, 'Transfer', 'GDG-CKR', 'GDG-SBY', 'Pengiriman lampu LED dan bearing untuk perbaikan penerangan dan roller dock gudang Surabaya.',
            [['SC-LMP-001', 4], ['SC-BRG-001', 4]], 'Diposting'],
        [89, 15, 'Adjustment', 'GDG-CKR', null, 'Stok opname kuartal II: oli hidrolik lebih 1 pail (salah catat retur), heater band rusak 1 pcs.',
            [['SC-OLI-002', 1], ['SC-HTR-001', -1]], 'Diposting'],
        [88, 14, 'Adjustment', 'GDG-SBY', null, 'Stok opname kuartal II gudang Surabaya: satu ban forklift rusak di rak.',
            [['SC-BAN-001', -1]], 'Diposting'],
        [61, 10, 'Pengeluaran', 'GDG-CKR', null, 'Pemakaian ribbon printer label untuk pelabelan ulang stok gudang bahan baku.',
            [['SC-RBN-001', 10]], 'Diposting'],
        [46, 9, 'Transfer', 'GDG-CKR', 'GDG-SBY', 'Transfer v-belt ke Surabaya dibatalkan: kebutuhan dipenuhi langsung dari PO ke gudang Surabaya.',
            [['SC-BLT-001', 4]], 'Dibatalkan'],
        [34, 13, 'Pengeluaran', 'GDG-JKT', null, 'Penggantian filter AC ruang rapat dan ruang direksi oleh vendor kebersihan gedung.',
            [['SC-FLT-005', 6]], 'Diposting'],
        [26, 10, 'Transfer', 'GDG-CKR', 'GDG-SBY', 'Replenishment grease dan oli hidrolik gudang Surabaya.',
            [['SC-GRS-001', 1], ['SC-OLI-002', 2]], 'Diposting'],
        [11, 11, 'Pengeluaran', 'GDG-SBY', null, 'Penggantian lampu LED area loading dock gudang Surabaya oleh teknisi kontraktor.',
            [['SC-LMP-001', 2]], 'Diposting'],
        [0, 7, 'Transfer', 'GDG-CKR', 'GDG-SBY', 'Draft transfer filter oli dan oli hidrolik untuk servis truk box Surabaya, menunggu jadwal ekspedisi.',
            [['SC-FLT-001', 2], ['SC-OLI-002', 2]], 'Draft'],
    ];

    /** @var list<string> */
    private const CATATAN_SETUJU = [
        'Sesuai kebutuhan dan masih dalam pagu pos anggaran.',
        'Disetujui, prioritaskan penyedia dengan garansi terpanjang.',
        'Oke, pastikan barang diperiksa gudang sebelum berita acara.',
        'Disetujui. Mohon negosiasi harga minimal 5%.',
        null,
        'Setuju, sudah dicek dengan data pemakaian tiga bulan terakhir.',
    ];

    /** @var list<array{0: CarbonImmutable, 1: int, 2: string, 3: Closure(): void}> */
    private array $jadwal = [];

    /** @var array<int, string> Id anggaran per tahun. */
    private array $anggaran = [];

    /** @var array<int, array<string, string>> Id pos anggaran per tahun dan kode. */
    private array $posAnggaran = [];

    /** @var array<string, string> Id dokumen hasil langkah sebelumnya (usulan, rencana, PP, RFQ, penawaran, PO, tagihan). */
    private array $dokumen = [];

    /** @var array<string, int> Penghitung nomor buatan seeder (GRN, pembayaran, penawaran, tagihan, seri). */
    private array $penghitung = [];

    /** @var array<string, true>|null */
    private ?array $hariLiburCache = null;

    public function run(): void
    {
        $this->masukKonteks();

        if ($this->sudahDisemai('Anggaran')) {
            return;
        }

        $this->rencanakanAlurPersetujuan();
        $this->rencanakanAnggaran();
        $this->rencanakanTransaksiAnggaran();

        foreach (self::USULAN_ASET as $urutan => $usulan) {
            $this->rencanakanUsulanAset($urutan, $usulan);
        }

        foreach (self::RENCANA_PENGADAAN as $rencana) {
            $this->rencanakanRencanaPengadaan($rencana);
        }

        foreach (self::PERMINTAAN_PEMBELIAN as $urutan => $permintaan) {
            $this->rencanakanPermintaanPembelian($urutan, $permintaan);
        }

        foreach (self::MUTASI_STOK as $urutan => $mutasi) {
            $this->rencanakanMutasiStok($urutan, $mutasi);
        }

        $this->jalankanJadwal();
    }

    // ------------------------------------------------------------------
    // Penjadwalan
    // ------------------------------------------------------------------

    /**
     * Mendaftarkan satu langkah pada waktunya. Langkah setelah hari ini tidak didaftarkan
     * (mengembalikan false) supaya rantai dokumen berhenti alami di titik "sedang berjalan";
     * langkah hari ini yang jamnya belum tiba dimajukan ke saat ini.
     *
     * @param  Closure(): void  $langkah
     */
    private function jadwalkan(CarbonImmutable $waktu, string $email, Closure $langkah): bool
    {
        $sekarang = CarbonImmutable::now();

        if ($waktu->greaterThan($sekarang)) {
            if ($waktu->setTimezone(self::ZONA)->toDateString() !== $sekarang->setTimezone(self::ZONA)->toDateString()) {
                return false;
            }

            $waktu = $sekarang->subMinute();
        }

        $this->jadwal[] = [$waktu, count($this->jadwal), $email, $langkah];

        return true;
    }

    private function jalankanJadwal(): void
    {
        usort($this->jadwal, fn (array $a, array $b): int => [$a[0]->getTimestamp(), $a[1]] <=> [$b[0]->getTimestamp(), $b[1]]);

        foreach ($this->jadwal as [$waktu, , $email, $langkah]) {
            $this->padaWaktu($waktu, $langkah, $email);
        }

        $this->command?->info(sprintf('%s: %d langkah dijalankan.', self::class, count($this->jadwal)));
    }

    /** Awal rantai dokumen: `$hariLalu` hari ke belakang, digeser maju bila jatuh di hari Minggu atau libur. */
    private function awalRantai(int $hariLalu, int $jam, int $menit): CarbonImmutable
    {
        $lokal = $this->hariLalu($hariLalu, $jam, $menit)->setTimezone(self::ZONA);

        while ($this->bukanHariKerja($lokal)) {
            $lokal = $lokal->addDay();
        }

        return $lokal->utc();
    }

    /**
     * Waktu langkah berikutnya: `$selangHari` hari setelah langkah sebelumnya pada jam kerja
     * tertentu (WIB), melompati Minggu dan hari libur, dan selalu sesudah langkah sebelumnya.
     */
    private function langkahBerikutnya(CarbonImmutable $sebelumnya, int $selangHari, int $jam, int $menit = 0): CarbonImmutable
    {
        $lokal = $sebelumnya->setTimezone(self::ZONA)->startOfDay()->addDays($selangHari)->setTime($jam, $menit);

        while ($this->bukanHariKerja($lokal)) {
            $lokal = $lokal->addDay();
        }

        $waktu = $lokal->utc();

        return $waktu->greaterThan($sebelumnya) ? $waktu : $sebelumnya->addMinutes(35);
    }

    private function bukanHariKerja(CarbonImmutable $lokal): bool
    {
        if ($lokal->isSunday()) {
            return true;
        }

        if ($this->hariLiburCache === null) {
            $this->hariLiburCache = [];
            foreach (DB::table('HariLibur')->where('OrganisasiId', $this->organisasiId())->get(['Tanggal', 'BerulangTahunan']) as $libur) {
                $tanggal = substr((string) $libur->Tanggal, 0, 10);
                $this->hariLiburCache[$tanggal] = true;
                if ((bool) $libur->BerulangTahunan) {
                    $this->hariLiburCache['*-'.substr($tanggal, 5)] = true;
                }
            }
        }

        return isset($this->hariLiburCache[$lokal->toDateString()]) || isset($this->hariLiburCache['*-'.$lokal->format('m-d')]);
    }

    private function tahunSekarang(): int
    {
        return CarbonImmutable::now()->setTimezone(self::ZONA)->year;
    }

    private function tanggalHariIni(int $tambahHari = 0): string
    {
        return CarbonImmutable::now()->setTimezone(self::ZONA)->addDays($tambahHari)->toDateString();
    }

    private function nomorBerikutnya(string $seri): int
    {
        return $this->penghitung[$seri] = ($this->penghitung[$seri] ?? 0) + 1;
    }

    private function modelPengguna(string $email): Pengguna
    {
        return Pengguna::query()->findOrFail($this->pengguna($email));
    }

    // ------------------------------------------------------------------
    // Alur persetujuan
    // ------------------------------------------------------------------

    /**
     * Admin menyusun alur persetujuan untuk setiap dokumen yang didukung mesin persetujuan
     * sebelum riwayat transaksi dimulai. Jenis dokumen yang sudah punya alur (mis. dibuat
     * seeder lain lebih dulu) dilewati supaya tidak ada dua alur aktif untuk satu jenis.
     */
    private function rencanakanAlurPersetujuan(): void
    {
        $this->jadwalkan($this->awalRantai(359, 8, 0), self::ADMIN, function (): void {
            foreach (self::ALUR_PERSETUJUAN as [$kode, $nama, $jenisEntitas, $daftarTahap]) {
                $sudahAda = DB::table('AlurPersetujuan')
                    ->where('OrganisasiId', $this->organisasiId())
                    ->where('JenisEntitas', $jenisEntitas)
                    ->exists();

                if ($sudahAda) {
                    continue;
                }

                $alur = app(BuatAlurPersetujuan::class)->jalankan([
                    'Kode' => $kode,
                    'Nama' => $nama,
                    'JenisEntitas' => $jenisEntitas,
                ]);

                foreach ($daftarTahap as $urutan => $tahap) {
                    [$namaTahap, $jenisPenyetuju, $penyetuju, $batasMenit] = $tahap;
                    $ambangNilai = $tahap[4] ?? null;

                    app(BuatTahapPersetujuan::class)->jalankan($alur, [
                        'Urutan' => $urutan + 1,
                        'Nama' => $namaTahap,
                        'JenisPenyetuju' => $jenisPenyetuju,
                        'PeranId' => $jenisPenyetuju === 'Pengguna' ? null : $this->idDari('Peran', ['Kode' => $penyetuju]),
                        'PenggunaId' => $jenisPenyetuju === 'Pengguna' ? $this->pengguna($penyetuju) : null,
                        'JumlahMinimumPenyetuju' => 1,
                        'BolehMenyetujuiSendiri' => false,
                        'BatasWaktuMenit' => $batasMenit,
                        'Kondisi' => $ambangNilai === null ? null : ['NilaiMinimum' => $ambangNilai],
                    ]);
                }

                app(AktifkanAlurPersetujuan::class)->jalankan($alur->refresh());
            }
        });
    }

    private function setujui(string $jenisEntitas, string $entitasId, string $email, ?string $catatan): void
    {
        app(SetujuiPermintaanPersetujuan::class)->jalankan(
            $this->permintaanMenunggu($jenisEntitas, $entitasId),
            $this->modelPengguna($email),
            $catatan,
        );
    }

    private function tolak(string $jenisEntitas, string $entitasId, string $email, string $catatan): void
    {
        app(TolakPermintaanPersetujuan::class)->jalankan(
            $this->permintaanMenunggu($jenisEntitas, $entitasId),
            $this->modelPengguna($email),
            $catatan,
        );
    }

    private function masihMenunggu(string $jenisEntitas, string $entitasId): bool
    {
        return PermintaanPersetujuan::query()
            ->where('JenisEntitas', $jenisEntitas)
            ->where('EntitasId', $entitasId)
            ->where('Status', 'Menunggu')
            ->exists();
    }

    private function permintaanMenunggu(string $jenisEntitas, string $entitasId): PermintaanPersetujuan
    {
        return PermintaanPersetujuan::query()
            ->where('JenisEntitas', $jenisEntitas)
            ->where('EntitasId', $entitasId)
            ->where('Status', 'Menunggu')
            ->firstOrFail();
    }

    // ------------------------------------------------------------------
    // Anggaran
    // ------------------------------------------------------------------

    /**
     * Anggaran setiap tahun kalender yang tercakup riwayat (disusun awal Desember tahun
     * sebelumnya, atau saat Amanpoll mulai dipakai), plus usulan anggaran tahun depan yang
     * masih menunggu persetujuan direksi bila sudah memasuki musim penyusunan anggaran.
     */
    private function rencanakanAnggaran(): void
    {
        $hariIni = CarbonImmutable::now(self::ZONA)->startOfDay();
        $tahunIni = $hariIni->year;
        $tahunAwal = $hariIni->subDays(358)->year;

        for ($tahun = $tahunAwal; $tahun <= $tahunIni; $tahun++) {
            $disusun = CarbonImmutable::create($tahun - 1, 12, 8, 0, 0, 0, self::ZONA);
            $hariLalu = min(358, (int) round($disusun->diffInDays($hariIni, true)));
            $this->rencanakanSatuAnggaran($tahun, $tahun === $tahunIni ? 1 : 0, $hariLalu, true);
        }

        if ($hariIni->month >= 9) {
            $this->rencanakanSatuAnggaran($tahunIni + 1, 2, 12, false);
        }
    }

    private function rencanakanSatuAnggaran(int $tahun, int $kolomNilai, int $hariLalu, bool $disetujui): void
    {
        $waktu = $this->awalRantai($hariLalu, 9, 0);

        $this->jadwalkan($waktu, self::PENGADAAN, function () use ($tahun, $kolomNilai): void {
            $anggaran = app(KelolaAnggaran::class)->buat([
                'UnitOrganisasiId' => null,
                'Kode' => "ANG-{$tahun}",
                'Nama' => "Anggaran Pemeliharaan & Investasi Aset {$tahun}",
                'Tahun' => $tahun,
                'MataUang' => 'IDR',
                'Jumlah' => self::PAGU_ANGGARAN[$kolomNilai] * 1000000,
            ]);
            $this->anggaran[$tahun] = $anggaran->Id;
        });

        $waktu = $this->langkahBerikutnya($waktu, 0, 10, 30);
        $this->jadwalkan($waktu, self::PENGADAAN, function () use ($tahun, $kolomNilai): void {
            $anggaran = Anggaran::query()->findOrFail($this->anggaran[$tahun]);

            foreach (self::POS_ANGGARAN as [$kode, $nama, $induk, $nilai]) {
                $pos = app(KelolaPosAnggaran::class)->buat($anggaran, [
                    'IndukId' => $induk === null ? null : $this->posAnggaran[$tahun][$induk],
                    'Kode' => $kode,
                    'Nama' => $nama,
                    'Jumlah' => $nilai[$kolomNilai] * 1000000,
                ]);
                $this->posAnggaran[$tahun][$kode] = $pos->Id;
            }
        });

        $waktu = $this->langkahBerikutnya($waktu, 1, 9, 30);
        $this->jadwalkan($waktu, self::PENGADAAN, function () use ($tahun): void {
            app(KelolaAnggaran::class)->ajukan(Anggaran::query()->findOrFail($this->anggaran[$tahun]), $this->pengguna(self::PENGADAAN));
        });

        if (! $disetujui) {
            return;
        }

        $waktu = $this->langkahBerikutnya($waktu, 2, 10, 0);
        $this->jadwalkan($waktu, self::PENYETUJU, function () use ($tahun): void {
            $this->setujui('Anggaran', $this->anggaran[$tahun], self::PENYETUJU, "Anggaran {$tahun} disetujui sesuai rapat direksi.");
        });
    }

    private function posTahunIni(string $kode): string
    {
        $tahun = $this->tahunSekarang();

        return $this->posAnggaran[$tahun][$kode] ?? throw new RuntimeException("Pos anggaran {$kode} tahun {$tahun} belum ada.");
    }

    private function rencanakanTransaksiAnggaran(): void
    {
        foreach (self::TRANSAKSI_ANGGARAN as $urutan => [$hariLalu, $kodePos, $jenis, $jumlah, $keterangan]) {
            $this->jadwalkan($this->awalRantai($hariLalu, 14, 10 + $urutan * 5), self::PENGADAAN, function () use ($kodePos, $jenis, $jumlah, $keterangan): void {
                app(CatatTransaksiAnggaran::class)->jalankan(PosAnggaran::query()->findOrFail($this->posTahunIni($kodePos)), [
                    'Jenis' => $jenis,
                    'Jumlah' => $jumlah,
                    'Tanggal' => $this->tanggalHariIni(),
                    'Keterangan' => $keterangan,
                ]);
            });
        }
    }

    // ------------------------------------------------------------------
    // Usulan aset & rencana pengadaan
    // ------------------------------------------------------------------

    /** @param array{0: string, 1: int, 2: string, 3: string, 4: string, 5: string|null, 6: int, 7: int, 8: string, 9: bool, 10: string, 11: array{0: int, 1: int, 2: int, 3: int}, 12: string, 13: string} $usulan */
    private function rencanakanUsulanAset(int $urutan, array $usulan): void
    {
        [$kunci, $hariLalu, $unit, $kebutuhan, $kategori, $model, $jumlah, $harga, $jenis, $tahunDepan, $prioritas, $nilai, $akhir, $alasan] = $usulan;

        $waktu = $this->awalRantai($hariLalu, 9, 10 + $urutan * 4);
        $this->jadwalkan($waktu, self::PENGADAAN, function () use ($kunci, $unit, $kebutuhan, $kategori, $model, $jumlah, $harga, $jenis, $tahunDepan, $alasan): void {
            $this->dokumen[$kunci] = app(KelolaUsulanAset::class)->buat([
                'UnitOrganisasiId' => $this->idDari('UnitOrganisasi', ['Kode' => $unit]),
                'KategoriAsetId' => $this->idDari('KategoriAset', ['Kode' => $kategori]),
                'ModelAsetId' => $model === null ? null : $this->idDari('ModelAset', ['KodeModel' => $model]),
                'NamaKebutuhan' => $kebutuhan,
                'Jumlah' => $jumlah,
                'EstimasiHargaSatuan' => $harga,
                'Alasan' => $alasan,
                'JenisKebutuhan' => $jenis,
                'TahunKebutuhan' => $this->tahunSekarang() + ($tahunDepan ? 1 : 0),
                'Prioritas' => 'Normal',
            ], $this->pengguna(self::PENGADAAN))->Id;
        });

        if ($akhir === 'Draft') {
            return;
        }

        $waktu = $this->langkahBerikutnya($waktu, 0, 10, 45);
        if (! $this->jadwalkan($waktu, self::PENGADAAN, fn () => app(KelolaUsulanAset::class)->submit(UsulanAset::query()->findOrFail($this->dokumen[$kunci])))) {
            return;
        }

        $waktu = $this->langkahBerikutnya($waktu, 1, 13, 0);
        $jumlahKriteria = $akhir === 'Dinilai' ? 2 : 4;
        if (! $this->jadwalkan($waktu, self::PENGADAAN, function () use ($kunci, $nilai, $jumlahKriteria, $prioritas): void {
            $model = UsulanAset::query()->findOrFail($this->dokumen[$kunci]);
            for ($i = 0; $i < $jumlahKriteria; $i++) {
                app(KelolaUsulanAset::class)->nilai($model, [
                    'Kriteria' => self::KRITERIA_PENILAIAN[$i],
                    'Bobot' => self::BOBOT_KRITERIA[$i],
                    'Nilai' => $nilai[$i],
                    'Prioritas' => $i === $jumlahKriteria - 1 ? $prioritas : null,
                ], $this->pengguna(self::PENGADAAN));
            }
        })) {
            return;
        }

        if ($akhir === 'Dinilai') {
            return;
        }

        $waktu = $this->langkahBerikutnya($waktu, 0, 15, 0);
        if (! $this->jadwalkan($waktu, self::PENGADAAN, fn () => app(KelolaUsulanAset::class)->ajukanPersetujuan(UsulanAset::query()->findOrFail($this->dokumen[$kunci]), $this->pengguna(self::PENGADAAN)))) {
            return;
        }

        if ($akhir === 'MenungguManajer') {
            return;
        }

        $waktu = $this->langkahBerikutnya($waktu, 2, 10, 0);
        if (! $this->jadwalkan($waktu, self::MANAJER_ASET, fn () => $this->setujui('UsulanAset', $this->dokumen[$kunci], self::MANAJER_ASET, 'Kajian teknis sesuai; kebutuhan tercatat di rencana siklus hidup aset.'))) {
            return;
        }

        if ($akhir === 'MenungguPenyetuju') {
            return;
        }

        $waktu = $this->langkahBerikutnya($waktu, 1, 11, 0);
        $this->jadwalkan($waktu, self::PENYETUJU, $akhir === 'Ditolak'
            ? fn () => $this->tolak('UsulanAset', $this->dokumen[$kunci], self::PENYETUJU, 'Bukan prioritas aset produksi; opsi sewa kendaraan lebih efisien. Usulan tidak dilanjutkan tahun ini.')
            : fn () => $this->setujui('UsulanAset', $this->dokumen[$kunci], self::PENYETUJU, 'Disetujui untuk masuk rencana pengadaan.'));
    }

    /** @param array{0: string, 1: int, 2: string, 3: int, 4: string|null, 5: list<string>, 6: list<array{0: string, 1: string|null, 2: int, 3: string, 4: int, 5: int}>, 7: bool} $rencana */
    private function rencanakanRencanaPengadaan(array $rencana): void
    {
        [$kunci, $hariLalu, $nama, $geserTahun, $kodePos, $usulan, $barisTambahan, $difinalisasi] = $rencana;

        $waktu = $this->awalRantai($hariLalu, 10, 0);
        $this->jadwalkan($waktu, self::PENGADAAN, function () use ($kunci, $nama, $geserTahun, $kodePos, $usulan): void {
            // Rencana yang disusun akhir tahun berlaku untuk tahun berikutnya.
            $tahun = CarbonImmutable::now()->setTimezone(self::ZONA)->addDays(15)->year + $geserTahun;

            $this->dokumen[$kunci] = app(KelolaRencanaPengadaan::class)->buat([
                'Nama' => str_replace('{tahun}', (string) $tahun, $nama),
                'Tahun' => $tahun,
                'PosAnggaranId' => $kodePos === null ? null : $this->posAnggaran[$tahun][$kodePos],
                'UsulanAsetIds' => array_map(fn (string $kunciUsulan): string => $this->dokumen[$kunciUsulan], $usulan),
            ], $this->pengguna(self::PENGADAAN))->Id;
        });

        if ($barisTambahan !== []) {
            $waktu = $this->langkahBerikutnya($waktu, 0, 11, 0);
            $this->jadwalkan($waktu, self::PENGADAAN, function () use ($kunci, $barisTambahan): void {
                $model = RencanaPengadaan::query()->findOrFail($this->dokumen[$kunci]);
                foreach ($barisTambahan as [$deskripsi, $kodeSukuCadang, $jumlah, $satuan, $harga, $bulan]) {
                    app(KelolaRencanaPengadaan::class)->tambahDetail($model, [
                        'SukuCadangId' => $kodeSukuCadang === null ? null : $this->idDari('SukuCadang', ['Kode' => $kodeSukuCadang]),
                        'Deskripsi' => $deskripsi,
                        'Jumlah' => $jumlah,
                        'Satuan' => $satuan,
                        'HargaEstimasi' => $harga,
                        'BulanRencana' => $bulan,
                    ]);
                }
            });
        }

        if ($difinalisasi) {
            $waktu = $this->langkahBerikutnya($waktu, 2, 9, 0);
            $this->jadwalkan($waktu, self::PENGADAAN, fn () => app(KelolaRencanaPengadaan::class)->finalisasi(RencanaPengadaan::query()->findOrFail($this->dokumen[$kunci])));
        }
    }

    // ------------------------------------------------------------------
    // Siklus permintaan pembelian
    // ------------------------------------------------------------------

    /**
     * PP → verifikasi koordinator → persetujuan direksi → RFQ → penawaran → PO → persetujuan PO
     * → kirim (komitmen anggaran) → penerimaan (stok/aset) → tagihan → pembayaran (realisasi).
     *
     * @param  array{kunci: string, hari: int, unit: string, pos: string, prioritas: string, alasan: string, item: list<array{0: string, 1: string|null, 2: string, 3: int, 4: string, 5: int}>, penyedia: list<string>, gudang?: string, kirim?: int, tempo?: int, batas?: int, rencana?: string, berhenti?: string, sebagian?: list<int>, tolak?: string}  $spek
     */
    private function rencanakanPermintaanPembelian(int $urutan, array $spek): void
    {
        $kunci = $spek['kunci'];
        $berhenti = $spek['berhenti'] ?? null;
        $catatan = self::CATATAN_SETUJU[$urutan % count(self::CATATAN_SETUJU)];
        $waktu = $this->awalRantai($spek['hari'], 8 + $urutan % 2, 5 + ($urutan * 7) % 50);

        $langkah = function (int $selangHari, int $jam, int $menit, string $email, Closure $kerja) use (&$waktu): bool {
            $waktu = $this->langkahBerikutnya($waktu, $selangHari, $jam, $menit);

            return $this->jadwalkan($waktu, $email, $kerja);
        };

        if (! $this->jadwalkan($waktu, self::PENGADAAN, fn () => $this->buatPermintaanPembelian($spek))) {
            return;
        }

        if ($berhenti === 'Draft') {
            return;
        }

        if (! $langkah(0, 11, 0, self::PENGADAAN, fn () => app(KelolaPermintaanPembelian::class)->submit(PermintaanPembelian::query()->findOrFail($this->dokumen[$kunci]), $this->pengguna(self::PENGADAAN)))) {
            return;
        }

        if ($berhenti === 'MenungguKoordinator') {
            return;
        }

        $koordinator = self::KOORDINATOR_UNIT[$spek['unit']];
        if (! $langkah(1, 9, 15, $koordinator, fn () => $this->setujui('PermintaanPembelian', $this->dokumen[$kunci], $koordinator, 'Kebutuhan valid, sesuai catatan pemakaian dan jadwal pemeliharaan.'))) {
            return;
        }

        if ($berhenti === 'MenungguPenyetuju') {
            return;
        }

        // Langkah direktur dijadwalkan untuk setiap PP, tetapi PP di bawah ambang tahapnya
        // (Rp 25 juta) sudah selesai di koordinator saat langkah ini tiba, jadi dilewati.
        if ($berhenti === 'Ditolak') {
            $langkah(1, 14, 0, self::PENYETUJU, function () use ($kunci, $spek): void {
                if ($this->masihMenunggu('PermintaanPembelian', $this->dokumen[$kunci])) {
                    $this->tolak('PermintaanPembelian', $this->dokumen[$kunci], self::PENYETUJU, $spek['tolak'] ?? 'Ditolak.');
                }
            });

            return;
        }

        if (! $langkah(1, 14, 0, self::PENYETUJU, function () use ($kunci, $catatan): void {
            if ($this->masihMenunggu('PermintaanPembelian', $this->dokumen[$kunci])) {
                $this->setujui('PermintaanPembelian', $this->dokumen[$kunci], self::PENYETUJU, $catatan);
            }
        })) {
            return;
        }

        $batasHari = $spek['batas'] ?? 7;
        if (! $langkah(1, 9, 30, self::PENGADAAN, fn () => $this->bukaPermintaanPenawaran($kunci, $spek['penyedia'], $batasHari))) {
            return;
        }

        if ($berhenti === 'RfqMenunggu') {
            return;
        }

        $penyedia = $berhenti === 'RfqSebagian' ? [$spek['penyedia'][0]] : $spek['penyedia'];
        foreach ($penyedia as $indeks => $kodePenyedia) {
            if (! $langkah($indeks === 0 ? 2 : 1, 10 + $indeks, 20, self::PENGADAAN, fn () => $this->catatPenawaran($kunci, $urutan, $indeks, $kodePenyedia, $spek))) {
                return;
            }
        }

        if ($berhenti === 'RfqSebagian') {
            return;
        }

        if (! $langkah(1, 15, 0, self::PENGADAAN, fn () => app(KelolaPenawaranPenyedia::class)->pilih(PenawaranPenyedia::query()->findOrFail($this->dokumen["{$kunci}:penawaran:0"])))) {
            return;
        }

        if (! $langkah(1, 9, 0, self::PENGADAAN, fn () => $this->buatDanAjukanPesanan($kunci, $spek))) {
            return;
        }

        if ($berhenti === 'PoMenunggu') {
            return;
        }

        if (! $langkah(1, 11, 0, self::PENYETUJU, fn () => $this->setujui('PesananPembelian', $this->dokumen["{$kunci}:po"], self::PENYETUJU, 'PO sesuai penawaran terpilih.'))) {
            return;
        }

        if (! $langkah(0, 13, 30, self::PENGADAAN, fn () => app(KelolaPesananPembelian::class)->kirim(PesananPembelian::query()->findOrFail($this->dokumen["{$kunci}:po"])))) {
            return;
        }

        if ($berhenti === 'Dikirim') {
            return;
        }

        $sebagian = $berhenti === 'DiterimaSebagian' ? ($spek['sebagian'] ?? [0]) : null;
        if (! $langkah($spek['kirim'] ?? 10, 10, 0, self::PENGADAAN, fn () => $this->terimaBarang($kunci, $spek, $sebagian))) {
            return;
        }

        if ($sebagian !== null || $berhenti === 'Diterima') {
            return;
        }

        $tempo = $spek['tempo'] ?? 30;
        if (! $langkah(3, 14, 0, self::PENGADAAN, fn () => $this->catatTagihan($kunci, $spek, $tempo))) {
            return;
        }

        if ($berhenti === 'Ditagih') {
            return;
        }

        $langkah(max(1, $tempo - 3), 10, 30, self::PENGADAAN, fn () => $this->bayarTagihan($kunci, $berhenti === 'DibayarSebagian'));
    }

    /** @param array{kunci: string, unit: string, pos: string, prioritas: string, alasan: string, item: list<array{0: string, 1: string|null, 2: string, 3: int, 4: string, 5: int}>, rencana?: string} $spek */
    private function buatPermintaanPembelian(array $spek): void
    {
        $detail = [];
        foreach ($spek['item'] as [$jenis, $referensi, $deskripsi, $jumlah, $satuan, $harga]) {
            $detail[] = [
                'JenisItem' => $jenis,
                'SukuCadangId' => $jenis === 'SukuCadang' ? $this->idDari('SukuCadang', ['Kode' => $referensi]) : null,
                'AsetReferensiId' => $jenis === 'Aset' ? $this->idDari('Aset', ['KodeAset' => $referensi]) : null,
                'Deskripsi' => $deskripsi,
                'Jumlah' => $jumlah,
                'Satuan' => $satuan,
                'HargaEstimasi' => $harga,
                'Spesifikasi' => $jenis === 'Aset' ? 'Garansi pabrikan minimal 12 bulan, termasuk instalasi dan commissioning di lokasi.' : null,
            ];
        }

        $this->dokumen[$spek['kunci']] = app(KelolaPermintaanPembelian::class)->buat([
            'UnitOrganisasiId' => $this->idDari('UnitOrganisasi', ['Kode' => $spek['unit']]),
            'RencanaPengadaanId' => isset($spek['rencana']) ? $this->dokumen[$spek['rencana']] : null,
            'PosAnggaranId' => $this->posTahunIni($spek['pos']),
            'TanggalPermintaan' => $this->tanggalHariIni(),
            'TanggalDibutuhkan' => $this->tanggalHariIni(21),
            'Prioritas' => $spek['prioritas'],
            'Alasan' => $spek['alasan'],
            'Detail' => $detail,
        ], $this->pengguna(self::PENGADAAN))->Id;
    }

    /** @param list<string> $penyedia */
    private function bukaPermintaanPenawaran(string $kunci, array $penyedia, int $batasHari): void
    {
        $batas = CarbonImmutable::now()->setTimezone(self::ZONA)->startOfDay()->addDays($batasHari)->setTime(17, 0)->utc();

        $rfq = app(KelolaPermintaanPenawaran::class)->buat(
            PermintaanPembelian::query()->findOrFail($this->dokumen[$kunci]),
            [
                'BatasPenawaran' => $batas,
                'Catatan' => 'Mohon penawaran sudah termasuk PPN 11%, ongkos kirim ke lokasi, dan garansi minimal 3 bulan. Cantumkan waktu pengiriman per item.',
                'PenyediaIds' => array_map(fn (string $kode): string => $this->idDari('Penyedia', ['Kode' => $kode]), $penyedia),
            ],
            $this->pengguna(self::PENGADAAN),
        );

        app(KelolaPermintaanPenawaran::class)->buka($rfq);
        $this->dokumen["{$kunci}:rfq"] = $rfq->Id;
    }

    /**
     * Penyedia pertama selalu pemenang: harganya 0–6% di bawah estimasi; pembanding 5–14% lebih mahal.
     *
     * @param  array{item: list<array{0: string, 1: string|null, 2: string, 3: int, 4: string, 5: int}>, kirim?: int}  $spek
     */
    private function catatPenawaran(string $kunci, int $urutan, int $indeks, string $kodePenyedia, array $spek): void
    {
        $rfq = PermintaanPenawaran::query()->findOrFail($this->dokumen["{$kunci}:rfq"]);
        $detailPermintaan = PermintaanPembelian::query()->findOrFail($this->dokumen[$kunci])->detail()->get()->keyBy('Deskripsi');
        $faktor = (0.94 + ($urutan % 5) * 0.015) * ($indeks === 0 ? 1 : 1.05 + $indeks * 0.045);

        $baris = [];
        foreach ($spek['item'] as [, , $deskripsi, $jumlah, , $harga]) {
            $hargaSatuan = (int) (round($harga * $faktor / 1000) * 1000);
            $baris[] = [
                'DetailPermintaanPembelianId' => $detailPermintaan[$deskripsi]->Id,
                'Jumlah' => $jumlah,
                'HargaSatuan' => $hargaSatuan,
                'Diskon' => 0,
                'Pajak' => (int) round($hargaSatuan * $jumlah * 0.11),
                'WaktuPengirimanHari' => ($spek['kirim'] ?? 10) + $indeks * 3,
            ];
        }

        $penawaran = app(KelolaPenawaranPenyedia::class)->catat($rfq, [
            'PenyediaId' => $this->idDari('Penyedia', ['Kode' => $kodePenyedia]),
            'NomorPenawaran' => sprintf('Q-%s/%s/%03d', substr($kodePenyedia, 4), CarbonImmutable::now()->format('ym'), $this->nomorBerikutnya("penawaran:{$kodePenyedia}")),
            'TanggalPenawaran' => $this->tanggalHariIni(),
            'BerlakuSampai' => $this->tanggalHariIni(30),
            'MataUang' => 'IDR',
            'Catatan' => $indeks === 0 ? 'Harga franco lokasi, garansi 6 bulan.' : 'Harga belum termasuk instalasi; stok sebagian indent.',
            'Detail' => $baris,
        ]);

        $this->dokumen["{$kunci}:penawaran:{$indeks}"] = $penawaran->Id;
    }

    /** @param array{kirim?: int, gudang?: string, berhenti?: string} $spek */
    private function buatDanAjukanPesanan(string $kunci, array $spek): void
    {
        $gudang = isset($spek['gudang'])
            ? DB::table('Gudang')->where('Id', $this->idDari('Gudang', ['Kode' => $spek['gudang']]))->value('Nama')
            : 'lokasi pekerjaan';

        $po = app(KelolaPesananPembelian::class)->buatDariPenawaran(
            PenawaranPenyedia::query()->findOrFail($this->dokumen["{$kunci}:penawaran:0"]),
            [
                'TanggalPesanan' => $this->tanggalHariIni(),
                'TanggalKirimRencana' => $this->tanggalHariIni($spek['kirim'] ?? 10),
                'Catatan' => "Pengiriman ke {$gudang}. Termin pembayaran 30 hari setelah tagihan dan berita acara serah terima diterima.",
            ],
            $this->pengguna(self::PENGADAAN),
        );
        $this->dokumen["{$kunci}:po"] = $po->Id;

        if (($spek['berhenti'] ?? null) !== 'PoDraft') {
            app(KelolaPesananPembelian::class)->ajukan($po, $this->pengguna(self::PENGADAAN));
        }
    }

    /**
     * @param  array{item: list<array{0: string, 1: string|null, 2: string, 3: int, 4: string, 5: int}>, penyedia: list<string>, gudang?: string}  $spek
     * @param  list<int>|null  $hanyaIndeks  Indeks item yang sudah datang (penerimaan sebagian).
     */
    private function terimaBarang(string $kunci, array $spek, ?array $hanyaIndeks): void
    {
        $po = PesananPembelian::query()->findOrFail($this->dokumen["{$kunci}:po"]);
        $detailPo = $po->detail()->get()->keyBy('Deskripsi');
        $kodePenyedia = $spek['penyedia'][0];
        $adaBarang = false;

        $baris = [];
        foreach ($spek['item'] as $indeks => [$jenis, , $deskripsi, $jumlah]) {
            if ($hanyaIndeks !== null && ! in_array($indeks, $hanyaIndeks, true)) {
                continue;
            }

            /** @var DetailPesananPembelian $detail */
            $detail = $detailPo[$deskripsi];
            $nomorSeri = [];
            if ($jenis === 'Aset') {
                for ($unit = 0; $unit < $jumlah; $unit++) {
                    $nomorSeri[] = sprintf('SN%s%s%05d', substr($kodePenyedia, 4), CarbonImmutable::now()->format('ym'), 1000 + $this->nomorBerikutnya('seri'));
                }
            }
            $adaBarang = $adaBarang || $jenis !== 'Jasa';

            $baris[] = [
                'DetailPesananPembelianId' => $detail->Id,
                'JumlahDiterima' => $jumlah,
                'JumlahDitolak' => 0,
                'Kondisi' => 'Baik',
                'NomorSeri' => $nomorSeri,
                'Catatan' => $jenis === 'Jasa' ? 'Pekerjaan selesai, berita acara ditandatangani koordinator teknik.' : null,
            ];
        }

        // Barang diterima dan dicatat Kepala Gudang; penerimaan jasa (berita acara) oleh pengadaan.
        // Nomor GRN mengikuti pola dokumen, sama seperti penerimaan yang dicatat lewat aplikasi.
        $penerima = $adaBarang ? self::GUDANG : self::PENGADAAN;
        Auth::onceUsingId($this->pengguna($penerima));

        app(CatatPenerimaanPembelian::class)->jalankan($po, [
            'GudangId' => isset($spek['gudang']) ? $this->idDari('Gudang', ['Kode' => $spek['gudang']]) : null,
            'TanggalTerima' => CarbonImmutable::now(),
            'NomorSuratJalan' => sprintf('SJ/%s/%s/%04d', substr($kodePenyedia, 4), CarbonImmutable::now()->format('ym'), 200 + $this->nomorBerikutnya("sj:{$kodePenyedia}")),
            'Catatan' => $adaBarang
                ? ($hanyaIndeks !== null ? 'Pengiriman parsial; sisa item masih indent dari pabrikan.' : 'Barang diperiksa bersama Kepala Gudang: jumlah dan kondisi sesuai surat jalan.')
                : 'Penerimaan jasa berdasarkan berita acara penyelesaian pekerjaan.',
            'Detail' => $baris,
        ], $this->pengguna($penerima));
    }

    /** @param array{penyedia: list<string>} $spek */
    private function catatTagihan(string $kunci, array $spek, int $tempo): void
    {
        $po = PesananPembelian::query()->findOrFail($this->dokumen["{$kunci}:po"]);
        $kodePenyedia = $spek['penyedia'][0];

        $tagihan = app(KelolaTagihanPenyedia::class)->buat($po, [
            'NomorTagihan' => sprintf('INV/%s/%s/%04d', substr($kodePenyedia, 4), CarbonImmutable::now()->format('ym'), 100 + $this->nomorBerikutnya("tagihan:{$kodePenyedia}")),
            'TanggalTagihan' => $this->tanggalHariIni(),
            'JatuhTempo' => $this->tanggalHariIni($tempo),
            'Subtotal' => Uang::dariString((string) $po->Subtotal)->kurang(Uang::dariString((string) $po->Diskon))->keString(),
            'Pajak' => (string) $po->Pajak,
        ]);

        $this->dokumen["{$kunci}:tagihan"] = $tagihan->Id;
    }

    private function bayarTagihan(string $kunci, bool $sebagian): void
    {
        $tagihan = TagihanPenyedia::query()->findOrFail($this->dokumen["{$kunci}:tagihan"]);
        $sisa = Uang::dariString((string) $tagihan->Sisa);
        $jumlah = $sebagian ? Uang::dariMinor(intdiv($sisa->nilaiMinor(), 200) * 100)->keString() : $sisa->keString();
        $tahun = $this->tahunSekarang();
        $nomor = $this->nomorBerikutnya("bayar:{$tahun}");

        app(CatatPembayaranPenyedia::class)->jalankan($tagihan, [
            'NomorPembayaran' => sprintf('BYR/%d/%04d', $tahun, $nomor),
            'TanggalBayar' => $this->tanggalHariIni(),
            'Jumlah' => $jumlah,
            'Metode' => 'Transfer Bank',
            'Referensi' => sprintf('BCA KCU Cikarang — TRX%s%05d', CarbonImmutable::now()->format('ymd'), 3100 + $nomor * 13),
        ], $this->pengguna(self::PENGADAAN));
    }

    // ------------------------------------------------------------------
    // Mutasi stok non-perintah kerja
    // ------------------------------------------------------------------

    /** @param array{0: int, 1: int, 2: string, 3: string|null, 4: string|null, 5: string, 6: list<array{0: string, 1: int}>, 7: string} $mutasi */
    private function rencanakanMutasiStok(int $urutan, array $mutasi): void
    {
        [$hariLalu, $jam, $jenis, $asal, $tujuan, $catatan, $baris, $status] = $mutasi;
        $waktu = $this->awalRantai($hariLalu, $jam, 10 + ($urutan * 11) % 45);
        $kunci = "mutasi:{$urutan}";

        $this->jadwalkan($waktu, self::GUDANG, function () use ($kunci, $jenis, $asal, $tujuan, $catatan, $baris, $status): void {
            $gudangAsalId = $asal === null ? null : $this->idDari('Gudang', ['Kode' => $asal]);
            $detail = [];

            foreach ($baris as [$kodeSukuCadang, $jumlah]) {
                $sukuCadangId = $this->idDari('SukuCadang', ['Kode' => $kodeSukuCadang]);
                $jumlah = $status === 'Diposting' ? $this->jumlahAman($jenis, $gudangAsalId, $sukuCadangId, $jumlah) : $jumlah;

                if ($jumlah !== 0) {
                    $detail[] = [$sukuCadangId, $jumlah];
                }
            }

            if ($detail === []) {
                $this->command?->warn("Mutasi stok \"{$catatan}\" dilewati: stok gudang asal tidak mencukupi.");

                return;
            }

            if ($this->penomoranMutasiSudahLewat()) {
                $this->command?->warn("Mutasi stok \"{$catatan}\" dilewati: penomoran MutasiStok sudah berada di periode yang lebih baru.");

                return;
            }

            $mutasiStok = app(BuatMutasiStok::class)->jalankan([
                'Jenis' => $jenis,
                'GudangAsalId' => $gudangAsalId,
                'GudangTujuanId' => $tujuan === null ? null : $this->idDari('Gudang', ['Kode' => $tujuan]),
                'Catatan' => $catatan,
            ], $this->pengguna(self::GUDANG));

            foreach ($detail as [$sukuCadangId, $jumlah]) {
                app(TambahDetailMutasiStok::class)->jalankan($mutasiStok, [
                    'SukuCadangId' => $sukuCadangId,
                    'Jumlah' => $jumlah,
                    'HargaSatuan' => DB::table('SukuCadang')->where('Id', $sukuCadangId)->value('HargaRataRata'),
                ]);
            }

            if ($status === 'Diposting') {
                app(PostingMutasiStok::class)->jalankan($mutasiStok->refresh(), $this->pengguna(self::GUDANG));
            }

            $this->dokumen[$kunci] = $mutasiStok->Id;
        });

        if ($status === 'Dibatalkan') {
            $this->jadwalkan($this->langkahBerikutnya($waktu, 1, 8, 30), self::GUDANG, function () use ($kunci): void {
                $mutasiStok = MutasiStok::query()->findOrFail($this->dokumen[$kunci]);
                app(BatalkanMutasiStok::class)->jalankan($mutasiStok);
            });
        }
    }

    /**
     * LayananNomorDokumen mengulang nomor dari 1 setiap kali periode berganti, juga bila jam
     * dimundurkan ke periode sebelumnya. Bila seeder lain sudah menomori mutasi stok di periode
     * yang lebih baru (mis. DemoPemeliharaanSeeder berjalan lebih dulu), mutasi bertanggal lampau
     * akan bertabrakan dengan nomor yang sudah ada, jadi mutasi itu dilewati.
     */
    private function penomoranMutasiSudahLewat(): bool
    {
        $periodeAktif = DB::table('NomorDokumen')
            ->where('OrganisasiId', $this->organisasiId())
            ->where('JenisDokumen', 'MutasiStok')
            ->value('PeriodeAktif');

        return is_string($periodeAktif) && $periodeAktif !== '' && (int) substr($periodeAktif, 0, 4) > $this->tahunSekarang();
    }

    /**
     * Jumlah yang aman diposting: pengeluaran dan penyesuaian minus tidak boleh memakan stok
     * yang sudah direservasi perintah kerja, dan transfer menyisakan stok minimum di gudang asal.
     * Seeder lain boleh berjalan sebelum seeder ini, jadi saldo dibaca saat langkahnya dijalankan.
     */
    private function jumlahAman(string $jenis, ?string $gudangAsalId, string $sukuCadangId, int $jumlah): int
    {
        $keluar = $jenis === 'Pengeluaran' || $jenis === 'Transfer' || ($jenis === 'Adjustment' && $jumlah < 0);
        if (! $keluar || $gudangAsalId === null) {
            return $jumlah;
        }

        $saldo = DB::table('StokSukuCadang')
            ->where('GudangId', $gudangAsalId)
            ->where('SukuCadangId', $sukuCadangId)
            ->whereNull('LokasiGudangId')
            ->whereNull('KelompokSukuCadangId')
            ->first(['JumlahTersedia', 'JumlahDitahan']);

        $bebas = $saldo === null ? 0 : (int) floor((float) $saldo->JumlahTersedia - (float) $saldo->JumlahDitahan);
        if ($jenis === 'Transfer') {
            $bebas -= (int) DB::table('SukuCadang')->where('Id', $sukuCadangId)->value('StokMinimum');
        }

        $bebas = max(0, $bebas);

        return $jumlah < 0 ? -min(-$jumlah, $bebas) : min($jumlah, $bebas);
    }
}
