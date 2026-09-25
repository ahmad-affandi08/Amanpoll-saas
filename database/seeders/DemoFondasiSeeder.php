<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Actions\PasangPeranAwal;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Fondasi perusahaan demo PT Sinar Nusantara Industri: profil, langganan,
 * struktur unit, lokasi bertingkat tiga situs, hari libur, peran, dan
 * pengguna untuk setiap peran bawaan.
 *
 * Idempoten: seluruh baris ditulis lewat `simpan()` yang mempertahankan Id,
 * sehingga aman dijalankan ulang di atas data yang sudah ada.
 */
final class DemoFondasiSeeder extends Seeder
{
    use KonteksDemo;

    /**
     * Kode unit, nama, jenis, kode induk, dan apakah unit itu memelihara aset.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string|null, 4: bool}>
     */
    private const UNIT = [
        ['PUSAT', 'Direksi & Kantor Pusat', 'Direktorat', null, false],
        ['OPS', 'Divisi Operasional', 'Divisi', 'PUSAT', false],
        ['TEKFAS', 'Teknik & Fasilitas', 'Bagian', 'OPS', true],
        ['IT', 'Teknologi Informasi', 'Bagian', 'PUSAT', true],
        ['PROD', 'Produksi', 'Bagian', 'OPS', false],
        ['QA', 'Mutu & Laboratorium', 'Bagian', 'OPS', false],
        ['GDL', 'Gudang & Logistik', 'Bagian', 'OPS', false],
        ['PGD', 'Pengadaan', 'Bagian', 'PUSAT', false],
        ['KEU', 'Keuangan & Akuntansi', 'Bagian', 'PUSAT', false],
        ['K3', 'K3 & Kepatuhan', 'Bagian', 'PUSAT', false],
        ['HRGA', 'SDM & Umum', 'Bagian', 'PUSAT', false],
    ];

    /**
     * Kode, nama, kategori, kode induk, unit pemilik, lantai, alamat.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string|null, 4: string, 5: string|null, 6: string|null}>
     */
    private const LOKASI = [
        ['JKT', 'Kantor Pusat Jakarta — Menara Sinar', 'GEDUNG', null, 'PUSAT', null, 'Jl. Jend. Sudirman Kav. 45, Jakarta Selatan'],
        ['JKT-L12', 'Menara Sinar Lt. 12 — Direksi & Keuangan', 'LANTAI', 'JKT', 'PUSAT', '12', null],
        ['JKT-L12-RR', 'Ruang Rapat Utama Lt. 12', 'RUANG', 'JKT-L12', 'PUSAT', '12', null],
        ['JKT-L15', 'Menara Sinar Lt. 15 — IT & Pengadaan', 'LANTAI', 'JKT', 'IT', '15', null],
        ['JKT-L15-SRV', 'Ruang Server Lt. 15', 'RUANG', 'JKT-L15', 'IT', '15', null],
        ['CKR', 'Pabrik Cikarang', 'GEDUNG', null, 'OPS', null, 'Kawasan Industri Jababeka II Blok C-7, Cikarang, Bekasi'],
        ['CKR-PRDA', 'Gedung Produksi A', 'AREA-PRODUKSI', 'CKR', 'PROD', '1', null],
        ['CKR-PRDA-INJ', 'Lini Injeksi 1–4', 'AREA-PRODUKSI', 'CKR-PRDA', 'PROD', '1', null],
        ['CKR-PRDA-BLW', 'Lini Blow Molding', 'AREA-PRODUKSI', 'CKR-PRDA', 'PROD', '1', null],
        ['CKR-UTL', 'Gedung Utilitas (Genset, Kompresor, Chiller)', 'UTILITAS', 'CKR', 'TEKFAS', '1', null],
        ['CKR-GBB', 'Gudang Bahan Baku', 'GUDANG', 'CKR', 'GDL', '1', null],
        ['CKR-GSC', 'Gudang Suku Cadang', 'GUDANG', 'CKR', 'TEKFAS', '1', null],
        ['CKR-LAB', 'Laboratorium QC', 'RUANG', 'CKR', 'QA', '2', null],
        ['CKR-KTR', 'Kantor Pabrik & Pos Keamanan', 'RUANG', 'CKR', 'OPS', '2', null],
        ['SBY', 'Gudang Distribusi Surabaya', 'GUDANG', null, 'GDL', null, 'Jl. Margomulyo Indah Blok B-12, Surabaya'],
    ];

