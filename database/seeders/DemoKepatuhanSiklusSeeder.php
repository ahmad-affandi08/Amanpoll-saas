<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Aset\Application\Actions\AssignPenanggungJawabAset;
use App\Domain\Aset\Application\Actions\BuatRelasiAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kepatuhan\Application\Actions\KelolaIntegrasiEksternal;
use App\Domain\Kepatuhan\Application\Actions\KelolaKepatuhanAset;
use App\Domain\Kepatuhan\Application\Actions\KelolaPemetaanDataEksternal;
use App\Domain\Kepatuhan\Application\Actions\KelolaSertifikasiAset;
use App\Domain\Kepatuhan\Application\Actions\KelolaStandarKepatuhan;
use App\Domain\Kepatuhan\Application\Services\LayananKepatuhan;
use App\Domain\Kepatuhan\Domain\Enums\StatusIntegrasiEksternal;
use App\Domain\Kepatuhan\Domain\Enums\StatusKepatuhanAset;
use App\Domain\Kepatuhan\Domain\Enums\StatusSertifikasiAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\KepatuhanAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SertifikasiAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\StandarKepatuhan;
use App\Domain\Kolaborasi\Application\Actions\BuatTag;
use App\Domain\Kolaborasi\Application\Actions\TambahkanTagKeEntitas;
use App\Domain\Kolaborasi\Application\Actions\TambahKomentar;
use App\Domain\Kontrak\Application\Actions\KelolaCakupanAsetKontrak;
use App\Domain\Kontrak\Application\Actions\KelolaKontrak;
use App\Domain\Kontrak\Application\Actions\KelolaLayananKontrak;
use App\Domain\Kontrak\Application\Services\LayananPeringatanKontrak;
use App\Domain\Kontrak\Domain\Enums\StatusKontrak;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Penyedia\Application\Actions\BuatPenilaianPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Persetujuan\Application\Actions\AktifkanAlurPersetujuan;
use App\Domain\Persetujuan\Application\Actions\BuatAlurPersetujuan;
use App\Domain\Persetujuan\Application\Actions\BuatTahapPersetujuan;
use App\Domain\Persetujuan\Application\Actions\SetujuiPermintaanPersetujuan;
use App\Domain\Persetujuan\Application\Actions\TolakPermintaanPersetujuan;
use App\Domain\Persetujuan\Application\Services\LayananPenyetuju;
use App\Domain\Persetujuan\Domain\Enums\StatusPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\SiklusAset\Application\Actions\BatalkanPengajuanPenghapusanAset;
use App\Domain\SiklusAset\Application\Actions\BatalkanPermintaanMutasiAset;
use App\Domain\SiklusAset\Application\Actions\BuatPengajuanPenghapusanAset;
use App\Domain\SiklusAset\Application\Actions\BuatPermintaanMutasiAset;
use App\Domain\SiklusAset\Application\Actions\BuatSerahTerimaAset;
use App\Domain\SiklusAset\Application\Actions\EksekusiMutasiAset;
use App\Domain\SiklusAset\Application\Actions\EksekusiPenghapusanAset;
use App\Domain\SiklusAset\Application\Actions\PindaiPengambilanAset;
use App\Domain\SiklusAset\Application\Actions\PutuskanDetailMutasiAset;
use App\Domain\SiklusAset\Application\Actions\SubmitPengajuanPenghapusanAset;
use App\Domain\SiklusAset\Application\Actions\SubmitPermintaanMutasiAset;
use App\Domain\SiklusAset\Application\Actions\TambahDetailMutasiAset;
use App\Domain\SiklusAset\Application\Actions\TambahDetailPenghapusanAset;
use App\Domain\SiklusAset\Application\Actions\TambahDetailSerahTerimaAset;
use App\Domain\SiklusAset\Application\Actions\TerimaSerahTerimaAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Transaksi demo kelompok kontrak, kepatuhan, siklus aset, penilaian penyedia,
 * dan kolaborasi untuk PT Sinar Nusantara Industri selama ± 12 bulan terakhir.
 *
 * Isinya: kontrak servis dengan penyedia (lift, genset, AMC TI, AC, kalibrasi,
 * proteksi kebakaran) beserta layanan, cakupan aset, dan pemakaiannya; penilaian
 * penyedia per triwulan; standar kepatuhan (SMK3, riksa uji Permenaker, PUIL/SLO,
 * ISO 9001:2015, APAR, hydrant) dengan hasil pemeriksaan per aset; sertifikat aset;
 * integrasi ERP tanpa sinkronisasi jaringan; mutasi, serah terima, dan penghapusan
 * aset lewat alur persetujuan; riwayat penanggung jawab; relasi aset; serta tag
 * dan komentar diskusi.
 *
 * Seluruh langkah memanggil Action resmi dengan jam dimundurkan (`padaWaktu`),
 * sehingga nomor dokumen, persetujuan, riwayat lokasi, notifikasi, dan audit
 * sama dengan yang dihasilkan aplikasi. Tabel penandanya `Kontrak`.
 */
final class DemoKepatuhanSiklusSeeder extends Seeder
{
    use KonteksDemo;

    private const MANAJER = 'manajer.aset@amanpoll.test';

    private const PENGADAAN = 'pengadaan@amanpoll.test';

    private const PENYETUJU = 'penyetuju@amanpoll.test';

    private const ADMIN = 'admin@amanpoll.test';

    private const KALIBRASI = 'kalibrasi@amanpoll.test';

    /**
     * Kontrak penyedia. Kunci, nomor, nama, jenis, penyedia, hari lalu mulai (null bila
     * dihitung mundur dari tanggal berakhir), hari lalu berakhir (null bila dihitung dari
     * durasi), durasi bulan, nilai, peringatan hari sebelum, hari lalu dicatat, aset
     * tercakup, catatan, layanan.
     *
     * Layanan: nama, deskripsi, kuota (null = tanpa batas), satuan, pemakaian. Pemakaian
     * berupa daftar [hari lalu, jumlah] atau pola berulang ['setiap' => hari, 'mulai' => hari
     * lalu, 'jumlah' => n].
     *
     * @var array<string, array{nomor: string, nama: string, jenis: string, penyedia: string, mulai: int|null, berakhir: int|null, bulan: int, nilai: int, peringatan: int, dicatat: int, aset: list<string>, catatan: string, layanan: list<array{0: string, 1: string, 2: int|null, 3: string, 4: array<int|string, mixed>}>}>
     */
    private const KONTRAK = [
        'GENSET' => [
            'nomor' => 'SNI/KTR/PGD/2025/031',
            'nama' => 'Kontrak Perawatan Genset Pabrik Cikarang 2025–2027',
            'jenis' => 'Pemeliharaan',
            'penyedia' => 'VND-001',
            'mulai' => 352,
            'berakhir' => null,
            'bulan' => 24,
            'nilai' => 336000000,
            'peringatan' => 60,
            'dicatat' => 350,
            'aset' => ['UTL-GEN-01', 'UTL-GEN-02'],
            'catatan' => "Servis berkala tiap 250 jam atau 3 bulan, load bank test tahunan, panggilan darurat maks. respon 4 jam.\nTermasuk oli, filter oli, dan filter solar; aki dan suku cadang mayor ditagih terpisah.",
            'layanan' => [
                ['Servis berkala 250 jam / 3 bulanan', 'Penggantian oli & filter, pemeriksaan sistem pendingin, AVR, dan ATS.', 16, 'kunjungan', ['setiap' => 91, 'mulai' => 345, 'jumlah' => 2]],
                ['Load bank test tahunan', 'Uji beban 100% selama 2 jam dengan load bank resistif milik penyedia.', 4, 'pengujian', [[290, 2]]],
                ['Panggilan darurat 24 jam', 'Respon maksimal 4 jam ke Pabrik Cikarang.', 8, 'panggilan', [[233, 1], [61, 1]]],
            ],
        ],
        'AC' => [
            'nomor' => 'SNI/KTR/PGD/2025/034',
            'nama' => 'Kontrak Servis AC, AC Presisi & Chiller 2025–2026',
            'jenis' => 'Pemeliharaan',
            'penyedia' => 'VND-003',
            'mulai' => 345,
            'berakhir' => null,
            'bulan' => 12,
            'nilai' => 144000000,
            'peringatan' => 30,
            'dicatat' => 343,
            'aset' => ['UTL-CHL-01', 'PRD-AC-01', 'GDL-AC-01', 'GDG-AC-01', 'GDG-AC-02', 'GDG-AC-03', 'GDG-AC-04'],
            'catatan' => 'Cuci AC split triwulanan, perawatan AC presisi ruang server bulanan, overhaul chiller York sekali setahun. Freon dan kapasitor ditagih sesuai pemakaian.',
            'layanan' => [
                ['Cuci & servis AC split triwulanan', 'Lima unit AC split di Jakarta, Cikarang, dan Surabaya.', 20, 'unit', ['setiap' => 91, 'mulai' => 335, 'jumlah' => 5]],
                ['Perawatan AC presisi ruang server bulanan', 'Pemeriksaan kompresor, filter, humidifier, dan alarm suhu ruang server Lt. 15.', 12, 'kunjungan', ['setiap' => 30, 'mulai' => 340, 'jumlah' => 1]],
                ['Overhaul chiller tahunan', 'Pembersihan tube kondensor, analisis oli kompresor, kalibrasi sensor.', 1, 'paket', [[180, 1]]],
            ],
        ],
        'AMC-TI' => [
            'nomor' => 'SNI/KTR/IT/2025/012',
            'nama' => 'Annual Maintenance Contract Infrastruktur TI 2025–2026',
            'jenis' => 'Layanan',
            'penyedia' => 'VND-006',
            'mulai' => 260,
            'berakhir' => null,
            'bulan' => 12,
            'nilai' => 432000000,
            'peringatan' => 60,
            'dicatat' => 258,
            'aset' => ['IT-SRV-01', 'IT-SRV-02', 'IT-NET-01', 'IT-NET-02', 'IT-NET-03', 'IT-NAS-01', 'GDG-UPS-01', 'IT-CCT-01', 'IT-CCT-02'],
            'catatan' => 'Dukungan remote 8×5, kunjungan onsite, penggantian suku cadang server/jaringan, pembaruan firmware triwulanan. SLA respon kritis 2 jam.',
            'layanan' => [
                ['Dukungan remote 8×5 & monitoring', 'Tiket helpdesk penyedia untuk server, jaringan, UPS, dan CCTV.', null, 'tiket', ['setiap' => 21, 'mulai' => 252, 'jumlah' => 4]],
                ['Kunjungan onsite teknisi', 'Jakarta dan Cikarang, termasuk pembaruan firmware.', 24, 'kunjungan', ['setiap' => 30, 'mulai' => 250, 'jumlah' => 2]],
                ['Penggantian suku cadang (HDD/PSU/fan)', 'Suku cadang original, garansi 90 hari.', 10, 'unit', [[188, 2], [97, 1], [21, 1]]],
            ],
        ],
        'LIFT' => [
            'nomor' => 'SNI/KTR/PGD/2026/004',
            'nama' => 'Kontrak Perawatan Lift Menara Sinar & Lift Barang Cikarang 2026',
            'jenis' => 'Pemeliharaan',
            'penyedia' => 'VND-004',
            'mulai' => 230,
            'berakhir' => null,
            'bulan' => 12,
            'nilai' => 198000000,
            'peringatan' => 60,
            'dicatat' => 228,
            'aset' => ['GDG-LFT-01', 'GDG-LFT-02', 'GDG-LFT-03'],
            'catatan' => 'Perawatan preventif bulanan tiga unit lift, panggilan darurat 24 jam (respon maks. 2 jam), pendampingan riksa uji Disnaker.',
            'layanan' => [
                ['Perawatan preventif bulanan', 'Pemeriksaan mesin traksi, pintu, governor, dan pelumasan rel.', 36, 'kunjungan', ['setiap' => 30, 'mulai' => 225, 'jumlah' => 3]],
                ['Panggilan darurat 24 jam', 'Termasuk evakuasi penumpang terjebak.', 12, 'panggilan', [[201, 1], [142, 1], [88, 1], [37, 1], [6, 1]]],
                ['Penggantian tali baja & komponen aus', 'Ditagih sesuai daftar harga lampiran kontrak.', null, 'paket', [[120, 1]]],
            ],
        ],
        'KALIBRASI' => [
            'nomor' => 'SNI/KTR/QA/2026/002',
            'nama' => 'Kontrak Kalibrasi Tahunan Alat Ukur Laboratorium QC 2026',
            'jenis' => 'Layanan',
            'penyedia' => 'VND-007',
            'mulai' => 200,
            'berakhir' => null,
            'bulan' => 12,
            'nilai' => 86500000,
            'peringatan' => 45,
            'dicatat' => 198,
            'aset' => ['LAB-TMB-01', 'LAB-TMB-02', 'LAB-JSR-01', 'LAB-JSR-02', 'LAB-MLT-01', 'LAB-OVN-01', 'LAB-TNS-01', 'LAB-PRG-01'],
            'catatan' => 'Kalibrasi di lokasi oleh laboratorium terakreditasi KAN (ISO/IEC 17025), sertifikat tertelusur SI dikirim maksimal 7 hari kerja.',
            'layanan' => [
                ['Kalibrasi terakreditasi KAN di lokasi', 'Timbangan, jangka sorong, multimeter, oven, tensile tester, pressure gauge.', 8, 'alat', [[170, 3], [95, 2], [40, 1]]],
                ['Kalibrasi ulang / verifikasi pasca-perbaikan', 'Untuk alat yang diperbaiki atau hasilnya diragukan.', 2, 'alat', [[52, 1]]],
            ],
        ],
        'PROTEKSI-2025' => [
            'nomor' => 'SNI/KTR/K3/2025/007',
            'nama' => 'Kontrak Perawatan APAR & Hydrant 2025–2026',
            'jenis' => 'Pemeliharaan',
            'penyedia' => 'VND-008',
            'mulai' => null,
            'berakhir' => 117,
            'bulan' => 12,
            'nilai' => 96000000,
            'peringatan' => 30,
            'dicatat' => 355,
            'aset' => ['K3-APR-01', 'K3-APR-02', 'K3-APR-03', 'K3-HYD-01', 'UTL-PMP-03'],
            'catatan' => 'Kontrak lama, dicatat ulang saat Amanpoll mulai dipakai. Dua inspeksi triwulan pertama sudah terlaksana sebelum pencatatan.',
            'layanan' => [
                ['Inspeksi & perawatan APAR/hydrant triwulanan', 'Pemeriksaan tekanan, segel, selang, dan uji jalan pompa hydrant.', 4, 'kunjungan', [[354, 2], [280, 1], [190, 1]]],
                ['Isi ulang APAR CO2/powder', 'Termasuk penggantian segel dan label.', 36, 'tabung', [[280, 12], [190, 8]]],
            ],
        ],
        'PROTEKSI-2026' => [
            'nomor' => 'SNI/KTR/K3/2026/011',
            'nama' => 'Kontrak Perawatan APAR & Hydrant 2026–2027 (Perpanjangan)',
            'jenis' => 'Pemeliharaan',
            'penyedia' => 'VND-008',
            'mulai' => 116,
            'berakhir' => null,
            'bulan' => 12,
            'nilai' => 104000000,
            'peringatan' => 30,
            'dicatat' => 113,
            'aset' => ['K3-APR-01', 'K3-APR-02', 'K3-APR-03', 'K3-HYD-01', 'UTL-PMP-03'],
            'catatan' => 'Perpanjangan kontrak SNI/KTR/K3/2025/007 dengan tambahan uji fungsi pompa hydrant bulanan. Harga naik 8,3%.',
            'layanan' => [
                ['Inspeksi & perawatan APAR/hydrant triwulanan', 'Pemeriksaan tekanan, segel, selang, dan kotak hydrant.', 4, 'kunjungan', [[100, 1], [10, 1]]],
                ['Isi ulang APAR CO2/powder', 'Termasuk penggantian segel dan label.', 36, 'tabung', [[100, 6]]],
                ['Uji fungsi pompa hydrant bulanan', 'Uji jalan pompa diesel & jockey, pencatatan tekanan.', 12, 'pengujian', ['setiap' => 30, 'mulai' => 110, 'jumlah' => 1]],
            ],
        ],
        'SEWA-FORKLIFT' => [
            'nomor' => 'SNI/KTR/GDL/2026/003',
            'nama' => 'Sewa Forklift Tambahan Musim Puncak Q4 2026',
            'jenis' => 'Sewa',
            'penyedia' => 'VND-010',
            'mulai' => -10,
            'berakhir' => null,
            'bulan' => 3,
            'nilai' => 54000000,
            'peringatan' => 14,
            'dicatat' => 40,
            'aset' => [],
            'catatan' => 'Satu unit forklift diesel 3 ton termasuk operator, untuk Gudang Bahan Baku Cikarang.',
            'layanan' => [],
        ],
    ];

