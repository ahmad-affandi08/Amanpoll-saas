<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Izin\ScopeLingkup;
use App\Domain\Pemeliharaan\Application\Actions\BuatKeluhan;
use App\Domain\Pemeliharaan\Application\Actions\BuatPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\CatatBiayaPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\GunakanSukuCadangPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\KelolaWaktuHentiAset;
use App\Domain\Pemeliharaan\Application\Actions\KelolaWaktuKerja;
use App\Domain\Pemeliharaan\Application\Actions\KonfirmasiPenerimaDiPerangkat;
use App\Domain\Pemeliharaan\Application\Actions\KonfirmasiPenerimaOlehPelapor;
use App\Domain\Pemeliharaan\Application\Actions\KonfirmasiPenyelesaianKeluhan;
use App\Domain\Pemeliharaan\Application\Actions\ResponsPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\SimpanAnalisisKegagalan;
use App\Domain\Pemeliharaan\Application\Actions\SimpanKategoriKeluhan;
use App\Domain\Pemeliharaan\Application\Actions\SimpanKodeKegagalan;
use App\Domain\Pemeliharaan\Application\Actions\SimpanTingkatLayanan;
use App\Domain\Pemeliharaan\Application\Actions\TugaskanPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\UbahPrioritasKeluhan;
use App\Domain\Pemeliharaan\Application\Actions\UbahStatusKeluhan;
use App\Domain\Pemeliharaan\Application\Actions\UbahStatusPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\HasilKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Persediaan\Application\Actions\BuatReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Transaksi pemeliharaan korektif perusahaan demo selama ± 12 bulan: tingkat layanan
 * (SLA) beserta eskalasinya, kategori keluhan per unit pengelola, kode kegagalan,
 * keluhan dari staf, dan perintah kerja lengkap dengan penugasan, waktu kerja,
 * downtime, suku cadang, biaya, analisis kegagalan, dan konfirmasi penerima.
 *
 * Setiap langkah berjalan lewat Action resmi dengan jam dimundurkan (`padaWaktu`)
 * dan pengguna yang memang berhak (pelapor melapor, koordinator meninjau dan
 * menugaskan, teknisi mengerjakan), sehingga nomor dokumen, batas SLA, stok, riwayat
 * status, dan audit sama seperti hasil aplikasi. Pekerjaan preventif disemai seeder lain.
 */
final class DemoPemeliharaanSeeder extends Seeder
{
    use KonteksDemo;

    /** Tarif jasa teknisi internal per jam untuk biaya tenaga kerja perintah kerja. */
    private const TARIF_TEKNISI_PER_JAM = 85000;

    /** Domain email seluruh akun demo. */
    private const DOMAIN_EMAIL = '@amanpoll.test';

    /**
     * Kode, nama, deskripsi, hari kerja ISO, jam mulai, jam selesai, memperhitungkan hari libur,
     * aturan per prioritas [menit respons, menit penyelesaian, menghitung jam kerja],
     * dan apakah eskalasi tahap ketiga sampai ke direksi.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: list<int>, 4: string, 5: string, 6: bool, 7: array<string, array{0: int, 1: int, 2: bool}>, 8: bool}>
     */
    private const TINGKAT_LAYANAN = [
        [
            'SLA-PRODUKSI', 'SLA Mesin & Utilitas Produksi (24/7)',
            'Pabrik berjalan tiga shift; gangguan mesin produksi, utilitas, dan K3 dihitung jam kalender tanpa jeda malam atau hari libur.',
            [1, 2, 3, 4, 5, 6, 7], '00:00', '23:59', false,
            ['Kritis' => [30, 480, false], 'Tinggi' => [60, 1440, false], 'Normal' => [120, 2880, false], 'Rendah' => [240, 7200, false]],
            true,
        ],
        [
            'SLA-FASILITAS', 'SLA Gedung & Fasilitas (Jam Kerja)',
            'Layanan gedung, HVAC, dan lift untuk kantor dan area pendukung; dihitung pada jam kerja Senin–Sabtu kecuali hari libur nasional.',
            [1, 2, 3, 4, 5, 6], '07:00', '17:00', true,
            ['Kritis' => [60, 480, false], 'Tinggi' => [120, 600, true], 'Normal' => [240, 1800, true], 'Rendah' => [480, 3000, true]],
            false,
        ],
        [
            'SLA-TI', 'SLA Layanan TI',
            'Dukungan komputer, jaringan, printer, dan CCTV; jam kerja Senin–Jumat 08.00–17.00. Gangguan kritis (server, jaringan inti) dihitung jam kalender.',
            [1, 2, 3, 4, 5], '08:00', '17:00', true,
            ['Kritis' => [30, 240, false], 'Tinggi' => [60, 540, true], 'Normal' => [120, 1080, true], 'Rendah' => [480, 2700, true]],
            false,
        ],
    ];

    /**
     * Kode, nama, kode induk, unit pengelola, kode SLA, prioritas bawaan, aset wajib.
     *
     * @var list<array{0: string, 1: string, 2: string|null, 3: string, 4: string, 5: string, 6: bool}>
     */
    private const KATEGORI_KELUHAN = [
        ['KK-TEKFAS', 'Teknik & Fasilitas', null, 'TEKFAS', 'SLA-FASILITAS', 'Normal', false],
        ['KK-LISTRIK', 'Listrik & Utilitas', 'KK-TEKFAS', 'TEKFAS', 'SLA-PRODUKSI', 'Tinggi', false],
        ['KK-MESIN', 'Mesin Produksi & Alat Angkut', 'KK-TEKFAS', 'TEKFAS', 'SLA-PRODUKSI', 'Tinggi', true],
        ['KK-HVAC', 'AC & Pendingin (HVAC)', 'KK-TEKFAS', 'TEKFAS', 'SLA-FASILITAS', 'Normal', false],
        ['KK-LIFT', 'Lift & Eskalator', 'KK-TEKFAS', 'TEKFAS', 'SLA-FASILITAS', 'Tinggi', true],
        ['KK-GEDUNG', 'Gedung & Sipil', 'KK-TEKFAS', 'TEKFAS', 'SLA-FASILITAS', 'Rendah', false],
        ['KK-K3', 'K3 & Proteksi Kebakaran', 'KK-TEKFAS', 'TEKFAS', 'SLA-PRODUKSI', 'Tinggi', false],
        ['KK-TI', 'Layanan TI', null, 'IT', 'SLA-TI', 'Normal', false],
        ['KK-KOMPUTER', 'Komputer & Jaringan', 'KK-TI', 'IT', 'SLA-TI', 'Normal', false],
        ['KK-PRINTER', 'Printer & Perangkat Cetak', 'KK-TI', 'IT', 'SLA-TI', 'Rendah', false],
        ['KK-CCTV', 'CCTV & Keamanan', 'KK-TI', 'IT', 'SLA-TI', 'Normal', false],
    ];

    /**
     * Kode, jenis, nama, kode kategori aset (null = berlaku umum), keterangan.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string|null, 4: string}>
     */
    private const KODE_KEGAGALAN = [
        ['MSL-01', 'Masalah', 'Mati total / tidak mau menyala', null, 'Peralatan tidak merespons sama sekali saat dinyalakan.'],
        ['MSL-02', 'Masalah', 'Suhu proses tidak tercapai / tidak stabil', 'KAT-MESIN', 'Zona pemanas atau pendingin mesin di luar toleransi setting.'],
        ['MSL-03', 'Masalah', 'Kebocoran oli / hidrolik', 'KAT-MESIN', 'Tetesan atau rembesan oli dari sistem hidrolik atau pelumasan.'],
        ['MSL-04', 'Masalah', 'Getaran atau bunyi abnormal', null, 'Getaran, bunyi kasar, atau berdecit di luar kondisi normal.'],
        ['MSL-05', 'Masalah', 'Tekanan udara turun', 'KAT-KOMP', 'Tekanan keluaran di bawah setting kerja.'],
        ['MSL-06', 'Masalah', 'Overheat / trip suhu tinggi', null, 'Proteksi suhu bekerja atau suhu komponen di atas batas.'],
        ['MSL-07', 'Masalah', 'Tidak dingin / kapasitas pendinginan turun', 'KAT-AC', 'Suhu ruang atau air dingin tidak mencapai setting.'],
        ['MSL-08', 'Masalah', 'Berhenti mendadak / macet', 'KAT-LIFT', 'Unit berhenti di tengah operasi atau pintu tidak bekerja.'],
        ['MSL-09', 'Masalah', 'Koneksi jaringan putus / lambat', 'KAT-IT', 'Layanan jaringan terputus, hilang-timbul, atau sangat lambat.'],
        ['MSL-10', 'Masalah', 'Proteksi trip / MCB jatuh', 'KAT-PANEL', 'Pemutus sirkuit trip berulang.'],
        ['MSL-11', 'Masalah', 'Gagal start / gagal ambil beban', 'KAT-GENSET', 'Genset tidak hidup atau terlambat mengambil beban.'],
        ['MSL-12', 'Masalah', 'Kebocoran air / fluida', null, 'Air, refrigeran, atau fluida lain keluar dari sistem.'],
        ['MSL-13', 'Masalah', 'Kerusakan media penyimpanan', 'KAT-IT', 'Disk rusak, degraded, atau penuh sehingga layanan terganggu.'],
        ['PNY-01', 'Penyebab', 'Keausan komponen karena umur pakai', null, 'Komponen mencapai batas umur pakainya.'],
        ['PNY-02', 'Penyebab', 'Pelumasan kurang / oli terkontaminasi', null, 'Pelumas kurang, kotor, atau salah spesifikasi.'],
        ['PNY-03', 'Penyebab', 'Kotoran / penyumbatan', null, 'Debu, kerak, atau material asing menyumbat saluran.'],
        ['PNY-04', 'Penyebab', 'Sambungan listrik kendur atau terbakar', null, 'Terminal kendur, kabel getas, atau kontak terbakar.'],
        ['PNY-05', 'Penyebab', 'Seal, gasket, atau O-ring rusak', null, 'Perapat mengeras, sobek, atau aus.'],
        ['PNY-06', 'Penyebab', 'Beban berlebih', null, 'Peralatan dipakai melebihi kapasitas rancangan.'],
        ['PNY-07', 'Penyebab', 'Kesalahan operasi / setting', null, 'Setting parameter atau cara operasi tidak sesuai instruksi kerja.'],
        ['PNY-08', 'Penyebab', 'Firmware atau konfigurasi bermasalah', 'KAT-IT', 'Bug firmware, konfigurasi berubah, atau lisensi kedaluwarsa.'],
        ['PNY-09', 'Penyebab', 'Kebocoran refrigeran', 'KAT-AC', 'Tekanan refrigeran turun karena kebocoran pipa atau sambungan.'],
        ['PNY-10', 'Penyebab', 'Baterai / aki melemah', null, 'Kapasitas baterai turun di bawah kebutuhan start atau backup.'],
        ['PNY-11', 'Penyebab', 'Kegagalan komponen elektronik', null, 'Sensor, kartu kontrol, atau modul elektronik rusak.'],
        ['TDK-01', 'Tindakan', 'Ganti komponen rusak', null, 'Komponen diganti dengan suku cadang baru.'],
        ['TDK-02', 'Tindakan', 'Pembersihan dan pelumasan', null, 'Pembersihan menyeluruh lalu pelumasan ulang sesuai spesifikasi.'],
        ['TDK-03', 'Tindakan', 'Penyetelan ulang / kalibrasi setting', null, 'Parameter disetel ulang dan diuji sampai normal.'],
        ['TDK-04', 'Tindakan', 'Perbaikan sambungan & terminasi kabel', null, 'Terminal dikencangkan, kabel dan lug diganti bila perlu.'],
        ['TDK-05', 'Tindakan', 'Isi ulang refrigeran & perbaikan kebocoran', 'KAT-AC', 'Kebocoran dilas/dipres lalu refrigeran diisi ulang.'],
        ['TDK-06', 'Tindakan', 'Pembaruan firmware / konfigurasi ulang', 'KAT-IT', 'Firmware diperbarui atau konfigurasi dipulihkan dari cadangan.'],
        ['TDK-07', 'Tindakan', 'Perbaikan oleh vendor', null, 'Pekerjaan dilakukan penyedia jasa dengan pengawasan teknisi internal.'],
        ['TDK-08', 'Tindakan', 'Pengarahan ulang operator', null, 'Operator diberi pengarahan ulang instruksi kerja.'],
    ];

