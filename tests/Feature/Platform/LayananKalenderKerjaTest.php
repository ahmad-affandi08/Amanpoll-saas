<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Services\LayananKalenderKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LayananKalenderKerjaTest extends TestCase
{
    use RefreshDatabase;

    public function test_hari_libur_berulang_tahunan_terdeteksi_di_tahun_berapa_pun(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        HariLibur::create(['Tanggal' => '2020-12-25', 'Nama' => 'Natal', 'BerulangTahunan' => true]);
        $konteks->bersihkan();

        $layanan = app(LayananKalenderKerja::class);

        $this->assertTrue($layanan->apakahHariLibur($organisasi->Id, Carbon::parse('2030-12-25')));
        $this->assertFalse($layanan->apakahHariLibur($organisasi->Id, Carbon::parse('2030-12-24')));
    }

    public function test_hari_libur_tidak_berulang_hanya_berlaku_tanggal_tersebut(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        HariLibur::create(['Tanggal' => '2026-05-01', 'Nama' => 'Cuti Bersama', 'BerulangTahunan' => false]);
        $konteks->bersihkan();

        $layanan = app(LayananKalenderKerja::class);

        $this->assertTrue($layanan->apakahHariLibur($organisasi->Id, Carbon::parse('2026-05-01')));
        $this->assertFalse($layanan->apakahHariLibur($organisasi->Id, Carbon::parse('2027-05-01')));
    }

    public function test_hari_libur_khusus_lokasi_tidak_berlaku_untuk_lokasi_lain(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $lokasiJakarta = Lokasi::create(['Kode' => 'JKT', 'Nama' => 'Jakarta', 'Status' => 'Aktif']);
        $lokasiSurabaya = Lokasi::create(['Kode' => 'SBY', 'Nama' => 'Surabaya', 'Status' => 'Aktif']);
        HariLibur::create(['Tanggal' => '2026-06-22', 'Nama' => 'HUT Jakarta', 'BerulangTahunan' => true, 'LokasiId' => $lokasiJakarta->Id]);
        $konteks->bersihkan();

        $layanan = app(LayananKalenderKerja::class);

        $this->assertTrue($layanan->apakahHariLibur($organisasi->Id, Carbon::parse('2026-06-22'), $lokasiJakarta->Id));
        $this->assertFalse($layanan->apakahHariLibur($organisasi->Id, Carbon::parse('2026-06-22'), $lokasiSurabaya->Id));
    }

    public function test_hari_kerja_berikutnya_melompati_akhir_pekan_dan_hari_libur(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        // Jumat 2026-01-02, lalu Senin 2026-01-05 sengaja dijadikan cuti bersama.
        HariLibur::create(['Tanggal' => '2026-01-05', 'Nama' => 'Cuti Bersama', 'BerulangTahunan' => false]);
        $konteks->bersihkan();

        $berikutnya = app(LayananKalenderKerja::class)->hariKerjaBerikutnya($organisasi->Id, Carbon::parse('2026-01-02'));

        $this->assertSame('2026-01-06', $berikutnya->toDateString());
    }
}