    /**
     * Email, nama, jabatan, nomor pegawai, unit, kode peran, dan unit lingkup peran
     * (null = seluruh organisasi).
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string|null}>
     */
    private const PENGGUNA = [
        ['admin@amanpoll.test', 'Hendra Wijaya', 'Kepala Sistem Informasi', 'SNI-0012', 'IT', 'SUPERADMIN', null],
        ['manajer.aset@amanpoll.test', 'Dewi Kartika', 'Manajer Aset & Fasilitas', 'SNI-0034', 'OPS', 'MANAJER-ASET', null],
        ['koordinator.teknik@amanpoll.test', 'Rudi Hartono', 'Supervisor Teknik & Fasilitas', 'SNI-0101', 'TEKFAS', 'KOORDINATOR-PEMELIHARAAN', 'TEKFAS'],
        ['koordinator.it@amanpoll.test', 'Maya Lestari', 'Supervisor IT Support', 'SNI-0145', 'IT', 'KOORDINATOR-PEMELIHARAAN', 'IT'],
        ['teknisi.teknik@amanpoll.test', 'Agus Setiawan', 'Teknisi Mekanikal', 'SNI-0212', 'TEKFAS', 'TEKNISI', 'TEKFAS'],
        ['teknisi.listrik@amanpoll.test', 'Budi Santoso', 'Teknisi Elektrikal', 'SNI-0218', 'TEKFAS', 'TEKNISI', 'TEKFAS'],
        ['teknisi.it@amanpoll.test', 'Fajar Nugroho', 'Teknisi IT', 'SNI-0231', 'IT', 'TEKNISI', 'IT'],
        ['kalibrasi@amanpoll.test', 'Sri Wahyuni', 'Analis Metrologi', 'SNI-0305', 'QA', 'PETUGAS-KALIBRASI', null],
        ['gudang@amanpoll.test', 'Joko Prasetyo', 'Kepala Gudang Suku Cadang', 'SNI-0402', 'GDL', 'OPERATOR-GUDANG', null],
        ['pengadaan@amanpoll.test', 'Linda Permata', 'Staf Pengadaan', 'SNI-0511', 'PGD', 'STAF-PENGADAAN', null],
        ['penyetuju@amanpoll.test', 'Bambang Sutrisno', 'Direktur Operasional', 'SNI-0003', 'PUSAT', 'PENYETUJU', null],
        ['auditor@amanpoll.test', 'Ratna Sari', 'Auditor Internal', 'SNI-0607', 'K3', 'AUDITOR', null],
        ['pelapor@amanpoll.test', 'Andi Saputra', 'Operator Produksi', 'SNI-1120', 'PROD', 'PELAPOR', null],
        ['pelapor.kantor@amanpoll.test', 'Siti Rahmawati', 'Staf Keuangan', 'SNI-0822', 'KEU', 'PELAPOR', null],
    ];

    /**
     * Hari libur nasional 2026 (hari kerja SLA melompatinya).
     *
     * @var list<array{0: string, 1: string, 2: bool}>
     */
    private const HARI_LIBUR = [
        ['2026-01-01', 'Tahun Baru Masehi', true],
        ['2026-02-17', 'Tahun Baru Imlek', false],
        ['2026-03-19', 'Hari Suci Nyepi', false],
        ['2026-03-20', 'Idul Fitri 1447 H', false],
        ['2026-03-21', 'Idul Fitri 1447 H (hari kedua)', false],
        ['2026-04-03', 'Wafat Yesus Kristus', false],
        ['2026-05-01', 'Hari Buruh Internasional', true],
        ['2026-05-14', 'Kenaikan Yesus Kristus', false],
        ['2026-05-27', 'Idul Adha 1447 H', false],
        ['2026-05-31', 'Hari Raya Waisak', false],
        ['2026-06-01', 'Hari Lahir Pancasila', true],
        ['2026-06-16', 'Tahun Baru Islam 1448 H', false],
        ['2026-08-17', 'Hari Kemerdekaan RI', true],
        ['2026-08-25', 'Maulid Nabi Muhammad SAW', false],
        ['2026-12-25', 'Hari Raya Natal', true],
    ];

    public function run(): void
    {
        $organisasiId = $this->semaiOrganisasi();
        $this->semaiLangganan($organisasiId);

        $konteks = app(KonteksOrganisasi::class);
        $konteksSebelumnya = $konteks->id();
        $konteks->tetapkan($organisasiId);

        try {
            $unit = $this->semaiUnit($organisasiId);
            $this->semaiLokasi($organisasiId, $unit);
            $this->semaiHariLibur($organisasiId);
            $this->semaiNomorDokumen($organisasiId);

            app(PasangPeranAwal::class)->jalankan($organisasiId);
            $this->semaiPeranSuperAdmin($organisasiId);
            $this->semaiPengguna($organisasiId, $unit);
        } finally {
            $konteks->tetapkan($konteksSebelumnya);
        }
    }