    /**
     * Penilaian penyedia: skor dasar [kualitas, ketepatan waktu, harga, layanan], perubahan
     * per triwulan (dari yang terlama), dan catatan per triwulan.
     *
     * @var array<string, array{0: array{int, int, int, int}, 1: list<array{int, int, int, int}>, 2: list<string>}>
     */
    private const PENILAIAN = [
        'VND-001' => [[88, 85, 78, 86], [[0, 0, 0, 0], [1, 2, 0, 1], [2, 1, 1, 2], [1, 3, 1, 2]], [
            'Servis 250 jam tepat jadwal. Laporan servis terlambat dikirim 5 hari.',
            'Respon panggilan darurat genset #2 dalam 3 jam, sesuai SLA.',
            'Load bank test lancar, rekomendasi penggantian radiator genset #2 jelas dan berbiaya wajar.',
            'Kinerja stabil; teknisi sama setiap kunjungan sehingga riwayat mesin terjaga.',
        ]],
        'VND-003' => [[80, 76, 84, 78], [[0, 0, 0, 0], [-2, -3, 0, -2], [-5, -6, 0, -4], [-8, -9, -1, -6]], [
            'Cuci AC triwulan I selesai sesuai jadwal.',
            'Dua kunjungan AC presisi mundur satu minggu karena teknisi kurang.',
            'Keluhan berulang AC ruang keuangan; perbaikan pertama tidak tuntas.',
            'Ketepatan waktu menurun; perlu klausul SLA respon 4 jam saat perpanjangan kontrak.',
        ]],
        'VND-004' => [[90, 88, 75, 89], [[0, 0, 0, 0], [1, 0, 0, 1], [0, 1, 0, 1], [2, 2, 1, 2]], [
            'Perawatan bulanan konsisten, logbook lift selalu diisi.',
            'Evakuasi penumpang terjebak lift #2 selesai dalam 40 menit.',
            'Penggantian tali baja lift barang rapi, dokumentasi lengkap.',
            'Pendampingan riksa uji Disnaker sangat membantu; harga suku cadang masih tinggi.',
        ]],
        'VND-006' => [[86, 82, 72, 88], [[0, 0, 0, 0], [2, 1, 1, 1], [3, 3, 2, 2], [2, 4, 3, 2]], [
            'Onboarding AMC berjalan baik, inventaris perangkat diverifikasi bersama.',
            'Penggantian HDD server backup dalam 1 hari kerja.',
            'Pembaruan firmware core switch dilakukan di luar jam kerja sesuai permintaan.',
            'Respon tiket kritis rata-rata 1,5 jam, di bawah SLA 2 jam.',
        ]],
        'VND-007' => [[92, 79, 80, 87], [[0, 0, 0, 0], [0, -1, 2, 1], [1, 2, 2, 2], [1, 4, 3, 3]], [
            'Sertifikat kalibrasi tertelusur, namun terbit 12 hari kerja setelah kalibrasi.',
            'Jadwal kalibrasi mundur sekali karena teknisi sakit.',
            'Sertifikat terbit 6 hari kerja, sesuai kontrak baru.',
            'Kalibrasi ulang tensile tester pasca-perbaikan dilayani cepat.',
        ]],
        'VND-008' => [[83, 80, 82, 81], [[0, 0, 0, 0], [1, 2, 2, 1], [3, 4, 2, 3], [4, 5, 3, 4]], [
            'Isi ulang APAR tepat waktu, label masa berlaku terpasang rapi.',
            'Temuan kotak hydrant tanpa selang ditindaklanjuti dalam 3 hari.',
            'Perpanjangan kontrak dengan uji pompa hydrant bulanan disepakati.',
            'Uji pompa hydrant bulanan tercatat lengkap dengan tekanan dan debit.',
        ]],
        'VND-002' => [[87, 84, 76, 85], [[0, 0, 0, 0], [1, 1, 0, 1]], [
            'Overhaul kompresor #1 selesai 1 hari lebih cepat dari rencana.',
            'Stok separator oli tersedia saat dibutuhkan.',
        ]],
        'VND-005' => [[78, 72, 88, 75], [[0, 0, 0, 0], [1, 3, 0, 2]], [
            'Harga suku cadang kompetitif, tetapi dua kali pengiriman parsial.',
            'Pengiriman lebih tepat waktu setelah evaluasi triwulan lalu.',
        ]],
        'VND-009' => [[89, 77, 70, 84], [[0, 0, 0, 0], [0, 2, 1, 1]], [
            'Nozzle dan heater band original, lead time impor 5 minggu.',
            'Stok konsinyasi heater band di Cikarang memperpendek lead time.',
        ]],
        'VND-010' => [[85, 83, 79, 86], [[0, 0, 0, 0], [1, 1, 0, 0]], [
            'Servis besar forklift #1 6.000 jam sesuai standar pabrikan.',
            'Respon perbaikan hidrolik forklift #2 dalam 1 hari.',
        ]],
    ];

