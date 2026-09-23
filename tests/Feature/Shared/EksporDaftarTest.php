<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ZipArchive;

/**
 * Ekspor daftar operasional, diuji lewat register aset.
 *
 * Yang dijaga di sini bukan sekadar berkasnya terbentuk, melainkan isinya:
 * ekspor yang tidak menghormati penyaring atau batas tenant tetap menghasilkan
 * berkas yang tampak benar, dan pemegangnya tidak punya cara tahu.
 */
class EksporDaftarTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $pengguna;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-EKS', 'Nama' => 'Organisasi Ekspor']);
        $this->pengguna = $this->buatPengguna($this->organisasi);
    }

    private function konteks(): KonteksOrganisasi
    {
        return app(KonteksOrganisasi::class);
    }

    private function buatPengguna(Organisasi $organisasi, bool $denganIzin = true): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($denganIzin) {
            $this->konteks()->tetapkan($organisasi->Id);
            $izin = Izin::firstOrCreate(['Kode' => 'Aset.Lihat'], ['Nama' => 'Lihat Aset', 'Modul' => 'Aset']);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            $this->konteks()->bersihkan();
        }

        return $pengguna;
    }

    /** @param  array<string, mixed>  $atribut */
    private function buatAset(Organisasi $organisasi, array $atribut = []): Aset
    {
        $this->konteks()->tetapkan($organisasi->Id);
        $kategori = KategoriAset::firstOrCreate(
            ['Kode' => 'KAT-'.$organisasi->Kode],
            ['Nama' => 'Alat Medis'],
        );
        $aset = Aset::create(array_merge([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => 'Aset '.uniqid(),
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
            'Versi' => 1,
        ], $atribut));
        $this->konteks()->bersihkan();

        return $aset;
    }

    private function unduh(string $kueri = ''): string
    {
        $respons = $this->actingAs($this->pengguna)->get('/aset/ekspor'.$kueri);
        $respons->assertOk();

        return $respons->streamedContent();
    }

    public function test_csv_memuat_kepala_kolom_dan_baris_asetnya(): void
    {
        $this->buatAset($this->organisasi, ['Nama' => 'Ventilator Ruang ICU']);

        $isi = $this->unduh();

        $this->assertStringContainsString('Kode Aset', $isi);
        $this->assertStringContainsString('Kondisi', $isi);
        $this->assertStringContainsString('Ventilator Ruang ICU', $isi);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function formatBerkas(): array
    {
        return [
            // fputcsv mengutip kepala kolom yang mengandung spasi.
            'csv' => ['csv', '"Kode Aset"'],
            'xlsx' => ['xlsx', 'PK'],
            'pdf' => ['pdf', '%PDF'],
        ];
    }

    #[DataProvider('formatBerkas')]
    public function test_setiap_format_menghasilkan_berkas_yang_dikenali(string $format, string $penanda): void
    {
        $this->buatAset($this->organisasi);

        $isi = $this->unduh('?format='.$format);

        $this->assertStringStartsWith($penanda, ltrim($isi, "\xEF\xBB\xBF"));
    }

    public function test_xlsx_memuat_nama_aset_di_dalam_lembarnya(): void
    {
        $this->buatAset($this->organisasi, ['Nama' => 'Inkubator Bayi']);

        $path = tempnam(sys_get_temp_dir(), 'uji').'.xlsx';
        file_put_contents($path, $this->unduh('?format=xlsx'));

        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $xml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();

            $this->assertStringContainsString('Inkubator Bayi', $xml);
        } finally {
            @unlink($path);
        }
    }

    /**
     * Isi unduhan dihasilkan sesudah middleware membersihkan konteks organisasi.
     * Tanpa konteks yang ditetapkan ulang di dalam alirannya, berkasnya terkirim
     * hanya berisi kepala kolom -- terlihat wajar, dan kosongnya baru ketahuan
     * setelah sampai ke tangan orang.
     */
    public function test_berkas_tidak_kosong_meski_dihasilkan_di_luar_permintaan(): void
    {
        $this->buatAset($this->organisasi, ['Nama' => 'Monitor Pasien']);
        $this->buatAset($this->organisasi, ['Nama' => 'Suction Pump']);

        $isi = $this->unduh();

        $this->assertSame(
            2,
            substr_count($isi, 'AST-'),
            'Seluruh baris aset organisasi ini harus ikut terbawa.',
        );
    }

    public function test_aset_organisasi_lain_tidak_ikut_terbawa(): void
    {
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'Organisasi Lain']);
        $this->buatAset($lain, ['Nama' => 'Milik Tetangga']);
        $this->buatAset($this->organisasi, ['Nama' => 'Milik Sendiri']);

        $isi = $this->unduh();

        $this->assertStringContainsString('Milik Sendiri', $isi);
        $this->assertStringNotContainsString('Milik Tetangga', $isi);
    }

    public function test_penyaring_di_layar_ikut_berlaku_pada_berkasnya(): void
    {
        $this->buatAset($this->organisasi, ['Nama' => 'Defibrilator']);
        $this->buatAset($this->organisasi, ['Nama' => 'Autoclave']);

        $isi = $this->unduh('?cari=Defibrilator');

        $this->assertStringContainsString('Defibrilator', $isi);
        $this->assertStringNotContainsString('Autoclave', $isi);
    }

    public function test_pengguna_tanpa_izin_lihat_aset_ditolak(): void
    {
        $tanpaIzin = $this->buatPengguna($this->organisasi, denganIzin: false);

        $this->actingAs($tanpaIzin)->get('/aset/ekspor')->assertForbidden();
    }
}