    /**
     * Skenario pemeliharaan korektif, dari keluhan (`pelapor` terisi) maupun perintah kerja
     * langsung dari koordinator. Diurutkan menurut waktu saat dijalankan.
     *
     * Kunci: hari/jam/menit (atau menitLalu untuk kejadian hari ini), pelapor, kategori,
     * aset atau lokasi, judul, deskripsi, urgensi (usulan pelapor di Mode Lapangan),
     * prioritas (ditetapkan koordinator), akhir (keluhan yang berhenti sebelum perintah
     * kerja), teknisi, tahap akhir perintah kerja, durasi menit kerja, downtime (jenis),
     * sukuCadang, biaya, analisis [masalah, penyebab, tindakan, akar masalah, pencegahan],
     * ringkasan, konfirmasi, rating, ulasan, tunggu (menit menunggu suku cadang), dan lanjutan.
     *
     * @var list<array<string, mixed>>
     */
    private const KASUS = [
        // ── Oktober 2025 ──
        [
            'hari' => 352, 'jam' => 8, 'menit' => 20, 'pelapor' => 'pelapor', 'kategori' => 'KK-MESIN', 'aset' => 'PRD-INJ-01',
            'judul' => 'Heater band zona 3 barrel mesin injeksi #1 mati',
            'deskripsi' => 'Suhu zona 3 hanya 170°C padahal setting 230°C. Produk tutup botol 28 mm banyak short shot, mesin dihentikan.',
            'urgensi' => 'KerjaTerhenti', 'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 95, 'downtime' => 'TidakTerencana',
            'sukuCadang' => [['SC-HTR-001', 1]],
            'analisis' => ['MSL-02', 'PNY-01', 'TDK-01', 'Elemen heater band zona 3 putus karena umur pakai (terpasang ± 3 tahun).', 'Catat umur heater band di kartu mesin dan ukur resistansi tiap PM bulanan.'],
            'ringkasan' => 'Heater band zona 3 diganti baru, resistansi dan arus diuji normal, suhu tercapai dalam 25 menit.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Cepat ditangani, mesin jalan lagi sebelum ganti shift.',
        ],
        [
            'hari' => 345, 'jam' => 10, 'prioritas' => 'Tinggi', 'aset' => 'UTL-KMP-01',
            'judul' => 'Kompresor #1 trip suhu tinggi (overheat)',
            'deskripsi' => 'Kompresor GA75 #1 trip dengan alarm suhu elemen 110°C saat beban puncak. Kompresor #2 menanggung seluruh beban.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 180, 'downtime' => 'TidakTerencana',
            'sukuCadang' => [['SC-FLT-003', 1], ['SC-OLI-003', 1]],
            'analisis' => ['MSL-06', 'PNY-03', 'TDK-02', 'Cooler oli dan filter udara tersumbat debu area utilitas sehingga pendinginan elemen kurang.', 'Bersihkan cooler setiap bulan dan pasang kasa penyaring pada louver ruang kompresor.'],
            'ringkasan' => 'Filter udara dan oli kompresor diganti, cooler dibersihkan dengan udara tekan dan cairan degreaser. Uji beban 2 jam suhu stabil 82°C.',
            'konfirmasi' => ['perangkat', 'Wahyu Hidayat', 'Supervisor Utilitas'],
        ],
        [
            'hari' => 338, 'jam' => 9, 'menit' => 40, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-HVAC', 'aset' => 'GDG-AC-03',
            'judul' => 'AC ruang keuangan tidak dingin',
            'deskripsi' => 'AC menyala tetapi yang keluar angin biasa sejak pagi. Suhu ruangan sampai 29°C, tim tutup buku bulanan kepanasan.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 120,
            'sukuCadang' => [['SC-KAP-001', 1], ['SC-FRN-001', 1]],
            'analisis' => ['MSL-07', 'PNY-09', 'TDK-05', 'Kapasitor kompresor melemah dan tekanan freon rendah akibat rembesan di sambungan flare.', 'Periksa tekanan freon dan kapasitor pada servis AC tiga bulanan.'],
            'ringkasan' => 'Kapasitor kompresor diganti, sambungan flare dibuat ulang, freon R-32 diisi sampai tekanan 120 psi. Suhu keluar 12°C.',
            'konfirmasi' => 'pelapor', 'rating' => 4, 'ulasan' => 'Sudah dingin lagi, terima kasih.',
        ],
        [
            'hari' => 331, 'jam' => 14, 'menit' => 10, 'pelapor' => 'gudang', 'kategori' => 'KK-MESIN', 'aset' => 'GDL-FRK-02',
            'judul' => 'Forklift #2 rem kurang pakem',
            'deskripsi' => 'Saat membawa palet resin 1 ton, forklift #2 perlu jarak pengereman jauh lebih panjang. Unit kami parkir, tidak dipakai.',
            'urgensi' => 'Berbahaya', 'prioritas' => 'Kritis', 'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 150, 'downtime' => 'TidakTerencana',
            'biaya' => [['Vendor', 'Servis sistem rem & penggantian kampas rem', 2850000, 'VND-010']],
            'analisis' => ['MSL-04', 'PNY-01', 'TDK-07', 'Kampas rem aus sampai batas dan minyak rem terkontaminasi air.', 'Tambahkan pemeriksaan tebal kampas dan minyak rem pada checklist harian operator forklift.'],
            'ringkasan' => 'Bersama teknisi Toyota Material Handling: kampas rem diganti, minyak rem dikuras dan diganti DOT 3, uji pengereman beban penuh lulus.',
            'konfirmasi' => 'keluhan', 'rating' => 5, 'ulasan' => 'Forklift aman dipakai lagi.',
        ],
        // ── November 2025 ──
        [
            'hari' => 324, 'jam' => 8, 'prioritas' => 'Tinggi', 'aset' => 'UTL-GEN-02',
            'judul' => 'Genset cadangan gagal start saat uji mingguan',
            'deskripsi' => 'Pada uji beban mingguan Senin pagi genset Perkins 500 kVA hanya berputar lemah lalu mati. Tegangan aki terbaca 10,9 V.',
            'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 120,
            'sukuCadang' => [['SC-AKI-001', 2]],
            'analisis' => ['MSL-11', 'PNY-10', 'TDK-01', 'Dua aki starter sudah berumur 4 tahun dan tidak lagi menahan muatan; charger baterai sesekali mati.', 'Ukur tegangan dan berat jenis aki pada uji mingguan; ganti aki setiap 3 tahun.'],
            'ringkasan' => 'Dua aki starter 12V 200Ah diganti, terminal dibersihkan, charger baterai diperiksa normal. Uji start 3 kali berhasil, uji beban 30 menit.',
        ],
        [
            'hari' => 318, 'jam' => 10, 'menit' => 30, 'pelapor' => 'pengadaan', 'kategori' => 'KK-KOMPUTER', 'lokasi' => 'JKT-L15',
            'judul' => 'Wi-Fi lantai 15 sering putus',
            'deskripsi' => 'Sejak kemarin koneksi Wi-Fi di area pengadaan putus-sambung tiap beberapa menit. Rapat daring dengan pemasok sering terputus.',
            'teknisi' => ['it'], 'tahap' => 'Ditutup', 'durasi' => 90,
            'analisis' => ['MSL-09', 'PNY-08', 'TDK-06', 'Access point sisi timur memakai firmware lama yang memiliki bug roaming; kanal bertabrakan dengan AP gedung sebelah.', 'Jadwalkan pembaruan firmware AP tiap kuartal dan tinjau kanal Wi-Fi setelah ada perubahan gedung sekitar.'],
            'ringkasan' => 'Firmware 4 access point diperbarui, kanal 2,4 GHz dipindah ke 1/6/11 tanpa tumpang tindih, uji roaming normal.',
            'konfirmasi' => 'keluhan', 'rating' => 4, 'ulasan' => 'Sekarang stabil.',
        ],
        [
            'hari' => 311, 'jam' => 13, 'menit' => 15, 'pelapor' => 'pelapor', 'kategori' => 'KK-LISTRIK', 'aset' => 'UTL-PNL-02',
            'judul' => 'MCB panel SDP produksi A sering trip',
            'deskripsi' => 'MCB jalur mesin crusher dan MTC trip tiga kali sejak pagi, lini injeksi 1–2 ikut berhenti tiap kali trip.',
            'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 150, 'downtime' => 'TidakTerencana',
            'sukuCadang' => [['SC-MCB-001', 1], ['SC-KNT-001', 1]],
            'analisis' => ['MSL-10', 'PNY-06', 'TDK-01', 'Beban jalur crusher bertambah setelah penambahan MTC tanpa penyesuaian rating MCB; kontaktor crusher kontaknya sudah bopeng.', 'Setiap penambahan beban di panel wajib dihitung ulang oleh tim elektrikal sebelum disambung.'],
            'ringkasan' => 'MCB 3P 32A dan kontaktor LC1D32 jalur crusher diganti, beban MTC dipindah ke jalur cadangan, arus fasa seimbang.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Sudah tidak trip lagi, mantap.',
        ],
        [
            'hari' => 305, 'jam' => 11, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-PRINTER', 'aset' => 'IT-PRN-01',
            'judul' => 'Printer lantai 12 hasil cetak bergaris',
            'deskripsi' => 'Setiap cetakan ada garis hitam vertikal di sisi kiri, laporan untuk direksi jadi tidak rapi.',
            'teknisi' => ['it'], 'tahap' => 'Ditutup', 'durasi' => 45,
            'sukuCadang' => [['SC-TNR-001', 1]],
            'ringkasan' => 'Toner HP 76A diganti (drum tergores), jalur kertas dibersihkan, uji cetak 20 halaman bersih.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Hasil cetak sudah bersih.',
        ],
        // ── Desember 2025 ──
        [
            'hari' => 298, 'jam' => 9, 'prioritas' => 'Tinggi', 'aset' => 'GDG-LFT-02',
            'judul' => 'Lift #2 pintu tidak menutup sempurna',
            'deskripsi' => 'Pintu lift #2 membuka kembali berulang kali di lantai 5 dan 12. Lift dimatikan sementara untuk keamanan.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 240, 'downtime' => 'TidakTerencana',
            'biaya' => [['Vendor', 'Penyetelan door operator & penggantian roller pintu', 4750000, 'VND-004']],
            'analisis' => ['MSL-08', 'PNY-01', 'TDK-07', 'Roller hanger pintu aus sehingga daun pintu miring dan menyentuh sensor safety edge.', 'Minta vendor memeriksa roller pintu pada kunjungan servis bulanan.'],
            'ringkasan' => 'Teknisi PT Lift Indo Jaya mengganti 4 roller hanger dan menyetel door operator; uji buka-tutup 50 siklus tanpa gangguan.',
        ],
        [
            'hari' => 292, 'jam' => 10, 'menit' => 45, 'pelapor' => 'kalibrasi', 'kategori' => 'KK-LISTRIK', 'lokasi' => 'CKR-LAB',
            'judul' => 'Stopkontak meja lab korslet, bau hangus',
            'deskripsi' => 'Stopkontak di meja uji tarik mengeluarkan percikan dan bau hangus saat alat dicolokkan. Stopkontak sudah kami matikan dari MCB.',
            'urgensi' => 'Berbahaya', 'prioritas' => 'Kritis', 'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 60,
            'biaya' => [['Lainnya', 'Stopkontak industri & kabel NYM 3×2,5 mm² (beli lokal)', 385000, null]],
            'ringkasan' => 'Stopkontak dan kabel yang hangus diganti, terminal di kotak sambung dikencangkan, uji tahanan isolasi > 100 MΩ.',
            'konfirmasi' => 'keluhan', 'rating' => 5, 'ulasan' => 'Respons cepat, terima kasih.',
        ],
        [
            'hari' => 286, 'jam' => 7, 'menit' => 50, 'pelapor' => 'pelapor', 'kategori' => 'KK-MESIN', 'aset' => 'PRD-BLW-01',
            'judul' => 'Blow molding #1 parison tidak stabil, botol tipis sebelah',
            'deskripsi' => 'Botol 600 ml tebal dindingnya tidak rata, sisi kanan tipis. Reject naik sampai 12% di shift malam.',
            'teknisi' => ['teknik', 'listrik'], 'tahap' => 'Ditutup', 'durasi' => 210, 'downtime' => 'TidakTerencana',
            'sukuCadang' => [['SC-TC-001', 2], ['SC-HTR-001', 1]],
            'analisis' => ['MSL-02', 'PNY-11', 'TDK-01', 'Thermocouple die head membaca 15°C lebih rendah sehingga pemanas bekerja berlebih di satu sisi.', 'Verifikasi pembacaan thermocouple die head dengan termometer referensi setiap bulan.'],
            'ringkasan' => 'Thermocouple die head dan heater band sisi kanan diganti, profil parison disetel ulang bersama operator.',
            'konfirmasi' => 'bermasalah', 'alasanBermasalah' => 'Botol masih ada yang tipis di bagian bahu, reject sekitar 5%.',
            'durasiUlang' => 90, 'ringkasanUlang' => 'Programmer parison (wall thickness control) dikalibrasi ulang titik 8–12, reject turun di bawah 1% selama 3 jam produksi.',
            'rating' => 4, 'ulasan' => 'Setelah disetel ulang hasilnya bagus.',
        ],
        [
            'hari' => 279, 'jam' => 15, 'pelapor' => 'pelapor', 'kategori' => 'KK-GEDUNG', 'lokasi' => 'CKR-PRDA',
            'judul' => 'Atap gedung produksi A bocor dekat panel SDP',
            'deskripsi' => 'Hujan deras sore ini, air menetes dari atap tepat di sebelah panel SDP produksi A. Sementara kami tadah dengan ember.',
            'urgensi' => 'Berbahaya', 'prioritas' => 'Tinggi', 'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 240,
            'biaya' => [['Lainnya', 'Sealant atap, seng gelombang, dan baut roofing', 1450000, null]],
            'ringkasan' => 'Dua lembar seng yang berkarat diganti, sambungan talang dan baut roofing disealant ulang. Uji siram air tidak ada rembesan.',
            'konfirmasi' => 'keluhan', 'rating' => 4, 'ulasan' => 'Sudah tidak bocor waktu hujan kemarin.',
        ],
        [
            'hari' => 272, 'jam' => 9, 'menit' => 30, 'prioritas' => 'Tinggi', 'aset' => 'IT-SRV-02',
            'judul' => 'Server file & backup: satu disk RAID degraded',
            'deskripsi' => 'iDRAC mengirim peringatan disk 3 pada RAID 5 server file gagal. Array berjalan degraded tanpa redundansi.',
            'teknisi' => ['it'], 'tahap' => 'Ditutup', 'durasi' => 120,
            'sukuCadang' => [['SC-HDD-001', 1]],
            'analisis' => ['MSL-13', 'PNY-01', 'TDK-01', 'Disk SAS slot 3 mencapai batas sektor rusak (predictive failure) setelah 28 bulan beroperasi 24 jam.', 'Pantau status predictive failure iDRAC mingguan dan simpan satu disk cadangan di gudang.'],
            'ringkasan' => 'Disk slot 3 diganti hot-swap, rebuild RAID selesai dalam 5 jam tanpa henti layanan, status array Optimal.',
        ],
        // ── Januari 2026 ──
        [
            'hari' => 266, 'jam' => 10, 'pelapor' => 'manajer.aset', 'kategori' => 'KK-K3', 'aset' => 'K3-APR-01',
            'judul' => 'Dua APAR CO2 produksi A tekanan di bawah zona hijau',
            'deskripsi' => 'Saat inspeksi bulanan K3 ditemukan dua tabung APAR di dekat lini injeksi jarum manometernya di zona merah.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 60,
            'sukuCadang' => [['SC-APR-001', 2]],
            'biaya' => [['Vendor', 'Isi ulang & uji hidrostatik 2 tabung APAR', 900000, 'VND-008']],
            'ringkasan' => 'Dua tabung diganti dengan tabung isi ulang dari stok, tabung lama dikirim ke PT Proteksi Api Sentosa untuk isi ulang dan uji hidrostatik.',
            'konfirmasi' => 'keluhan', 'rating' => 5, 'ulasan' => 'Tuntas hari itu juga.',
        ],
        [
            'hari' => 259, 'jam' => 8, 'menit' => 35, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-LIFT', 'aset' => 'GDG-LFT-01',
            'judul' => 'Lift #1 berhenti di antara lantai 7 dan 8',
            'deskripsi' => 'Pagi ini lift #1 berhenti mendadak di antara lantai 7 dan 8, empat karyawan terjebak ± 10 menit sampai dievakuasi sekuriti.',
            'urgensi' => 'Berbahaya', 'prioritas' => 'Kritis', 'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 180, 'downtime' => 'TidakTerencana',
            'biaya' => [['Vendor', 'Perbaikan kontrol & penggantian sensor level lantai', 7800000, 'VND-004']],
            'analisis' => ['MSL-08', 'PNY-11', 'TDK-07', 'Sensor level lantai 8 rusak sehingga kontroler kehilangan posisi dan menghentikan kereta demi keamanan.', 'Tambahkan pemeriksaan sensor level pada servis bulanan vendor; latih ulang sekuriti prosedur evakuasi.'],
            'ringkasan' => 'Vendor mengganti sensor level lantai 8 dan memperbarui parameter kontroler; uji jalan semua lantai dan uji rem darurat lulus.',
            'konfirmasi' => 'pelapor', 'rating' => 4, 'ulasan' => 'Sudah normal, semoga tidak terulang.',
        ],
        [
            'hari' => 252, 'jam' => 9, 'prioritas' => 'Normal', 'aset' => 'UTL-TRF-01',
            'judul' => 'Trafo distribusi: suhu minyak tinggi saat beban puncak',
            'deskripsi' => 'Pembacaan termometer trafo 2000 kVA mencapai 88°C pada pukul 14.00. Perlu cek beban dan kipas pendingin.',
            'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 120,
            'analisis' => ['MSL-06', 'PNY-06', 'TDK-03', 'Beban fasa S lebih tinggi 18% dari fasa lain setelah penambahan mesin injeksi #4.', 'Evaluasi keseimbangan beban trafo setiap kali ada mesin baru dipasang.'],
            'ringkasan' => 'Beban beberapa jalur dipindah untuk menyeimbangkan fasa, radiator dibersihkan. Suhu minyak turun ke 71°C pada beban puncak.',
        ],
        [
            'hari' => 246, 'jam' => 13, 'menit' => 40, 'pelapor' => 'pelapor', 'kategori' => 'KK-MESIN', 'aset' => 'PRD-INJ-03',
            'judul' => 'Mesin injeksi #3 nozzle bocor material',
            'deskripsi' => 'Material PP meleleh keluar dari sambungan nozzle dan menumpuk di sprue bushing. Mesin kami hentikan.',
            'urgensi' => 'KerjaTerhenti', 'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 180, 'downtime' => 'TidakTerencana',
            'sukuCadang' => [['SC-NZL-001', 1]],
            'analisis' => ['MSL-03', 'PNY-01', 'TDK-01', 'Ulir nozzle tip aus sehingga dudukan tidak rapat terhadap sprue bushing.', 'Periksa kondisi nozzle tip setiap penggantian mold.'],
            'ringkasan' => 'Nozzle tip diganti baru, radius dudukan terhadap sprue bushing dicek dengan kertas karbon, uji produksi 1 jam tidak bocor.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Terima kasih, sudah tidak bocor.',
        ],
        [
            'hari' => 240, 'jam' => 10, 'menit' => 15, 'pelapor' => 'gudang', 'kategori' => 'KK-PRINTER', 'aset' => 'IT-PRN-03',
            'judul' => 'Printer label Zebra gudang hasil cetak pudar',
            'deskripsi' => 'Label barcode palet pudar dan tidak terbaca scanner, pengiriman ke pelanggan tertahan.',
            'teknisi' => ['it'], 'tahap' => 'Ditutup', 'durasi' => 60,
            'sukuCadang' => [['SC-RBN-001', 2]],
            'ringkasan' => 'Ribbon diganti, print head dibersihkan dengan alkohol isopropil, darkness disetel ulang. Barcode terbaca di semua scanner.',
            'konfirmasi' => 'keluhan', 'rating' => 5, 'ulasan' => 'Label jelas lagi.',
        ],
        // ── Februari 2026 ──
        [
            'hari' => 234, 'jam' => 8, 'menit' => 30, 'prioritas' => 'Normal', 'aset' => 'PRD-CRS-01',
            'judul' => 'Crusher: V-belt slip dan bunyi kasar',
            'deskripsi' => 'Saat patroli, crusher terdengar berdecit dan putaran turun ketika diisi runner. Bearing sisi puli terasa panas.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 90, 'downtime' => 'TidakTerencana',
            'sukuCadang' => [['SC-BLT-001', 2], ['SC-BRG-001', 2]],
            'ringkasan' => 'Dua V-belt B-68 dan dua bearing 6205 diganti, tegangan belt disetel, puli diluruskan.',
        ],
        [
            'hari' => 228, 'jam' => 14, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-KOMPUTER', 'aset' => 'IT-PC-03',
            'judul' => 'Laptop keuangan sering mati sendiri',
            'deskripsi' => 'Laptop mati tiba-tiba walau baterai penuh, sudah tiga kali hari ini saat membuka laporan besar.',
            'teknisi' => ['it'], 'tahap' => 'Ditutup', 'durasi' => 120,
            'analisis' => ['MSL-01', 'PNY-11', 'TDK-01', 'Chip daya pada mainboard rusak; biaya penggantian mainboard melebihi 60% harga laptop baru.', 'Usulkan penggantian laptop yang berumur di atas 3 tahun pada rencana pengadaan tahunan.'],
            'ringkasan' => 'Mainboard rusak dan tidak ekonomis diperbaiki. Data dipindah ke laptop cadangan, unit lama dinonaktifkan dan diusulkan penghapusan.',
            'konfirmasi' => 'keluhan', 'rating' => 3, 'ulasan' => 'Sudah dipinjami laptop pengganti, semoga cepat dapat unit baru.',
        ],
        [
            'hari' => 221, 'jam' => 9, 'menit' => 5, 'pelapor' => 'pelapor', 'kategori' => 'KK-LISTRIK', 'aset' => 'UTL-GEN-01',
            'judul' => 'Listrik PLN padam, genset terlambat ambil beban',
            'deskripsi' => 'PLN padam pukul 09.00, genset utama baru ambil beban hampir 3 menit kemudian. Semua mesin injeksi mati dan material di barrel harus dipurging.',
            'urgensi' => 'KerjaTerhenti', 'prioritas' => 'Kritis', 'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 150, 'downtime' => 'TidakTerencana',
            'sukuCadang' => [['SC-KNT-001', 1]],
            'biaya' => [['Vendor', 'Pemeriksaan & setting ulang panel ATS/AMF', 3500000, 'VND-001']],
            'analisis' => ['MSL-11', 'PNY-04', 'TDK-04', 'Kontaktor ATS sisi genset kontaknya kotor dan timer AMF tersetel 150 detik (seharusnya 15 detik).', 'Uji simulasi PLN padam setiap bulan dan kunci parameter AMF dengan kata sandi.'],
            'ringkasan' => 'Kontaktor ATS diganti, timer AMF dikembalikan ke 15 detik, uji simulasi padam 3 kali: genset ambil beban dalam 12 detik.',
            'konfirmasi' => 'pelapor', 'rating' => 4, 'ulasan' => 'Semoga kalau padam lagi genset langsung ambil beban.',
        ],
        [
            'hari' => 215, 'jam' => 10, 'prioritas' => 'Tinggi', 'aset' => 'GDL-FRK-01',
            'judul' => 'Forklift #1 bocor oli hidrolik di silinder angkat',
            'deskripsi' => 'Ditemukan rembesan oli hidrolik di silinder angkat forklift #1; garpu turun perlahan saat membawa beban.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 150, 'downtime' => 'TidakTerencana',
            'sukuCadang' => [['SC-OLI-002', 1]],
            'biaya' => [['Vendor', 'Seal kit silinder angkat & jasa bongkar-pasang', 1650000, 'VND-010']],
            'analisis' => ['MSL-03', 'PNY-05', 'TDK-01', 'Seal piston silinder angkat mengeras dan sobek.', 'Hindari membawa beban melebihi 2,5 ton; periksa rembesan pada inspeksi harian.'],
            'ringkasan' => 'Seal kit silinder angkat diganti bersama teknisi TMHI, oli hidrolik ditambah, uji angkat 2,5 ton selama 15 menit tidak turun.',
        ],
        [
            'hari' => 208, 'jam' => 11, 'menit' => 20, 'pelapor' => 'pelapor', 'kategori' => 'KK-MESIN', 'aset' => 'PRD-MTC-01',
            'judul' => 'MTC tidak mencapai suhu set 80°C',
            'deskripsi' => 'Mould temperature controller hanya naik sampai 62°C, permukaan produk jadi kusam.',
            'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 90,
            'sukuCadang' => [['SC-TC-001', 1]],
            'ringkasan' => 'Thermocouple MTC diganti dan relay SSR diperiksa, suhu tercapai 80°C dalam 20 menit.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Produk sudah mengkilap lagi.',
        ],
        // ── Maret 2026 (menjelang Lebaran, produksi penuh) ──
        [
            'hari' => 203, 'jam' => 9, 'menit' => 45, 'pelapor' => 'kalibrasi', 'kategori' => 'KK-HVAC', 'lokasi' => 'CKR-LAB',
            'judul' => 'AC laboratorium QC tidak dingin, suhu ruang 29°C',
            'deskripsi' => 'Standar ruang uji 23 ± 2°C. Sejak pagi suhu 29°C sehingga uji tarik dan uji dimensi harus ditunda.',
            'prioritas' => 'Tinggi', 'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 150,
            'sukuCadang' => [['SC-FRN-001', 1], ['SC-KAP-001', 1]],
            'analisis' => ['MSL-07', 'PNY-09', 'TDK-05', 'Kebocoran halus di pipa evaporator unit indoor lab.', 'Masukkan AC laboratorium ke jadwal servis bulanan karena ruang uji memerlukan suhu stabil.'],
            'ringkasan' => 'Kebocoran di pipa evaporator dilas, kapasitor fan outdoor diganti, freon diisi ulang. Suhu ruang 23°C dalam 40 menit.',
            'konfirmasi' => 'keluhan', 'rating' => 5, 'ulasan' => 'Suhu ruang uji sudah sesuai standar.',
        ],
        [
            'hari' => 196, 'jam' => 7, 'menit' => 40, 'prioritas' => 'Kritis', 'aset' => 'IT-NET-02',
            'judul' => 'Firewall reboot sendiri, VPN ke Surabaya putus',
            'deskripsi' => 'FortiGate 200F reboot dua kali sejak subuh; VPN ke gudang Surabaya dan akses ERP dari pabrik terputus.',
            'teknisi' => ['it'], 'tahap' => 'Ditutup', 'durasi' => 90, 'downtime' => 'TidakTerencana',
            'biaya' => [['Vendor', 'Tiket dukungan & upgrade firmware FortiOS', 2200000, 'VND-006']],
            'analisis' => ['MSL-09', 'PNY-08', 'TDK-06', 'Kebocoran memori pada versi FortiOS terpasang menyebabkan conserve mode lalu reboot.', 'Terapkan pembaruan firmware terjadwal dan pantau pemakaian memori firewall di dasbor NOC.'],
            'ringkasan' => 'Bersama PT Mitra Datacom, FortiOS diperbarui ke versi stabil terbaru, konfigurasi dicadangkan, VPN pulih.',
        ],
        [
            'hari' => 190, 'jam' => 10, 'pelapor' => 'pelapor', 'kategori' => 'KK-LISTRIK', 'lokasi' => 'CKR-PRDA-INJ',
            'judul' => 'Lampu high bay area injeksi mati 3 titik',
            'deskripsi' => 'Tiga lampu di atas lini injeksi 2 dan 3 mati, area pemeriksaan visual produk jadi gelap.',
            'urgensi' => 'MenggangguKerja', 'prioritas' => 'Normal', 'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 120,
            'sukuCadang' => [['SC-LMP-001', 2]],
            'biaya' => [['Lainnya', 'Sewa scissor lift 1 hari', 1200000, null]],
            'ringkasan' => 'Dua lampu LED high bay diganti baru, satu titik hanya konektor longgar dan diperbaiki. Kuat penerangan 420 lux.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Terang lagi, terima kasih.',
        ],
        [
            'hari' => 184, 'jam' => 14, 'menit' => 30, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-GEDUNG', 'lokasi' => 'JKT-L12',
            'judul' => 'Pintu kaca ruang keuangan engselnya lepas',
            'deskripsi' => 'Pintu kaca masuk ruang keuangan miring dan sulit ditutup, khawatir kacanya jatuh.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 60,
            'biaya' => [['Lainnya', 'Floor hinge Dorma BTS 75 V', 1850000, null]],
            'ringkasan' => 'Floor hinge diganti baru dan penahan pintu disetel, pintu menutup sendiri dengan halus.',
            'konfirmasi' => 'keluhan', 'rating' => 5, 'ulasan' => 'Rapi, terima kasih.',
        ],
        [
            'hari' => 176, 'jam' => 8, 'menit' => 10, 'pelapor' => 'pelapor', 'kategori' => 'KK-MESIN', 'aset' => 'PRD-INJ-02',
            'judul' => 'Mesin injeksi #2 clamp tidak mau menutup',
            'deskripsi' => 'Clamp tidak merespons perintah tutup, layar menampilkan alarm valve clamp. Order tutup galon untuk Lebaran tertahan.',
            'urgensi' => 'KerjaTerhenti', 'prioritas' => 'Kritis', 'teknisi' => ['teknik', 'listrik'], 'tahap' => 'Ditutup', 'durasi' => 180, 'downtime' => 'TidakTerencana',
            'sukuCadang' => [['SC-SOL-001', 1], ['SC-OLI-002', 1]],
            'analisis' => ['MSL-01', 'PNY-11', 'TDK-01', 'Kumparan solenoid valve clamp terbakar akibat tegangan kontrol drop saat beban puncak.', 'Pasang stabilizer tegangan 24 VDC pada panel kontrol mesin injeksi #2.'],
            'ringkasan' => 'Solenoid valve clamp diganti, power supply 24 VDC diperiksa dan terminal dikencangkan, oli hidrolik ditambah. Uji 100 siklus normal.',
            'konfirmasi' => 'pelapor', 'rating' => 4, 'ulasan' => 'Order bisa kekejar, terima kasih tim teknik.',
        ],
        [
            'hari' => 170, 'jam' => 9, 'prioritas' => 'Tinggi', 'aset' => 'UTL-CHL-01',
            'judul' => 'Chiller York: alarm tekanan kondensor tinggi',
            'deskripsi' => 'Chiller trip alarm high pressure dua kali, suhu air pendingin mold naik ke 18°C.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 240, 'downtime' => 'TidakTerencana',
            'biaya' => [['Vendor', 'Chemical cleaning tube kondensor chiller', 8500000, 'VND-003']],
            'analisis' => ['MSL-06', 'PNY-03', 'TDK-07', 'Kerak kapur di tube kondensor menurunkan perpindahan panas; kualitas air cooling tower menurun.', 'Kontrak pengolahan air cooling tower bulanan dan chemical cleaning kondensor tahunan.'],
            'ringkasan' => 'CV Sejuk Mandiri Teknik melakukan chemical cleaning tube kondensor; tekanan kondensor kembali 135 psi dan suhu air pendingin 12°C.',
        ],
        // ── April 2026 ──
        [
            'hari' => 163, 'jam' => 10, 'menit' => 50, 'pelapor' => 'manajer.aset', 'kategori' => 'KK-CCTV', 'aset' => 'IT-CCT-01',
            'judul' => 'Empat kamera CCTV area loading tidak merekam',
            'deskripsi' => 'Saat investigasi selisih stok, rekaman kamera 9–12 area loading tanggal kemarin kosong.',
            'teknisi' => ['it'], 'tahap' => 'Ditutup', 'durasi' => 120,
            'biaya' => [['Vendor', 'HDD surveillance 4 TB untuk NVR & jasa pasang', 2450000, 'VND-006']],
            'analisis' => ['MSL-13', 'PNY-01', 'TDK-01', 'Satu HDD NVR rusak sehingga kanal yang tersimpan di disk tersebut gagal merekam.', 'Aktifkan notifikasi email kesehatan disk NVR ke tim IT.'],
            'ringkasan' => 'HDD NVR yang rusak diganti, jadwal rekaman kanal 9–12 dipulihkan, notifikasi kesehatan disk diaktifkan.',
            'konfirmasi' => 'keluhan', 'rating' => 4, 'ulasan' => 'Sudah merekam lagi.',
        ],
        [
            'hari' => 157, 'jam' => 13, 'pelapor' => 'pelapor', 'kategori' => 'KK-MESIN', 'aset' => 'PRD-BLW-02',
            'judul' => 'Blow molding #2 bocor udara pada blow pin',
            'deskripsi' => 'Terdengar desis udara dari blow pin station 2, botol tidak mengembang penuh.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 90, 'downtime' => 'TidakTerencana',
            'biaya' => [['Lainnya', 'O-ring & fitting pneumatik', 650000, null]],
            'ringkasan' => 'O-ring blow pin dan dua fitting pneumatik diganti, uji tiup 30 siklus tanpa kebocoran.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Botol sudah bagus lagi.',
        ],
        [
            'hari' => 150, 'jam' => 9, 'menit' => 30, 'prioritas' => 'Tinggi', 'aset' => 'UTL-PMP-03',
            'judul' => 'Pompa hydrant diesel tidak auto-start saat uji',
            'deskripsi' => 'Pada uji mingguan sistem hydrant, pompa diesel tidak start otomatis ketika tekanan header turun ke 6 bar.',
            'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 120,
            'sukuCadang' => [['SC-MCB-001', 1]],
            'analisis' => ['MSL-11', 'PNY-11', 'TDK-01', 'Pressure switch start pompa diesel macet dan MCB kontrol panel jatuh.', 'Uji auto-start pompa hydrant setiap minggu dan catat di log K3.'],
            'ringkasan' => 'Pressure switch dibersihkan dan disetel ulang, MCB kontrol diganti. Uji auto-start 3 kali berhasil pada 6 bar.',
            'konfirmasi' => ['perangkat', 'Samsul Arifin', 'Koordinator K3 Pabrik'],
        ],
        [
            'hari' => 143, 'jam' => 10, 'pelapor' => 'gudang', 'kategori' => 'KK-MESIN', 'aset' => 'GDL-FRK-03',
            'judul' => 'Forklift Surabaya ban depan aus dan retak',
            'deskripsi' => 'Ban solid depan forklift gudang Surabaya sudah aus melewati garis batas dan retak di sisi kiri.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 120,
            'sukuCadang' => [['SC-BAN-001', 2]],
            'biaya' => [['Vendor', 'Jasa press & pasang ban solid', 750000, 'VND-010']],
            'ringkasan' => 'Dua ban solid depan diganti dari stok gudang Surabaya dengan mesin press TMHI cabang Surabaya.',
            'konfirmasi' => 'keluhan', 'rating' => 5, 'ulasan' => 'Forklift sudah bisa dipakai lagi.',
        ],
        [
            'hari' => 140, 'jam' => 9, 'menit' => 15, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-HVAC', 'aset' => 'GDG-AC-03',
            'judul' => 'AC ruang keuangan tidak menyala',
            'deskripsi' => 'AC tidak menyala sama sekali saat ditekan remote.',
            'akhir' => 'Dibatalkan', 'alasan' => 'Ternyata baterai remote habis, AC sudah menyala normal.',
        ],
        [
            'hari' => 136, 'jam' => 11, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-HVAC', 'aset' => 'GDG-AC-01',
            'judul' => 'AC ruang rapat lantai 12 meneteskan air',
            'deskripsi' => 'Air menetes dari unit indoor AC ruang rapat utama ke meja rapat, sudah kami alasi handuk.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 60,
            'sukuCadang' => [['SC-FLT-005', 2]],
            'ringkasan' => 'Saluran drain dan bak penampung dibersihkan, filter AC diganti.',
            'konfirmasi' => 'belumBeres', 'alasanBelumBeres' => 'Dua hari kemudian masih menetes lagi saat rapat siang.',
            'lanjutan' => [
                'judul' => 'Perbaikan lanjutan: pipa drain AC ruang rapat lantai 12',
                'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 150,
                'biaya' => [['Vendor', 'Pemasangan ulang pipa drain & insulasi', 1350000, 'VND-003']],
                'analisis' => ['MSL-12', 'PNY-03', 'TDK-07', 'Kemiringan pipa drain tidak cukup dan insulasi pipa sobek sehingga air kondensasi menetes di luar bak.', 'Periksa kemiringan pipa drain setiap pemasangan AC baru.'],
                'ringkasan' => 'Pipa drain dipasang ulang dengan kemiringan 2%, insulasi pipa refrigeran diganti. Uji 3 jam tidak ada tetesan.',
                'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Sekarang benar-benar tidak menetes.',
            ],
        ],
        // ── Mei 2026 ──
        [
            'hari' => 128, 'jam' => 14, 'prioritas' => 'Tinggi', 'aset' => 'GDG-UPS-01',
            'judul' => 'UPS ruang server alarm baterai lemah',
            'deskripsi' => 'UPS APC 40 kVA menampilkan alarm "battery needs replacement"; estimasi backup tinggal 4 menit.',
            'teknisi' => ['it'], 'tahap' => 'Ditutup', 'durasi' => 60,
            'biaya' => [['Vendor', 'Penggantian satu string baterai UPS (16 blok)', 18500000, 'VND-006']],
            'analisis' => ['MSL-01', 'PNY-10', 'TDK-07', 'Baterai VRLA berumur 34 bulan dengan suhu ruang server sempat tinggi; kapasitas turun di bawah 60%.', 'Jaga suhu ruang server ≤ 24°C dan lakukan uji runtime UPS setiap 6 bulan.'],
            'ringkasan' => 'PT Mitra Datacom mengganti 16 blok baterai secara hot-swap, uji runtime 22 menit pada beban 60%.',
        ],
        [
            'hari' => 121, 'jam' => 7, 'menit' => 45, 'pelapor' => 'pelapor', 'kategori' => 'KK-LISTRIK', 'aset' => 'UTL-KMP-02',
            'judul' => 'Tekanan angin di lini injeksi turun',
            'deskripsi' => 'Tekanan angin di lini injeksi hanya 5,5 bar, robot take-out sering gagal menjepit produk.',
            'urgensi' => 'MenggangguKerja', 'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 120, 'downtime' => 'TidakTerencana',
            'biaya' => [['Vendor', 'Perbaikan unloader valve kompresor GA75', 3200000, 'VND-002']],
            'analisis' => ['MSL-05', 'PNY-05', 'TDK-07', 'Diafragma unloader valve kompresor #2 sobek sehingga kompresor sering unload.', 'Minta vendor memeriksa unloader valve pada servis 4.000 jam.'],
            'ringkasan' => 'Unloader valve kompresor #2 diperbaiki bersama PT Atlas Kompresindo, tekanan kembali 7 bar stabil.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Robot sudah normal.',
        ],
        [
            'hari' => 114, 'jam' => 13, 'menit' => 20, 'pelapor' => 'pengadaan', 'kategori' => 'KK-PRINTER', 'aset' => 'IT-PRN-02',
            'judul' => 'Printer kantor pabrik sering paper jam',
            'deskripsi' => 'Setiap mencetak lebih dari 5 lembar kertas selalu tersangkut. Dokumen PO untuk pemasok jadi terlambat.',
            'teknisi' => ['it'], 'tahap' => 'Ditutup', 'durasi' => 45,
            'biaya' => [['Lainnya', 'Pickup roller kit HP M404', 450000, null]],
            'ringkasan' => 'Pickup roller dan separation pad diganti, uji cetak 50 lembar tanpa tersangkut.',
            'konfirmasi' => 'koordinator',
        ],
        [
            'hari' => 107, 'jam' => 9, 'prioritas' => 'Normal', 'aset' => 'GDG-PMP-01',
            'judul' => 'Pompa transfer air menara berbunyi kasar',
            'deskripsi' => 'Pompa transfer ke tangki atap berbunyi kasar dan ada rembesan air di seal.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 150,
            'sukuCadang' => [['SC-BRG-002', 2], ['SC-SEL-001', 1]],
            'analisis' => ['MSL-04', 'PNY-01', 'TDK-01', 'Bearing motor aus dan mechanical seal bocor setelah 9 tahun operasi.', 'Tambahkan pengukuran getaran pompa pada PM triwulan.'],
            'ringkasan' => 'Dua bearing 6310 dan mechanical seal diganti, alignment kopling disetel, getaran turun ke 2,1 mm/s.',
        ],
        [
            'hari' => 100, 'jam' => 10, 'menit' => 25, 'pelapor' => 'pelapor', 'kategori' => 'KK-MESIN', 'aset' => 'PRD-INJ-04',
            'judul' => 'Mesin injeksi #4 alarm thermocouple zona nozzle',
            'deskripsi' => 'Muncul alarm TC open di zona nozzle, heater otomatis mati dan mesin berhenti.',
            'urgensi' => 'KerjaTerhenti', 'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 60, 'downtime' => 'TidakTerencana',
            'sukuCadang' => [['SC-TC-001', 1]],
            'ringkasan' => 'Thermocouple zona nozzle diganti, kabel kompensasi dirapikan agar tidak terjepit cover.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Cepat, mantap.',
        ],
        // ── Juni 2026 ──
        [
            'hari' => 95, 'jam' => 9, 'prioritas' => 'Rendah', 'aset' => 'GDL-TRK-01',
            'judul' => 'Truk box: lampu rem kiri mati',
            'deskripsi' => 'Lampu rem kiri truk box Hino tidak menyala saat pemeriksaan sebelum berangkat.',
            'teknisi' => ['teknik'], 'tahap' => 'Dibatalkan', 'alasan' => 'Ditangani bengkel resmi Hino saat servis berkala minggu ini.',
        ],
        [
            'hari' => 86, 'jam' => 7, 'menit' => 30, 'prioritas' => 'Tinggi', 'aset' => 'UTL-PNL-01',
            'judul' => 'Panel LVMDP: titik panas di busbar fasa R',
            'deskripsi' => 'Hasil thermography bulanan menunjukkan suhu sambungan busbar fasa R 78°C (fasa lain 45°C). Perlu shutdown terencana.',
            'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 180, 'downtime' => 'Terencana',
            'biaya' => [['Vendor', 'Pengencangan & pembersihan busbar saat shutdown', 4200000, 'VND-001']],
            'analisis' => ['MSL-06', 'PNY-04', 'TDK-04', 'Baut sambungan busbar fasa R kendur akibat siklus panas-dingin.', 'Thermography panel utama setiap bulan dan torsi ulang baut busbar setiap shutdown tahunan.'],
            'ringkasan' => 'Shutdown Sabtu pagi: baut busbar dikencangkan dengan torsi 70 Nm, permukaan kontak dibersihkan. Thermography ulang 46°C.',
        ],
        [
            'hari' => 80, 'jam' => 10, 'pelapor' => 'pengadaan', 'kategori' => 'KK-GEDUNG', 'lokasi' => 'JKT-L15',
            'judul' => 'Permintaan kursi kerja baru untuk staf pengadaan',
            'deskripsi' => 'Dua kursi kerja sudah tidak nyaman, mohon diganti dengan yang baru.',
            'akhir' => 'Ditolak', 'alasan' => 'Bukan kerusakan fasilitas. Silakan ajukan lewat permintaan pengadaan ke bagian SDM & Umum.',
        ],
        [
            'hari' => 74, 'jam' => 8, 'menit' => 50, 'pelapor' => 'pelapor', 'kategori' => 'KK-MESIN', 'aset' => 'PRD-INJ-01',
            'judul' => 'Mesin injeksi #1 bocor oli di pompa hidrolik',
            'deskripsi' => 'Oli hidrolik merembes dari pompa utama dan menggenang di bawah mesin, level oli turun.',
            'urgensi' => 'KerjaTerhenti', 'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 210, 'downtime' => 'TidakTerencana', 'tunggu' => 1080,
            'catatanTahap' => 'Shaft seal pompa hidrolik tidak ada di stok, dipesan kilat dari PT Haitian Mesin Plastik Indonesia.',
            'sukuCadang' => [['SC-OLI-002', 2], ['SC-BRG-001', 2]],
            'biaya' => [['Vendor', 'Shaft seal pompa hidrolik (pengiriman kilat)', 2350000, 'VND-009']],
            'analisis' => ['MSL-03', 'PNY-05', 'TDK-01', 'Shaft seal pompa hidrolik utama mengeras karena suhu oli sering di atas 55°C.', 'Bersihkan cooler oli hidrolik bulanan dan pasang alarm suhu oli 50°C.'],
            'ringkasan' => 'Shaft seal dan bearing pompa hidrolik diganti, oli hidrolik ditambah 2 pail, cooler oli dibersihkan. Uji produksi 2 jam tidak bocor.',
            'konfirmasi' => 'pelapor', 'rating' => 4, 'ulasan' => 'Agak lama karena menunggu part, tapi sekarang beres.',
        ],
        [
            'hari' => 67, 'jam' => 9, 'menit' => 20, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-KOMPUTER', 'lokasi' => 'JKT-L12',
            'judul' => 'Tidak bisa akses ERP dari lantai 12',
            'deskripsi' => 'Seluruh komputer di ruang keuangan tidak bisa membuka ERP dan email sejak pukul 09.00, padahal hari ini batas pembayaran pajak.',
            'urgensi' => 'KerjaTerhenti', 'prioritas' => 'Tinggi', 'teknisi' => ['it'], 'tahap' => 'Ditutup', 'durasi' => 60,
            'analisis' => ['MSL-09', 'PNY-11', 'TDK-01', 'Switch akses lantai 12 hang akibat modul daya bermasalah; patch cord uplink juga rusak.', 'Pasang pemantauan SNMP untuk switch akses lantai kantor.'],
            'ringkasan' => 'Switch akses lantai 12 di-restart dan patch cord uplink diganti. Koneksi ERP pulih, pemantauan SNMP ditambahkan.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Tertolong sekali, pembayaran pajak tepat waktu.',
        ],
        [
            'hari' => 60, 'jam' => 8, 'prioritas' => 'Tinggi', 'aset' => 'PRD-BLW-01',
            'judul' => 'Blow molding #1: bearing motor extruder panas',
            'deskripsi' => 'Patroli menemukan bearing sisi belakang motor extruder 92°C dan bunyi kasar. Dijadwalkan perbaikan saat ganti mold.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 240, 'downtime' => 'TidakTerencana',
            'sukuCadang' => [['SC-BRG-002', 1], ['SC-GRS-001', 1]],
            'analisis' => ['MSL-04', 'PNY-02', 'TDK-01', 'Grease bearing mengering karena interval pelumasan terlewat saat produksi puncak.', 'Masukkan pelumasan bearing motor extruder ke jadwal PM dua mingguan.'],
            'ringkasan' => 'Bearing 6310 sisi belakang diganti, bearing depan dilumasi ulang. Suhu bearing 58°C setelah 2 jam operasi.',
        ],
        // ── Juli–Agustus 2026 ──
        [
            'hari' => 55, 'jam' => 13, 'menit' => 30, 'pelapor' => 'gudang', 'kategori' => 'KK-LISTRIK', 'lokasi' => 'CKR-GBB',
            'judul' => 'Lampu gudang bahan baku banyak yang mati',
            'deskripsi' => 'Lorong rak C dan D gelap, operator forklift kesulitan membaca label palet.',
            'prioritas' => 'Normal', 'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 90,
            'sukuCadang' => [['SC-LMP-001', 2]],
            'ringkasan' => 'Dua lampu high bay diganti, dua titik lain driver LED direset dan konektor diperbaiki.',
            'konfirmasi' => 'keluhan', 'rating' => 5, 'ulasan' => 'Sudah terang.',
        ],
        [
            'hari' => 50, 'jam' => 7, 'menit' => 20, 'pelapor' => 'pelapor', 'kategori' => 'KK-MESIN', 'aset' => 'PRD-CRS-01',
            'judul' => 'Crusher tidak mau jalan',
            'deskripsi' => 'Tombol start crusher ditekan tapi motor tidak berputar.',
            'urgensi' => 'MenggangguKerja', 'akhir' => 'Dibatalkan', 'alasan' => 'Ternyata tombol emergency stop masih tertekan, mesin sudah jalan normal.',
        ],
        [
            'hari' => 46, 'jam' => 10, 'prioritas' => 'Tinggi', 'aset' => 'IT-NET-01',
            'judul' => 'Core switch: uplink lantai 12 error CRC',
            'deskripsi' => 'Pemantauan menunjukkan error CRC meningkat pada port uplink ke lantai 12, pengguna mengeluh lambat.',
            'teknisi' => ['it'], 'tahap' => 'Ditutup', 'durasi' => 60,
            'sukuCadang' => [['SC-SFP-001', 1]],
            'analisis' => ['MSL-09', 'PNY-11', 'TDK-01', 'Modul SFP+ uplink mengalami degradasi daya optik.', 'Pantau daya optik transceiver lewat SNMP dan simpan modul cadangan.'],
            'ringkasan' => 'Modul SFP+ 10G SR diganti, kabel fiber dibersihkan, error CRC nol setelah 24 jam.',
        ],
        [
            'hari' => 41, 'jam' => 14, 'menit' => 10, 'pelapor' => 'pelapor', 'kategori' => 'KK-HVAC', 'aset' => 'PRD-AC-01',
            'judul' => 'AC ruang kontrol produksi mati total',
            'deskripsi' => 'AC ruang kontrol tidak menyala, PLC dan HMI di ruang itu mulai panas.',
            'urgensi' => 'MenggangguKerja', 'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 90,
            'sukuCadang' => [['SC-KAP-001', 1]],
            'ringkasan' => 'Kapasitor kompresor diganti dan terminal kabel outdoor dikencangkan, AC dingin kembali.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Ruang kontrol adem lagi.',
        ],
        [
            'hari' => 36, 'jam' => 9, 'menit' => 50, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-LIFT', 'aset' => 'GDG-LFT-02',
            'judul' => 'Lift #2 berdecit saat bergerak',
            'deskripsi' => 'Lift #2 berbunyi decit keras di antara lantai 3 sampai 6, penumpang khawatir.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 120, 'downtime' => 'TidakTerencana',
            'biaya' => [['Vendor', 'Pelumasan rel & penggantian guide shoe', 3600000, 'VND-004']],
            'analisis' => ['MSL-04', 'PNY-02', 'TDK-07', 'Guide shoe kereta aus dan rel kurang pelumas.', 'Vendor wajib melumasi rel setiap kunjungan bulanan dan melaporkannya.'],
            'ringkasan' => 'Vendor mengganti 4 guide shoe dan melumasi rel pemandu; uji jalan tanpa bunyi.',
            'konfirmasi' => 'pelapor', 'rating' => 4, 'ulasan' => 'Sudah halus lagi.',
        ],
        [
            'hari' => 31, 'jam' => 8, 'menit' => 30, 'prioritas' => 'Tinggi', 'aset' => 'UTL-GEN-01',
            'judul' => 'Genset utama: rembesan solar di fuel line',
            'deskripsi' => 'Saat pemeriksaan harian ditemukan rembesan solar di sambungan fuel line dekat filter.',
            'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 90,
            'sukuCadang' => [['SC-FLT-002', 1]],
            'analisis' => ['MSL-12', 'PNY-05', 'TDK-01', 'O-ring rumah filter solar retak.', 'Ganti O-ring setiap penggantian filter solar.'],
            'ringkasan' => 'Filter solar beserta O-ring rumah filter diganti, sambungan fuel line dikencangkan, uji jalan 15 menit tanpa rembesan.',
        ],
        [
            'hari' => 27, 'jam' => 10, 'menit' => 40, 'pelapor' => 'pelapor', 'kategori' => 'KK-MESIN', 'aset' => 'PRD-INJ-03',
            'judul' => 'Mesin injeksi #3 heater band zona 2 putus',
            'deskripsi' => 'Zona 2 tidak panas, alarm deviasi suhu. Mesin berhenti di tengah order preform.',
            'urgensi' => 'KerjaTerhenti', 'teknisi' => ['listrik'], 'tahap' => 'Ditutup', 'durasi' => 75, 'downtime' => 'TidakTerencana',
            'sukuCadang' => [['SC-HTR-001', 1]],
            'ringkasan' => 'Heater band zona 2 diganti, arus heater diuji 5,4 A sesuai spesifikasi.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Top, cepat.',
        ],
        [
            'hari' => 24, 'jam' => 15, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-HVAC', 'aset' => 'GDG-AC-02',
            'judul' => 'AC ruang direksi kurang dingin',
            'deskripsi' => 'Sekretariat direksi meminta dicek karena AC ruang direksi terasa kurang dingin.',
            'akhir' => 'Ditolak', 'alasan' => 'Duplikat: unit ini sudah masuk jadwal servis AC berkala minggu ini.',
        ],
        [
            'hari' => 20, 'jam' => 8, 'menit' => 40, 'prioritas' => 'Normal', 'aset' => 'GDL-FRK-02',
            'judul' => 'Forklift #2 kebocoran radiator',
            'deskripsi' => 'Air radiator forklift #2 berkurang cepat, ada tetesan di bawah radiator.',
            'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 120, 'downtime' => 'TidakTerencana',
            'biaya' => [['Vendor', 'Perbaikan core radiator', 1950000, 'VND-010']],
            'analisis' => ['MSL-12', 'PNY-01', 'TDK-07', 'Core radiator keropos di dua sirip bawah.', 'Gunakan coolant sesuai spesifikasi, bukan air biasa.'],
            'ringkasan' => 'Radiator dibongkar dan core diperbaiki di bengkel TMHI, diisi coolant baru, uji jalan 1 jam normal.',
        ],
        // ── September 2026 ──
        [
            'hari' => 17, 'jam' => 13, 'menit' => 30, 'pelapor' => 'manajer.aset', 'kategori' => 'KK-KOMPUTER', 'aset' => 'IT-PC-01',
            'judul' => 'PC workstation CAD engineering lambat dan sering hang',
            'deskripsi' => 'Tim engineering melaporkan PC CAD hang saat membuka desain mold tutup galon, pekerjaan revisi mold tertunda.',
            'teknisi' => ['it'], 'tahap' => 'Ditutup', 'durasi' => 120,
            'biaya' => [['Lainnya', 'RAM DDR4 32 GB (2×16 GB)', 1650000, null]],
            'ringkasan' => 'RAM ditambah menjadi 64 GB, driver kartu grafis diperbarui, SSD dibersihkan. File assembly besar terbuka tanpa hang.',
            'konfirmasi' => 'keluhan', 'rating' => 5, 'ulasan' => 'Jauh lebih cepat.',
        ],
        [
            'hari' => 13, 'jam' => 9, 'menit' => 10, 'pelapor' => 'pelapor', 'kategori' => 'KK-K3', 'aset' => 'K3-HYD-01',
            'judul' => 'Kebocoran pipa hydrant dekat gudang bahan baku',
            'deskripsi' => 'Pipa hydrant di samping gudang bahan baku menyemburkan air dari sambungan flange, tekanan header turun.',
            'urgensi' => 'Berbahaya', 'teknisi' => ['teknik'], 'tahap' => 'Ditutup', 'durasi' => 240, 'downtime' => 'TidakTerencana',
            'biaya' => [['Vendor', 'Penggantian pipa 4" dan flange sistem hydrant', 5400000, 'VND-008']],
            'analisis' => ['MSL-12', 'PNY-05', 'TDK-07', 'Gasket flange getas dan pipa berkarat di titik sambungan.', 'Inspeksi visual jalur pipa hydrant setiap bulan dan cat ulang pipa yang berkarat.'],
            'ringkasan' => 'Satu segmen pipa 4" dan gasket flange diganti oleh PT Proteksi Api Sentosa, sistem diuji tekan 10 bar selama 1 jam tanpa kebocoran.',
            'konfirmasi' => 'pelapor', 'rating' => 5, 'ulasan' => 'Terima kasih, tanggap sekali.',
        ],
        [
            'hari' => 10, 'jam' => 10, 'prioritas' => 'Rendah', 'aset' => 'IT-CCT-02',
            'judul' => 'CCTV menara: dua kamera lobby buram berembun',
            'deskripsi' => 'Dua kamera outdoor di pintu masuk lobby buram karena embun di dalam housing.',
            'teknisi' => ['it'], 'tahap' => 'Selesai', 'durasi' => 60,
            'biaya' => [['Lainnya', 'Housing kamera outdoor IP66 (2 unit)', 600000, null]],
            'ringkasan' => 'Housing kedua kamera diganti dan diberi silica gel, gambar jernih siang dan malam.',
            'konfirmasi' => ['perangkat', 'Rahmat Hidayat', 'Komandan Regu Keamanan'],
        ],
        [
            'hari' => 8, 'jam' => 10, 'menit' => 30, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-HVAC', 'aset' => 'GDG-AC-03',
            'judul' => 'AC ruang keuangan bocor air',
            'deskripsi' => 'Unit indoor AC ruang keuangan meneteskan air ke lemari arsip.',
            'teknisi' => ['teknik'], 'tahap' => 'Selesai', 'durasi' => 90,
            'sukuCadang' => [['SC-FLT-005', 2]],
            'ringkasan' => 'Drain pan dan saluran drain dibersihkan dari lumut, filter diganti, uji 1 jam tidak menetes.',
            'konfirmasi' => 'menunggu',
        ],
        [
            'hari' => 6, 'jam' => 14, 'menit' => 20, 'pelapor' => 'gudang', 'kategori' => 'KK-PRINTER', 'aset' => 'IT-PRN-03',
            'judul' => 'Printer label gudang error "head open"',
            'deskripsi' => 'Printer label menampilkan pesan head open padahal tutup sudah terkunci, label pengiriman tidak bisa dicetak.',
            'teknisi' => ['it'], 'tahap' => 'Selesai', 'durasi' => 45,
            'ringkasan' => 'Sensor head open dibersihkan dan kabel sensor yang terjepit dirapikan. Uji cetak 100 label normal.',
            'konfirmasi' => 'menunggu',
        ],
        [
            'hari' => 5, 'jam' => 8, 'menit' => 15, 'prioritas' => 'Normal', 'aset' => 'PRD-CRS-01',
            'judul' => 'Crusher: pisau tumpul, hasil gilingan kasar',
            'deskripsi' => 'Hasil gilingan runner banyak yang kasar dan menyumbat hopper mesin injeksi.',
            'teknisi' => ['teknik'], 'tahap' => 'Selesai', 'durasi' => 180,
            'biaya' => [['Lainnya', 'Jasa asah pisau crusher (bengkel luar)', 1100000, null]],
            'ringkasan' => 'Pisau putar dan pisau tetap dibongkar, diasah di bengkel luar, celah pisau disetel 0,3 mm.',
        ],
        [
            'hari' => 4, 'jam' => 9, 'menit' => 20, 'pelapor' => 'pelapor', 'kategori' => 'KK-LISTRIK', 'aset' => 'UTL-PMP-02',
            'judul' => 'Pompa air bersih #2 bocor di seal, air menggenang',
            'deskripsi' => 'Air menyembur dari seal pompa #2 dan menggenang di ruang utilitas. Pompa sudah kami matikan, pompa #1 masih jalan.',
            'teknisi' => ['teknik'], 'tahap' => 'MenungguSukuCadang', 'durasi' => 120,
            'sukuCadang' => [['SC-SEL-001', 1]],
            'catatanTahap' => 'Mechanical seal tersedia di stok, tetapi shaft sleeve aus; menunggu shaft sleeve dari PT Sumber Suku Cadang Industri.',
        ],
        [
            'hari' => 3, 'jam' => 10, 'menit' => 30, 'pelapor' => 'pengadaan', 'kategori' => 'KK-GEDUNG', 'lokasi' => 'JKT-L15',
            'judul' => 'Wastafel pantry lantai 15 mampet',
            'deskripsi' => 'Air di wastafel pantry tidak turun dan mulai berbau.',
            'akhir' => 'Ditinjau',
        ],
        [
            'hari' => 2, 'jam' => 10, 'prioritas' => 'Normal', 'aset' => 'UTL-PNL-02',
            'judul' => 'Panel SDP produksi A: ganti kontaktor kapasitor bank',
            'deskripsi' => 'Kontaktor step 3 kapasitor bank berbunyi dan cos φ turun ke 0,84. Penggantian perlu shutdown panel.',
            'teknisi' => ['listrik'], 'tahap' => 'Dijeda', 'durasi' => 60, 'jadwalMulaiHari' => -1,
            'sukuCadang' => [['SC-KNT-001', 1]],
            'catatanTahap' => 'Pemeriksaan selesai dan kontaktor sudah disiapkan; penggantian menunggu shutdown panel hari Sabtu pukul 13.00.',
        ],
        [
            'hari' => 1, 'jam' => 8, 'menit' => 5, 'pelapor' => 'pelapor', 'kategori' => 'KK-KOMPUTER', 'aset' => 'IT-NET-03',
            'judul' => 'Jaringan kantor pabrik putus-putus sejak pagi',
            'deskripsi' => 'Komputer admin produksi dan printer kantor pabrik sering kehilangan koneksi, input hasil produksi ke ERP gagal.',
            'urgensi' => 'MenggangguKerja', 'prioritas' => 'Tinggi', 'teknisi' => ['it'], 'tahap' => 'MenungguVerifikasi', 'durasi' => 90,
            'analisis' => ['MSL-09', 'PNY-04', 'TDK-04', 'Konektor RJ45 uplink switch distribusi pabrik korosi karena kelembapan ruang panel.', 'Pindahkan switch ke rak tertutup dengan kipas dan periksa konektor setiap PM.'],
            'ringkasan' => 'Konektor uplink dikrimping ulang, port switch dipindah, loop STP dibersihkan. Koneksi stabil selama 3 jam pemantauan.',
        ],
        [
            'hari' => 1, 'jam' => 13, 'menit' => 10, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-LISTRIK', 'lokasi' => 'JKT-L12',
            'judul' => 'Lampu ruang keuangan berkedip dan mati dua titik',
            'deskripsi' => 'Dua lampu panel LED di atas meja kasir berkedip lalu mati, meja jadi gelap.',
            'prioritas' => 'Normal', 'teknisi' => ['listrik'], 'tahap' => 'MenungguVerifikasi', 'durasi' => 45,
            'ringkasan' => 'Driver LED dua panel lampu diganti dengan unit cadangan kantor, lampu menyala stabil.',
        ],
        // ── Hari ini ──
        [
            'menitLalu' => 60, 'jam' => 7, 'menit' => 30, 'prioritas' => 'Tinggi', 'aset' => 'UTL-KMP-02',
            'judul' => 'Kompresor #2: tekanan keluaran turun ke 5,8 bar',
            'deskripsi' => 'Tekanan keluaran kompresor #2 turun dari setting 7 bar ke 5,8 bar, arus motor normal. Kompresor #1 sementara menanggung beban.',
            'teknisi' => ['teknik'], 'tahap' => 'Dikerjakan', 'terima' => 10, 'mulai' => 20,
            'sukuCadang' => [['SC-FLT-003', 1]],
        ],
        [
            'menitLalu' => 70, 'jam' => 8, 'pelapor' => 'gudang', 'kategori' => 'KK-LISTRIK', 'lokasi' => 'CKR-GSC',
            'judul' => 'Lampu high bay gudang suku cadang mati empat titik',
            'deskripsi' => 'Empat lampu di lorong rak B dan C gudang suku cadang mati, pengambilan barang harus memakai senter.',
            'prioritas' => 'Normal', 'teknisi' => ['listrik'], 'tahap' => 'Diterima', 'respons' => 15, 'terima' => 15,
            'sukuCadang' => [['SC-LMP-001', 4]],
        ],
        [
            'menitLalu' => 50, 'jam' => 8, 'menit' => 15, 'prioritas' => 'Normal', 'aset' => 'IT-NAS-01',
            'judul' => 'NAS backup: job backup malam gagal, volume hampir penuh',
            'deskripsi' => 'Laporan pagi menunjukkan job backup ERP semalam gagal; volume NAS terpakai 96%.',
            'teknisi' => ['it'], 'tahap' => 'Dikerjakan', 'terima' => 10, 'mulai' => 15,
        ],
        [
            'menitLalu' => 40, 'jam' => 8, 'menit' => 40, 'pelapor' => 'pelapor', 'kategori' => 'KK-MESIN', 'aset' => 'PRD-INJ-02',
            'judul' => 'Mesin injeksi #2 bocor oli hidrolik di area clamp',
            'deskripsi' => 'Oli hidrolik menetes dari selang di area clamp ke lantai, lantai licin. Mesin kami hentikan menunggu teknisi.',
            'urgensi' => 'KerjaTerhenti', 'prioritas' => 'Kritis', 'teknisi' => ['teknik'], 'tahap' => 'Ditugaskan', 'respons' => 12,
        ],
        [
            'menitLalu' => 35, 'jam' => 9, 'menit' => 10, 'pelapor' => 'pelapor.kantor', 'kategori' => 'KK-PRINTER', 'aset' => 'IT-PRN-01',
            'judul' => 'Printer lantai 12 tidak terdeteksi di jaringan',
            'deskripsi' => 'Semua komputer di lantai 12 tidak bisa mencetak, printer tidak muncul di daftar perangkat.',
            'prioritas' => 'Normal', 'teknisi' => ['it'], 'tahap' => 'Ditugaskan', 'respons' => 20,
        ],
        [
            'menitLalu' => 10, 'jam' => 10, 'pelapor' => 'kalibrasi', 'kategori' => 'KK-LISTRIK', 'aset' => 'LAB-OVN-01',
            'judul' => 'Oven laboratorium suhu naik-turun ±8°C',
            'deskripsi' => 'Oven Yamato untuk uji penuaan produk tidak stabil, suhu berayun 62–78°C dari setting 70°C.',
            'akhir' => 'Baru',
        ],
        [
            'menitLalu' => 5, 'jam' => 10, 'menit' => 30, 'pelapor' => 'pelapor', 'kategori' => 'KK-GEDUNG', 'lokasi' => 'CKR-PRDA-BLW',
            'judul' => 'Lantai area blow molding retak dan licin',
            'deskripsi' => 'Lantai epoxy di depan mesin blow molding #2 retak memanjang dan licin terkena tetesan oli, rawan terpeleset.',
            'urgensi' => 'Berbahaya', 'akhir' => 'Baru',
        ],
    ];