    /**
     * Standar kepatuhan: kode, nama, penerbit, versi, jenis industri, deskripsi,
     * persyaratan [kode, nama, bukti, interval hari, catatan pemeriksaan patuh],
     * aset yang ditugaskan, dan pemeriksanya.
     *
     * @var list<array{kode: string, nama: string, penerbit: string, versi: string, industri: string, deskripsi: string, persyaratan: list<array{0: string, 1: string, 2: string, 3: int, 4: string}>, aset: list<string>, pemeriksa: string}>
     */
    private const STANDAR = [
        [
            'kode' => 'SMK3-PP50-2012',
            'nama' => 'Sistem Manajemen Keselamatan dan Kesehatan Kerja (SMK3)',
            'penerbit' => 'Pemerintah RI — PP No. 50 Tahun 2012',
            'versi' => '2012',
            'industri' => 'Manufaktur',
            'deskripsi' => 'Kriteria audit SMK3 elemen 6 (keamanan bekerja berdasarkan SMK3) yang diterapkan pada mesin produksi dan utilitas bertekanan.',
            'persyaratan' => [
                ['SMK3-6.4', 'Inspeksi K3 berkala mesin dan peralatan produksi', 'Checklist inspeksi K3 bertanda tangan Ahli K3 Umum', 180, 'Inspeksi K3 berkala tanpa temuan mayor.'],
                ['SMK3-6.7', 'Prosedur penguncian energi (LOTO) dan pengaman mesin berfungsi', 'SOP LOTO, daftar gembok & tag, foto safety guard/interlock', 365, 'Gembok & tag LOTO lengkap, interlock pintu pengaman berfungsi.'],
                ['SMK3-6.10', 'Rambu K3, APD, dan jalur evakuasi di area mesin', 'Foto rambu, daftar APD, denah jalur evakuasi', 365, 'Rambu dan marka jalur evakuasi terpasang jelas.'],
            ],
            'aset' => ['PRD-INJ-01', 'PRD-INJ-02', 'PRD-INJ-03', 'PRD-INJ-04', 'PRD-BLW-01', 'PRD-BLW-02', 'UTL-KMP-01', 'UTL-KMP-02'],
            'pemeriksa' => self::MANAJER,
        ],
        [
            'kode' => 'PERMENAKER-6-2017',
            'nama' => 'K3 Elevator dan Eskalator',
            'penerbit' => 'Kementerian Ketenagakerjaan — Permenaker No. 6 Tahun 2017',
            'versi' => '2017',
            'industri' => 'Gedung & Fasilitas',
            'deskripsi' => 'Kewajiban pemeriksaan dan pengujian (riksa uji) berkala elevator oleh Pengawas K3 atau Ahli K3 Elevator.',
            'persyaratan' => [
                ['LIFT-RIKSA', 'Riksa uji berkala elevator oleh Pengawas/Ahli K3', 'Laporan riksa uji dan Surat Keterangan Memenuhi Syarat K3', 365, 'Riksa uji berkala lulus, SKMS K3 terbit.'],
                ['LIFT-TALI', 'Pemeriksaan tali baja, governor, dan rem pengaman', 'Laporan perawatan penyedia dan foto hasil ukur diameter tali', 180, 'Diameter tali baja dalam toleransi, governor dan rem pengaman normal.'],
            ],
            'aset' => ['GDG-LFT-01', 'GDG-LFT-02', 'GDG-LFT-03'],
            'pemeriksa' => self::MANAJER,
        ],
        [
            'kode' => 'PERMENAKER-8-2020',
            'nama' => 'K3 Pesawat Angkat dan Pesawat Angkut',
            'penerbit' => 'Kementerian Ketenagakerjaan — Permenaker No. 8 Tahun 2020',
            'versi' => '2020',
            'industri' => 'Manufaktur & Logistik',
            'deskripsi' => 'Forklift wajib riksa uji berkala dan hanya dioperasikan operator ber-SIO.',
            'persyaratan' => [
                ['PAA-RIKSA', 'Riksa uji berkala forklift oleh Pengawas K3 Disnaker', 'Laporan riksa uji dan Surat Keterangan Memenuhi Syarat K3', 365, 'Riksa uji lulus: uji beban 125%, rem, dan sistem hidrolik normal.'],
                ['PAA-SIO', 'Operator memiliki Surat Izin Operator (SIO) yang berlaku', 'Salinan SIO operator dan jadwal shift', 365, 'Seluruh operator shift memiliki SIO kelas II yang berlaku.'],
            ],
            'aset' => ['GDL-FRK-01', 'GDL-FRK-02', 'GDL-FRK-03'],
            'pemeriksa' => self::MANAJER,
        ],
        [
            'kode' => 'PUIL-2011-SLO',
            'nama' => 'PUIL 2011 (SNI 0225:2011), K3 Listrik & Sertifikat Laik Operasi',
            'penerbit' => 'BSN · Kementerian ESDM · Permenaker No. 12 Tahun 2015',
            'versi' => '2011',
            'industri' => 'Kelistrikan',
            'deskripsi' => 'Instalasi listrik tegangan menengah dan pembangkit cadangan wajib memenuhi PUIL, diperiksa berkala, dan memiliki SLO yang berlaku.',
            'persyaratan' => [
                ['PUIL-TERMO', 'Inspeksi visual dan termografi panel/instalasi', 'Laporan termografi dengan foto inframerah titik sambungan', 365, 'Tidak ada hotspot di atas 10 °C dari referensi.'],
                ['PUIL-GROUND', 'Pengukuran tahanan pembumian ≤ 5 Ω', 'Berita acara pengukuran earth tester', 365, 'Tahanan pembumian terukur 1,8–3,2 Ω.'],
                ['PUIL-SLO', 'Sertifikat Laik Operasi (SLO) masih berlaku', 'Salinan SLO dari LIT TR terakreditasi', 1825, 'SLO berlaku dan sesuai kapasitas terpasang.'],
            ],
            'aset' => ['UTL-PNL-01', 'UTL-PNL-02', 'UTL-TRF-01', 'UTL-GEN-01', 'UTL-GEN-02'],
            'pemeriksa' => self::MANAJER,
        ],
        [
            'kode' => 'ISO-9001-2015',
            'nama' => 'ISO 9001:2015 Sistem Manajemen Mutu',
            'penerbit' => 'International Organization for Standardization',
            'versi' => '2015',
            'industri' => 'Manufaktur Kemasan Plastik',
            'deskripsi' => 'Klausul 7.1.3 (infrastruktur) dan 7.1.5 (sumber daya pemantauan dan pengukuran) untuk alat laboratorium QC.',
            'persyaratan' => [
                ['ISO-7.1.5', 'Alat pemantauan dan pengukuran terkalibrasi dan tertelusur', 'Sertifikat kalibrasi KAN, label status kalibrasi pada alat', 365, 'Sertifikat kalibrasi berlaku, label status terpasang.'],
                ['ISO-7.1.3', 'Infrastruktur dipelihara sesuai rencana dan terdokumentasi', 'Jadwal dan catatan pemeliharaan alat', 365, 'Catatan pemeliharaan lengkap sesuai jadwal.'],
            ],
            'aset' => ['LAB-TMB-01', 'LAB-TMB-02', 'LAB-JSR-01', 'LAB-JSR-02', 'LAB-TNS-01', 'LAB-MLT-01'],
            'pemeriksa' => self::KALIBRASI,
        ],
        [
            'kode' => 'PERMENAKERTRANS-04-1980',
            'nama' => 'Syarat Pemasangan dan Pemeliharaan Alat Pemadam Api Ringan (APAR)',
            'penerbit' => 'Kemenakertrans — Permenakertrans No. PER.04/MEN/1980',
            'versi' => '1980',
            'industri' => 'Umum',
            'deskripsi' => 'APAR diperiksa dua kali setahun dan diisi ulang/diuji setiap tahun oleh pihak berkompeten.',
            'persyaratan' => [
                ['APAR-6BLN', 'Pemeriksaan 6 bulanan: tekanan, segel, selang, penempatan', 'Kartu pemeriksaan APAR yang ditandatangani', 180, 'Seluruh tabung tekanan di zona hijau, segel utuh.'],
                ['APAR-TAHUNAN', 'Pemeriksaan tahunan dan isi ulang oleh pihak berkompeten', 'Berita acara isi ulang dan label masa berlaku', 365, 'Isi ulang tahunan selesai, label masa berlaku baru terpasang.'],
            ],
            'aset' => ['K3-APR-01', 'K3-APR-02', 'K3-APR-03'],
            'pemeriksa' => self::MANAJER,
        ],
        [
            'kode' => 'SNI-03-1745-2000',
            'nama' => 'Sistem Pipa Tegak dan Hydrant',
            'penerbit' => 'BSN — SNI 03-1745-2000 · Permen PU No. 26/PRT/M/2008',
            'versi' => '2000',
            'industri' => 'Umum',
            'deskripsi' => 'Pompa hydrant diuji jalan berkala dan sistem diuji tekanan serta debit setiap tahun.',
            'persyaratan' => [
                ['HYD-UJI-POMPA', 'Uji jalan pompa hydrant bulanan', 'Log uji jalan pompa (tekanan, durasi, suhu)', 30, 'Pompa diesel start otomatis, tekanan keluaran 7 bar.'],
                ['HYD-TEKANAN', 'Uji tekanan dan debit sistem hydrant tahunan', 'Berita acara uji tekanan & debit', 365, 'Tekanan sisa di titik terjauh 4,5 bar, debit sesuai desain.'],
            ],
            'aset' => ['K3-HYD-01', 'UTL-PMP-03'],
            'pemeriksa' => self::MANAJER,
        ],
    ];

    /**
     * Hasil pemeriksaan yang menyimpang dari pola bawaan (Patuh dalam masa berlaku):
     * [aset|persyaratan] => [hari lalu, status, catatan, masa berlaku manual (hari lalu, negatif = mendatang) atau null].
     *
     * @var array<string, array{0: int, 1: string, 2: string, 3: int|null}>
     */
    private const PEMERIKSAAN_KHUSUS = [
        'GDG-LFT-01|LIFT-RIKSA' => [347, 'Patuh', 'Riksa uji lulus dengan catatan: ganti lampu darurat kabin. Riksa uji berikutnya wajib sebelum masa berlaku habis.', null],
        'GDG-LFT-02|LIFT-RIKSA' => [320, 'Patuh', 'Riksa uji lulus. Getaran kabin sedikit tinggi, dipantau penyedia.', null],
        'GDL-FRK-02|PAA-RIKSA' => [355, 'Patuh', 'Riksa uji lulus. Masa berlaku mengikuti SKMS K3 yang terbit tahun lalu.', 12],
        'UTL-GEN-02|PUIL-TERMO' => [340, 'Patuh', 'Hotspot 6 °C di terminal ATS, masih dalam batas; pantau pada termografi berikutnya.', null],
        'UTL-PNL-02|PUIL-GROUND' => [20, 'TidakPatuh', 'Tahanan pembumian terukur 7,8 Ω (> 5 Ω). Perlu penambahan elektroda dan perbaikan bak kontrol.', null],
        'PRD-INJ-02|SMK3-6.7' => [33, 'TidakPatuh', 'Dua gembok LOTO hilang dan interlock pintu safety guard tidak memutus motor. Mesin hanya boleh dioperasikan dengan pengawasan.', null],
        'K3-APR-02|APAR-6BLN' => [15, 'TidakPatuh', 'Dua dari delapan tabung tekanannya di bawah zona hijau; satu segel putus. Sudah diminta isi ulang ke penyedia.', null],
        'K3-APR-01|APAR-TAHUNAN' => [295, 'Patuh', 'Isi ulang tahunan oleh PT Proteksi Api Sentosa selesai.', null],
    ];

