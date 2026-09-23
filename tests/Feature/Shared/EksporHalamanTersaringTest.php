<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KodeKegagalan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatInspeksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Empat halaman yang dipindahkan ke paginasi server, dijaga di sisi ekspornya.
 *
 * Perpindahan itu memang dilakukan supaya berkas ekspornya jujur: sebelumnya
 * penyaringnya hidup di klien, jadi tombol Ekspor mengunduh seluruh daftar
 * padahal layarnya sedang tersempit. Kejujuran itu sekarang bertumpu pada satu
 * hal saja -- `ekspor()` memakai `kueriTersaring()` yang sama dengan
 * `index()`. Bila salah satunya kembali mengoper kueri polos, berkasnya
 * kembali memuat seluruh daftar dan tidak ada yang memberi tahu pemegangnya.
 */
final class EksporHalamanTersaringTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $pengguna;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-EHT', 'Nama' => 'RS Penyaring']);
        $this->pengguna = $this->buatPengguna(['Kalibrasi.Kelola', 'Pemeliharaan.Kelola', 'PerintahKerja.Kelola']);
    }

    /** @param  list<string>  $kodeIzin */
    private function buatPengguna(array $kodeIzin): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);

        foreach ($kodeIzin as $kode) {
            $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        }

        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
        app(KonteksOrganisasi::class)->bersihkan();

        return $pengguna;
    }

    private function dalamOrganisasi(callable $aksi): void
    {
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $aksi();
        app(KonteksOrganisasi::class)->bersihkan();
    }

    /**
     * Tiap kasus: rute ekspornya, cara menyemai dua baris, lalu nama yang harus
     * ikut dan nama yang harus tertinggal ketika penyaring pencariannya dipakai.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function halamanTersaring(): array
    {
        return [
            'jenis kalibrasi' => [
                'jenisKalibrasi',
                '/kalibrasi/jenis/ekspor',
                'Kalibrasi Suhu Ruang',
                'Kalibrasi Tekanan Gas',
            ],
            'kode kegagalan' => [
                'kodeKegagalan',
                '/pemeliharaan/kode-kegagalan/ekspor',
                'Motor Terbakar',
                'Sensor Melenceng',
            ],
            'templat daftar periksa' => [
                'templatDaftarPeriksa',
                '/preventif-inspeksi/templat-daftar-periksa/ekspor',
                'Periksa Harian Ventilator',
                'Periksa Bulanan Genset',
            ],
            'templat inspeksi' => [
                'templatInspeksi',
                '/preventif-inspeksi/templat-inspeksi/ekspor',
                'Inspeksi Triwulan Inkubator',
                'Inspeksi Tahunan Autoklaf',
            ],
        ];
    }

    private function semai(string $jenis, string $ikut, string $tertinggal): void
    {
        $this->dalamOrganisasi(function () use ($jenis, $ikut, $tertinggal): void {
            foreach ([$ikut, $tertinggal] as $nomor => $nama) {
                $kode = 'UJI-'.$nomor.'-'.strtoupper(substr(uniqid(), -5));

                match ($jenis) {
                    'jenisKalibrasi' => JenisKalibrasi::create([
                        'Kode' => $kode, 'Nama' => $nama, 'Aktif' => true,
                    ]),
                    'kodeKegagalan' => KodeKegagalan::create([
                        'Kode' => $kode, 'Nama' => $nama, 'Jenis' => 'Kerusakan', 'Aktif' => true,
                    ]),
                    'templatDaftarPeriksa' => TemplatDaftarPeriksa::create([
                        'Kode' => $kode, 'Nama' => $nama, 'Jenis' => 'Inspeksi', 'VersiTemplat' => 1, 'Aktif' => true,
                    ]),
                    // TemplatInspeksi wajib menunjuk satu daftar periksa.
                    'templatInspeksi' => TemplatInspeksi::create([
                        'Kode' => $kode,
                        'Nama' => $nama,
                        'TemplatDaftarPeriksaId' => TemplatDaftarPeriksa::create([
                            'Kode' => 'DP-'.$kode,
                            'Nama' => 'Daftar Periksa Pendukung '.$nomor,
                            'Jenis' => 'Inspeksi',
                            'VersiTemplat' => 1,
                            'Aktif' => true,
                        ])->Id,
                        'IntervalHari' => 90,
                        'Aktif' => true,
                    ]),
                };
            }
        });
    }

    #[DataProvider('halamanTersaring')]
    public function test_pencarian_di_layar_ikut_berlaku_pada_berkas_ekspornya(
        string $jenis,
        string $rute,
        string $ikut,
        string $tertinggal,
    ): void {
        $this->semai($jenis, $ikut, $tertinggal);

        $respons = $this->actingAs($this->pengguna)->get($rute.'?cari='.urlencode($ikut));
        $respons->assertOk();
        $isi = $respons->streamedContent();

        $this->assertStringContainsString($ikut, $isi);
        $this->assertStringNotContainsString(
            $tertinggal,
            $isi,
            'Baris yang tersaring keluar dari layar tidak boleh ada di berkasnya.',
        );
    }

    #[DataProvider('halamanTersaring')]
    public function test_berkasnya_tetap_memuat_seluruh_baris_saat_tanpa_penyaring(
        string $jenis,
        string $rute,
        string $ikut,
        string $tertinggal,
    ): void {
        $this->semai($jenis, $ikut, $tertinggal);

        $isi = $this->actingAs($this->pengguna)->get($rute)->streamedContent();

        // Pembanding bagi test di atas: tanpa penyaring keduanya harus ada,
        // sehingga hilangnya satu baris di sana benar-benar karena penyaringnya
        // bekerja, bukan karena barisnya memang tidak pernah ikut.
        $this->assertStringContainsString($ikut, $isi);
        $this->assertStringContainsString($tertinggal, $isi);
    }
}