    /** @var array<string, string> Id tingkat layanan per kode. */
    private array $tingkatLayanan = [];

    /** @var array<string, string> Id kategori keluhan per kode. */
    private array $kategoriKeluhan = [];

    /** @var array<string, string> Id kode kegagalan per kode. */
    private array $kodeKegagalan = [];

    /** @var array<string, object{Id: string, LokasiId: string, UnitPengelolaId: string|null}> */
    private array $aset = [];

    /** @var array<string, string> Email koordinator per Id unit pengelola. */
    private array $koordinatorUnit = [];

    /** @var array<string, list<array{0: CarbonImmutable, 1: CarbonImmutable}>> Sesi kerja yang sudah tercatat per teknisi. */
    private array $sesiTeknisi = [];

    /** @var array<string, bool> Pelapor yang tanda tangannya sudah tersimpan di profil. */
    private array $sudahBertandaTangan = [];

    /** @var array<string, float> Jumlah suku cadang yang dipakai atau direservasi seeder ini. */
    private array $pemakaianSukuCadang = [];

    private ?string $berkasTandaTangan = null;

    public function run(): void
    {
        $this->masukKonteks();

        if ($this->sudahDisemai('Keluhan')) {
            return;
        }

        mt_srand(46);
        $this->siapkanAcuan();
        $this->semaiTingkatLayanan();
        $this->semaiKategoriKeluhan();
        $this->semaiKodeKegagalan();

        try {
            foreach ($this->kasusTerurut() as $kasus) {
                $this->jalankanKasus($kasus);
            }
        } finally {
            if ($this->berkasTandaTangan !== null && is_file($this->berkasTandaTangan)) {
                unlink($this->berkasTandaTangan);
            }
        }

        $this->periksaPemakaianSukuCadang();
    }