    /**
     * Sertifikat aset: aset, jenis, nomor, penerbit, terbit (hari lalu), berlaku sampai
     * (hari lalu, negatif = mendatang), dicatat (hari lalu).
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: int, 5: int, 6: int}>
     */
    private const SERTIFIKAT = [
        ['UTL-PNL-01', 'Sertifikat Laik Operasi (SLO) Instalasi Tegangan Menengah', 'SLO-TM/0214/LIT-SCI/2022', 'PT Sucofindo — LIT TR terakreditasi Ditjen Gatrik ESDM', 1290, -535, 352],
        ['UTL-GEN-01', 'Sertifikat Laik Operasi (SLO) Pembangkit Genset 1000 kVA', 'SLO-PTL/0877/LIT-SCI/2023', 'PT Sucofindo — LIT TR terakreditasi Ditjen Gatrik ESDM', 1100, -725, 352],
        ['UTL-GEN-02', 'Sertifikat Riksa Uji K3 Instalasi Genset (Permenaker 12/2015)', '566/K3-LISTRIK/DISNAKERTRANS-JABAR/2025', 'Disnakertrans Provinsi Jawa Barat', 340, -25, 338],
        ['UTL-TRF-01', 'Sertifikat Uji Minyak Trafo (BDV & DGA)', 'LAB-TRF/2026/0418', 'PT Kalibrasi Presisi Indonesia', 100, -265, 98],
        ['GDG-LFT-01', 'Surat Keterangan Memenuhi Syarat K3 Elevator', '4312/SKMS-K3/ELV/DISNAKERTRANS-DKI/2025', 'Disnakertrans Provinsi DKI Jakarta', 347, -18, 345],
        ['GDG-LFT-02', 'Surat Keterangan Memenuhi Syarat K3 Elevator', '4987/SKMS-K3/ELV/DISNAKERTRANS-DKI/2025', 'Disnakertrans Provinsi DKI Jakarta', 320, -45, 318],
        ['GDG-LFT-03', 'Surat Keterangan Memenuhi Syarat K3 Elevator', '1204/SKMS-K3/ELV/DISNAKERTRANS-JABAR/2024', 'Disnakertrans Provinsi Jawa Barat', 420, 55, 352],
        ['GDL-FRK-01', 'Surat Keterangan Memenuhi Syarat K3 Pesawat Angkat & Angkut (Forklift)', '2231/SKMS-K3/PAA/DISNAKERTRANS-JABAR/2026', 'Disnakertrans Provinsi Jawa Barat', 210, -155, 208],
        ['GDL-FRK-02', 'Surat Keterangan Memenuhi Syarat K3 Pesawat Angkat & Angkut (Forklift)', '1876/SKMS-K3/PAA/DISNAKERTRANS-JABAR/2025', 'Disnakertrans Provinsi Jawa Barat', 377, 12, 352],
        ['GDL-FRK-03', 'Surat Keterangan Memenuhi Syarat K3 Pesawat Angkat & Angkut (Forklift)', '0914/SKMS-K3/PAA/DISNAKERTRANS-JATIM/2026', 'Disnakertrans Provinsi Jawa Timur', 150, -215, 148],
        ['K3-HYD-01', 'Sertifikat Riksa Uji Instalasi Proteksi Kebakaran (Hydrant)', '0677/K3-PK/DISNAKERTRANS-JABAR/2026', 'Disnakertrans Provinsi Jawa Barat', 160, -205, 158],
        ['K3-APR-01', 'Sertifikat Pengisian Ulang & Uji APAR CO2', 'PAS/APAR/2025/1193', 'PT Proteksi Api Sentosa', 295, -70, 293],
    ];

    /**
     * Relasi aset: induk, anak, jenis, hari lalu mulai.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: int}>
     */
    private const RELASI = [
        ['UTL-TRF-01', 'UTL-PNL-01', 'Terkait', 350],
        ['UTL-GEN-01', 'UTL-PNL-01', 'Terkait', 350],
        ['UTL-GEN-02', 'UTL-PNL-01', 'Terkait', 350],
        ['UTL-PNL-01', 'UTL-PNL-02', 'Terkait', 350],
        ['GDG-UPS-01', 'IT-SRV-01', 'Terkait', 349],
        ['GDG-UPS-01', 'IT-SRV-02', 'Terkait', 349],
        ['GDG-UPS-01', 'IT-NET-01', 'Terkait', 349],
        ['GDG-UPS-01', 'IT-NET-02', 'Terkait', 349],
        ['GDG-UPS-01', 'IT-NAS-01', 'Terkait', 349],
        ['K3-HYD-01', 'UTL-PMP-03', 'Komponen', 349],
        ['PRD-INJ-01', 'PRD-MTC-01', 'Terkait', 348],
    ];

    /**
     * Riwayat penanggung jawab, urut kronologis: aset, email pengguna atau `unit:KODE`, hari lalu, catatan.
     *
     * @var list<array{0: string, 1: string, 2: int, 3: string}>
     */
    private const PENANGGUNG_JAWAB = [
        ['IT-PC-03', 'pelapor.kantor@amanpoll.test', 356, 'Laptop kerja Staf Keuangan.'],
        ['GDL-FRK-01', 'unit:GDL', 355, 'Dioperasikan Bagian Gudang & Logistik, Gudang Bahan Baku.'],
        ['GDL-FRK-02', 'unit:GDL', 355, 'Dioperasikan Bagian Gudang & Logistik, Gudang Bahan Baku.'],
        ['UTL-GEN-01', 'teknisi.listrik@amanpoll.test', 354, 'PIC harian: cek level solar, tegangan aki, dan pemanasan mingguan.'],
        ['UTL-GEN-02', 'teknisi.listrik@amanpoll.test', 354, 'PIC harian: cek level solar, tegangan aki, dan pemanasan mingguan.'],
        ['IT-SRV-01', 'teknisi.it@amanpoll.test', 353, 'PIC harian server ERP: backup, patch, dan pemantauan kapasitas.'],
        ['IT-PC-01', 'unit:OPS', 353, 'Workstation bersama kantor pabrik.'],
        ['LAB-TMB-01', 'kalibrasi@amanpoll.test', 352, 'Penanggung jawab alat ukur laboratorium QC.'],
        ['IT-PC-02', 'penyetuju@amanpoll.test', 340, 'Diserahkan kepada Direktur Operasional (dokumen serah terima).'],
        ['IT-PC-01', 'koordinator.teknik@amanpoll.test', 200, 'Pindah ke Teknik & Fasilitas untuk gambar modifikasi mesin.'],
        ['GDL-FRK-01', 'gudang@amanpoll.test', 120, 'Diserahkan ke Kepala Gudang setelah servis besar 6.000 jam.'],
        ['IT-SRV-01', 'koordinator.it@amanpoll.test', 102, 'Alih PIC ke Supervisor IT Support selama proyek migrasi ERP.'],
        ['IT-PC-03', 'unit:IT', 46, 'Dikembalikan ke IT dalam keadaan rusak untuk proses penghapusan.'],
        ['UTL-GEN-02', 'teknisi.teknik@amanpoll.test', 30, 'Rotasi PIC: teknisi elektrikal fokus pada perbaikan pembumian panel SDP.'],
    ];

    /**
     * Tag: nama, warna, aset yang ditandai.
     *
     * @var list<array{0: string, 1: string, 2: list<string>}>
     */
    private const TAG = [
        ['Kritis', '#dc2626', ['UTL-GEN-01', 'UTL-PNL-01', 'UTL-CHL-01', 'IT-SRV-01', 'PRD-INJ-02', 'K3-HYD-01']],
        ['Perlu Anggaran', '#d97706', ['UTL-PMP-02', 'GDG-AC-03', 'UTL-CHL-01', 'GDL-FRK-02', 'IT-NET-03']],
        ['Audit 2026', '#2563eb', ['GDG-LFT-01', 'GDG-LFT-02', 'GDL-FRK-02', 'UTL-PNL-01', 'UTL-PNL-02', 'LAB-TMB-01', 'K3-APR-02']],
        ['Rencana Penggantian', '#7c3aed', ['UTL-GEN-02', 'PRD-CRS-01', 'IT-PRN-02', 'IT-PC-03']],
        ['Wajib Riksa Uji', '#0891b2', ['GDG-LFT-01', 'GDG-LFT-02', 'GDG-LFT-03', 'GDL-FRK-01', 'GDL-FRK-02', 'GDL-FRK-03', 'K3-HYD-01']],
        ['Garansi Aktif', '#16a34a', ['PRD-INJ-04', 'GDL-FRK-03', 'IT-NET-02', 'IT-NAS-01']],
    ];

    private ?CarbonImmutable $acuanWaktu = null;

    /** @var array<string, Aset> */
    private array $cacheAset = [];

    /** @var array<string, Kontrak> */
    private array $kontrak = [];

    /** @var list<array{0: string, 1: list<string>, 2: string|null, 3: string|null, 4: string, 5: string, 6: int, 7: int|null, 8: string|null}> */
    private array $antrianSerahTerima = [];

    /** @var array<string, string> Id dokumen siklus aset per kunci, untuk komentar. */
    private array $dokumen = [];

    public function run(): void
    {
        $this->masukKonteks();
        $this->acuan();

        if ($this->sudahDisemai('Kontrak')) {
            return;
        }

        $this->semaiAlurPersetujuan();
        $this->semaiTag();
        $this->semaiRelasiAset();
        $this->semaiPenanggungJawab();
        $this->semaiKontrak();
        $this->semaiPenilaianPenyedia();
        $this->semaiKepatuhan();
        $this->semaiSertifikasi();
        $this->semaiIntegrasiEksternal();
        $this->semaiMutasiDanSerahTerima();
        $this->semaiSerahTerima();
        $this->semaiTagEntitas();
        $this->semaiPenghapusan();
        $this->semaiKomentar();
        $this->jalankanPenjadwalPeringatan();
    }

    /** Alur persetujuan mutasi dan penghapusan aset: satu tahap oleh peran Penyetuju. */
    private function semaiAlurPersetujuan(): void
    {
        $peranPenyetuju = $this->idDari('Peran', ['Kode' => 'PENYETUJU']);

        $alur = [
            ['ALUR-MUTASI-ASET', 'Persetujuan Mutasi Aset', 'PermintaanMutasiAset', 'Persetujuan Direktur Operasional', 2880],
            ['ALUR-PENGHAPUSAN-ASET', 'Persetujuan Penghapusan Aset', 'PengajuanPenghapusanAset', 'Persetujuan Direktur Operasional', 4320],
        ];

        foreach ($alur as [$kode, $nama, $jenisEntitas, $namaTahap, $batasMenit]) {
            if (AlurPersetujuan::query()->where('JenisEntitas', $jenisEntitas)->where('Aktif', true)->exists()) {
                continue;
            }

            $this->padaWaktu($this->waktu(359, 8, 30), function () use ($kode, $nama, $jenisEntitas, $namaTahap, $batasMenit, $peranPenyetuju): void {
                $alurPersetujuan = app(BuatAlurPersetujuan::class)->jalankan([
                    'Kode' => $kode,
                    'Nama' => $nama,
                    'JenisEntitas' => $jenisEntitas,
                ]);

                app(BuatTahapPersetujuan::class)->jalankan($alurPersetujuan, [
                    'Urutan' => 1,
                    'Nama' => $namaTahap,
                    'JenisPenyetuju' => 'Peran',
                    'PeranId' => $peranPenyetuju,
                    'JumlahMinimumPenyetuju' => 1,
                    'BolehMenyetujuiSendiri' => false,
                    'BatasWaktuMenit' => $batasMenit,
                ]);

                app(AktifkanAlurPersetujuan::class)->jalankan($alurPersetujuan);
            }, self::ADMIN);
        }
    }

    private function semaiTag(): void
    {
        $this->padaWaktu($this->waktu(358, 9), function (): void {
            foreach (self::TAG as [$nama, $warna]) {
                app(BuatTag::class)->jalankan(['Nama' => $nama, 'Warna' => $warna]);
            }
        }, self::ADMIN);
    }

    private function semaiRelasiAset(): void
    {
        foreach (self::RELASI as [$induk, $anak, $jenis, $hari]) {
            $this->padaWaktu($this->waktu($hari, 10), fn () => app(BuatRelasiAset::class)->jalankan([
                'AsetIndukId' => $this->aset($induk)->Id,
                'AsetAnakId' => $this->aset($anak)->Id,
                'JenisRelasi' => $jenis,
                'Jumlah' => 1,
                'MulaiPada' => $this->tanggal($hari),
            ]), self::MANAJER);
        }
    }

    private function semaiPenanggungJawab(): void
    {
        foreach (self::PENANGGUNG_JAWAB as $urutan => [$kodeAset, $pemegang, $hari, $catatan]) {
            $data = str_starts_with($pemegang, 'unit:')
                ? ['UnitOrganisasiId' => $this->idDari('UnitOrganisasi', ['Kode' => substr($pemegang, 5)])]
                : ['PenggunaId' => $this->pengguna($pemegang)];

            $this->padaWaktu(
                $this->waktu($hari, 15, $urutan % 4 * 10),
                fn () => app(AssignPenanggungJawabAset::class)->jalankan($this->aset($kodeAset), [...$data, 'Catatan' => $catatan]),
                self::MANAJER,
            );
        }
    }