    private function semaiOrganisasi(): string
    {
        // Umur organisasi satu tahun lebih, supaya riwayat transaksi setahun ke belakang masuk akal.
        $berdiri = CarbonImmutable::now()->subMonths(14)->startOfMonth();

        return $this->simpan('Organisasi', ['Kode' => self::KODE_ORGANISASI_DEMO], [
            'Nama' => 'PT Sinar Nusantara Industri',
            'NamaLegal' => 'PT Sinar Nusantara Industri Tbk',
            'JenisUsaha' => 'Manufaktur Kemasan Plastik',
            'NomorIdentitasPajak' => '01.234.567.8-431.000',
            'Email' => 'info@sinarnusantara.test',
            'Telepon' => '021-89830120',
            'Alamat' => 'Kawasan Industri Jababeka II Blok C-7, Cikarang, Kabupaten Bekasi',
            'Negara' => 'Indonesia',
            'Provinsi' => 'Jawa Barat',
            'Kota' => 'Kabupaten Bekasi',
            'ZonaWaktu' => 'Asia/Jakarta',
            'Status' => 'Aktif',
            'Demo' => 0,
            'DibuatPada' => $berdiri,
            'DiperbaruiPada' => now(),
        ]);
    }

    /** Paket Profesional dengan seluruh modul terbuka dan kuota longgar, berlangganan tahunan. */
    private function semaiLangganan(string $organisasiId): void
    {
        $paketId = $this->simpan('PaketLangganan', ['Kode' => 'PROFESIONAL'], [
            'Nama' => 'Profesional',
            'Deskripsi' => 'Seluruh modul operasional, kalibrasi, kepatuhan, pelaporan lanjutan, dan integrasi.',
            'HargaBulanan' => 4500000,
            'HargaTahunan' => 45000000,
            'MataUang' => 'IDR',
            'Aktif' => 1,
            'DiperbaruiPada' => now(),
        ]);

        $batas = ['batas.aset' => 1000, 'batas.pengguna' => 100, 'batas.lokasi' => 300, 'batas.whatsapp_bulanan' => 1000];

        foreach (DB::table('FiturPaket')->get(['Id', 'Kode']) as $fitur) {
            $this->simpan('PaketFitur', ['PaketLanggananId' => $paketId, 'FiturPaketId' => $fitur->Id], [
                'Diizinkan' => 1,
                'BatasNilai' => $batas[$fitur->Kode] ?? null,
            ], denganStempel: false);
        }

        $mulai = CarbonImmutable::now()->subMonths(13)->startOfMonth();

        $this->simpan('Langganan', ['OrganisasiId' => $organisasiId, 'PaketLanggananId' => $paketId], [
            'Siklus' => 'Tahunan',
            'MulaiPada' => $mulai->toDateString(),
            'BerakhirPada' => $mulai->addYears(2)->subDay()->toDateString(),
            'UjiCobaSampai' => null,
            'Status' => 'Aktif',
            'DiperbaruiPada' => now(),
        ]);
    }

    /** @return array<string, string> Id unit per kode. */
    private function semaiUnit(string $organisasiId): array
    {
        $id = [];

        foreach (self::UNIT as $urutan => [$kode, $nama, $jenis, $induk, $mengelolaAset]) {
            $id[$kode] = $this->simpan('UnitOrganisasi', ['OrganisasiId' => $organisasiId, 'Kode' => $kode], [
                'IndukId' => $induk === null ? null : $id[$induk],
                'Nama' => $nama,
                'Jenis' => $jenis,
                'Status' => 'Aktif',
                'MengelolaAset' => $mengelolaAset ? 1 : 0,
                'Urutan' => $urutan + 1,
                'DiperbaruiPada' => now(),
            ]);
        }

        return $id;
    }

