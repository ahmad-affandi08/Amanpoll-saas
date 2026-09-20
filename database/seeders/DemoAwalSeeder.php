<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class DemoAwalSeeder extends Seeder
{
    public function run(): void
    {
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

        // 6. Pengguna Admin (password: password)
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
                'KataSandi' => Hash::make('password'),
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
    }
}