    /** Kontrak, layanan, cakupan aset, pemakaian, penutupan kontrak kedaluwarsa, dan pembatalan. */
    private function semaiKontrak(): void
    {
        $kelolaKontrak = app(KelolaKontrak::class);
        $kelolaLayanan = app(KelolaLayananKontrak::class);
        $kelolaCakupan = app(KelolaCakupanAsetKontrak::class);

        foreach (self::KONTRAK as $kunci => $spek) {
            $berakhir = $spek['berakhir'] === null ? null : CarbonImmutable::parse($this->tanggal($spek['berakhir']));
            $mulai = $spek['mulai'] === null && $berakhir !== null
                ? $berakhir->subMonthsNoOverflow($spek['bulan'])->addDay()
                : CarbonImmutable::parse($this->tanggal((int) $spek['mulai']));
            $berakhir ??= $mulai->addMonthsNoOverflow($spek['bulan'])->subDay();

            $kontrak = $this->padaWaktu($this->waktu($spek['dicatat'], 9, 15), function () use ($spek, $mulai, $berakhir, $kelolaKontrak, $kelolaLayanan, $kelolaCakupan): Kontrak {
                $kontrak = $kelolaKontrak->buat([
                    'PenyediaId' => $this->idDari('Penyedia', ['Kode' => $spek['penyedia']]),
                    'Nomor' => $spek['nomor'],
                    'Nama' => $spek['nama'],
                    'Jenis' => $spek['jenis'],
                    'MulaiPada' => $mulai->toDateString(),
                    'BerakhirPada' => $berakhir->toDateString(),
                    'Nilai' => $spek['nilai'],
                    'MataUang' => 'IDR',
                    'PeringatanHariSebelum' => $spek['peringatan'],
                    'Catatan' => $spek['catatan'],
                ]);

                foreach ($spek['layanan'] as [$nama, $deskripsi, $kuota, $satuan]) {
                    $kelolaLayanan->tambah($kontrak, [
                        'Nama' => $nama,
                        'Deskripsi' => $deskripsi,
                        'Kuota' => $kuota,
                        'Satuan' => $satuan,
                    ]);
                }

                foreach ($spek['aset'] as $kodeAset) {
                    $kelolaCakupan->lampirkan($kontrak, ['AsetId' => $this->aset($kodeAset)->Id]);
                }

                return $kontrak;
            }, self::PENGADAAN);

            $this->kontrak[$kunci] = $kontrak;
            $this->catatPemakaianLayanan($kontrak, $spek['layanan']);
        }

        // Sewa forklift dibatalkan sebelum mulai berjalan.
        $this->padaWaktu($this->waktu(18, 14), fn () => $kelolaKontrak->batalkan(
            $this->kontrak['SEWA-FORKLIFT'],
            'Volume distribusi Q4 direvisi turun; tiga forklift yang ada mencukupi.',
        ), self::PENGADAAN);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: int|null, 3: string, 4: array<int|string, mixed>}>  $daftarLayanan
     */
    private function catatPemakaianLayanan(Kontrak $kontrak, array $daftarLayanan): void
    {
        $kelolaLayanan = app(KelolaLayananKontrak::class);
        $layananTersimpan = $kontrak->layanan()->get()->keyBy('Nama');

        foreach ($daftarLayanan as [$nama, , , , $pola]) {
            $layanan = $layananTersimpan[$nama];
            $jadwal = [];

            if (isset($pola['setiap'])) {
                for ($hari = (int) $pola['mulai']; $hari >= 1; $hari -= (int) $pola['setiap']) {
                    $jadwal[] = [$hari, (int) $pola['jumlah']];
                }
            } else {
                $jadwal = $pola;
            }

            foreach ($jadwal as $urutan => [$hari, $jumlah]) {
                $this->padaWaktu(
                    $this->waktu($hari, 13 + $urutan % 3, 20),
                    fn () => $kelolaLayanan->catatPemakaian($layanan, (string) $jumlah),
                    self::MANAJER,
                );
            }
        }
    }

    /** Penilaian penyedia per triwulan kalender, dinilai staf pengadaan ± seminggu setelah triwulan berakhir. */
    private function semaiPenilaianPenyedia(): void
    {
        $hariIni = $this->acuan()->startOfDay();
        $buatPenilaian = app(BuatPenilaianPenyedia::class);
        $penilai = $this->pengguna(self::PENGADAAN);

        foreach (self::PENILAIAN as $kodePenyedia => [$dasar, $perubahan, $catatan]) {
            $penyedia = Penyedia::query()->where('Kode', $kodePenyedia)->firstOrFail();
            $jumlahTriwulan = count($perubahan);

            foreach ($perubahan as $urutan => $selisih) {
                $mulai = $hariIni->startOfQuarter()->subQuarters($jumlahTriwulan - $urutan);
                $selesai = $mulai->endOfQuarter()->startOfDay();
                $hari = (int) $selesai->addDays(8 + $urutan % 3)->diffInDays($hariIni);

                if ($hari < 0 || $hari > 358) {
                    continue;
                }

                $skor = array_map(static fn (int $nilai, int $beda): int => max(0, min(100, $nilai + $beda)), $dasar, $selisih);

                $this->padaWaktu($this->waktu($hari, 10, 30), fn () => $buatPenilaian->jalankan($penyedia, [
                    'PeriodeMulai' => $mulai->toDateString(),
                    'PeriodeSelesai' => $selesai->toDateString(),
                    'SkorKualitas' => $skor[0],
                    'SkorKetepatanWaktu' => $skor[1],
                    'SkorHarga' => $skor[2],
                    'SkorLayanan' => $skor[3],
                    'Catatan' => $catatan[$urutan],
                ], $penilai), self::PENGADAAN);
            }
        }
    }

    /** Standar, persyaratan, penugasan ke aset, dan hasil pemeriksaan kepatuhan. */
    private function semaiKepatuhan(): void
    {
        $kelolaStandar = app(KelolaStandarKepatuhan::class);
        $kelolaKepatuhan = app(KelolaKepatuhanAset::class);
        $urutanPemeriksaan = 0;

        foreach (self::STANDAR as $urutanStandar => $spek) {
            $standar = $this->padaWaktu($this->waktu(359, 10, $urutanStandar * 5), function () use ($spek, $kelolaStandar): StandarKepatuhan {
                $standar = $kelolaStandar->buat([
                    'Kode' => $spek['kode'],
                    'Nama' => $spek['nama'],
                    'Penerbit' => $spek['penerbit'],
                    'VersiStandar' => $spek['versi'],
                    'JenisIndustri' => $spek['industri'],
                    'Deskripsi' => $spek['deskripsi'],
                    'Aktif' => true,
                ]);

                foreach ($spek['persyaratan'] as [$kode, $nama, $bukti, $interval]) {
                    $kelolaStandar->tambahPersyaratan($standar, [
                        'Kode' => $kode,
                        'Nama' => $nama,
                        'BuktiYangDiperlukan' => $bukti,
                        'IntervalHari' => $interval,
                    ]);
                }

                return $standar;
            }, self::MANAJER);

            $this->padaWaktu($this->waktu(357, 9, $urutanStandar * 5), function () use ($spek, $standar, $kelolaKepatuhan): void {
                foreach ($spek['aset'] as $kodeAset) {
                    $kelolaKepatuhan->tugaskanStandar($this->aset($kodeAset), $standar);
                }
            }, self::MANAJER);

            $persyaratan = $standar->persyaratan()->get()->keyBy('Kode');

            foreach ($spek['aset'] as $kodeAset) {
                foreach ($spek['persyaratan'] as [$kodePersyaratan, , , $interval, $catatanPatuh]) {
                    $kepatuhan = KepatuhanAset::query()
                        ->where('AsetId', $this->aset($kodeAset)->Id)
                        ->where('PersyaratanKepatuhanId', $persyaratan[$kodePersyaratan]->Id)
                        ->firstOrFail();

                    [$hari, $status, $catatan, $berlakuManual] = self::PEMERIKSAAN_KHUSUS["{$kodeAset}|{$kodePersyaratan}"]
                        ?? [$this->hariPemeriksaanBawaan($interval, $urutanPemeriksaan), StatusKepatuhanAset::Patuh->value, $catatanPatuh, null];
                    $urutanPemeriksaan++;

                    $saat = $this->waktu($hari, 9 + $urutanPemeriksaan % 7, $urutanPemeriksaan % 6 * 10);

                    $this->padaWaktu($saat, fn () => $kelolaKepatuhan->catatPemeriksaan($kepatuhan, [
                        'Status' => $status,
                        'TanggalPemeriksaan' => $saat->setTimezone('Asia/Jakarta')->toDateString(),
                        'BerlakuSampai' => $berlakuManual === null ? null : $this->tanggal($berlakuManual),
                        'Catatan' => $catatan,
                    ], $this->pengguna($spek['pemeriksa'])), $spek['pemeriksa']);
                }
            }
        }

        // Dua alat laboratorium baru masuk lingkup ISO 9001 dan belum diperiksa.
        $iso = StandarKepatuhan::query()->where('Kode', 'ISO-9001-2015')->firstOrFail();
        $this->padaWaktu($this->waktu(8, 10), function () use ($iso, $kelolaKepatuhan): void {
            foreach (['LAB-OVN-01', 'LAB-PRG-01'] as $kodeAset) {
                $kelolaKepatuhan->tugaskanStandar($this->aset($kodeAset), $iso);
            }
        }, self::KALIBRASI);
    }

    /**
     * Hari pemeriksaan bawaan yang tersebar namun masih dalam masa berlaku interval.
     */
    private function hariPemeriksaanBawaan(int $interval, int $urutan): int
    {
        if ($interval <= 30) {
            return 3 + ($urutan * 7) % 20;
        }

        if ($interval <= 180) {
            return 20 + ($urutan * 41) % 140;
        }

        return 25 + ($urutan * 41) % 290;
    }

    private function semaiSertifikasi(): void
    {
        $kelola = app(KelolaSertifikasiAset::class);

        foreach (self::SERTIFIKAT as [$kodeAset, $jenis, $nomor, $penerbit, $terbit, $berlaku, $dicatat]) {
            $this->padaWaktu($this->waktu($dicatat, 11), fn () => $kelola->terbitkan($this->aset($kodeAset), [
                'JenisSertifikasi' => $jenis,
                'NomorSertifikat' => $nomor,
                'Penerbit' => $penerbit,
                'TerbitPada' => $this->tanggal($terbit),
                'BerlakuSampai' => $this->tanggal($berlaku),
            ]), self::MANAJER);
        }

        // Lift barang lulus riksa uji ulang: sertifikat baru terbit, yang lama dicabut.
        $this->padaWaktu($this->waktu(50, 10), function () use ($kelola): void {
            $aset = $this->aset('GDG-LFT-03');
            $lama = SertifikasiAset::query()
                ->where('AsetId', $aset->Id)
                ->where('Status', StatusSertifikasiAset::Aktif->value)
                ->firstOrFail();

            $kelola->terbitkan($aset, [
                'JenisSertifikasi' => 'Surat Keterangan Memenuhi Syarat K3 Elevator',
                'NomorSertifikat' => '3398/SKMS-K3/ELV/DISNAKERTRANS-JABAR/2026',
                'Penerbit' => 'Disnakertrans Provinsi Jawa Barat',
                'TerbitPada' => $this->tanggal(52),
                'BerlakuSampai' => $this->tanggal(52 - 365),
            ]);
            $kelola->cabut($lama, 'Digantikan SKMS K3 hasil riksa uji ulang 2026 (nomor 3398/SKMS-K3/ELV/DISNAKERTRANS-JABAR/2026).');
        }, self::MANAJER);
    }