    private function siapkanAcuan(): void
    {
        $organisasiId = $this->organisasiId();

        foreach (DB::table('Aset')->where('OrganisasiId', $organisasiId)->get(['Id', 'KodeAset', 'LokasiId', 'UnitPengelolaId']) as $baris) {
            $this->aset[(string) $baris->KodeAset] = $baris;
        }

        $this->koordinatorUnit = [
            $this->idDari('UnitOrganisasi', ['Kode' => 'TEKFAS']) => 'koordinator.teknik'.self::DOMAIN_EMAIL,
            $this->idDari('UnitOrganisasi', ['Kode' => 'IT']) => 'koordinator.it'.self::DOMAIN_EMAIL,
        ];
    }

    /** SLA dengan aturan per prioritas dan tiga tahap eskalasi, disusun manajer aset di awal pemakaian. */
    private function semaiTingkatLayanan(): void
    {
        $peranKoordinator = $this->idDari('Peran', ['Kode' => 'KOORDINATOR-PEMELIHARAAN']);
        $manajer = $this->pengguna('manajer.aset'.self::DOMAIN_EMAIL);
        $direktur = $this->pengguna('penyetuju'.self::DOMAIN_EMAIL);

        foreach (self::TINGKAT_LAYANAN as $urutan => [$kode, $nama, $deskripsi, $hariKerja, $jamMulai, $jamSelesai, $hariLibur, $aturan, $keDireksi]) {
            $eskalasi = [
                ['Tahap' => 1, 'Pemicu' => 'Menjelang', 'SetelahMenit' => 30, 'PeranId' => $peranKoordinator, 'PenggunaId' => null, 'Kanal' => ['InApp'], 'Aktif' => true],
                ['Tahap' => 2, 'Pemicu' => 'Terlewati', 'SetelahMenit' => 0, 'PeranId' => null, 'PenggunaId' => $manajer, 'Kanal' => ['InApp', 'Email'], 'Aktif' => true],
            ];

            if ($keDireksi) {
                $eskalasi[] = ['Tahap' => 3, 'Pemicu' => 'Terlewati', 'SetelahMenit' => 240, 'PeranId' => null, 'PenggunaId' => $direktur, 'Kanal' => ['InApp', 'Email', 'WhatsApp'], 'Aktif' => true];
            }

            $tingkatLayanan = $this->padaWaktu($this->hariLalu(360 - $urutan, 9, 10 * $urutan), fn () => app(SimpanTingkatLayanan::class)->jalankan([
                'Kode' => $kode,
                'Nama' => $nama,
                'Deskripsi' => $deskripsi,
                'HariKerja' => $hariKerja,
                'JamKerjaMulai' => $jamMulai,
                'JamKerjaSelesai' => $jamSelesai,
                'MemperhitungkanHariLibur' => $hariLibur,
                'Aktif' => true,
                'Aturan' => array_map(
                    fn (string $prioritas, array $target): array => [
                        'Prioritas' => $prioritas,
                        'MenitRespons' => $target[0],
                        'MenitPenyelesaian' => $target[1],
                        'MenghitungJamKerja' => $target[2],
                    ],
                    array_keys($aturan),
                    array_values($aturan),
                ),
                'Eskalasi' => $eskalasi,
            ]), 'manajer.aset'.self::DOMAIN_EMAIL);

            $this->tingkatLayanan[$kode] = $tingkatLayanan->Id;
        }
    }

