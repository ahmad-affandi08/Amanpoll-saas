<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Actions\PasangPeranAwal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Organisasi, pengguna, dan master contoh untuk pengembangan.
 *
 * Seeder ini menolak berjalan di produksi. Peringatan di runbook sudah ada
 * sejak lama, tetapi `php artisan db:seed --force` adalah perintah refleks dan
 * prosa tidak menahan siapa pun yang sedang terburu-buru; yang ditanamnya
 * bukan sekadar data contoh melainkan akun Super Admin berkata sandi yang
 * dapat ditebak.
 */
final class DemoAwalSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'DemoAwalSeeder tidak boleh dijalankan di produksi: isinya organisasi, '
                .'aset, dan akun Super Admin contoh. Semai kunci wajibnya satu per satu '
                .'(IzinSeeder, FiturPaketSeeder, FiturPlatformSeeder, TahapPipelineSeeder).',
            );
        }

        // 1. Organisasi Demo
        $organisasi = DB::table('Organisasi')->where('Kode', 'AMANPOLL')->first();
        $organisasiId = $organisasi?->Id ?? (string) Str::ulid();

        DB::table('Organisasi')->updateOrInsert(
            ['Kode' => 'AMANPOLL'],
            [
                'Id' => $organisasiId,
                'Nama' => 'PT Amanpoll Solusi Teknologi',
                'NamaLegal' => 'PT Amanpoll Solusi Teknologi Indonesia',
                'JenisUsaha' => 'Teknologi & Manajemen Aset',
                'Status' => 'Aktif',
                'ZonaWaktu' => 'Asia/Jakarta',
                'Email' => 'kontak@amanpoll.com',
                'DibuatPada' => now(),
                'DiperbaruiPada' => now(),
            ]
        );

        // 2. Unit Organisasi
        $unitId = DB::table('UnitOrganisasi')
            ->where('OrganisasiId', $organisasiId)
            ->where('Kode', 'PUSAT')
            ->value('Id') ?? (string) Str::ulid();

        DB::table('UnitOrganisasi')->updateOrInsert(
            ['OrganisasiId' => $organisasiId, 'Kode' => 'PUSAT'],
            [
                'Id' => $unitId,
                'Nama' => 'Kantor Pusat & Operasional',
                'Jenis' => 'Direktorat',
                'Status' => 'Aktif',
                'DibuatPada' => now(),
                'DiperbaruiPada' => now(),
            ]
        );

        // 3. Lokasi
        $lokasiId = DB::table('Lokasi')
            ->where('OrganisasiId', $organisasiId)
            ->where('Kode', 'HQ-L01')
            ->value('Id') ?? (string) Str::ulid();

        DB::table('Lokasi')->updateOrInsert(
            ['OrganisasiId' => $organisasiId, 'Kode' => 'HQ-L01'],
            [
                'Id' => $lokasiId,
                'UnitOrganisasiId' => $unitId,
                'Nama' => 'Gedung Pusat - Lantai 1 (Server & Operasional)',
                'Lantai' => '1',
                'Status' => 'Aktif',
                'DibuatPada' => now(),
                'DiperbaruiPada' => now(),
            ]
        );

        // 4. Peran Super Admin
        $peranId = DB::table('Peran')
            ->where('OrganisasiId', $organisasiId)
            ->where('Kode', 'SUPERADMIN')
            ->value('Id') ?? (string) Str::ulid();

        DB::table('Peran')->updateOrInsert(
            ['OrganisasiId' => $organisasiId, 'Kode' => 'SUPERADMIN'],
            [
                'Id' => $peranId,
                'Nama' => 'Super Administrator',
                'Keterangan' => 'Akses penuh ke seluruh modul sistem Amanpoll',
                'BawaanSistem' => 1,
                'DibuatPada' => now(),
                'DiperbaruiPada' => now(),
            ]
        );

        // 5. Hubungkan seluruh Izin ke Peran Super Admin
        $semuaIzin = DB::table('Izin')->get(['Id']);
        foreach ($semuaIzin as $izin) {
            $peranIzinId = DB::table('PeranIzin')
                ->where('PeranId', $peranId)
                ->where('IzinId', $izin->Id)
                ->value('Id') ?? (string) Str::ulid();

            DB::table('PeranIzin')->updateOrInsert(
                ['PeranId' => $peranId, 'IzinId' => $izin->Id],
                ['Id' => $peranIzinId, 'DibuatPada' => now()]
            );
        }

        // 6. Pengguna Admin; kata sandinya dari config('amanpoll.demo.kata_sandi').
        $penggunaId = DB::table('Pengguna')
            ->where('OrganisasiId', $organisasiId)
            ->where('Email', 'admin@amanpoll.test')
            ->value('Id') ?? (string) Str::ulid();

        DB::table('Pengguna')->updateOrInsert(
            ['OrganisasiId' => $organisasiId, 'Email' => 'admin@amanpoll.test'],
            [
                'Id' => $penggunaId,
                'UnitOrganisasiId' => $unitId,
                'Nama' => 'Super Admin Amanpoll',
                'KataSandi' => Hash::make((string) config('amanpoll.demo.kata_sandi')),
                'JenisPengguna' => 'Internal',
                'Status' => 'Aktif',
                'DibuatPada' => now(),
                'DiperbaruiPada' => now(),
            ]
        );

        // 7. Hubungkan Pengguna ke Peran Super Admin
        $penggunaPeranId = DB::table('PenggunaPeran')
            ->where('OrganisasiId', $organisasiId)
            ->where('PenggunaId', $penggunaId)
            ->where('PeranId', $peranId)
            ->value('Id') ?? (string) Str::ulid();

        DB::table('PenggunaPeran')->updateOrInsert(
            [
                'OrganisasiId' => $organisasiId,
                'PenggunaId' => $penggunaId,
                'PeranId' => $peranId,
            ],
            [
                'Id' => $penggunaPeranId,
                'DibuatPada' => now(),
            ]
        );

        // 8. Kategori Aset Demo
        $kategoriAsetId = DB::table('KategoriAset')
            ->where('OrganisasiId', $organisasiId)
            ->where('Kode', 'KAT-IT')
            ->value('Id') ?? (string) Str::ulid();

        DB::table('KategoriAset')->updateOrInsert(
            ['OrganisasiId' => $organisasiId, 'Kode' => 'KAT-IT'],
            [
                'Id' => $kategoriAsetId,
                'Nama' => 'Peralatan TI & Server',
                'UmurManfaatBulan' => 48,
                'MetodePenyusutanBawaan' => 'GarisLurus',
                'PersentaseNilaiResidu' => 5.0000,
                'MemerlukanPemeliharaan' => 1,
                'DibuatPada' => now(),
                'DiperbaruiPada' => now(),
            ]
        );

        // 9. Master Gudang Demo
        if (DB::getSchemaBuilder()->hasTable('Gudang')) {
            $gudangId = DB::table('Gudang')
                ->where('OrganisasiId', $organisasiId)
                ->where('Kode', 'GDG-01')
                ->value('Id') ?? (string) Str::ulid();

            DB::table('Gudang')->updateOrInsert(
                ['OrganisasiId' => $organisasiId, 'Kode' => 'GDG-01'],
                [
                    'Id' => $gudangId,
                    'Nama' => 'Gudang Suku Cadang Utama',
                    'LokasiId' => $lokasiId,
                    'Status' => 'Aktif',
                    'DibuatPada' => now(),
                    'DiperbaruiPada' => now(),
                ]
            );
        }

        // 10. Penyedia (Vendor) Demo
        if (DB::getSchemaBuilder()->hasTable('Penyedia')) {
            $penyediaId = DB::table('Penyedia')
                ->where('OrganisasiId', $organisasiId)
                ->where('Kode', 'VND-001')
                ->value('Id') ?? (string) Str::ulid();

            DB::table('Penyedia')->updateOrInsert(
                ['OrganisasiId' => $organisasiId, 'Kode' => 'VND-001'],
                [
                    'Id' => $penyediaId,
                    'Nama' => 'PT Solusi Teknologi Nusantara',
                    'Email' => 'sales@solusinusantara.test',
                    'Telepon' => '021-5551234',
                    'Status' => 'Aktif',
                    'DibuatPada' => now(),
                    'DiperbaruiPada' => now(),
                ]
            );
        }

        // 11. Dua unit pengelola (PRD 8.21)
        $this->semaiUnitPengelola($organisasiId, $unitId, $lokasiId, $kategoriAsetId);
    }

    /**
     * Dua bagian pemeliharaan dalam satu organisasi: Teknik & Fasilitas dan IT.
     *
     * Masing-masing mendapat aset, kategori keluhan, gudang, serta teknisi dan
     * koordinator yang perannya berlingkup unitnya sendiri, supaya pemisahan
     * antrian, penugasan, dan stok bisa dicoba langsung dari akun contoh.
     * Hanya kedua bagian ini yang bertanda Mengelola Aset, dan setiap aset,
     * kategori keluhan, dan gudang contoh (termasuk gudang utama dari langkah 9)
     * menunjuk salah satunya: tidak ada data contoh yang jatuh ke antrian tanpa pemilik.
     * Peran Teknisi dan Koordinator diambil dari katalog peran awal, bukan
     * dibuat ulang di sini.
     */
    private function semaiUnitPengelola(string $organisasiId, string $unitIndukId, string $lokasiId, string $kategoriAsetTiId): void
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteksSebelumnya = $konteks->id();
        $konteks->tetapkan($organisasiId);

        try {
            app(PasangPeranAwal::class)->jalankan($organisasiId);
        } finally {
            $konteks->tetapkan($konteksSebelumnya);
        }

        $peranKoordinator = (string) DB::table('Peran')->where('OrganisasiId', $organisasiId)->where('Kode', 'KOORDINATOR-PEMELIHARAAN')->value('Id');
        $peranTeknisi = (string) DB::table('Peran')->where('OrganisasiId', $organisasiId)->where('Kode', 'TEKNISI')->value('Id');

        $kategoriAsetFasilitasId = $this->simpan('KategoriAset', ['OrganisasiId' => $organisasiId, 'Kode' => 'KAT-FAS'], [
            'Nama' => 'Mesin & Utilitas Gedung',
            'UmurManfaatBulan' => 120,
            'MetodePenyusutanBawaan' => 'GarisLurus',
            'PersentaseNilaiResidu' => 10.0000,
            'MemerlukanPemeliharaan' => 1,
            'DiperbaruiPada' => now(),
        ]);

        $bagian = [
            [
                'Kode' => 'TEKFAS',
                'Nama' => 'Teknik & Fasilitas',
                'KategoriAset' => $kategoriAsetFasilitasId,
                'Aset' => ['AST-FAS-001', 'Genset 250 kVA Gedung Pusat'],
                'KategoriKeluhan' => ['KK-FAS', 'Listrik & Utilitas'],
                'Gudang' => ['GDG-FAS', 'Gudang Teknik & Fasilitas'],
                // Gudang suku cadang utama (langkah 9) menyimpan suku cadang mesin dan utilitas.
                'GudangLain' => ['GDG-01'],
                'Koordinator' => ['koordinator.teknik@amanpoll.test', 'Rudi Hartono'],
                'Teknisi' => ['teknisi.teknik@amanpoll.test', 'Agus Setiawan'],
            ],
            [
                'Kode' => 'IT',
                'Nama' => 'Teknologi Informasi',
                'KategoriAset' => $kategoriAsetTiId,
                'Aset' => ['AST-IT-001', 'Printer Jaringan Lantai 1'],
                'KategoriKeluhan' => ['KK-IT', 'Komputer & Jaringan'],
                'Gudang' => ['GDG-IT', 'Gudang IT'],
                'GudangLain' => [],
                'Koordinator' => ['koordinator.it@amanpoll.test', 'Maya Lestari'],
                'Teknisi' => ['teknisi.it@amanpoll.test', 'Fajar Nugroho'],
            ],
        ];

        foreach ($bagian as $satu) {
            $unitPengelolaId = $this->simpan('UnitOrganisasi', ['OrganisasiId' => $organisasiId, 'Kode' => $satu['Kode']], [
                'IndukId' => $unitIndukId,
                'Nama' => $satu['Nama'],
                'Jenis' => 'Bagian',
                'Status' => 'Aktif',
                'MengelolaAset' => 1,
                'DiperbaruiPada' => now(),
            ]);

            // Aset tetap milik kantor pusat; yang memeliharanya bagian ini.
            $this->simpan('Aset', ['OrganisasiId' => $organisasiId, 'KodeAset' => $satu['Aset'][0]], [
                'UnitOrganisasiId' => $unitIndukId,
                'LokasiId' => $lokasiId,
                'KategoriAsetId' => $satu['KategoriAset'],
                'UnitPengelolaId' => $unitPengelolaId,
                'Nama' => $satu['Aset'][1],
                'Status' => 'Aktif',
                'Kondisi' => 'Baik',
                'DiperbaruiPada' => now(),
            ]);

            $this->simpan('KategoriKeluhan', ['OrganisasiId' => $organisasiId, 'Kode' => $satu['KategoriKeluhan'][0]], [
                'Nama' => $satu['KategoriKeluhan'][1],
                'UnitPengelolaId' => $unitPengelolaId,
                'Aktif' => 1,
            ]);

            $this->simpan('Gudang', ['OrganisasiId' => $organisasiId, 'Kode' => $satu['Gudang'][0]], [
                'Nama' => $satu['Gudang'][1],
                'LokasiId' => $lokasiId,
                'UnitPengelolaId' => $unitPengelolaId,
                'Status' => 'Aktif',
                'DiperbaruiPada' => now(),
            ]);

            DB::table('Gudang')
                ->where('OrganisasiId', $organisasiId)
                ->whereIn('Kode', $satu['GudangLain'])
                ->update(['UnitPengelolaId' => $unitPengelolaId]);

            foreach ([[$satu['Koordinator'], $peranKoordinator], [$satu['Teknisi'], $peranTeknisi]] as [[$email, $nama], $peranId]) {
                $penggunaId = $this->simpan('Pengguna', ['OrganisasiId' => $organisasiId, 'Email' => $email], [
                    'UnitOrganisasiId' => $unitPengelolaId,
                    'Nama' => $nama,
                    'KataSandi' => Hash::make((string) config('amanpoll.demo.kata_sandi')),
                    'JenisPengguna' => 'Internal',
                    'Status' => 'Aktif',
                    'DiperbaruiPada' => now(),
                ]);

                // Berlingkup unitnya sendiri: tanpa ini keduanya melihat seluruh organisasi.
                $this->simpan('PenggunaPeran', [
                    'OrganisasiId' => $organisasiId,
                    'PenggunaId' => $penggunaId,
                    'PeranId' => $peranId,
                ], [
                    'UnitOrganisasiId' => $unitPengelolaId,
                    'LokasiId' => null,
                ]);
            }
        }
    }

    /**
     * updateOrInsert yang mempertahankan Id baris yang sudah ada, supaya
     * menjalankan seeder berulang tidak memutus relasi yang menunjuknya.
     *
     * @param  array<string, mixed>  $kunci
     * @param  array<string, mixed>  $nilai
     */
    private function simpan(string $tabel, array $kunci, array $nilai): string
    {
        $ada = DB::table($tabel)->where($kunci)->value('Id');
        $id = is_string($ada) ? $ada : (string) Str::ulid();

        DB::table($tabel)->updateOrInsert($kunci, $ada === null ? [...$nilai, 'Id' => $id, 'DibuatPada' => now()] : $nilai);

        return $id;
    }
}
