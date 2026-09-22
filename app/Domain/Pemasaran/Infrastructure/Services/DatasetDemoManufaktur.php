<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Services;

use App\Domain\Pemasaran\Domain\Contracts\DatasetDemo;
use App\Domain\Pemasaran\Domain\Enums\ModulDemo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Satu pabrik kecil: lokasi, aset, perintah kerja, dan rencana preventifnya (MARKETING.md 11). */
final class DatasetDemoManufaktur implements DatasetDemo
{
    public const KODE = 'manufaktur';

    public function kode(): string
    {
        return self::KODE;
    }

    public function nama(): string
    {
        return 'Pabrik Manufaktur Kecil';
    }

    /** @return list<ModulDemo> */
    public function modul(): array
    {
        return [ModulDemo::Aset, ModulDemo::PerintahKerja, ModulDemo::Preventif];
    }

    /** @return list<string> */
    public function tabel(): array
    {
        return [
            'JadwalPemeliharaan',
            'RencanaPemeliharaanAset',
            'RencanaPemeliharaan',
            'PerintahKerjaAset',
            'PerintahKerja',
            'Aset',
            'KategoriAset',
            'Lokasi',
            'UnitOrganisasi',
        ];
    }

    public function bangun(string $organisasiId): void
    {
        $sekarang = CarbonImmutable::now();

        $unitId = $this->unit($organisasiId, $sekarang);
        $lokasi = $this->lokasi($organisasiId, $unitId, $sekarang);
        $kategoriId = $this->kategori($organisasiId, $sekarang);
        $aset = $this->aset($organisasiId, $unitId, $lokasi, $kategoriId, $sekarang);

        $this->perintahKerja($organisasiId, $unitId, $lokasi, $aset, $sekarang);
        $this->preventif($organisasiId, $aset, $sekarang);
    }

    private function unit(string $organisasiId, CarbonImmutable $sekarang): string
    {
        $id = (string) Str::ulid();

        DB::table('UnitOrganisasi')->insert([
            'Id' => $id,
            'OrganisasiId' => $organisasiId,
            'Kode' => 'PRODUKSI',
            'Nama' => 'Divisi Produksi',
            'Jenis' => 'Divisi',
            'Status' => 'Aktif',
            'DibuatPada' => $sekarang,
            'DiperbaruiPada' => $sekarang,
        ]);

        return $id;
    }

    /** @return array<string, string> */
    private function lokasi(string $organisasiId, string $unitId, CarbonImmutable $sekarang): array
    {
        $daftar = [
            'LINE-A' => 'Lini Perakitan A',
            'LINE-B' => 'Lini Perakitan B',
            'UTIL' => 'Ruang Utilitas',
        ];

        $hasil = [];

        foreach ($daftar as $kode => $nama) {
            $id = (string) Str::ulid();
            $hasil[$kode] = $id;

            DB::table('Lokasi')->insert([
                'Id' => $id,
                'OrganisasiId' => $organisasiId,
                'UnitOrganisasiId' => $unitId,
                'Kode' => $kode,
                'Nama' => $nama,
                'Status' => 'Aktif',
                'DibuatPada' => $sekarang,
                'DiperbaruiPada' => $sekarang,
            ]);
        }

        return $hasil;
    }

    private function kategori(string $organisasiId, CarbonImmutable $sekarang): string
    {
        $id = (string) Str::ulid();

        DB::table('KategoriAset')->insert([
            'Id' => $id,
            'OrganisasiId' => $organisasiId,
            'Kode' => 'MESIN',
            'Nama' => 'Mesin Produksi',
            'UmurManfaatBulan' => 120,
            'MemerlukanPemeliharaan' => 1,
            'DibuatPada' => $sekarang,
            'DiperbaruiPada' => $sekarang,
        ]);

        return $id;
    }