    /** Integrasi ERP tanpa sinkronisasi (tidak butuh jaringan), dengan pemetaan kode aset dan penyedia. */
    private function semaiIntegrasiEksternal(): void
    {
        $integrasi = $this->padaWaktu($this->waktu(90, 10), fn () => app(KelolaIntegrasiEksternal::class)->buat([
            'Kode' => 'ERP-SAP-B1',
            'Nama' => 'SAP Business One — Modul Aset Tetap & Pemasok',
            'Jenis' => 'ERP',
            'UrlDasar' => 'https://erp.sinarnusantara.test/b1s/v1',
            'MetodeAutentikasi' => 'Basic',
            'Konfigurasi' => ['Pengguna' => 'amanpoll.sync', 'KataSandi' => 'ganti-saat-produksi', 'BasisDataPerusahaan' => 'SNI_PROD'],
        ]), self::ADMIN);

        $pemetaan = app(KelolaPemetaanDataEksternal::class);
        $this->padaWaktu($this->waktu(88, 14), function () use ($integrasi, $pemetaan): void {
            foreach (['UTL-GEN-01' => 'FA-100041', 'UTL-CHL-01' => 'FA-100057', 'PRD-INJ-03' => 'FA-100112', 'PRD-INJ-04' => 'FA-100131', 'PRD-BLW-01' => 'FA-100088', 'IT-SRV-01' => 'FA-100164'] as $kodeAset => $kodeEksternal) {
                $pemetaan->petakan($integrasi, ['JenisEntitas' => 'Aset', 'EntitasId' => $this->aset($kodeAset)->Id, 'KodeEksternal' => $kodeEksternal]);
            }

            foreach (['VND-001' => 'V-200011', 'VND-003' => 'V-200027', 'VND-004' => 'V-200034', 'VND-006' => 'V-200052', 'VND-007' => 'V-200063', 'VND-008' => 'V-200071'] as $kodePenyedia => $kodeEksternal) {
                $pemetaan->petakan($integrasi, ['JenisEntitas' => 'Penyedia', 'EntitasId' => $this->idDari('Penyedia', ['Kode' => $kodePenyedia]), 'KodeEksternal' => $kodeEksternal]);
            }
        }, self::ADMIN);

        // Belum disinkronkan: menunggu IP server Amanpoll di-whitelist tim SAP.
        $this->padaWaktu($this->waktu(88, 15), fn () => app(KelolaIntegrasiEksternal::class)->ubahStatus($integrasi, StatusIntegrasiEksternal::Nonaktif->value), self::ADMIN);
    }

    /** Permintaan mutasi aset dari draft sampai dieksekusi, lengkap dengan serah terima yang menyertainya. */
    private function semaiMutasiDanSerahTerima(): void
    {
        $daftar = [
            'PINJAM-FORKLIFT' => [
                'jenis' => 'Peminjaman', 'aset' => ['GDL-FRK-02'], 'tujuan' => 'SBY', 'unitTujuan' => null,
                'alasan' => 'Peminjaman forklift #2 ke Gudang Distribusi Surabaya untuk puncak pengiriman akhir tahun (± 2 bulan).',
                'diajukan' => 312, 'keputusan' => [310, true, 'Disetujui. Pastikan biaya angkut masuk anggaran distribusi.'], 'eksekusi' => 308,
                'serahTerima' => ['Peminjaman Antar Lokasi', 'gudang@amanpoll.test', null, 'PerluPerhatian', 'Diterima Kepala Gudang Surabaya beserta kunci, buku log, dan APAR unit.', 308],
            ],
            'KEMBALI-FORKLIFT' => [
                'jenis' => 'Pengembalian', 'aset' => ['GDL-FRK-02'], 'tujuan' => 'CKR-GBB', 'unitTujuan' => null,
                'alasan' => 'Pengembalian forklift #2 dari Surabaya setelah puncak pengiriman selesai.',
                'diajukan' => 238, 'keputusan' => [236, true, null], 'eksekusi' => 234,
                'serahTerima' => ['Pengembalian Antar Lokasi', null, 'gudang@amanpoll.test', 'PerluPerhatian', 'Ban depan kiri aus, dijadwalkan ganti bersama servis 6.500 jam.', 234],
            ],
            'CAD-KE-TEKNIK' => [
                'jenis' => 'AntarUnit', 'aset' => ['IT-PC-01'], 'tujuan' => 'CKR-UTL', 'unitTujuan' => 'TEKFAS',
                'alasan' => 'Workstation CAD dipindah ke Teknik & Fasilitas untuk gambar modifikasi mould dan jig mesin.',
                'diajukan' => 204, 'keputusan' => [202, true, null], 'eksekusi' => 200,
                'serahTerima' => ['Penyerahan ke Pengguna', self::ADMIN, 'koordinator.teknik@amanpoll.test', 'Baik', 'Termasuk monitor 27", lisensi CAD aktif, dan akun jaringan pabrik.', 200],
            ],
            'RELAYOUT-BLOW' => [
                'jenis' => 'AntarLokasi', 'aset' => ['PRD-MTC-01', 'PRD-CRS-01'], 'tujuan' => 'CKR-PRDA-BLW', 'unitTujuan' => null,
                'alasan' => 'Relayout lini blow molding: MTC dan crusher didekatkan ke mesin blow.',
                'diajukan' => 156, 'keputusan' => [154, true, 'Setuju, kerjakan saat shutdown akhir pekan.'], 'eksekusi' => 152,
                'tolakDetail' => ['PRD-CRS-01' => 'Crusher masih dipakai lini injeksi untuk regrind; relayout crusher ditunda.'],
            ],
            'NAS-KE-PABRIK' => [
                'jenis' => 'AntarLokasi', 'aset' => ['IT-NAS-01'], 'tujuan' => 'CKR-KTR', 'unitTujuan' => null,
                'alasan' => 'NAS backup dipindah ke pabrik sebagai salinan offsite.',
                'diajukan' => 97, 'keputusan' => [95, false, 'Ruang kantor pabrik belum memenuhi syarat suhu dan daya cadangan. Ajukan ulang setelah rak dan UPS terpasang.'],
            ],
            'PRINTER-LABEL-SBY' => [
                'jenis' => 'AntarLokasi', 'aset' => ['IT-PRN-03'], 'tujuan' => 'SBY', 'unitTujuan' => null,
                'alasan' => 'Printer label Zebra dipakai sementara di Gudang Surabaya.',
                'diajukan' => 64, 'batal' => 62,
            ],
            'TIMBANGAN-SBY' => [
                'jenis' => 'AntarLokasi', 'aset' => ['LAB-TMB-02'], 'tujuan' => 'SBY', 'unitTujuan' => null,
                'alasan' => 'Timbangan lantai 3 ton dibutuhkan untuk verifikasi berat palet di Gudang Distribusi Surabaya.',
                'diajukan' => 14, 'keputusan' => [12, true, 'Disetujui. Kalibrasi ulang setelah tiba di Surabaya.'], 'pindai' => 1,
            ],
            'PRINTER-LT15' => [
                'jenis' => 'AntarLokasi', 'aset' => ['IT-PRN-01'], 'tujuan' => 'JKT-L15', 'unitTujuan' => null,
                'alasan' => 'Printer Lt. 12 dipindah ke Lt. 15 karena tim pengadaan pindah lantai.',
                'diajukan' => 3,
            ],
            'APAR-LT12' => [
                'jenis' => 'Reposisi', 'aset' => ['K3-APR-03'], 'tujuan' => 'JKT-L12', 'unitTujuan' => null,
                'alasan' => 'Penataan ulang titik APAR sesuai denah evakuasi baru Lt. 12.',
                'diajukan' => 0, 'draft' => true,
            ],
        ];

        $pemohon = $this->pemohon('PermintaanMutasiAset');

        foreach ($daftar as $kunci => $spek) {
            $permintaan = $this->padaWaktu($this->waktu($spek['diajukan'], 9), function () use ($spek, $pemohon): PermintaanMutasiAset {
                $asetPertama = $this->aset($spek['aset'][0]);
                $permintaan = app(BuatPermintaanMutasiAset::class)->jalankan([
                    'JenisMutasi' => $spek['jenis'],
                    'UnitAsalId' => $asetPertama->UnitOrganisasiId,
                    'UnitTujuanId' => $spek['unitTujuan'] === null ? null : $this->idDari('UnitOrganisasi', ['Kode' => $spek['unitTujuan']]),
                    'LokasiAsalId' => $asetPertama->LokasiId,
                    'LokasiTujuanId' => $this->idDari('Lokasi', ['Kode' => $spek['tujuan']]),
                    'Alasan' => $spek['alasan'],
                ], $this->pengguna($pemohon));

                foreach ($spek['aset'] as $kodeAset) {
                    app(TambahDetailMutasiAset::class)->jalankan($permintaan, $this->aset($kodeAset)->Id, null);
                }

                if (! ($spek['draft'] ?? false)) {
                    app(SubmitPermintaanMutasiAset::class)->jalankan($permintaan, $this->pengguna($pemohon));
                }

                return $permintaan->refresh();
            }, $pemohon);

            $this->dokumen["mutasi:{$kunci}"] = $permintaan->Id;

            if (isset($spek['batal'])) {
                $this->padaWaktu($this->waktu($spek['batal'], 10), fn () => app(BatalkanPermintaanMutasiAset::class)->jalankan($permintaan->refresh()), $pemohon);
            }

            if (isset($spek['keputusan'])) {
                [$hari, $setuju, $catatan] = $spek['keputusan'];
                $this->putuskanPersetujuan('PermintaanMutasiAset', $permintaan->Id, $hari, $setuju, $catatan);
            }

            foreach ($spek['tolakDetail'] ?? [] as $kodeAset => $alasanTolak) {
                $this->padaWaktu($this->waktu((int) $spek['eksekusi'], 9), function () use ($permintaan, $spek, $kodeAset, $alasanTolak): void {
                    foreach ($spek['aset'] as $kode) {
                        $detail = $permintaan->detailMutasiAset()->where('AsetId', $this->aset($kode)->Id)->firstOrFail();
                        $ditolak = $kode === $kodeAset;
                        app(PutuskanDetailMutasiAset::class)->jalankan($detail, ! $ditolak, $this->pengguna(self::MANAJER), $ditolak ? $alasanTolak : null);
                    }
                }, self::MANAJER);
            }

            if (isset($spek['pindai'])) {
                $this->padaWaktu($this->waktu($spek['pindai'], 10), fn () => app(PindaiPengambilanAset::class)->jalankan(
                    $permintaan->refresh(),
                    $spek['aset'][0],
                    $this->pengguna('gudang@amanpoll.test'),
                ), self::MANAJER);
            }

            if (isset($spek['eksekusi'])) {
                $this->padaWaktu($this->waktu($spek['eksekusi'], 14), fn () => app(EksekusiMutasiAset::class)->jalankan($permintaan->refresh(), $this->pengguna(self::MANAJER)), self::MANAJER);
                $this->cacheAset = [];
            }

            if (isset($spek['serahTerima'])) {
                [$jenis, $menyerahkan, $menerima, $kondisi, $catatan, $hari] = $spek['serahTerima'];
                $this->antrianSerahTerima[] = [$jenis, $spek['aset'], $menyerahkan, $menerima, $kondisi, $catatan, $hari, $hari, $permintaan->Id];
            }
        }
    }