    /** Kategori bertingkat: induk per unit pengelola, anak menentukan SLA dan prioritas bawaan. */
    private function semaiKategoriKeluhan(): void
    {
        $peranKoordinator = $this->idDari('Peran', ['Kode' => 'KOORDINATOR-PEMELIHARAAN']);

        foreach (self::KATEGORI_KELUHAN as [$kode, $nama, $induk, $unit, $sla, $prioritas, $asetWajib]) {
            $kategori = $this->padaWaktu($this->hariLalu(357, 10), fn () => app(SimpanKategoriKeluhan::class)->jalankan([
                'IndukId' => $induk === null ? null : $this->kategoriKeluhan[$induk],
                'Kode' => $kode,
                'Nama' => $nama,
                'TingkatLayananId' => $this->tingkatLayanan[$sla],
                'PrioritasBawaan' => $prioritas,
                'AsetWajib' => $asetWajib,
                'PeranPenanggungJawabId' => $peranKoordinator,
                'UnitPengelolaId' => $this->idDari('UnitOrganisasi', ['Kode' => $unit]),
                'Aktif' => true,
            ]), 'manajer.aset'.self::DOMAIN_EMAIL);

            $this->kategoriKeluhan[$kode] = $kategori->Id;
        }
    }

    /** Pustaka kode masalah–penyebab–tindakan untuk analisis kegagalan. */
    private function semaiKodeKegagalan(): void
    {
        foreach (self::KODE_KEGAGALAN as [$kode, $jenis, $nama, $kategoriAset, $keterangan]) {
            $hasil = $this->padaWaktu($this->hariLalu(357, 11), fn () => app(SimpanKodeKegagalan::class)->jalankan([
                'KategoriAsetId' => $kategoriAset === null ? null : $this->idDari('KategoriAset', ['Kode' => $kategoriAset]),
                'Kode' => $kode,
                'Nama' => $nama,
                'Jenis' => $jenis,
                'Keterangan' => $keterangan,
                'Aktif' => true,
            ]), 'koordinator.teknik'.self::DOMAIN_EMAIL);

            $this->kodeKegagalan[$kode] = $hasil->Id;
        }
    }

