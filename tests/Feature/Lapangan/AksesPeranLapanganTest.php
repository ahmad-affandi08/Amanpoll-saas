<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\KomentarEntitas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Persediaan\Domain\Enums\StatusGudang;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\Inspeksi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Peran lapangan bawaan tanpa izin Kelola (TASK 39.01, PRD 8.20).
 *
 * Seluruh pengguna di sini memegang peran TEKNISI/PELAPOR hasil pemasangan
 * katalog yang sesungguhnya, jadi test ini gagal bila katalog kembali memberi
 * Kelola ataupun bila pencabutannya mematikan pekerjaan lapangan.
 *
 * Pengguna lapangan murni dialihkan dari halaman dasbor, jadi test yang
 * membuka halaman daftar/detail memakai `bisaMembukaDasbor()`: peran meja
 * tanpa izin apa pun ditambahkan, sehingga kewenangannya tetap persis milik
 * peran lapangan tetapi middleware Mode Lapangan tidak mengalihkannya.
 */
final class AksesPeranLapanganTest extends KasusLapangan
{
    public function test_teknisi_yang_ditugaskan_menerima_mengerjakan_dan_menyelesaikan_tiket_tanpa_kelola(): void
    {
        $teknisi = $this->bisaMembukaDasbor($this->penggunaDenganPeran(['TEKNISI']));
        $perintahKerja = $this->buatPerintahKerja('WO-TUGAS', $teknisi);
        $penugasan = $this->dalamOrganisasi(fn () => PenugasanPerintahKerja::query()->where('PerintahKerjaId', $perintahKerja->Id)->firstOrFail());
        $this->actingAs($teknisi);

        $this->get("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}")->assertOk();

        $this->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan/{$penugasan->Id}/respons", ['Respons' => 'Terima'])
            ->assertSessionDoesntHaveErrors();
        $this->assertSame(StatusPerintahKerja::Diterima->value, $this->statusPerintahKerja($perintahKerja));

        $this->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => 'Dikerjakan', 'Catatan' => 'Mulai dikerjakan.', 'Versi' => $this->versi($perintahKerja),
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame(StatusPerintahKerja::Dikerjakan->value, $this->statusPerintahKerja($perintahKerja));

        $this->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/analisis-kegagalan", [
            'AkarMasalah' => 'Bantalan aus.', 'TindakanKorektif' => 'Bantalan diganti.',
        ])->assertSessionDoesntHaveErrors();

        $this->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => 'MenungguVerifikasi', 'RingkasanPenyelesaian' => 'Pompa kembali normal.', 'Versi' => $this->versi($perintahKerja),
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusPerintahKerja($perintahKerja));

        // Menutup verifikasi tetap milik koordinator.
        $this->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => 'Selesai', 'Versi' => $this->versi($perintahKerja),
        ])->assertForbidden();
    }

    /** Teknisi meminta suku cadang: stok ditahan untuk pekerjaannya, jumlah tersedianya tidak berkurang. */
    public function test_teknisi_yang_ditugaskan_meminta_suku_cadang_tanpa_mengubah_stok(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $perintahKerja = $this->buatPerintahKerja('WO-SC', $teknisi, StatusPerintahKerja::Dikerjakan);
        [$gudang, $sukuCadang, $stok] = $this->dalamOrganisasi(function (): array {
            $gudang = Gudang::create(['Kode' => 'GDG-'.uniqid(), 'Nama' => 'Gudang Teknik', 'Status' => StatusGudang::Aktif->value]);
            $sukuCadang = SukuCadang::create([
                'Kode' => 'SC-'.uniqid(), 'Nama' => 'Bantalan 6204', 'SatuanDasar' => 'Buah',
                'HargaRataRata' => 50000, 'StokMinimum' => 1, 'Status' => StatusSukuCadang::Aktif->value,
            ]);
            $stok = StokSukuCadang::create([
                'GudangId' => $gudang->Id, 'SukuCadangId' => $sukuCadang->Id,
                'JumlahTersedia' => 10, 'JumlahDipesan' => 0, 'JumlahDitahan' => 0,
            ]);

            return [$gudang, $sukuCadang, $stok];
        });

        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/reservasi-suku-cadang", [
            'GudangId' => $gudang->Id, 'SukuCadangId' => $sukuCadang->Id, 'Jumlah' => 2,
        ])->assertSessionDoesntHaveErrors();

        $this->dalamOrganisasi(function () use ($perintahKerja, $stok): void {
            $this->assertSame(1, ReservasiSukuCadang::query()->where('PerintahKerjaId', $perintahKerja->Id)->count());
            $this->assertEquals(10, $stok->fresh()?->JumlahTersedia);
        });
    }

    public function test_teknisi_tidak_melihat_maupun_membuka_tiket_yang_tidak_ditugaskan_kepadanya(): void
    {
        $teknisi = $this->bisaMembukaDasbor($this->penggunaDenganPeran(['TEKNISI']));
        $rekan = $this->penggunaDenganPeran(['TEKNISI']);
        $this->buatPerintahKerja('WO-MILIK', $teknisi);
        $milikRekan = $this->buatPerintahKerja('WO-REKAN', $rekan);
        $tanpaTeknisi = $this->buatPerintahKerja('WO-KOSONG', null);
        $this->actingAs($teknisi);

        $this->get('/pemeliharaan/perintah-kerja')
            ->assertInertia(fn ($halaman) => $halaman
                ->has('perintahKerja.data', 1)
                ->where('perintahKerja.data.0.Nomor', 'WO-MILIK'));
        $this->get("/pemeliharaan/perintah-kerja/{$milikRekan->Id}")->assertForbidden();
        $this->get("/pemeliharaan/perintah-kerja/{$tanpaTeknisi->Id}")->assertForbidden();

        // Membuat dan membagi tiket tetap milik pemegang PerintahKerja.Kelola.
        $this->dalamOrganisasi(function () use ($teknisi, $milikRekan): void {
            $this->assertTrue(Gate::forUser($teknisi)->denies('create', PerintahKerja::class));
            $this->assertTrue(Gate::forUser($teknisi)->denies('assign', $milikRekan));
        });
    }

    /** Checklist dan inspeksi tetap berjalan: TEKNISI masih memegang Pemeliharaan.Kelola. */
    public function test_teknisi_tetap_dapat_melaksanakan_checklist_dan_inspeksi(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);

        $this->dalamOrganisasi(function () use ($teknisi): void {
            $this->assertTrue(Gate::forUser($teknisi)->allows('create', PelaksanaanDaftarPeriksa::class));
            $this->assertTrue(Gate::forUser($teknisi)->allows('create', Inspeksi::class));
        });
    }

    public function test_teknisi_melampirkan_foto_dan_catatan_hanya_ke_tiket_yang_ditugaskan(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $milik = $this->buatPerintahKerja('WO-FOTO', $teknisi, StatusPerintahKerja::Dikerjakan);
        $bukanMilik = $this->buatPerintahKerja('WO-ORANG', null);
        $this->actingAs($teknisi);

        $this->post('/kolaborasi/berkas', [
            'Berkas' => UploadedFile::fake()->image('sebelum.jpg'), 'JenisEntitas' => 'PerintahKerja', 'EntitasId' => $milik->Id, 'Kategori' => 'FotoSebelum',
        ])->assertSessionDoesntHaveErrors()->assertRedirect();
        $this->post('/kolaborasi/komentar', ['JenisEntitas' => 'PerintahKerja', 'EntitasId' => $milik->Id, 'Isi' => 'Bantalan berbunyi.'])
            ->assertRedirect();

        $this->post('/kolaborasi/berkas', [
            'Berkas' => UploadedFile::fake()->image('curi.jpg'), 'JenisEntitas' => 'PerintahKerja', 'EntitasId' => $bukanMilik->Id,
        ])->assertForbidden();
        $this->post('/kolaborasi/komentar', ['JenisEntitas' => 'PerintahKerja', 'EntitasId' => $bukanMilik->Id, 'Isi' => 'Iseng.'])
            ->assertForbidden();
        $this->getJson("/kolaborasi/lampiran?jenisEntitas=PerintahKerja&entitasId={$milik->Id}")->assertOk()->assertJsonCount(1);

        $this->dalamOrganisasi(function () use ($milik, $bukanMilik): void {
            $this->assertSame(1, LampiranEntitas::query()->where('EntitasId', $milik->Id)->count());
            $this->assertSame(1, KomentarEntitas::query()->where('EntitasId', $milik->Id)->count());
            $this->assertSame(0, LampiranEntitas::query()->where('EntitasId', $bukanMilik->Id)->count());
            $this->assertSame(0, KomentarEntitas::query()->where('EntitasId', $bukanMilik->Id)->count());
        });
    }

    public function test_pelapor_hanya_melihat_keluhannya_sendiri_termasuk_di_ruangan_yang_sama(): void
    {
        $ruangan = $this->dalamOrganisasi(fn () => Lokasi::create(['Kode' => 'R-'.uniqid(), 'Nama' => 'Ruang Produksi', 'Status' => 'Aktif']));
        $ruanganLain = $this->dalamOrganisasi(fn () => Lokasi::create(['Kode' => 'R-'.uniqid(), 'Nama' => 'Gudang Timur', 'Status' => 'Aktif']));
        $pelapor = $this->bisaMembukaDasbor($this->penggunaDenganPeran(['PELAPOR'], lokasiId: $ruangan->Id), $ruangan);
        $rekanSeruangan = $this->penggunaDenganPeran(['PELAPOR'], lokasiId: $ruangan->Id);
        $milik = $this->buatKeluhan('Mesin bocor', $pelapor, $ruangan);
        $milikRekan = $this->buatKeluhan('Lampu mati', $rekanSeruangan, $ruangan);
        $this->buatKeluhan('Pintu macet', $rekanSeruangan, $ruanganLain);
        $this->actingAs($pelapor);

        $this->get('/pemeliharaan/keluhan')
            ->assertInertia(fn ($halaman) => $halaman
                ->has('keluhan.data', 1)
                ->where('keluhan.data.0.Judul', 'Mesin bocor'));
        $this->get("/pemeliharaan/keluhan/{$milik->Id}")->assertOk();
        $this->get("/pemeliharaan/keluhan/{$milikRekan->Id}")->assertForbidden();
        // Tanpa Keluhan.Kelola, pelapor tidak lagi dapat memajukan status keluhan.
        $this->put("/pemeliharaan/keluhan/{$milik->Id}/status", ['Status' => 'Ditinjau', 'Versi' => 1])->assertForbidden();
    }

    public function test_pelapor_menambah_keterangan_hanya_pada_keluhannya_sendiri(): void
    {
        $pelapor = $this->penggunaDenganPeran(['PELAPOR']);
        $lain = $this->penggunaDenganPeran(['PELAPOR']);
        $milik = $this->buatKeluhan('AC menetes', $pelapor, null);
        $bukanMilik = $this->buatKeluhan('Kran patah', $lain, null);
        $this->actingAs($pelapor);

        $this->post('/kolaborasi/komentar', ['JenisEntitas' => 'Keluhan', 'EntitasId' => $milik->Id, 'Isi' => 'Tetesannya makin deras.'])
            ->assertRedirect();
        $this->post('/kolaborasi/komentar', ['JenisEntitas' => 'Keluhan', 'EntitasId' => $bukanMilik->Id, 'Isi' => 'Ikut.'])
            ->assertForbidden();

        $this->dalamOrganisasi(function () use ($milik, $bukanMilik): void {
            $this->assertSame(1, KomentarEntitas::query()->where('EntitasId', $milik->Id)->count());
            $this->assertSame(0, KomentarEntitas::query()->where('EntitasId', $bukanMilik->Id)->count());
        });
    }

    /** Menambah peran meja tanpa izin, dengan cakupan ruangan yang sama bila ada. */
    private function bisaMembukaDasbor(Pengguna $pengguna, ?Lokasi $lokasi = null): Pengguna
    {
        $this->dalamOrganisasi(function () use ($pengguna, $lokasi): void {
            $peran = Peran::create(['Kode' => 'MEJA-KOSONG-'.uniqid(), 'Nama' => 'Meja tanpa izin']);
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id, 'LokasiId' => $lokasi?->Id]);
        });

        return $pengguna;
    }

    private function buatPerintahKerja(string $nomor, ?Pengguna $teknisi, StatusPerintahKerja $status = StatusPerintahKerja::Ditugaskan): PerintahKerja
    {
        return $this->dalamOrganisasi(function () use ($nomor, $teknisi, $status): PerintahKerja {
            $perintahKerja = PerintahKerja::create([
                'Nomor' => $nomor,
                'Jenis' => 'Korektif',
                'Judul' => 'Perbaikan '.$nomor,
                'Prioritas' => 'Normal',
                'Status' => $status->value,
            ]);

            if ($teknisi !== null) {
                PenugasanPerintahKerja::create([
                    'PerintahKerjaId' => $perintahKerja->Id,
                    'PenggunaId' => $teknisi->Id,
                    'Status' => $status === StatusPerintahKerja::Ditugaskan ? 'Ditugaskan' : 'Diterima',
                ]);
            }

            return $perintahKerja;
        });
    }

    private function buatKeluhan(string $judul, Pengguna $pelapor, ?Lokasi $lokasi): Keluhan
    {
        return $this->dalamOrganisasi(fn (): Keluhan => Keluhan::create([
            'Nomor' => 'KLH-'.uniqid(),
            'Judul' => $judul,
            'Deskripsi' => 'Dilaporkan dari lapangan.',
            'LokasiId' => $lokasi?->Id,
            'PelaporId' => $pelapor->Id,
            'Status' => 'Baru',
            'DilaporkanPada' => now(),
        ]));
    }

    private function statusPerintahKerja(PerintahKerja $perintahKerja): string
    {
        return (string) $this->dalamOrganisasi(fn () => PerintahKerja::query()->whereKey($perintahKerja->Id)->value('Status'));
    }

    private function versi(PerintahKerja $perintahKerja): int
    {
        return (int) $this->dalamOrganisasi(fn () => PerintahKerja::query()->whereKey($perintahKerja->Id)->value('Versi'));
    }
}
