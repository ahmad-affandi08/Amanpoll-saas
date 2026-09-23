<?php

declare(strict_types=1);

namespace Tests\Feature\Kepatuhan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kepatuhan\Domain\Enums\StatusSertifikasiAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SertifikasiAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ekspor daftar sertifikasi aset.
 *
 * Daftar ini yang dilampirkan ke berkas akreditasi, jadi yang dijaga bukan
 * berkasnya terbentuk melainkan isinya: berkas yang mengabaikan penyaring atau
 * batas tenant tetap tampak sah, dan yang melampirkannya tidak punya cara tahu.
 */
final class EksporSertifikasiAsetTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $pengguna;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-SRT', 'Nama' => 'RS Sertifikasi']);
        $this->pengguna = $this->buatPengguna($this->organisasi, 'Kepatuhan.Kelola');
    }

    private function konteks(): KonteksOrganisasi
    {
        return app(KonteksOrganisasi::class);
    }

    private function buatPengguna(Organisasi $organisasi, ?string $kodeIzin): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== null) {
            $this->konteks()->tetapkan($organisasi->Id);
            $izin = Izin::firstOrCreate(['Kode' => $kodeIzin], ['Nama' => $kodeIzin, 'Modul' => 'Kepatuhan']);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            $this->konteks()->bersihkan();
        }

        return $pengguna;
    }

    /** @param  array<string, mixed>  $atribut */
    private function buatSertifikat(Organisasi $organisasi, array $atribut = []): SertifikasiAset
    {
        $this->konteks()->tetapkan($organisasi->Id);

        $kategori = KategoriAset::firstOrCreate(
            ['Kode' => 'KAT-'.$organisasi->Kode],
            ['Nama' => 'Alat Medis'],
        );
        $aset = Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => $atribut['NamaAset'] ?? 'Aset '.uniqid(),
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
            'Versi' => 1,
        ]);
        unset($atribut['NamaAset']);

        $sertifikat = SertifikasiAset::create(array_merge([
            'AsetId' => $aset->Id,
            'JenisSertifikasi' => 'Kalibrasi',
            'NomorSertifikat' => 'SRT-'.uniqid(),
            'Penerbit' => 'BPFK Surakarta',
            'TerbitPada' => '2025-01-10',
            'BerlakuSampai' => '2026-01-09',
            'Status' => StatusSertifikasiAset::Aktif->value,
        ], $atribut));

        $this->konteks()->bersihkan();

        return $sertifikat;
    }

    private function unduh(string $kueri = ''): string
    {
        $respons = $this->actingAs($this->pengguna)->get('/kepatuhan/sertifikasi/ekspor'.$kueri);
        $respons->assertOk();

        return $respons->streamedContent();
    }

    public function test_berkas_memuat_kolom_dan_isi_sertifikatnya(): void
    {
        $this->buatSertifikat($this->organisasi, [
            'NomorSertifikat' => 'SRT-AKREDITASI-01',
            'NamaAset' => 'Ventilator ICU 3',
        ]);

        $isi = $this->unduh();

        $this->assertStringContainsString('Nomor Sertifikat', $isi);
        $this->assertStringContainsString('Berlaku Sampai', $isi);
        $this->assertStringContainsString('SRT-AKREDITASI-01', $isi);
        $this->assertStringContainsString('Ventilator ICU 3', $isi);
        $this->assertStringContainsString('BPFK Surakarta', $isi);
    }

    public function test_penyaring_status_ikut_ke_berkasnya(): void
    {
        $this->buatSertifikat($this->organisasi, [
            'NomorSertifikat' => 'SRT-MASIH-AKTIF',
            'Status' => StatusSertifikasiAset::Aktif->value,
        ]);
        $this->buatSertifikat($this->organisasi, [
            'NomorSertifikat' => 'SRT-SUDAH-DICABUT',
            'Status' => StatusSertifikasiAset::Dicabut->value,
        ]);

        $isi = $this->unduh('?status='.StatusSertifikasiAset::Dicabut->value);

        $this->assertStringContainsString('SRT-SUDAH-DICABUT', $isi);
        $this->assertStringNotContainsString('SRT-MASIH-AKTIF', $isi);
    }

    public function test_pencarian_nomor_sertifikat_ikut_ke_berkasnya(): void
    {
        $this->buatSertifikat($this->organisasi, ['NomorSertifikat' => 'SRT-DICARI-99']);
        $this->buatSertifikat($this->organisasi, ['NomorSertifikat' => 'SRT-LAINNYA-01']);

        $isi = $this->unduh('?cari=DICARI');

        $this->assertStringContainsString('SRT-DICARI-99', $isi);
        $this->assertStringNotContainsString('SRT-LAINNYA-01', $isi);
    }

    public function test_sertifikat_organisasi_lain_tidak_pernah_ikut(): void
    {
        $lain = Organisasi::create(['Kode' => 'ORG-SRT-LAIN', 'Nama' => 'RS Tetangga']);
        $this->buatSertifikat($lain, ['NomorSertifikat' => 'SRT-TETANGGA-01']);
        $this->buatSertifikat($this->organisasi, ['NomorSertifikat' => 'SRT-SENDIRI-01']);

        $isi = $this->unduh();

        $this->assertStringContainsString('SRT-SENDIRI-01', $isi);
        $this->assertStringNotContainsString('SRT-TETANGGA-01', $isi);
    }

    public function test_pengguna_tanpa_izin_kepatuhan_ditolak(): void
    {
        $tanpaIzin = $this->buatPengguna($this->organisasi, null);

        $this->actingAs($tanpaIzin)->get('/kepatuhan/sertifikasi/ekspor')->assertForbidden();
    }
}