    /**
     * Kasus diurutkan menurut waktu lapor supaya nomor dokumen mengikuti tanggal.
     *
     * @return list<array<string, mixed>>
     */
    private function kasusTerurut(): array
    {
        $kasus = array_map(fn (array $satu): array => [...$satu, 'waktuLapor' => $this->waktuLapor($satu)], self::KASUS);
        usort($kasus, fn (array $a, array $b): int => $a['waktuLapor'] <=> $b['waktuLapor']);

        return $kasus;
    }

    /** @param array<string, mixed> $kasus */
    private function waktuLapor(array $kasus): CarbonImmutable
    {
        $jam = (int) ($kasus['jam'] ?? 9);
        $menit = (int) ($kasus['menit'] ?? 0);

        // Kejadian hari ini: pada jamnya bila sudah lewat, tetapi selalu cukup lama
        // sebelum sekarang supaya seluruh langkah lanjutannya tidak jatuh di masa depan.
        if (isset($kasus['menitLalu'])) {
            return $this->hariLalu(0, $jam, $menit)->min(CarbonImmutable::now()->subMinutes((int) $kasus['menitLalu']));
        }

        $hari = (int) $kasus['hari'];
        $waktu = $this->hariLalu($hari, $jam, $menit);

        // Kantor dan bengkel libur hari Minggu: laporan dimajukan ke Sabtu.
        return $waktu->setTimezone('Asia/Jakarta')->isSunday() ? $this->hariLalu($hari + 1, $jam, $menit) : $waktu;
    }