    /**
     * @param  array<string, string>  $lokasi
     * @return array<string, string>
     */
    private function aset(
        string $organisasiId,
        string $unitId,
        array $lokasi,
        string $kategoriId,
        CarbonImmutable $sekarang,
    ): array {
        $daftar = [
            ['MSN-001', 'Mesin Injeksi Plastik 120T', 'LINE-A', 'Beroperasi'],
            ['MSN-002', 'Konveyor Perakitan Utama', 'LINE-A', 'Beroperasi'],
            ['MSN-003', 'Mesin Press Hidrolik 60T', 'LINE-B', 'Beroperasi'],
            ['MSN-004', 'Kompresor Udara 75kW', 'UTIL', 'Perbaikan'],
            ['MSN-005', 'Genset Cadangan 250kVA', 'UTIL', 'Beroperasi'],
        ];

        $hasil = [];

        foreach ($daftar as [$kode, $nama, $kodeLokasi, $status]) {
            $id = (string) Str::ulid();
            $hasil[$kode] = $id;

            DB::table('Aset')->insert([
                'Id' => $id,
                'OrganisasiId' => $organisasiId,
                'UnitOrganisasiId' => $unitId,
                'LokasiId' => $lokasi[$kodeLokasi],
                'KategoriAsetId' => $kategoriId,
                'KodeAset' => $kode,
                'Nama' => $nama,
                'Status' => $status,
                'Kondisi' => 'Baik',
                'TingkatKritis' => 'Tinggi',
                // Kode QR-nya diisi supaya peristiwa QrDilihat punya sesuatu untuk ditunjuk.
                'KodeQr' => 'QR-'.$kode,
                'DibuatPada' => $sekarang,
                'DiperbaruiPada' => $sekarang,
            ]);
        }

        return $hasil;
    }

    /**
     * @param  array<string, string>  $lokasi
     * @param  array<string, string>  $aset
     */
    private function perintahKerja(
        string $organisasiId,
        string $unitId,
        array $lokasi,
        array $aset,
        CarbonImmutable $sekarang,
    ): void {
        $daftar = [
            ['WO-DEMO-001', 'Korektif', 'Kompresor berisik dan tekanan turun', 'UTIL', 'MSN-004', 'Tinggi', 'Dikerjakan'],
            ['WO-DEMO-002', 'Preventif', 'Pelumasan konveyor bulanan', 'LINE-A', 'MSN-002', 'Sedang', 'Dijadwalkan'],
            ['WO-DEMO-003', 'Korektif', 'Kebocoran oli mesin press', 'LINE-B', 'MSN-003', 'Tinggi', 'Baru'],
        ];

        foreach ($daftar as $urutan => [$nomor, $jenis, $judul, $kodeLokasi, $kodeAset, $prioritas, $status]) {
            $id = (string) Str::ulid();

            DB::table('PerintahKerja')->insert([
                'Id' => $id,
                'OrganisasiId' => $organisasiId,
                'Nomor' => $nomor,
                'Jenis' => $jenis,
                'Judul' => $judul,
                'Prioritas' => $prioritas,
                'Status' => $status,
                'LokasiId' => $lokasi[$kodeLokasi],
                'UnitOrganisasiId' => $unitId,
                'DijadwalkanMulaiPada' => $sekarang->addDays($urutan),
                'DibuatPada' => $sekarang,
                'DiperbaruiPada' => $sekarang,
            ]);

            DB::table('PerintahKerjaAset')->insert([
                'Id' => (string) Str::ulid(),
                'OrganisasiId' => $organisasiId,
                'PerintahKerjaId' => $id,
                'AsetId' => $aset[$kodeAset],
                'Utama' => 1,
                'DibuatPada' => $sekarang,
            ]);
        }
    }

    /** @param array<string, string> $aset */
    private function preventif(string $organisasiId, array $aset, CarbonImmutable $sekarang): void
    {
        $rencanaId = (string) Str::ulid();

        DB::table('RencanaPemeliharaan')->insert([
            'Id' => $rencanaId,
            'OrganisasiId' => $organisasiId,
            'Kode' => 'PM-BULANAN',
            'Nama' => 'Pemeliharaan Preventif Bulanan',
            'DibuatPada' => $sekarang,
            'DiperbaruiPada' => $sekarang,
        ]);

        foreach (['MSN-001', 'MSN-003'] as $kode) {
            $rencanaAsetId = (string) Str::ulid();

            DB::table('RencanaPemeliharaanAset')->insert([
                'Id' => $rencanaAsetId,
                'OrganisasiId' => $organisasiId,
                'RencanaPemeliharaanId' => $rencanaId,
                'AsetId' => $aset[$kode],
                'TanggalMulai' => $sekarang->toDateString(),
                'DibuatPada' => $sekarang,
                'DiperbaruiPada' => $sekarang,
            ]);

            DB::table('JadwalPemeliharaan')->insert([
                'Id' => (string) Str::ulid(),
                'OrganisasiId' => $organisasiId,
                'RencanaPemeliharaanAsetId' => $rencanaAsetId,
                'TanggalJadwal' => $sekarang->addDays(14)->toDateString(),
                'DibuatPada' => $sekarang,
            ]);
        }
    }
}