    /** @param array<string, string> $unit */
    private function semaiLokasi(string $organisasiId, array $unit): void
    {
        $kategori = [];
        foreach ([
            'GEDUNG' => 'Gedung / Situs',
            'LANTAI' => 'Lantai',
            'RUANG' => 'Ruangan',
            'AREA-PRODUKSI' => 'Area Produksi',
            'GUDANG' => 'Gudang',
            'UTILITAS' => 'Area Utilitas',
        ] as $kode => $nama) {
            $kategori[$kode] = $this->simpan('KategoriLokasi', ['OrganisasiId' => $organisasiId, 'Kode' => $kode], [
                'Nama' => $nama,
                'DiperbaruiPada' => now(),
            ]);
        }

        $id = [];
        foreach (self::LOKASI as [$kode, $nama, $kodeKategori, $induk, $kodeUnit, $lantai, $alamat]) {
            $id[$kode] = $this->simpan('Lokasi', ['OrganisasiId' => $organisasiId, 'Kode' => $kode], [
                'UnitOrganisasiId' => $unit[$kodeUnit],
                'KategoriLokasiId' => $kategori[$kodeKategori],
                'IndukId' => $induk === null ? null : $id[$induk],
                'Nama' => $nama,
                'Alamat' => $alamat,
                'Lantai' => $lantai,
                'ZonaWaktu' => str_starts_with($kode, 'SBY') ? 'Asia/Jakarta' : null,
                'Status' => 'Aktif',
                'DiperbaruiPada' => now(),
            ]);
        }
    }

    private function semaiHariLibur(string $organisasiId): void
    {
        foreach (self::HARI_LIBUR as [$tanggal, $nama, $berulang]) {
            $this->simpan('HariLibur', ['OrganisasiId' => $organisasiId, 'Tanggal' => $tanggal], [
                'Nama' => $nama,
                'BerulangTahunan' => $berulang ? 1 : 0,
            ]);
        }
    }

    /**
     * Pola nomor dokumen tanpa reset tahunan. Seeder riwayat berjalan per modul dan
     * masing-masing memundurkan jam ke tahun lalu; dengan reset tahunan, modul kedua
     * yang kembali ke 2025 memulai lagi dari 0001 dan menabrak nomor modul pertama.
     */
    private function semaiNomorDokumen(string $organisasiId): void
    {
        foreach (LayananNomorDokumen::AWALAN_BAWAAN as $jenis => $awalan) {
            $this->simpan('NomorDokumen', ['OrganisasiId' => $organisasiId, 'JenisDokumen' => $jenis], [
                'Awalan' => $awalan,
                'FormatNomor' => '{Awalan}/{Tahun}/{Nomor:4}',
                'ResetPeriode' => 'TidakAda',
                'DiperbaruiPada' => now(),
            ]);
        }
    }

    /** Peran pemilik dengan seluruh izin, dipegang admin sistem. */
    private function semaiPeranSuperAdmin(string $organisasiId): void
    {
        $peranId = $this->simpan('Peran', ['OrganisasiId' => $organisasiId, 'Kode' => 'SUPERADMIN'], [
            'Nama' => 'Super Administrator',
            'Keterangan' => 'Akses penuh ke seluruh modul sistem Amanpoll',
            'BawaanSistem' => 1,
            'DiperbaruiPada' => now(),
        ]);

        foreach (DB::table('Izin')->pluck('Id') as $izinId) {
            $this->simpan('PeranIzin', ['PeranId' => $peranId, 'IzinId' => $izinId], []);
        }
    }

    /** @param array<string, string> $unit */
    private function semaiPengguna(string $organisasiId, array $unit): void
    {
        $kataSandi = Hash::make((string) config('amanpoll.demo.kata_sandi'));

        foreach (self::PENGGUNA as [$email, $nama, $jabatan, $nomorPegawai, $kodeUnit, $kodePeran, $lingkup]) {
            $penggunaId = $this->simpan('Pengguna', ['OrganisasiId' => $organisasiId, 'Email' => $email], [
                'UnitOrganisasiId' => $unit[$kodeUnit],
                'Nama' => $nama,
                'Jabatan' => $jabatan,
                'NomorPegawai' => $nomorPegawai,
                'Telepon' => '0812'.substr(preg_replace('/\D/', '', $nomorPegawai).'00000000', 0, 8),
                'KataSandi' => $kataSandi,
                'JenisPengguna' => 'Internal',
                'Status' => 'Aktif',
                'EmailTerverifikasiPada' => now(),
                'DiperbaruiPada' => now(),
            ]);

            $peranId = DB::table('Peran')->where('OrganisasiId', $organisasiId)->where('Kode', $kodePeran)->value('Id');

            // Peran berlingkup unit membatasi antrian koordinator dan teknisi ke bagiannya sendiri.
            $this->simpan('PenggunaPeran', [
                'OrganisasiId' => $organisasiId,
                'PenggunaId' => $penggunaId,
                'PeranId' => $peranId,
            ], [
                'UnitOrganisasiId' => $lingkup === null ? null : $unit[$lingkup],
                'LokasiId' => null,
            ]);
        }
    }
}