    /**
     * Serah terima dari mutasi ditambah yang berdiri sendiri (laptop, forklift, pengembalian,
     * peminjaman alat), dibuat berurutan menurut tanggal supaya nomor dokumennya urut.
     */
    private function semaiSerahTerima(): void
    {
        $this->antrianSerahTerima[] = ['Penyerahan ke Pengguna', ['IT-PC-02'], self::ADMIN, self::PENYETUJU, 'Baik', 'Laptop kerja Direktur Operasional beserta charger, tas, dan akun domain. Enkripsi disk aktif.', 340, 340, null];
        $this->antrianSerahTerima[] = ['Penyerahan ke Pengguna', ['GDL-FRK-01'], 'koordinator.teknik@amanpoll.test', 'gudang@amanpoll.test', 'Baik', 'Serah terima forklift #1 setelah servis besar 6.000 jam oleh PT Toyota Material Handling Indonesia.', 120, 120, null];
        $this->antrianSerahTerima[] = ['Pengembalian', ['IT-PC-03'], 'pelapor.kantor@amanpoll.test', self::ADMIN, 'Rusak', 'Layar retak dan motherboard mati total setelah tersiram air. Data sudah dipindah ke laptop pinjaman.', 46, 46, null];
        $this->antrianSerahTerima[] = ['Peminjaman Alat', ['LAB-PRG-01'], self::KALIBRASI, 'teknisi.teknik@amanpoll.test', 'Baik', 'Pressure gauge master untuk verifikasi manometer kompresor GA75; dikembalikan setelah pekerjaan selesai.', 1, null, null];

        usort($this->antrianSerahTerima, static fn (array $a, array $b): int => $b[6] <=> $a[6]);

        foreach ($this->antrianSerahTerima as $serahTerima) {
            $this->buatSerahTerima(...$serahTerima);
        }
    }

    /**
     * @param  list<string>  $kodeAset
     */
    private function buatSerahTerima(
        string $jenis,
        array $kodeAset,
        ?string $menyerahkan,
        ?string $menerima,
        string $kondisi,
        string $catatan,
        int $hariDiserahkan,
        ?int $hariDiterima,
        ?string $permintaanMutasiId = null,
    ): void {
        $serahTerima = $this->padaWaktu($this->waktu($hariDiserahkan, 15), function () use ($jenis, $kodeAset, $menyerahkan, $menerima, $catatan, $permintaanMutasiId) {
            $serahTerima = app(BuatSerahTerimaAset::class)->jalankan([
                'PermintaanMutasiAsetId' => $permintaanMutasiId,
                'Jenis' => $jenis,
                'PihakMenyerahkan' => $menyerahkan === null ? null : $this->pengguna($menyerahkan),
                'PihakMenerima' => $menerima === null ? null : $this->pengguna($menerima),
                'Catatan' => $catatan,
            ]);

            foreach ($kodeAset as $kode) {
                app(TambahDetailSerahTerimaAset::class)->jalankan($serahTerima, $this->aset($kode)->Id, $this->aset($kode)->Kondisi, null);
            }

            return $serahTerima;
        }, self::MANAJER);

        $this->dokumen['serahTerima:'.$kodeAset[0]] = $serahTerima->Id;

        if ($hariDiterima === null) {
            return;
        }

        $this->padaWaktu($this->waktu($hariDiterima, 16, 30), fn () => app(TerimaSerahTerimaAset::class)->jalankan(
            $serahTerima->refresh(),
            array_map(fn (string $kode): array => ['AsetId' => $this->aset($kode)->Id, 'KondisiSaatDiterima' => $kondisi], $kodeAset),
        ), self::MANAJER);
        $this->cacheAset = [];
    }

    /** Pengajuan penghapusan: satu dieksekusi, satu menunggu, satu ditolak, satu dibatalkan. */
    private function semaiPenghapusan(): void
    {
        $daftar = [
            'AC-KEUANGAN' => [
                'aset' => 'GDG-AC-03', 'metode' => 'Dijual', 'nilaiBuku' => 7200000, 'hasil' => 900000,
                'alasan' => 'AC ruang keuangan sering mati dan bocor; usulan ganti unit baru inverter.',
                'catatan' => null,
                'diajukan' => 158, 'keputusan' => [156, false, 'Masih ekonomis diperbaiki: ganti kapasitor dan isi ulang freon lewat kontrak servis AC.'],
            ],
            'CRUSHER' => [
                'aset' => 'PRD-CRS-01', 'metode' => 'Dijual', 'nilaiBuku' => 38500000, 'hasil' => 15000000,
                'alasan' => 'Rencana penggantian crusher 30 HP dengan unit 50 HP untuk kapasitas regrind.',
                'catatan' => null,
                'diajukan' => 75, 'batal' => 73,
            ],
            'LAPTOP-KEU' => [
                'aset' => 'IT-PC-03', 'metode' => 'Dijual', 'nilaiBuku' => 4375000, 'hasil' => 750000,
                'alasan' => 'Laptop rusak berat (motherboard mati, layar retak) akibat tersiram air; biaya perbaikan melebihi nilai buku.',
                'catatan' => 'Hard disk sudah dicabut dan dihancurkan tim IT (sanitasi data). Dijual sebagai e-waste ke pengepul berizin.',
                'diajukan' => 42, 'keputusan' => [40, true, 'Setuju dijual sebagai e-waste. Simpan berita acara pemusnahan HDD.'], 'eksekusi' => 37,
            ],
            'POMPA-2' => [
                'aset' => 'UTL-PMP-02', 'metode' => 'Dijual', 'nilaiBuku' => 58000000, 'hasil' => 3500000,
                'alasan' => 'Impeller dan casing pompa retak; biaya perbaikan Rp 41 juta (> 70% harga unit baru). Pompa #1 dan tangki cadangan mencukupi sementara.',
                'catatan' => 'Motor listrik 15 kW dilepas dan disimpan sebagai cadangan pompa #1 sebelum unit dijual.',
                'diajukan' => 7,
            ],
        ];

        $pemohon = $this->pemohon('PengajuanPenghapusanAset');

        foreach ($daftar as $kunci => $spek) {
            $pengajuan = $this->padaWaktu($this->waktu($spek['diajukan'], 10), function () use ($spek, $pemohon) {
                $pengajuan = app(BuatPengajuanPenghapusanAset::class)->jalankan([
                    'Alasan' => $spek['alasan'],
                    'MetodePenghapusan' => $spek['metode'],
                ], $this->pengguna($pemohon));

                app(TambahDetailPenghapusanAset::class)->jalankan($pengajuan, $this->aset($spek['aset'])->Id, $spek['nilaiBuku'], $spek['hasil'], $spek['catatan']);

                if (! isset($spek['batal'])) {
                    app(SubmitPengajuanPenghapusanAset::class)->jalankan($pengajuan, $this->pengguna($pemohon));
                }

                return $pengajuan->refresh();
            }, $pemohon);

            $this->dokumen["penghapusan:{$kunci}"] = $pengajuan->Id;

            if (isset($spek['batal'])) {
                $this->padaWaktu($this->waktu($spek['batal'], 11), fn () => app(BatalkanPengajuanPenghapusanAset::class)->jalankan($pengajuan->refresh()), $pemohon);
            }

            if (isset($spek['keputusan'])) {
                [$hari, $setuju, $catatan] = $spek['keputusan'];
                $this->putuskanPersetujuan('PengajuanPenghapusanAset', $pengajuan->Id, $hari, $setuju, $catatan);
            }

            if (isset($spek['eksekusi'])) {
                $this->padaWaktu($this->waktu($spek['eksekusi'], 14), fn () => app(EksekusiPenghapusanAset::class)->jalankan($pengajuan->refresh()), self::MANAJER);
            }
        }
    }

    /**
     * Pemohon dokumen siklus aset: Manajer Aset, kecuali alur persetujuan aktif menjadikan
     * Manajer Aset sebagai penyetuju (alur yang dipasang seeder lain lebih dulu). Pemohon
     * tidak boleh menyetujui permintaannya sendiri, jadi pengajuan beralih ke Kepala SI
     * yang juga memegang izin aset.
     */
    private function pemohon(string $jenisEntitas): string
    {
        $alur = AlurPersetujuan::query()->where('JenisEntitas', $jenisEntitas)->where('Aktif', true)->firstOrFail();
        $manajer = $this->pengguna(self::MANAJER);

        foreach ($alur->tahapPersetujuan()->get() as $tahap) {
            $penyetuju = match ($tahap->JenisPenyetuju) {
                'Pengguna' => [$tahap->PenggunaId],
                'Peran' => DB::table('PenggunaPeran')->where('PeranId', $tahap->PeranId)->pluck('PenggunaId')->all(),
                default => [],
            };

            if (in_array($manajer, $penyetuju, true)) {
                return self::ADMIN;
            }
        }

        return self::MANAJER;
    }

    /**
     * Memutuskan seluruh tahap persetujuan yang masih menunggu. Tiap tahap diputuskan
     * penyetuju yang berhak (bukan pemohon), berselang dua jam; penolakan jatuh pada
     * tahap terakhir, dan catatan keputusan ditulis pada tahap itu.
     */
    private function putuskanPersetujuan(string $jenisEntitas, string $entitasId, int $hari, bool $setuju, ?string $catatan): void
    {
        $layananPenyetuju = app(LayananPenyetuju::class);
        $preferensi = [self::PENYETUJU, self::MANAJER, self::ADMIN];

        for ($langkah = 0; $langkah < 5; $langkah++) {
            $permintaan = PermintaanPersetujuan::query()
                ->where('JenisEntitas', $jenisEntitas)
                ->where('EntitasId', $entitasId)
                ->where('Status', StatusPermintaanPersetujuan::Menunggu->value)
                ->first();

            if (! $permintaan instanceof PermintaanPersetujuan) {
                return;
            }

            $tahap = TahapPersetujuan::query()
                ->where('AlurPersetujuanId', $permintaan->AlurPersetujuanId)
                ->where('Urutan', $permintaan->TahapSaatIni)
                ->firstOrFail();
            $sisaTahap = TahapPersetujuan::query()
                ->where('AlurPersetujuanId', $permintaan->AlurPersetujuanId)
                ->where('Urutan', '>', $tahap->Urutan)
                ->count();
            $tahapTerakhir = $sisaTahap === 0;
            $entitas = app(RegistriEntitas::class)->cariEntitas($jenisEntitas, $entitasId);
            $calon = $layananPenyetuju->calonPenyetuju($tahap, $entitas)
                ->reject(fn (Pengguna $pengguna): bool => ! $tahap->BolehMenyetujuiSendiri && $pengguna->Id === $permintaan->DimintaOleh)
                ->keyBy('Id');

            $email = collect($preferensi)->first(fn (string $email): bool => $calon->has($this->pengguna($email)));
            $penyetuju = $email === null ? $calon->first() : $calon->get($this->pengguna($email));

            if (! $penyetuju instanceof Pengguna) {
                throw new RuntimeException("Tidak ada penyetuju yang berhak untuk tahap {$tahap->Nama}.");
            }

            $this->padaWaktu($this->waktu($hari, 11 - 2 * $sisaTahap), function () use ($permintaan, $penyetuju, $setuju, $tahapTerakhir, $catatan): void {
                if (! $setuju && $tahapTerakhir) {
                    app(TolakPermintaanPersetujuan::class)->jalankan($permintaan, $penyetuju, $catatan);

                    return;
                }

                app(SetujuiPermintaanPersetujuan::class)->jalankan($permintaan, $penyetuju, $tahapTerakhir ? $catatan : 'Dikaji, diteruskan ke tahap berikutnya.');
            }, (string) $penyetuju->Email);
        }
    }

