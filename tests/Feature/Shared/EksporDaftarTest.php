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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

        /*
         * Waktu dibekukan karena kop mencantumkan jam cetaknya. Tanpa ini
         * assertion membaca jam KEDUA KALINYA, sesudah responsnya selesai:
         * kop yang dicetak pada 10:59:59 diperiksa terhadap 11:00:00 dan
         * testnya merah tanpa ada yang rusak. Kegagalan sporadis semacam itu
         * tampak seperti regresi yang tidak ada.
         */
        // Disebut dalam UTC, bukan string polos: string polos diurai memakai
        // app.timezone (Asia/Jakarta), sehingga hitungan zona waktu di bawah
        // bergeser tujuh jam tanpa terlihat di test-nya.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-15 03:20:00', 'UTC'));

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-EKS', 'Nama' => 'Organisasi Ekspor']);
        $this->pengguna = $this->buatPengguna($this->organisasi);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
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
            // CSV tidak punya penanda biner; yang membuka berkasnya sekarang
            // adalah baris pertama kop, dan fputcsv mengutip nilai berspasi.
            'csv' => ['csv', 'Organisasi,"Organisasi Ekspor"'],
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

    /**
     * Kop berkas ekspor (kepala dokumen).
     *
     * Berkas ekspor beredar di luar aplikasi dan tidak dapat ditarik kembali.
     * Tanpa kop, yang memegangnya tidak tahu ini milik rumah sakit mana, daftar
     * apa, sejak kapan angkanya berlaku, dan dengan penyaring apa -- berkasnya
     * terlihat sah dan tetap menyesatkan.
     */
    /** @return array<string, array{0: string}> */
    public static function namaFormat(): array
    {
        return ['csv' => ['csv'], 'xlsx' => ['xlsx'], 'pdf' => ['pdf']];
    }

    #[DataProvider('namaFormat')]
    public function test_kop_menyebut_nama_organisasi_di_setiap_format(string $format): void
    {
        $this->buatAset($this->organisasi);

        $teks = $this->teksBerkas($format, $this->unduh('?format='.$format));

        $this->assertStringContainsString('Organisasi Ekspor', $teks);
    }

    #[DataProvider('namaFormat')]
    public function test_kop_menyebut_judul_dan_waktu_cetak_di_setiap_format(string $format): void
    {
        $this->buatAset($this->organisasi);

        $teks = $this->teksBerkas($format, $this->unduh('?format='.$format));

        // Judulnya diturunkan dari nama dasar berkas: `daftar-aset`.
        $this->assertStringContainsString('Daftar Aset', $teks);
        $this->assertStringContainsString('Asia/Jakarta', $teks);
        $this->assertStringContainsString(
            // Nilai harfiah, bukan now(): waktu sudah dibekukan di setUp, dan
            // assertion yang menghitung ulang jamnya sendiri akan tetap lulus
            // sekalipun kop mencetak jam server alih-alih jam organisasinya.
            '15-06-2026',
            $teks,
            'Tanggal cetak harus tercantum dalam zona waktu organisasinya.',
        );
    }

    #[DataProvider('namaFormat')]
    public function test_kop_tidak_pernah_menyebut_organisasi_lain(string $format): void
    {
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'Rumah Sakit Tetangga']);
        $this->buatAset($lain);
        $this->buatAset($this->organisasi);

        $teks = $this->teksBerkas($format, $this->unduh('?format='.$format));

        $this->assertStringContainsString('Organisasi Ekspor', $teks);
        $this->assertStringNotContainsString('Rumah Sakit Tetangga', $teks);
    }

    #[DataProvider('namaFormat')]
    public function test_kop_mencetak_penyaring_yang_sedang_berlaku(string $format): void
    {
        $this->buatAset($this->organisasi, ['Nama' => 'Defibrilator']);

        $teks = $this->teksBerkas($format, $this->unduh('?format='.$format.'&cari=Defibrilator'));

        $this->assertStringContainsString('Penyaring', $teks);
        $this->assertStringContainsString('Cari: Defibrilator', $teks);
        $this->assertStringNotContainsString(
            'Format: '.$format,
            $teks,
            'Parameter yang hanya mengatur bentuk berkas bukan penyaring isinya.',
        );
    }

    public function test_kop_menyebut_nama_legal_bila_berbeda_dari_nama_sehari_hari(): void
    {
        $this->organisasi->update(['NamaLegal' => 'RSUD Kabupaten Sragen']);
        $this->buatAset($this->organisasi);

        $isi = $this->unduh();

        $this->assertStringContainsString('Organisasi Ekspor (RSUD Kabupaten Sragen)', $isi);
    }

    public function test_waktu_cetak_mengikuti_zona_waktu_organisasinya(): void
    {
        $this->organisasi->update(['ZonaWaktu' => 'Asia/Jayapura']);
        $this->buatAset($this->organisasi);

        $isi = $this->unduh();

        $this->assertStringContainsString('Asia/Jayapura', $isi);
        // 03:20 UTC adalah 12:20 di Jayapura (UTC+9) dan 10:20 di Jakarta
        // (UTC+7). Nilai harfiah inilah yang membuktikan kop memakai zona waktu
        // organisasinya: dihitung ulang dengan now(), assertion ini akan lulus
        // pada zona mana pun.
        $this->assertStringContainsString('15-06-2026 12:20', $isi);
        $this->assertStringNotContainsString('15-06-2026 10:20', $isi);
    }

    /**
     * Nama organisasi berasal dari data tenant, jadi ia melewati penetralan
     * rumus yang sama dengan isi tabelnya. Kop yang tidak dinetralkan memindahkan
     * injeksi rumus ke baris pertama berkas, tempat yang paling pasti dibaca.
     */
    public function test_nama_organisasi_berawalan_rumus_dinetralkan_di_kop_csv(): void
    {
        $this->organisasi->update(['Nama' => '=cmd|\' /c calc\'!A0']);
        $this->buatAset($this->organisasi);

        $isi = $this->unduh();

        $this->assertStringContainsString("'=cmd|' /c calc'!A0", $isi);
    }

    public function test_nama_organisasi_berawalan_rumus_tidak_menjadi_sel_rumus_xlsx(): void
    {
        $this->organisasi->update(['Nama' => '=cmd|\' /c calc\'!A0']);
        $this->buatAset($this->organisasi);

        $xml = $this->teksBerkas('xlsx', $this->unduh('?format=xlsx'));

        $this->assertStringNotContainsString('<f>', $xml, 'Nama organisasi tidak boleh menjadi sel rumus.');
        // Ketiadaan <f> saja juga benar bila kopnya memang tidak ditulis;
        // nilai yang sudah dinetralkan harus benar-benar ada di lembarnya.
        $this->assertStringContainsString('&#039;=cmd', $xml);
    }

    /** PNG 1x1 yang sah; yang diuji logonya ikut tercetak, bukan rupanya. */
    private const PNG_SATU_PIKSEL = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_kop_pdf_menyisipkan_logo_yang_tersimpan_di_disk_aplikasi(): void
    {
        Storage::fake('public', ['url' => (string) config('filesystems.disks.public.url')]);
        Storage::disk('public')->put('organisasi/logo.png', (string) base64_decode(self::PNG_SATU_PIKSEL, true));
        $this->organisasi->update(['LogoUrl' => Storage::disk('public')->url('organisasi/logo.png')]);
        $this->buatAset($this->organisasi);

        $isi = $this->unduh('?format=pdf');

        $this->assertStringContainsString('/Subtype /Image', $isi, 'Logo lokal seharusnya ikut tercetak di PDF.');
        $this->assertStringContainsString('Organisasi Ekspor', $this->teksPdf($isi));
    }

    /**
     * LogoUrl diisi tenant. Dompdf berjalan dengan isRemoteEnabled mati, jadi
     * URL jarak jauh tidak pernah diambil server -- dan ekspornya tetap jadi,
     * hanya tanpa logo.
     */
    public function test_logo_jarak_jauh_dilewati_tanpa_menggagalkan_ekspor_pdf(): void
    {
        $this->organisasi->update(['LogoUrl' => 'https://penyerang.test/logo.png']);
        $this->buatAset($this->organisasi);

        $isi = $this->unduh('?format=pdf');

        $this->assertStringNotContainsString('/Subtype /Image', $isi, 'Gambar jarak jauh tidak boleh masuk ke PDF.');
        $this->assertStringContainsString('Organisasi Ekspor', $this->teksPdf($isi));
    }

    /** Isi terbaca dari berkas apa pun formatnya, supaya kopnya diperiksa di berkas yang sungguhan. */
    private function teksBerkas(string $format, string $isi): string
    {
        return match ($format) {
            'xlsx' => $this->lembarXlsx($isi),
            'pdf' => $this->teksPdf($isi),
            default => $isi,
        };
    }

    private function lembarXlsx(string $isi): string
    {
        $path = tempnam(sys_get_temp_dir(), 'uji').'.xlsx';
        file_put_contents($path, $isi);

        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true, 'Berkas XLSX tidak dapat dibuka.');
            $xml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();

            $this->assertNotSame('', $xml, 'sheet1.xml tidak ditemukan di dalam XLSX.');

            return $xml;
        } finally {
            @unlink($path);
        }
    }

    /**
     * Teks yang sungguh tercetak di PDF-nya, bukan HTML sebelum dirender.
     *
     * Dompdf memampatkan aliran isinya dan menulis teks sebagai UTF-16BE, jadi
     * berkasnya dibuka dulu: aliran dilepas mampatnya, lalu byte NUL penyela
     * antarhurufnya dibuang. Tanpa langkah ini isi PDF tidak dapat dicari sama
     * sekali, dan penjaganya akan berhenti pada '%PDF' -- yang hanya
     * membuktikan berkasnya PDF, bukan bahwa kopnya ada di dalamnya.
     */
    private function teksPdf(string $isi): string
    {
        $this->assertStringStartsWith('%PDF', $isi);

        preg_match_all('/stream\r?\n(.*?)endstream/s', $isi, $cocok);

        $teks = '';
        foreach ($cocok[1] as $aliran) {
            $lepas = @gzuncompress($aliran);

            if ($lepas !== false) {
                $teks .= $lepas;
            }
        }

        $this->assertNotSame('', $teks, 'Tidak ada aliran PDF yang dapat dibaca.');

        return str_replace("\x00", '', $teks);
    }
}
