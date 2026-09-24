<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

/**
 * Foto "Sesudah" dari teknisi tampil kepada pelapor saat konfirmasi (TASK 39.10 butir 3,
 * PRD 8.20): hanya kategori Sesudah pada perintah kerja dari keluhan milik pelapor itu.
 * Lampiran lain pada perintah kerja yang sama, dan foto perintah kerja keluhan orang lain,
 * tetap tertutup baginya.
 */
final class PelaporFotoSesudahTest extends KasusPelapor
{
    private Lokasi $lantai;

    private KategoriKeluhan $kategori;

    private Pengguna $pelapor;

    private Pengguna $teknisi;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->lantai = $this->lokasi('Lt. 12');
        $this->kategori = $this->kategori('Listrik');
        $this->pelapor = $this->pelaporDi($this->lantai);
        $this->teknisi = $this->penggunaDenganPeran(['TEKNISI']);
    }

    public function test_konfirmasi_menampilkan_hanya_foto_sesudah_dari_perintah_kerja_keluhan_sendiri(): void
    {
        $keluhan = $this->selesai($this->pelapor);
        $perintahKerja = $this->tugaskan($keluhan, $this->teknisi);
        $sesudah = $this->unggah($perintahKerja, 'FotoSesudah', 'sesudah.jpg');
        $this->unggah($perintahKerja, 'FotoSebelum', 'sebelum.jpg');
        $this->unggah($perintahKerja, 'TandaTangan', 'ttd.png');
        $keluhanRekan = $this->selesai($this->pelaporDi($this->lantai));
        $this->unggah($this->tugaskan($keluhanRekan, $this->teknisi), 'FotoSesudah', 'sesudah-rekan.jpg');

        $this->actingAs($this->pelapor)->get("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/Pelapor/Konfirmasi')
                ->where('fotoSesudah', [['BerkasId' => $sesudah->Id, 'Nama' => 'sesudah.jpg']]));
    }

    public function test_konfirmasi_tanpa_foto_sesudah_mengirim_galeri_kosong(): void
    {
        $keluhan = $this->selesai($this->pelapor);
        $perintahKerja = $this->tugaskan($keluhan, $this->teknisi);
        $this->unggah($perintahKerja, 'FotoSebelum', 'sebelum.jpg');

        $this->actingAs($this->pelapor)->get("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('fotoSesudah', []));
    }

    public function test_pelapor_hanya_boleh_mengunduh_foto_sesudah_keluhannya_sendiri(): void
    {
        $keluhan = $this->selesai($this->pelapor);
        $perintahKerja = $this->tugaskan($keluhan, $this->teknisi);
        $sesudah = $this->unggah($perintahKerja, 'FotoSesudah', 'sesudah.jpg');
        $sebelum = $this->unggah($perintahKerja, 'FotoSebelum', 'sebelum.jpg');
        $tandaTangan = $this->unggah($perintahKerja, 'TandaTangan', 'ttd.png');
        $sesudahRekan = $this->unggah($this->tugaskan($this->selesai($this->pelaporDi($this->lantai)), $this->teknisi), 'FotoSesudah', 'sesudah-rekan.jpg');
        $this->actingAs($this->pelapor);

        $this->get("/kolaborasi/berkas/{$sesudah->Id}/unduh")->assertOk();
        $this->get("/kolaborasi/berkas/{$sebelum->Id}/unduh")->assertForbidden();
        $this->get("/kolaborasi/berkas/{$tandaTangan->Id}/unduh")->assertForbidden();
        $this->get("/kolaborasi/berkas/{$sesudahRekan->Id}/unduh")->assertForbidden();
    }

    public function test_foto_sesudah_tidak_membuka_daftar_unggah_atau_hapus_lampiran_perintah_kerja(): void
    {
        $keluhan = $this->selesai($this->pelapor);
        $perintahKerja = $this->tugaskan($keluhan, $this->teknisi);
        $sesudah = $this->unggah($perintahKerja, 'FotoSesudah', 'sesudah.jpg');
        $this->actingAs($this->pelapor);

        $this->getJson("/kolaborasi/lampiran?jenisEntitas=PerintahKerja&entitasId={$perintahKerja->Id}")->assertForbidden();
        $this->delete("/kolaborasi/berkas/{$sesudah->Id}")->assertForbidden();
        $this->post('/kolaborasi/berkas', [
            'Berkas' => UploadedFile::fake()->image('palsu.jpg'),
            'JenisEntitas' => 'PerintahKerja', 'EntitasId' => $perintahKerja->Id, 'Kategori' => 'FotoSesudah',
        ])->assertForbidden();

        $this->assertNotNull($this->dalamOrganisasi(fn () => Berkas::query()->find($sesudah->Id)));
        $this->assertSame(1, $this->dalamOrganisasi(fn () => LampiranEntitas::query()->where('EntitasId', $perintahKerja->Id)->count()));
    }

    private function selesai(Pengguna $pelapor): Keluhan
    {
        return $this->keluhan($pelapor, $this->kategori, $this->lantai, [], [
            StatusKeluhan::Ditinjau, StatusKeluhan::Diterima, StatusKeluhan::Diproses, StatusKeluhan::Selesai,
        ]);
    }

    /** Foto diunggah teknisi yang ditugaskan lewat endpoint Kolaborasi yang dipakai layar kerjanya. */
    private function unggah(PerintahKerja $perintahKerja, string $kategori, string $nama): Berkas
    {
        $this->actingAs($this->teknisi)->post('/kolaborasi/berkas', [
            'Berkas' => UploadedFile::fake()->image($nama),
            'JenisEntitas' => 'PerintahKerja', 'EntitasId' => $perintahKerja->Id, 'Kategori' => $kategori,
        ])->assertSessionDoesntHaveErrors();

        return $this->dalamOrganisasi(fn () => Berkas::query()->where('NamaAsli', $nama)->latest('DibuatPada')->firstOrFail());
    }
}