    /** Tag pada aset, kontrak, dan perintah kerja (bila seeder pemeliharaan sudah berjalan). */
    private function semaiTagEntitas(): void
    {
        $tambahkan = app(TambahkanTagKeEntitas::class);
        $tag = DB::table('Tag')->where('OrganisasiId', $this->organisasiId())->pluck('Id', 'Nama');
        $hari = 300;

        foreach (self::TAG as [$nama, , $daftarAset]) {
            $this->padaWaktu($this->waktu($hari, 13), function () use ($tambahkan, $tag, $nama, $daftarAset): void {
                foreach ($daftarAset as $kodeAset) {
                    $asetId = DB::table('Aset')->where('OrganisasiId', $this->organisasiId())->where('KodeAset', $kodeAset)->whereNull('DihapusPada')->value('Id');
                    if (is_string($asetId)) {
                        $tambahkan->jalankan($tag[$nama], 'Aset', $asetId);
                    }
                }
            }, self::MANAJER);
            $hari -= 47;
        }

        $this->padaWaktu($this->waktu(22, 9), function () use ($tambahkan, $tag): void {
            $tambahkan->jalankan($tag['Perlu Anggaran'], 'Kontrak', $this->kontrak['AC']->Id);
            $tambahkan->jalankan($tag['Audit 2026'], 'Kontrak', $this->kontrak['PROTEKSI-2026']->Id);
            $tambahkan->jalankan($tag['Kritis'], 'Kontrak', $this->kontrak['AMC-TI']->Id);
        }, self::PENGADAAN);

        $perintahKerja = DB::table('PerintahKerja as pk')
            ->join('PerintahKerjaAset as pka', 'pka.PerintahKerjaId', '=', 'pk.Id')
            ->where('pk.OrganisasiId', $this->organisasiId())
            ->whereNull('pk.DihapusPada')
            ->whereIn('pka.AsetId', [$this->aset('UTL-GEN-01')->Id, $this->aset('UTL-CHL-01')->Id, $this->aset('PRD-INJ-02')->Id, $this->aset('GDG-LFT-02')->Id])
            ->orderByDesc('pk.DibuatPada')
            ->limit(4)
            ->distinct()
            ->pluck('pk.Id');

        if ($perintahKerja->isNotEmpty()) {
            $this->padaWaktu($this->waktu(2, 8), function () use ($tambahkan, $tag, $perintahKerja): void {
                foreach ($perintahKerja as $perintahKerjaId) {
                    $tambahkan->jalankan($tag['Kritis'], 'PerintahKerja', (string) $perintahKerjaId);
                }
                $tambahkan->jalankan($tag['Perlu Anggaran'], 'PerintahKerja', (string) $perintahKerja->first());
            }, 'koordinator.teknik@amanpoll.test');
        }
    }

    /** Diskusi singkat pada aset dan dokumen: tiap utas berupa pesan pembuka dan balasan berurutan. */
    private function semaiKomentar(): void
    {
        $utas = [
            ['Aset', $this->aset('UTL-PMP-02')->Id, 9, [
                [self::MANAJER, 'Pompa #2 sudah dibongkar penyedia: impeller dan casing retak. Estimasi perbaikan Rp 41 juta, hampir sama dengan unit baru.'],
                [self::ADMIN, 'Nilai buku per bulan ini Rp 58 juta. Kalau diajukan penghapusan, lampirkan berita acara kerusakan dari teknisi.'],
                [self::MANAJER, 'Siap, BA dari Pak Agus sudah ada; pengajuan penghapusan masuk minggu ini.'],
            ]],
            ['Aset', $this->aset('IT-SRV-01')->Id, 60, [
                [self::ADMIN, 'Firmware iDRAC dan BIOS server ERP sudah diperbarui oleh Mitra Datacom saat kunjungan onsite. Reboot 12 menit di luar jam kerja.'],
                [self::MANAJER, 'Terima kasih. Mohon catat juga di kontrak AMC supaya kuota kunjungan onsite ter-update.'],
            ]],
            ['Aset', $this->aset('GDL-FRK-02')->Id, 11, [
                [self::MANAJER, 'SKMS K3 forklift #2 sudah lewat masa berlaku. Sementara hanya boleh dipakai di dalam gudang, tidak ke area bongkar muat.'],
                [self::ADMIN, 'Sudah saya tambahkan tag Audit 2026. Jadwal riksa uji Disnaker Jabar sudah keluar?'],
                [self::MANAJER, 'Sudah, minggu depan. Penyedia forklift ikut mendampingi.'],
            ]],
            ['Kontrak', $this->kontrak['AC']->Id, 2, [
                [self::PENGADAAN, 'Kontrak servis AC berakhir sekitar tiga minggu lagi. CV Sejuk Mandiri mengajukan perpanjangan dengan kenaikan 6%.'],
                [self::MANAJER, 'Sebelum diperpanjang, masukkan catatan kinerja dua triwulan terakhir (ketepatan waktu turun) dan minta klausul SLA respon 4 jam untuk AC presisi ruang server.'],
                [self::PENGADAAN, 'Baik, sudah saya masukkan ke draft addendum. Sekalian minta pembanding dari dua penyedia lain.'],
            ]],
            ['Kontrak', $this->kontrak['LIFT']->Id, 36, [
                [self::MANAJER, 'Panggilan darurat lift #2 bulan ini sudah dua kali. Minta penyedia cek ulang sensor pintu, jangan hanya reset.'],
                [self::PENGADAAN, 'Sudah saya sampaikan ke PT Lift Indo Jaya; teknisi senior dijadwalkan kunjungan Kamis.'],
            ]],
            ['PengajuanPenghapusanAset', $this->dokumen['penghapusan:POMPA-2'], 6, [
                [self::MANAJER, 'Pengajuan sudah masuk alur persetujuan. Pompa #1 dan tangki cadangan cukup untuk kebutuhan air bersih sementara.'],
                [self::ADMIN, 'Pastikan motor 15 kW dilepas sebelum dijual, sesuai catatan di detail aset.'],
            ]],
            ['PermintaanMutasiAset', $this->dokumen['mutasi:TIMBANGAN-SBY'], 1, [
                [self::MANAJER, 'Timbangan sudah dipindai saat diambil oleh Pak Joko. Pengiriman ke Surabaya besok pagi bersama truk distribusi; eksekusi mutasi setelah tiba.'],
            ]],
            ['SertifikasiAset', SertifikasiAset::query()->where('AsetId', $this->aset('GDG-LFT-01')->Id)->value('Id'), 4, [
                [self::KALIBRASI, 'SKMS K3 lift #1 berakhir sekitar tiga minggu lagi. Permohonan riksa uji ke Disnakertrans DKI sudah diajukan?'],
                [self::MANAJER, 'Sudah, jadwal pemeriksaan tanggal 10. PT Lift Indo Jaya mendampingi sesuai kontrak.'],
            ]],
        ];

        $tambahKomentar = app(TambahKomentar::class);

        foreach ($utas as [$jenisEntitas, $entitasId, $hari, $pesan]) {
            $indukId = null;

            foreach ($pesan as $urutan => [$email, $isi]) {
                $komentar = $this->padaWaktu(
                    $this->waktu($hari, 9 + $urutan * 2, 15 + $urutan * 7),
                    fn () => $tambahKomentar->jalankan($jenisEntitas, (string) $entitasId, $isi, $indukId, $this->pengguna($email)),
                    $email,
                );
                $indukId ??= $komentar->Id;
            }
        }
    }

    /**
     * Menjalankan peringatan kontrak dan kepatuhan pada hari-hari penjadwal harian
     * benar-benar memicu sesuatu (ambang H-90/H-60/H-30, kedaluwarsa), lalu hari ini.
     */
    private function jalankanPenjadwalPeringatan(): void
    {
        $hariIni = $this->acuan()->startOfDay();
        $ambang = [90, 60, 30];
        $hariKontrak = [];
        $hariKepatuhan = [];

        foreach (Kontrak::query()->get() as $kontrak) {
            $sisa = (int) $hariIni->diffInDays(CarbonImmutable::parse($kontrak->BerakhirPada->toDateString(), 'Asia/Jakarta'), false);
            foreach ([...$ambang, (int) $kontrak->PeringatanHariSebelum, -1] as $batas) {
                $hari = $batas - $sisa;
                if ($hari > 0 && $hari <= 360) {
                    $hariKontrak[$hari] = true;
                }
            }
        }

        $berlaku = [
            ...KepatuhanAset::query()->whereNotNull('BerlakuSampai')->pluck('BerlakuSampai')->all(),
            ...SertifikasiAset::query()->where('Status', '!=', StatusSertifikasiAset::Dicabut->value)->whereNotNull('BerlakuSampai')->pluck('BerlakuSampai')->all(),
        ];
        foreach ($berlaku as $tanggal) {
            $sisa = (int) $hariIni->diffInDays(CarbonImmutable::parse((string) $tanggal, 'Asia/Jakarta')->startOfDay(), false);
            foreach ([...$ambang, -1] as $batas) {
                $hari = $batas - $sisa;
                if ($hari > 0 && $hari <= 120) {
                    $hariKepatuhan[$hari] = true;
                }
            }
        }

        krsort($hariKontrak);
        foreach (array_keys($hariKontrak) as $hari) {
            $this->padaWaktu($this->acuan()->subDays($hari)->setTime(7, 0)->utc(), fn () => app(LayananPeringatanKontrak::class)->kirimPeringatan($this->organisasiId()));
        }

        krsort($hariKepatuhan);
        foreach (array_keys($hariKepatuhan) as $hari) {
            $this->padaWaktu($this->acuan()->subDays($hari)->setTime(7, 0)->utc(), fn () => app(LayananKepatuhan::class)->kirimPeringatan($this->organisasiId()));
        }

        $this->padaWaktu($this->waktu(0, 7), function (): void {
            app(LayananPeringatanKontrak::class)->kirimPeringatan($this->organisasiId());
            app(LayananKepatuhan::class)->kirimPeringatan($this->organisasiId());
        });

        $ditutup = Kontrak::query()->where('Status', StatusKontrak::Berakhir->value)->count();
        $this->command?->info("DemoKepatuhanSiklusSeeder: {$ditutup} kontrak ditutup otomatis oleh penjadwal peringatan.");
    }

    private function aset(string $kode): Aset
    {
        return $this->cacheAset[$kode] ??= Aset::query()->where('KodeAset', $kode)->firstOrFail();
    }

    /** Tanggal lokal organisasi `$hariLalu` hari ke belakang dari hari ini yang sebenarnya (negatif = mendatang). */
    private function tanggal(int $hariLalu): string
    {
        return $this->acuan()->subDays($hariLalu)->toDateString();
    }

    /**
     * Jam kerja pada `$hari` hari lalu; hari Minggu digeser ke Sabtu, dan jam yang
     * jatuh di masa depan (langkah hari ini) ditarik ke beberapa menit lalu.
     */
    private function waktu(int $hari, int $jam, int $menit = 0): CarbonImmutable
    {
        if ($this->acuan()->subDays($hari)->isSunday()) {
            $hari++;
        }

        $waktu = $this->acuan()->subDays($hari)->setTime($jam, $menit)->utc();
        $sekarang = $this->acuan()->utc();

        return $waktu->gt($sekarang) ? $sekarang->subMinutes(10) : $waktu;
    }

    /**
     * Saat seeder mulai, di zona organisasi. Dipakai sebagai acuan semua tanggal relatif
     * karena `now()` di dalam `padaWaktu` sudah dimundurkan.
     */
    private function acuan(): CarbonImmutable
    {
        return $this->acuanWaktu ??= CarbonImmutable::now('Asia/Jakarta');
    }
}