    /** @param array<string, mixed> $kasus */
    private function jalankanKasus(array $kasus): void
    {
        /** @var CarbonImmutable $waktu */
        $waktu = $kasus['waktuLapor'];

        if (! isset($kasus['pelapor'])) {
            $unit = $this->asetKasus($kasus)?->UnitPengelolaId;
            $this->jalankanPerintahKerja($kasus, null, $waktu, $this->koordinatorUntuk($unit), null);

            return;
        }

        $pelapor = $kasus['pelapor'].self::DOMAIN_EMAIL;
        $urgensi = $kasus['urgensi'] ?? null;
        $keluhan = $this->padaWaktu($waktu, fn (): Keluhan => app(BuatKeluhan::class)->jalankan([
            'KategoriKeluhanId' => $this->kategoriKeluhan[$kasus['kategori']],
            'AsetId' => $this->asetKasus($kasus)?->Id,
            'LokasiId' => $this->lokasiKasus($kasus),
            'Judul' => $kasus['judul'],
            'Deskripsi' => $kasus['deskripsi'],
            'UsulanUrgensi' => $urgensi,
            'Prioritas' => null,
            'Sumber' => $urgensi === null ? 'Web' : 'Lapangan',
        ], $this->pengguna($pelapor)), $pelapor);

        $akhir = $kasus['akhir'] ?? null;

        if ($akhir === 'Baru') {
            return;
        }

        if ($akhir === 'Dibatalkan') {
            $this->ubahStatusKeluhan($keluhan->Id, StatusKeluhan::Dibatalkan, (string) $kasus['alasan'], $waktu->addMinutes(mt_rand(25, 90)), $pelapor);

            return;
        }

        $koordinator = $this->koordinatorUntuk($keluhan->UnitPengelolaId);
        $waktu = $this->dalamJamKerja($waktu->addMinutes((int) ($kasus['respons'] ?? mt_rand(10, 40))));
        $this->ubahStatusKeluhan($keluhan->Id, StatusKeluhan::Ditinjau, 'Diverifikasi koordinator dan diteruskan ke tim teknik.', $waktu, $koordinator);

        $prioritas = $kasus['prioritas'] ?? null;
        if ($prioritas !== null && $prioritas !== $this->keluhan($keluhan->Id)->Prioritas) {
            $this->padaWaktu($waktu->addMinute(), function () use ($keluhan, $prioritas): void {
                $terkini = $this->keluhan($keluhan->Id);
                app(UbahPrioritasKeluhan::class)->jalankan(
                    $terkini,
                    PrioritasKeluhan::from($prioritas),
                    'Disesuaikan koordinator setelah meninjau dampak ke operasional.',
                    $terkini->Versi,
                );
            }, $koordinator);
        }

        if ($akhir === 'Ditinjau') {
            return;
        }

        $waktu = $waktu->addMinutes(mt_rand(3, 8));

        if ($akhir === 'Ditolak') {
            $this->ubahStatusKeluhan($keluhan->Id, StatusKeluhan::Ditolak, (string) $kasus['alasan'], $waktu, $koordinator);

            return;
        }

        $this->ubahStatusKeluhan($keluhan->Id, StatusKeluhan::Diterima, 'Diterima, perintah kerja dibuat.', $waktu, $koordinator);
        $this->jalankanPerintahKerja($kasus, $keluhan->Id, $waktu->addMinutes(2), $koordinator, $pelapor);
    }

    /**
     * Alur satu perintah kerja dari dibuat sampai tahap akhirnya, lalu penuntasan keluhan asalnya.
     *
     * @param  array<string, mixed>  $kasus
     */
    private function jalankanPerintahKerja(array $kasus, ?string $keluhanId, CarbonImmutable $waktu, string $koordinator, ?string $pelapor): void
    {
        /** @var list<string> $teknisi */
        $teknisi = array_map(fn (string $nama): string => "teknisi.{$nama}".self::DOMAIN_EMAIL, $kasus['teknisi']);
        $utama = $teknisi[0];
        $aset = $this->asetKasus($kasus);
        $tahap = (string) $kasus['tahap'];
        $durasi = (int) ($kasus['durasi'] ?? 60);
        $downtime = $kasus['downtime'] ?? null;
        $jadwalMulai = isset($kasus['jadwalMulaiHari']) ? $this->hariLalu((int) $kasus['jadwalMulaiHari'], 13) : $waktu->addMinutes(30);
        $menitKerja = 0;

        $perintahKerja = $this->padaWaktu($waktu, fn (): PerintahKerja => app(BuatPerintahKerja::class)->jalankan([
            'KeluhanId' => $keluhanId,
            'Jenis' => 'Korektif',
            'Judul' => $kasus['judul'] ?? null,
            'Deskripsi' => $keluhanId === null ? $kasus['deskripsi'] : null,
            'Prioritas' => $keluhanId === null ? ($kasus['prioritas'] ?? 'Normal') : null,
            'LokasiId' => $keluhanId === null ? $this->lokasiKasus($kasus) : null,
            'DijadwalkanMulaiPada' => $jadwalMulai,
            'DijadwalkanSelesaiPada' => $jadwalMulai->addMinutes(max(60, $durasi)),
            'MembutuhkanWaktuHenti' => $downtime !== null,
            'MembutuhkanPersetujuan' => false,
            'AsetIds' => $aset === null ? [] : [$aset->Id],
        ], $this->pengguna($koordinator)), $koordinator);
        $id = $perintahKerja->Id;

        foreach ($teknisi as $urutan => $email) {
            $this->padaWaktu($waktu->addMinutes($urutan), fn () => app(TugaskanPerintahKerja::class)->jalankan(
                $this->perintahKerja($id),
                [$this->pengguna($email)],
                $urutan === 0 ? 'Pelaksana Utama' : 'Pendamping',
                false,
                $this->pengguna($koordinator),
            ), $koordinator);
        }

        if ($tahap === 'Ditugaskan') {
            return;
        }

        if ($tahap === 'Dibatalkan') {
            $this->ubahStatus($id, StatusPerintahKerja::Dibatalkan, (string) $kasus['alasan'], null, $this->dalamJamKerja($waktu->addDay()), $koordinator);

            return;
        }

        if ($keluhanId !== null && $this->keluhan($keluhanId)->Status === StatusKeluhan::Diterima->value) {
            $this->ubahStatusKeluhan($keluhanId, StatusKeluhan::Diproses, 'Teknisi ditugaskan dan sedang menangani.', $waktu->addMinutes(3), $koordinator);
        }

        $waktu = $waktu->addMinutes((int) ($kasus['terima'] ?? mt_rand(5, 20)));
        foreach ($teknisi as $urutan => $email) {
            $this->padaWaktu($waktu->addMinutes($urutan), function () use ($id, $email): void {
                $penugasan = PenugasanPerintahKerja::query()
                    ->where('PerintahKerjaId', $id)
                    ->where('PenggunaId', $this->pengguna($email))
                    ->where('Status', StatusPenugasanPerintahKerja::Ditugaskan->value)
                    ->firstOrFail();
                app(ResponsPenugasanPerintahKerja::class)->jalankan($penugasan, 'Terima', 'Segera ke lokasi.', $this->pengguna($email));
            }, $email);
        }

        if ($downtime === 'TidakTerencana' && $aset !== null) {
            $this->waktuHenti($id, $aset->Id, 'Mulai', 'TidakTerencana', 'Aset berhenti beroperasi: '.$kasus['judul'], $waktu->addMinutes(2), $utama);
        }

        if ($tahap === 'Diterima') {
            $this->reservasi($id, $kasus['sukuCadang'] ?? [], $waktu->addMinutes(5), $utama);

            return;
        }

        $mulai = $waktu->addMinutes((int) ($kasus['mulai'] ?? mt_rand(10, 45)));
        $this->ubahStatus($id, StatusPerintahKerja::Dikerjakan, 'Mulai pengerjaan di lokasi.', null, $mulai, $utama);

        if ($downtime === 'Terencana' && $aset !== null) {
            $this->waktuHenti($id, $aset->Id, 'Mulai', 'Terencana', 'Shutdown terencana: '.$kasus['judul'], $mulai->addMinute(), $utama);
        }

        $reservasi = $this->reservasi($id, $kasus['sukuCadang'] ?? [], $mulai->addMinutes(5), $utama);

        if ($tahap === 'Dikerjakan') {
            $this->sesiTeknisi[$utama][] = [$mulai, $mulai->addYear()];
            $this->padaWaktu($mulai->addMinutes(7), fn () => app(KelolaWaktuKerja::class)->jalankan(
                $this->perintahKerja($id),
                $this->pengguna($utama),
                'Mulai',
                'Pemeriksaan awal dan pengukuran.',
            ), $utama);

            return;
        }

        if (in_array($tahap, ['MenungguSukuCadang', 'Dijeda'], true)) {
            $awal = $this->sesiBebas([$utama], $mulai->addMinutes(10), $durasi);
            $this->catatSesi($id, [$utama], $awal, $durasi, 'Pemeriksaan dan pembongkaran awal.');
            $tujuan = $tahap === 'Dijeda' ? StatusPerintahKerja::Dijeda : StatusPerintahKerja::MenungguSukuCadang;
            $this->ubahStatus($id, $tujuan, (string) $kasus['catatanTahap'], null, $awal->addMinutes($durasi + 5), $utama);

            return;
        }

        $this->pakaiSukuCadang($id, $reservasi, $mulai->addMinutes(10), $utama);
        $awal = $this->sesiBebas($teknisi, $mulai->addMinutes(12), $durasi);

        if (isset($kasus['tunggu'])) {
            $bagianPertama = (int) round($durasi * 0.4);
            $menitKerja += $this->catatSesi($id, $teknisi, $awal, $bagianPertama, 'Pembongkaran dan diagnosis.');
            $jeda = $awal->addMinutes($bagianPertama + 5);
            $this->ubahStatus($id, StatusPerintahKerja::MenungguSukuCadang, (string) $kasus['catatanTahap'], null, $jeda, $utama);
            $lanjut = $this->dalamJamKerja($jeda->addMinutes((int) $kasus['tunggu']));
            $this->ubahStatus($id, StatusPerintahKerja::Dikerjakan, 'Suku cadang tiba, pekerjaan dilanjutkan.', null, $lanjut, $utama);
            $sisa = $durasi - $bagianPertama;
            $awal = $this->sesiBebas($teknisi, $lanjut->addMinutes(5), $sisa);
            $menitKerja += $this->catatSesi($id, $teknisi, $awal, $sisa, 'Pemasangan dan uji fungsi.');
            $selesai = $awal->addMinutes($sisa);
        } else {
            $menitKerja += $this->catatSesi($id, $teknisi, $awal, $durasi, 'Perbaikan dan uji fungsi.');
            $selesai = $awal->addMinutes($durasi);
        }

        if ($downtime !== null && $aset !== null) {
            $this->waktuHenti($id, $aset->Id, 'Selesai', (string) $downtime, 'Aset kembali beroperasi normal setelah perbaikan.', $selesai->addMinute(), $utama);
        }

        if (isset($kasus['analisis'])) {
            [$masalah, $penyebab, $tindakan, $akar, $pencegahan] = $kasus['analisis'];
            $this->padaWaktu($selesai->addMinutes(3), fn () => app(SimpanAnalisisKegagalan::class)->jalankan($this->perintahKerja($id), [
                'KodeMasalahId' => $this->kodeKegagalan[$masalah],
                'KodePenyebabId' => $this->kodeKegagalan[$penyebab],
                'KodeTindakanId' => $this->kodeKegagalan[$tindakan],
                'AkarMasalah' => $akar,
                'TindakanKorektif' => $kasus['ringkasan'],
                'TindakanPencegahan' => $pencegahan,
            ], $this->pengguna($utama)), $utama);
        }

        $konfirmasi = $kasus['konfirmasi'] ?? null;

        if (is_array($konfirmasi)) {
            [, $namaPenerima, $jabatanPenerima] = $konfirmasi;
            $this->padaWaktu($selesai->addMinutes(5), fn () => app(KonfirmasiPenerimaDiPerangkat::class)->jalankan(
                $this->perintahKerja($id),
                $this->pengguna($utama),
                $namaPenerima,
                $jabatanPenerima,
                $this->gambarTandaTangan(),
                null,
            ), $utama);
        }

        $waktuSerah = $selesai->addMinutes(8);
        $this->ubahStatus($id, StatusPerintahKerja::MenungguVerifikasi, 'Pekerjaan selesai, mohon diverifikasi.', (string) $kasus['ringkasan'], $waktuSerah, $utama);

        if ($tahap === 'MenungguVerifikasi') {
            return;
        }

        $waktuKonfirmasi = $waktuSerah;

        if (in_array($konfirmasi, ['pelapor', 'bermasalah'], true) && $pelapor !== null) {
            if ($konfirmasi === 'bermasalah') {
                $waktuKeberatan = $this->dalamJamKerja($waktuSerah->addMinutes(mt_rand(60, 180)));
                $this->konfirmasiPelapor($id, $pelapor, HasilKonfirmasiPenerima::MasihBermasalah, null, (string) $kasus['alasanBermasalah'], $waktuKeberatan);
                $durasiUlang = (int) ($kasus['durasiUlang'] ?? 60);
                $awalUlang = $this->sesiBebas([$utama], $this->dalamJamKerja($waktuKeberatan->addMinutes(mt_rand(40, 90))), $durasiUlang);
                $menitKerja += $this->catatSesi($id, [$utama], $awalUlang, $durasiUlang, 'Penyetelan ulang atas keberatan pelapor.');
                $waktuSerah = $awalUlang->addMinutes($durasiUlang + 5);
                $this->ubahStatus($id, StatusPerintahKerja::MenungguVerifikasi, 'Perbaikan ulang selesai.', (string) $kasus['ringkasanUlang'], $waktuSerah, $utama);
            }

            $waktuKonfirmasi = $this->dalamJamKerja($waktuSerah->addMinutes(mt_rand(20, 150)));
            $this->konfirmasiPelapor($id, $pelapor, HasilKonfirmasiPenerima::Diterima, (int) $kasus['rating'], (string) $kasus['ulasan'], $waktuKonfirmasi);
        }

        $waktuVerifikasi = $this->dalamJamKerja($waktuKonfirmasi->addMinutes(mt_rand(15, 90)));
        $this->catatBiaya($id, $kasus['biaya'] ?? [], $menitKerja, $waktuVerifikasi, $koordinator);
        $this->ubahStatus($id, StatusPerintahKerja::Selesai, 'Hasil pekerjaan diverifikasi koordinator.', null, $waktuVerifikasi->addMinutes(2), $koordinator);

        if ($keluhanId !== null) {
            $this->tuntaskanKeluhan($kasus, $keluhanId, $pelapor, $koordinator, $waktuVerifikasi->addMinutes(4));
        }

        if ($tahap === 'Ditutup') {
            $waktuTutup = $this->dalamJamKerja($waktuVerifikasi->addDays(mt_rand(1, 3))->setTimezone('Asia/Jakarta')->setTime(10, mt_rand(0, 50))->utc());
            if ($this->sudahLewat($waktuTutup)) {
                $this->ubahStatus($id, StatusPerintahKerja::Ditutup, 'Dokumen dan biaya lengkap, pekerjaan ditutup.', null, $waktuTutup, $koordinator);
            }
        }
    }

