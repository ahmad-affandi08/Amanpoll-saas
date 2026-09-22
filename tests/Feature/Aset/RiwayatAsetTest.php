<?php

declare(strict_types=1);

namespace Tests\Feature\Aset;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RiwayatAsetTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $pengguna;

    private Aset $aset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-RWT', 'Nama' => 'Organisasi Riwayat']);
        $this->pengguna = $this->buatPengguna();

        $this->konteks()->tetapkan($this->organisasi->Id);
        $kategori = KategoriAset::create(['Kode' => 'KAT-1', 'Nama' => 'Mesin']);
        $this->aset = Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-0001',
            'Nama' => 'Kompresor Utama',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
            'KodeQr' => (string) Str::ulid(),
            'DibuatOleh' => $this->pengguna->Id,
            'Versi' => 1,
        ]);
        $this->konteks()->bersihkan();
    }

    private function konteks(): KonteksOrganisasi
    {
        return app(KonteksOrganisasi::class);
    }

    private function buatPengguna(): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Teknisi',
            'Email' => 'teknisi+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $this->konteks()->tetapkan($this->organisasi->Id);
        $izin = Izin::firstOrCreate(['Kode' => 'Aset.Lihat'], ['Nama' => 'Lihat Aset', 'Modul' => 'Aset']);
        $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
        $this->konteks()->bersihkan();

        return $pengguna;
    }

    public function test_riwayat_pemeliharaan_mengumpulkan_keluhan_perintah_kerja_dan_waktu_henti(): void
    {
        $this->konteks()->tetapkan($this->organisasi->Id);

        Keluhan::create([
            'Nomor' => 'KEL-0001',
            'AsetId' => $this->aset->Id,
            'Judul' => 'Mesin berisik',
            'Deskripsi' => 'Terdengar bunyi kasar dari sisi kompresor.',
            'Prioritas' => 'Tinggi',
            'Status' => 'Baru',
            'Sumber' => 'Manual',
            'DilaporkanPada' => now()->subDays(3),
            'Versi' => 1,
        ]);

        $perintah = PerintahKerja::create([
            'Nomor' => 'PK-0001',
            'Jenis' => 'Korektif',
            'Judul' => 'Ganti bearing',
            'Prioritas' => 'Tinggi',
            'Status' => 'Ditutup',
            'DiselesaikanPada' => now()->subDay(),
            'DibuatOleh' => $this->pengguna->Id,
            'Versi' => 1,
        ]);
        PerintahKerjaAset::create([
            'PerintahKerjaId' => $perintah->Id,
            'AsetId' => $this->aset->Id,
            'Utama' => true,
        ]);

        WaktuHentiAset::create([
            'AsetId' => $this->aset->Id,
            'PerintahKerjaId' => $perintah->Id,
            'MulaiPada' => now()->subDays(2),
            'SelesaiPada' => now()->subDay(),
            'DurasiMenit' => 90,
            'Jenis' => 'TidakTerencana',
            'Alasan' => 'Bearing pecah',
        ]);

        $this->konteks()->bersihkan();

        $respons = $this->actingAs($this->pengguna)->getJson("/aset/{$this->aset->Id}/riwayat-pemeliharaan");

        $respons->assertOk();
        $respons->assertJsonPath('ringkasan.JumlahKeluhan', 1);
        $respons->assertJsonPath('ringkasan.JumlahPerintahKerja', 1);
        $respons->assertJsonPath('ringkasan.TotalMenitHenti', 90);
        $respons->assertJsonPath('keluhan.data.0.Judul', 'Mesin berisik');
        $respons->assertJsonPath('perintahKerja.data.0.Nomor', 'PK-0001');
        $respons->assertJsonPath('perintahKerja.data.0.Utama', true);
        $respons->assertJsonPath('waktuHenti.data.0.DurasiMenit', 90);
    }

    public function test_riwayat_kalibrasi_memuat_rencana_pelaksanaan_dan_jatuh_tempo(): void
    {
        $this->konteks()->tetapkan($this->organisasi->Id);

        $jenis = JenisKalibrasi::create(['Kode' => 'JKL-1', 'Nama' => 'Kalibrasi Tekanan']);
        RencanaKalibrasi::create([
            'AsetId' => $this->aset->Id,
            'JenisKalibrasiId' => $jenis->Id,
            'IntervalHari' => 365,
            'TanggalMulai' => now()->subYear()->toDateString(),
            'TanggalBerikutnya' => now()->addMonth()->toDateString(),
            'PeringatanHariSebelum' => 30,
            'Aktif' => true,
        ]);
        PelaksanaanKalibrasi::create([
            'Nomor' => 'KAL-0001',
            'AsetId' => $this->aset->Id,
            'JenisKalibrasiId' => $jenis->Id,
            'TanggalKalibrasi' => now()->subYear()->toDateString(),
            'TanggalBerlakuSampai' => now()->addMonth()->toDateString(),
            'Hasil' => 'Lolos',
            'NomorSertifikat' => 'SERT-001',
        ]);

        $this->konteks()->bersihkan();

        $respons = $this->actingAs($this->pengguna)->getJson("/aset/{$this->aset->Id}/riwayat-kalibrasi");

        $respons->assertOk();
        $respons->assertJsonPath('ringkasan.JumlahPelaksanaan', 1);
        $respons->assertJsonPath('ringkasan.HasilTerakhir', 'Lolos');
        $respons->assertJsonPath('ringkasan.JatuhTempoBerikutnya', now()->addMonth()->toDateString());
        $respons->assertJsonPath('rencana.0.JenisKalibrasi', 'Kalibrasi Tekanan');
        $respons->assertJsonPath('pelaksanaan.data.0.NomorSertifikat', 'SERT-001');
    }

    public function test_riwayat_aset_organisasi_lain_tidak_dapat_dibaca(): void
    {
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'Organisasi Lain']);
        $this->konteks()->tetapkan($lain->Id);
        $kategoriLain = KategoriAset::create(['Kode' => 'KAT-L', 'Nama' => 'Mesin Lain']);
        $asetLain = Aset::create([
            'KategoriAsetId' => $kategoriLain->Id,
            'KodeAset' => 'AST-LAIN',
            'Nama' => 'Aset Tetangga',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
            'KodeQr' => (string) Str::ulid(),
            'Versi' => 1,
        ]);
        $this->konteks()->bersihkan();

        $this->actingAs($this->pengguna)
            ->getJson("/aset/{$asetLain->Id}/riwayat-pemeliharaan")
            ->assertNotFound();
    }

    public function test_pengguna_tanpa_izin_lihat_aset_ditolak(): void
    {
        $tanpaIzin = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Tanpa Izin',
            'Email' => 'tanpa+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $this->actingAs($tanpaIzin)
            ->getJson("/aset/{$this->aset->Id}/riwayat-kalibrasi")
            ->assertForbidden();
    }
}
