<?php

declare(strict_types=1);

namespace Tests\Feature\Penyedia;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kontrak\Domain\Enums\StatusKontrak;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Penyedia\Domain\Enums\StatusPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KontakPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\PenilaianPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPesananPembelian;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusTagihanPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PenyediaTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(Organisasi $organisasi, ?string $kodeIzin = null): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== null) {
            $konteks = app(KonteksOrganisasi::class);
            $konteks->tetapkan($organisasi->Id);
            $izin = Izin::firstOrCreate(['Kode' => $kodeIzin], ['Nama' => $kodeIzin, 'Modul' => 'Uji']);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            $konteks->bersihkan();
        }

        return $pengguna;
    }

    private function buatPenyedia(Organisasi $organisasi): Penyedia
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $penyedia = Penyedia::create([
            'Kode' => 'PYD-'.uniqid(),
            'Nama' => 'Penyedia Uji',
            'Status' => StatusPenyedia::Aktif->value,
        ]);
        $konteks->bersihkan();

        return $penyedia;
    }

    public function test_pengguna_tanpa_izin_tidak_bisa_melihat_penyedia(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);

        $this->actingAs($pengguna)->get('/penyedia')->assertForbidden();
    }

    public function test_pengguna_dengan_izin_bisa_crud_penyedia(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');

        $this->actingAs($pengguna)->get('/penyedia')->assertOk();

        $this->actingAs($pengguna)->post('/penyedia', [
            'Kode' => 'PYD-001', 'Nama' => 'PT Sumber Makmur', 'Status' => 'Aktif',
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $penyedia = Penyedia::query()->where('Kode', 'PYD-001')->firstOrFail();
        $konteks->bersihkan();

        $this->actingAs($pengguna)->put("/penyedia/{$penyedia->Id}", [
            'Kode' => 'PYD-001', 'Nama' => 'PT Sumber Makmur Jaya', 'Status' => 'Nonaktif',
        ])->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseHas('Penyedia', ['Id' => $penyedia->Id, 'Nama' => 'PT Sumber Makmur Jaya', 'Status' => 'Nonaktif']);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->delete("/penyedia/{$penyedia->Id}")->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertSoftDeleted($penyedia);
        $konteks->bersihkan();
    }

    public function test_kode_penyedia_unik_per_organisasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');
        $this->buatPenyedia($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $existing = Penyedia::query()->firstOrFail();
        $konteks->bersihkan();

        $this->actingAs($pengguna)->post('/penyedia', [
            'Kode' => $existing->Kode, 'Nama' => 'Penyedia Lain', 'Status' => 'Aktif',
        ])->assertSessionHasErrors('Kode');
    }

    public function test_kategori_penyedia_tidak_bisa_dihapus_bila_masih_dipakai(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');
        $penyedia = $this->buatPenyedia($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $kategori = KategoriPenyedia::create(['Kode' => 'KAT-01', 'Nama' => 'Distributor']);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->post("/penyedia/{$penyedia->Id}/kategori", [
            'KategoriPenyediaId' => $kategori->Id,
        ])->assertSessionDoesntHaveErrors();

        $this->actingAs($pengguna)->delete("/penyedia/kategori/{$kategori->Id}")
            ->assertStatus(422);

        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseHas('KategoriPenyedia', ['Id' => $kategori->Id]);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->delete("/penyedia/{$penyedia->Id}/kategori/{$kategori->Id}")
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($pengguna)->delete("/penyedia/kategori/{$kategori->Id}")
            ->assertSessionDoesntHaveErrors();
    }

    public function test_hanya_satu_kontak_utama_per_penyedia(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');
        $penyedia = $this->buatPenyedia($organisasi);

        $this->actingAs($pengguna)->post("/penyedia/{$penyedia->Id}/kontak", [
            'Nama' => 'Budi', 'Utama' => true,
        ])->assertSessionDoesntHaveErrors();

        $this->actingAs($pengguna)->post("/penyedia/{$penyedia->Id}/kontak", [
            'Nama' => 'Siti', 'Utama' => true,
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $kontakUtama = KontakPenyedia::query()->where('PenyediaId', $penyedia->Id)->where('Utama', true)->get();
        $this->assertCount(1, $kontakUtama);
        $this->assertSame('Siti', $kontakUtama->first()->Nama);
        $konteks->bersihkan();
    }

    public function test_penilaian_penyedia_menghitung_skor_total_otomatis_dan_rekap(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');
        $penyedia = $this->buatPenyedia($organisasi);

        $this->actingAs($pengguna)->post("/penyedia/{$penyedia->Id}/penilaian", [
            'PeriodeMulai' => '2026-01-01', 'PeriodeSelesai' => '2026-03-31',
            'SkorKualitas' => 80, 'SkorKetepatanWaktu' => 90, 'SkorHarga' => 70, 'SkorLayanan' => 100,
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $penilaian = PenilaianPenyedia::query()->firstOrFail();
        $this->assertEquals(85.0, (float) $penilaian->SkorTotal);
        $this->assertNotNull($penilaian->DinilaiOleh);
        $konteks->bersihkan();

        $response = $this->actingAs($pengguna)->get("/penyedia/{$penyedia->Id}/penilaian");
        $response->assertOk();
        $this->assertSame(1, $response->json('rekap.JumlahPenilaian'));
        $this->assertEquals(85.0, (float) $response->json('rekap.SkorTotalRataRata'));
    }

    public function test_penyedia_lintas_organisasi_tidak_bisa_diakses(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $penggunaB = $this->buatPengguna($organisasiB, 'Penyedia.Kelola');
        $penyediaA = $this->buatPenyedia($organisasiA);

        $this->actingAs($penggunaB)->put("/penyedia/{$penyediaA->Id}", [
            'Kode' => 'HACK', 'Nama' => 'Hack', 'Status' => 'Aktif',
        ])->assertNotFound();
    }

    /**
     * Daftar penyedia hanya menjawab "siapa saja". Pertanyaan yang sebenarnya
     * dibawa orang -- sudah belanja berapa, berapa yang belum lunas, kontrak
     * mana yang masih jalan -- baru terjawab di halaman detail ini.
     */
    public function test_detail_penyedia_menampilkan_ringkasan_hubungan_dagang(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');
        $penyedia = $this->buatPenyedia($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);

        foreach ([1_000_000, 2_500_000] as $nilai) {
            PesananPembelian::create([
                'Nomor' => 'PO-'.uniqid(),
                'PenyediaId' => $penyedia->Id,
                'TanggalPesanan' => now()->subMonth()->toDateString(),
                'Total' => $nilai,
                'Status' => StatusPesananPembelian::Disetujui->value,
            ]);
        }

        TagihanPenyedia::create([
            'PenyediaId' => $penyedia->Id,
            'NomorTagihan' => 'INV-001',
            'TanggalTagihan' => now()->subWeek()->toDateString(),
            'Total' => 1_000_000,
            'Sisa' => 400_000,
            'Status' => StatusTagihanPenyedia::DibayarSebagian->value,
        ]);

        Kontrak::create([
            'PenyediaId' => $penyedia->Id,
            'Nomor' => 'KTR-001',
            'Nama' => 'Kontrak Pemeliharaan',
            'Jenis' => 'Layanan',
            'MulaiPada' => now()->subMonths(2)->toDateString(),
            'BerakhirPada' => now()->addYear()->toDateString(),
            'Nilai' => 9_000_000,
            'Status' => StatusKontrak::Aktif->value,
        ]);
        Kontrak::create([
            'PenyediaId' => $penyedia->Id,
            'Nomor' => 'KTR-002',
            'Nama' => 'Kontrak Lama',
            'Jenis' => 'Layanan',
            'MulaiPada' => now()->subYears(2)->toDateString(),
            'BerakhirPada' => now()->subYear()->toDateString(),
            'Status' => StatusKontrak::Berakhir->value,
        ]);

        $kategori = KategoriAset::create(['Kode' => 'KAT-'.uniqid(), 'Nama' => 'Mesin']);
        Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'PenyediaId' => $penyedia->Id,
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => 'Genset',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
            'KodeQr' => (string) Str::ulid(),
            'Versi' => 1,
        ]);

        $konteks->bersihkan();

        $this->actingAs($pengguna)->get("/penyedia/{$penyedia->Id}")
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->component('Penyedia/Show')
                ->where('penyedia.Nama', 'Penyedia Uji')
                ->where('ringkasan.JumlahPesanan', 2)
                ->where('ringkasan.NilaiPesanan', 3500000)
                ->where('ringkasan.SisaTagihan', 400000)
                // Hanya kontrak berstatus Aktif yang dihitung; yang sudah berakhir tidak.
                ->where('ringkasan.JumlahKontrakAktif', 1)
                ->where('ringkasan.JumlahAset', 1)
                ->etc());
    }

    public function test_riwayat_pengadaan_penyedia_mengumpulkan_pesanan_dan_tagihan(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');
        $penyedia = $this->buatPenyedia($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);

        $pesanan = PesananPembelian::create([
            'Nomor' => 'PO-9001',
            'PenyediaId' => $penyedia->Id,
            'TanggalPesanan' => now()->subMonth()->toDateString(),
            'Total' => 750_000,
            'Status' => StatusPesananPembelian::Dikirim->value,
        ]);
        TagihanPenyedia::create([
            'PenyediaId' => $penyedia->Id,
            'PesananPembelianId' => $pesanan->Id,
            'NomorTagihan' => 'INV-9001',
            'TanggalTagihan' => now()->subDays(10)->toDateString(),
            'JatuhTempo' => now()->addDays(20)->toDateString(),
            'Total' => 750_000,
            'Sisa' => 750_000,
            'Status' => StatusTagihanPenyedia::BelumDibayar->value,
        ]);

        $konteks->bersihkan();

        $respons = $this->actingAs($pengguna)->getJson("/penyedia/{$penyedia->Id}/riwayat-pengadaan");

        $respons->assertOk();
        $respons->assertJsonPath('ringkasan.JumlahPesanan', 1);
        $respons->assertJsonPath('ringkasan.SisaTagihan', 750000);
        $respons->assertJsonPath('pesanan.data.0.Nomor', 'PO-9001');
        $respons->assertJsonPath('tagihan.data.0.NomorTagihan', 'INV-9001');
        // Nomor pesanan ikut dibawa supaya tagihan dapat ditelusuri tanpa membuka modul lain.
        $respons->assertJsonPath('tagihan.data.0.NomorPesanan', 'PO-9001');
    }

    public function test_riwayat_layanan_penyedia_mengumpulkan_kontrak_dan_aset(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');
        $penyedia = $this->buatPenyedia($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);

        Kontrak::create([
            'PenyediaId' => $penyedia->Id,
            'Nomor' => 'KTR-7001',
            'Nama' => 'Kontrak Kalibrasi Tahunan',
            'Jenis' => 'Layanan',
            'MulaiPada' => now()->subMonth()->toDateString(),
            'BerakhirPada' => now()->addMonths(11)->toDateString(),
            'Nilai' => 5_000_000,
            'Status' => StatusKontrak::Aktif->value,
        ]);

        $kategori = KategoriAset::create(['Kode' => 'KAT-'.uniqid(), 'Nama' => 'Instrumen']);
        Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'PenyediaId' => $penyedia->Id,
            'KodeAset' => 'AST-7001',
            'Nama' => 'Timbangan Analitik',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
            'KodeQr' => (string) Str::ulid(),
            'Versi' => 1,
        ]);

        $konteks->bersihkan();

        $respons = $this->actingAs($pengguna)->getJson("/penyedia/{$penyedia->Id}/riwayat-layanan");

        $respons->assertOk();
        $respons->assertJsonPath('ringkasan.JumlahKontrakAktif', 1);
        $respons->assertJsonPath('ringkasan.NilaiKontrakAktif', 5000000);
        $respons->assertJsonPath('ringkasan.JumlahAset', 1);
        $respons->assertJsonPath('kontrak.data.0.Nomor', 'KTR-7001');
        $respons->assertJsonPath('aset.data.0.Nama', 'Timbangan Analitik');
    }

    public function test_detail_penyedia_organisasi_lain_tidak_dapat_dibuka(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi B']);
        $penggunaB = $this->buatPengguna($organisasiB, 'Penyedia.Kelola');
        $penyediaA = $this->buatPenyedia($organisasiA);

        $this->actingAs($penggunaB)->get("/penyedia/{$penyediaA->Id}")->assertNotFound();
        $this->actingAs($penggunaB)
            ->getJson("/penyedia/{$penyediaA->Id}/riwayat-pengadaan")
            ->assertNotFound();
    }

    public function test_detail_penyedia_ditolak_tanpa_izin(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi']);
        $penyedia = $this->buatPenyedia($organisasi);
        $tanpaIzin = $this->buatPengguna($organisasi);

        $this->actingAs($tanpaIzin)->get("/penyedia/{$penyedia->Id}")->assertForbidden();
        $this->actingAs($tanpaIzin)
            ->getJson("/penyedia/{$penyedia->Id}/riwayat-layanan")
            ->assertForbidden();
    }
}