    /**
     * Keluhan yang pelapornya sudah menjawab di tahap perintah kerja tertutup otomatis saat
     * verifikasi; selebihnya koordinator menandai Selesai lalu menunggu jawaban pelapor.
     *
     * @param  array<string, mixed>  $kasus
     */
    private function tuntaskanKeluhan(array $kasus, string $keluhanId, ?string $pelapor, string $koordinator, CarbonImmutable $waktu): void
    {
        if ($this->keluhan($keluhanId)->Status !== StatusKeluhan::Diproses->value || $pelapor === null) {
            return;
        }

        $nomorPerintahKerja = PerintahKerja::query()->withoutGlobalScope(ScopeLingkup::class)
            ->where('KeluhanId', $keluhanId)->latest('DibuatPada')->orderByDesc('Id')->value('Nomor');
        $this->ubahStatusKeluhan($keluhanId, StatusKeluhan::Selesai, "Perintah kerja {$nomorPerintahKerja} selesai diverifikasi.", $waktu, $koordinator);

        $konfirmasi = $kasus['konfirmasi'] ?? null;

        if ($konfirmasi === 'keluhan') {
            $waktuJawab = $this->dalamJamKerja($waktu->addMinutes(mt_rand(120, 1200)));
            if ($this->sudahLewat($waktuJawab)) {
                $this->konfirmasiKeluhan($keluhanId, true, (int) $kasus['rating'], (string) $kasus['ulasan'], $waktuJawab, $pelapor);
            }
        }

        if ($konfirmasi === 'koordinator') {
            $waktuTutup = $this->dalamJamKerja($waktu->addDays(3));
            if ($this->sudahLewat($waktuTutup)) {
                $this->ubahStatusKeluhan($keluhanId, StatusKeluhan::Ditutup, 'Ditutup koordinator: pelapor tidak memberi tanggapan dalam 3 hari kerja.', $waktuTutup, $koordinator);
            }
        }

        if ($konfirmasi === 'belumBeres') {
            $waktuJawab = $this->dalamJamKerja($waktu->addDays(2));
            $this->konfirmasiKeluhan($keluhanId, false, null, (string) $kasus['alasanBelumBeres'], $waktuJawab, $pelapor);
            $this->jalankanPerintahKerja(
                [...$kasus['lanjutan'], 'aset' => $kasus['aset'] ?? null],
                $keluhanId,
                $this->dalamJamKerja($waktuJawab->addMinutes(mt_rand(30, 90))),
                $koordinator,
                $pelapor,
            );
        }
    }

    /**
     * @param  list<array{0: string, 1: int}>  $daftar
     * @return list<string> Id reservasi
     */
    private function reservasi(string $perintahKerjaId, array $daftar, CarbonImmutable $waktu, string $teknisi): array
    {
        $hasil = [];

        foreach ($daftar as [$kode, $jumlah]) {
            $sukuCadangId = $this->idDari('SukuCadang', ['Kode' => $kode]);
            $gudangId = DB::table('StokSukuCadang')->where('SukuCadangId', $sukuCadangId)->where('JumlahTersedia', '>', 0)->value('GudangId');

            if (! is_string($gudangId)) {
                throw new RuntimeException("Stok {$kode} tidak ditemukan di gudang mana pun.");
            }

            $this->pemakaianSukuCadang[$kode] = ($this->pemakaianSukuCadang[$kode] ?? 0) + $jumlah;
            $reservasi = $this->padaWaktu($waktu, fn (): ReservasiSukuCadang => app(BuatReservasiSukuCadang::class)->jalankan([
                'GudangId' => $gudangId,
                'SukuCadangId' => $sukuCadangId,
                'PerintahKerjaId' => $perintahKerjaId,
                'Jumlah' => $jumlah,
            ], $this->pengguna($teknisi)), $teknisi);
            $hasil[] = $reservasi->Id;
        }

        return $hasil;
    }

    /** @param list<string> $reservasiIds */
    private function pakaiSukuCadang(string $perintahKerjaId, array $reservasiIds, CarbonImmutable $waktu, string $teknisi): void
    {
        foreach ($reservasiIds as $urutan => $reservasiId) {
            $this->padaWaktu($waktu->addMinutes($urutan), fn () => app(GunakanSukuCadangPerintahKerja::class)->jalankan(
                $this->perintahKerja($perintahKerjaId),
                ReservasiSukuCadang::query()->findOrFail($reservasiId),
                'Pakai',
                $this->pengguna($teknisi),
            ), $teknisi);
        }
    }

    /**
     * Mencatat satu sesi kerja utuh untuk tiap teknisi, dicatat saat sesi berakhir.
     *
     * @param  list<string>  $teknisi
     * @return int Total menit seluruh teknisi.
     */
    private function catatSesi(string $perintahKerjaId, array $teknisi, CarbonImmutable $mulai, int $durasi, string $catatan): int
    {
        $selesai = $mulai->addMinutes($durasi);

        foreach ($teknisi as $email) {
            $this->padaWaktu($selesai, fn () => app(KelolaWaktuKerja::class)->catatSelesai(
                $this->perintahKerja($perintahKerjaId),
                $this->pengguna($email),
                $mulai,
                $selesai,
                $catatan,
            ), $email);
        }

        return $durasi * count($teknisi);
    }

    /**
     * Awal sesi paling dini yang tidak bertumpuk dengan sesi lain milik para teknisi,
     * lalu dicatat sebagai terpakai.
     *
     * @param  list<string>  $teknisi
     */
    private function sesiBebas(array $teknisi, CarbonImmutable $mulai, int $durasi): CarbonImmutable
    {
        do {
            $bergeser = false;
            $selesai = $mulai->addMinutes($durasi);

            foreach ($teknisi as $email) {
                foreach ($this->sesiTeknisi[$email] ?? [] as [$awal, $akhir]) {
                    if ($mulai->lessThan($akhir) && $selesai->greaterThan($awal)) {
                        $mulai = $akhir->addMinutes(15);
                        $bergeser = true;

                        continue 3;
                    }
                }
            }
        } while ($bergeser);

        foreach ($teknisi as $email) {
            $this->sesiTeknisi[$email][] = [$mulai, $mulai->addMinutes($durasi)];
        }

        return $mulai;
    }

    private function waktuHenti(string $perintahKerjaId, string $asetId, string $aksi, string $jenis, string $alasan, CarbonImmutable $waktu, string $teknisi): void
    {
        $this->padaWaktu($waktu, fn () => app(KelolaWaktuHentiAset::class)->jalankan(
            $this->perintahKerja($perintahKerjaId),
            $asetId,
            $aksi,
            $jenis,
            $alasan,
        ), $teknisi);
    }

    /**
     * Biaya jasa teknisi internal dari total jam kerja, ditambah biaya vendor/lainnya.
     *
     * @param  list<array{0: string, 1: string, 2: int, 3: string|null}>  $biaya
     */
    private function catatBiaya(string $perintahKerjaId, array $biaya, int $menitKerja, CarbonImmutable $waktu, string $koordinator): void
    {
        $tanggal = $waktu->setTimezone('Asia/Jakarta')->toDateString();
        $baris = [];

        if ($menitKerja > 0) {
            $jam = round($menitKerja / 60, 1);
            $baris[] = [
                'JenisBiaya' => 'TenagaKerja',
                'Deskripsi' => 'Jasa teknisi internal '.str_replace('.', ',', (string) $jam).' jam',
                'Jumlah' => round($menitKerja / 60 * self::TARIF_TEKNISI_PER_JAM, -3),
                'PenyediaId' => null,
            ];
        }

        foreach ($biaya as [$jenis, $deskripsi, $jumlah, $penyedia]) {
            $baris[] = [
                'JenisBiaya' => $jenis,
                'Deskripsi' => $deskripsi,
                'Jumlah' => $jumlah,
                'PenyediaId' => $penyedia === null ? null : $this->idDari('Penyedia', ['Kode' => $penyedia]),
            ];
        }

        foreach ($baris as $satu) {
            $this->padaWaktu($waktu, fn () => app(CatatBiayaPerintahKerja::class)->jalankan(
                $this->perintahKerja($perintahKerjaId),
                [...$satu, 'MataUang' => 'IDR', 'TanggalBiaya' => $tanggal],
                $this->pengguna($koordinator),
            ), $koordinator);
        }
    }

    private function konfirmasiPelapor(string $perintahKerjaId, string $pelapor, HasilKonfirmasiPenerima $hasil, ?int $penilaian, string $komentar, CarbonImmutable $waktu): void
    {
        $gambar = $hasil === HasilKonfirmasiPenerima::Diterima && ! isset($this->sudahBertandaTangan[$pelapor])
            ? $this->gambarTandaTangan()
            : null;

        $this->padaWaktu($waktu, fn () => app(KonfirmasiPenerimaOlehPelapor::class)->jalankan(
            $this->perintahKerja($perintahKerjaId),
            Pengguna::query()->findOrFail($this->pengguna($pelapor)),
            $hasil,
            $penilaian,
            $komentar,
            $gambar,
        ), $pelapor);

        if ($gambar !== null) {
            $this->sudahBertandaTangan[$pelapor] = true;
        }
    }

    private function konfirmasiKeluhan(string $keluhanId, bool $beres, ?int $nilai, string $ulasan, CarbonImmutable $waktu, string $pelapor): void
    {
        $this->padaWaktu($waktu, function () use ($keluhanId, $beres, $nilai, $ulasan, $pelapor): void {
            $keluhan = $this->keluhan($keluhanId);
            app(KonfirmasiPenyelesaianKeluhan::class)->jalankan($keluhan, $beres, $nilai, $ulasan, $keluhan->Versi, $this->pengguna($pelapor));
        }, $pelapor);
    }

    private function ubahStatus(string $perintahKerjaId, StatusPerintahKerja $tujuan, ?string $catatan, ?string $ringkasan, CarbonImmutable $waktu, string $email): void
    {
        $this->padaWaktu($waktu, function () use ($perintahKerjaId, $tujuan, $catatan, $ringkasan, $email): void {
            $perintahKerja = $this->perintahKerja($perintahKerjaId);
            app(UbahStatusPerintahKerja::class)->jalankan($perintahKerja, $tujuan, $catatan, $ringkasan, $perintahKerja->Versi, $this->pengguna($email));
        }, $email);
    }

    private function ubahStatusKeluhan(string $keluhanId, StatusKeluhan $tujuan, ?string $catatan, CarbonImmutable $waktu, string $email): void
    {
        $this->padaWaktu($waktu, function () use ($keluhanId, $tujuan, $catatan, $email): void {
            $keluhan = $this->keluhan($keluhanId);
            app(UbahStatusKeluhan::class)->jalankan($keluhan, $tujuan, $catatan, $keluhan->Versi, $this->pengguna($email));
        }, $email);
    }

    private function perintahKerja(string $id): PerintahKerja
    {
        return PerintahKerja::query()->withoutGlobalScope(ScopeLingkup::class)->findOrFail($id);
    }

    private function keluhan(string $id): Keluhan
    {
        return Keluhan::query()->withoutGlobalScope(ScopeLingkup::class)->findOrFail($id);
    }

    /** @param array<string, mixed> $kasus */
    private function asetKasus(array $kasus): ?object
    {
        $kode = $kasus['aset'] ?? null;

        if ($kode === null) {
            return null;
        }

        return $this->aset[$kode] ?? throw new RuntimeException("Aset {$kode} tidak ditemukan.");
    }

    /** @param array<string, mixed> $kasus */
    private function lokasiKasus(array $kasus): string
    {
        return $this->asetKasus($kasus)?->LokasiId ?? $this->idDari('Lokasi', ['Kode' => $kasus['lokasi']]);
    }

    private function koordinatorUntuk(?string $unitPengelolaId): string
    {
        return $this->koordinatorUnit[(string) $unitPengelolaId] ?? 'koordinator.teknik'.self::DOMAIN_EMAIL;
    }

    /**
     * Langkah kantor (verifikasi, konfirmasi, penutupan) digeser ke jam kerja 07.30–17.00
     * dan melompati hari Minggu, kecuali bila geserannya jatuh di masa depan.
     */
    private function dalamJamKerja(CarbonImmutable $waktu): CarbonImmutable
    {
        $lokal = $waktu->setTimezone('Asia/Jakarta');

        if ($lokal->hour < 7) {
            $lokal = $lokal->setTime(7, 30 + mt_rand(0, 25));
        } elseif ($lokal->hour >= 17) {
            $lokal = $lokal->addDay()->setTime(7, 35 + mt_rand(0, 20));
        }

        if ($lokal->isSunday()) {
            $lokal = $lokal->addDay()->setTime(7, 35 + mt_rand(0, 20));
        }

        $hasil = $lokal->utc();

        return $this->sudahLewat($hasil) ? $hasil : $waktu;
    }

    private function sudahLewat(CarbonImmutable $waktu): bool
    {
        return $waktu->lessThan(CarbonImmutable::now()->subMinutes(2));
    }

    /** Gambar tanda tangan sederhana untuk konfirmasi penerima, dibuat sekali per jalan. */
    private function gambarTandaTangan(): UploadedFile
    {
        if ($this->berkasTandaTangan === null) {
            $gambar = imagecreatetruecolor(360, 120);

            if ($gambar === false) {
                throw new RuntimeException('Gambar tanda tangan tidak dapat dibuat.');
            }

            imagefill($gambar, 0, 0, (int) imagecolorallocate($gambar, 255, 255, 255));
            $tinta = (int) imagecolorallocate($gambar, 20, 40, 110);
            imagesetthickness($gambar, 3);
            $titikSebelum = [30, 80];

            for ($x = 30; $x <= 330; $x += 6) {
                $titik = [$x, (int) (70 + 28 * sin($x / 17) * cos($x / 41))];
                imageline($gambar, $titikSebelum[0], $titikSebelum[1], $titik[0], $titik[1], $tinta);
                $titikSebelum = $titik;
            }

            $this->berkasTandaTangan = sys_get_temp_dir().'/tanda-tangan-demo-'.getmypid().'.png';
            imagepng($gambar, $this->berkasTandaTangan);
        }

        return new UploadedFile($this->berkasTandaTangan, 'tanda-tangan.png', 'image/png', null, true);
    }

    /** Pemakaian seeder ini harus tetap jauh di bawah saldo awal supaya seeder lain masih kebagian stok. */
    private function periksaPemakaianSukuCadang(): void
    {
        foreach ($this->pemakaianSukuCadang as $kode => $jumlah) {
            $saldoAwal = (float) DB::table('DetailMutasiStok')
                ->join('MutasiStok', 'MutasiStok.Id', '=', 'DetailMutasiStok.MutasiStokId')
                ->where('MutasiStok.OrganisasiId', $this->organisasiId())
                ->where('MutasiStok.Jenis', 'Penerimaan')
                ->where('DetailMutasiStok.SukuCadangId', $this->idDari('SukuCadang', ['Kode' => $kode]))
                ->sum('DetailMutasiStok.Jumlah');

            if ($jumlah >= $saldoAwal) {
                throw new RuntimeException("Pemakaian {$kode} ({$jumlah}) mencapai saldo awalnya ({$saldoAwal}).");
            }
        }
    }
}
