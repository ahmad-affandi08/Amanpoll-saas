<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pelaporan;

use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Domain\Pelaporan\Application\Services\LayananEksporLaporan;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Jobs\BuatEksporLaporan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Application\Actions\UnggahLogoOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Ekspor\FormatEkspor;
use App\Shared\Infrastructure\Kompresi\MetodeKompresi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Dukungan\GambarUji;

/** Ekspor laporan (21.05): permintaan masuk antrean, berkasnya dibuat dalam ketiga format. */
final class EksporLaporanTest extends KasusPelaporan
{
    public function test_permintaan_ekspor_dikirim_ke_antrean_bukan_dikerjakan_dalam_permintaan(): void
    {
        Queue::fake();
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Ringkasan pekerjaan',
                'Format' => FormatEkspor::Csv->value,
                'KunciKpi' => ['perintah_kerja.aktif'],
            ])
            ->assertRedirect()
            ->assertSessionHas('sukses');

        Queue::assertPushedOn('low', BuatEksporLaporan::class);
    }

    public function test_permintaan_yang_memuat_kpi_di_luar_kewenangan_ditolak_terang_terangan(): void
    {
        Queue::fake();
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Diam-diam',
                'Format' => FormatEkspor::Csv->value,
                'KunciKpi' => ['perintah_kerja.aktif', 'aset.jumlah'],
            ])
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_format_di_luar_yang_didukung_gagal_validasi(): void
    {
        Queue::fake();
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Format karangan',
                'Format' => 'Docx',
                'KunciKpi' => ['perintah_kerja.aktif'],
            ])
            ->assertSessionHasErrors('Format');

        Queue::assertNothingPushed();
    }

    /**
     * @return list<array{0: FormatEkspor, 1: string}>
     */
    public static function formatEkspor(): array
    {
        return [
            'csv' => [FormatEkspor::Csv, 'csv'],
            'xlsx' => [FormatEkspor::Xlsx, 'xlsx'],
            'pdf' => [FormatEkspor::Pdf, 'pdf'],
        ];
    }

    #[DataProvider('formatEkspor')]
    public function test_job_menghasilkan_berkas_yang_dapat_dibaca(FormatEkspor $format, string $ekstensi): void
    {
        Storage::fake('local');
        $pengguna = $this->buatPengguna();
        $this->buatPerintahKerja();

        $this->jalankanJob($pengguna, $format, 'Ringkasan pekerjaan');

        $berkas = Berkas::query()->where('DiunggahOleh', $pengguna->Id)->firstOrFail();

        $this->assertSame(LayananEksporLaporan::JENIS_BERKAS, $berkas->DataTambahan['Jenis']);
        $this->assertSame($format->value, $berkas->DataTambahan['Format']);
        $this->assertSame($format->jenisMime(), $berkas->JenisMime);
        $this->assertStringEndsWith('.'.$ekstensi, $berkas->NamaAsli);
        $this->assertGreaterThan(0, $berkas->DataTambahan['JumlahBaris']);

        Storage::disk($berkas->MediaPenyimpanan)->assertExists($berkas->LokasiPenyimpanan);
        $isi = $this->isiAsli($berkas);

        $this->assertSame((int) $berkas->UkuranByte, strlen($isi));
        $this->assertSame($berkas->HashSha256, hash('sha256', $isi));
        $this->assertNotSame('', $isi);
    }

    public function test_berkas_csv_memuat_kepala_dan_rumus_kpi(): void
    {
        Storage::fake('local');
        $pengguna = $this->buatPengguna();
        $this->buatPerintahKerja();

        $this->jalankanJob($pengguna, FormatEkspor::Csv, 'Ringkasan pekerjaan');

        $berkas = Berkas::query()->where('DiunggahOleh', $pengguna->Id)->firstOrFail();
        $isi = $this->isiAsli($berkas);

        // Rumus ikut terbawa supaya angka tetap dapat ditelusuri setelah berkas lepas dari aplikasi (Gate 21).
        $this->assertStringContainsString('Formula', $isi);
        $this->assertStringContainsString('Perintah Kerja Aktif', $isi);
        $this->assertStringContainsString('COUNT(PerintahKerja) yang belum Selesai', $isi);
    }

    public function test_pemesan_diberi_tahu_saat_ekspor_selesai(): void
    {
        Storage::fake('local');
        $pengguna = $this->buatPengguna();
        $this->buatPerintahKerja();

        $this->jalankanJob($pengguna, FormatEkspor::Csv, 'Ringkasan pekerjaan');

        $this->assertSame(
            1,
            Notifikasi::query()
                ->where('PenggunaId', $pengguna->Id)
                ->where('JenisPeristiwa', 'Laporan.EksporSelesai')
                ->count(),
        );
    }

    public function test_ekspor_hanya_dapat_diunduh_pemesannya(): void
    {
        Storage::fake('local');
        $pemesan = $this->buatPengguna();
        $lain = $this->buatPengguna(['Laporan.Lihat']);
        $this->buatPerintahKerja();

        $this->jalankanJob($pemesan, FormatEkspor::Csv, 'Ringkasan pekerjaan');
        $berkas = Berkas::query()->where('DiunggahOleh', $pemesan->Id)->firstOrFail();

        $this->actingAs($lain)
            ->get(route('pelaporan.ekspor.unduh', $berkas))
            ->assertForbidden();

        $this->actingAs($pemesan)
            ->get(route('pelaporan.ekspor.unduh', $berkas))
            ->assertOk()
            ->assertDownload($berkas->NamaAsli);
    }

    public function test_berkas_yang_bukan_hasil_ekspor_tidak_dapat_diunduh_lewat_rute_ini(): void
    {
        $pengguna = $this->buatPengguna();
        $berkas = Berkas::create([
            'OrganisasiId' => $this->organisasi->Id,
            'NamaAsli' => 'lampiran.pdf',
            'NamaPenyimpanan' => 'lampiran.pdf',
            'MediaPenyimpanan' => 'local',
            'LokasiPenyimpanan' => 'lampiran/lampiran.pdf',
            'JenisMime' => 'application/pdf',
            'UkuranByte' => 10,
            'HashSha256' => hash('sha256', 'lampiran'),
            'DiunggahOleh' => $pengguna->Id,
        ]);

        $this->actingAs($pengguna)
            ->get(route('pelaporan.ekspor.unduh', $berkas))
            ->assertForbidden();
    }

    public function test_job_melewati_pengguna_yang_sudah_tidak_aktif(): void
    {
        Storage::fake('local');
        $pengguna = $this->buatPengguna();
        $pengguna->update(['Status' => 'Nonaktif']);

        $this->jalankanJob($pengguna, FormatEkspor::Csv, 'Ringkasan pekerjaan');

        $this->assertSame(0, Berkas::query()->where('DiunggahOleh', $pengguna->Id)->count());
    }

    /**
     * Laporan KPI adalah dokumen yang paling mungkin diedarkan ke luar
     * aplikasi, jadi ia harus menyebut rumah sakitnya sendiri -- sama seperti
     * seluruh ekspor daftar. Tanpa kop, penerimanya tidak punya cara tahu
     * berkas ini milik siapa.
     */
    public function test_pdf_laporan_memuat_nama_organisasinya(): void
    {
        $pengguna = $this->buatPengguna(['Laporan.Lihat']);
        $this->buatPerintahKerja();

        $this->jalankanJob($pengguna, FormatEkspor::Pdf, 'Kinerja Triwulan');

        $berkas = Berkas::query()->where('DiunggahOleh', $pengguna->Id)->firstOrFail();
        $teks = $this->teksPdf($this->isiAsli($berkas));

        $this->assertStringContainsString('Organisasi Pelaporan', $teks);
        $this->assertStringContainsString('Kinerja Triwulan', $teks);
    }

    /** Nama legal ikut disebut, karena itu yang dikenali di luar rumah sakit. */
    public function test_kop_laporan_menyebut_nama_legal_bila_berbeda(): void
    {
        $this->organisasi->update(['NamaLegal' => 'RSUD Kabupaten Sragen']);
        $pengguna = $this->buatPengguna(['Laporan.Lihat']);
        $this->buatPerintahKerja();

        $this->jalankanJob($pengguna, FormatEkspor::Csv, 'Kinerja Triwulan');

        $berkas = Berkas::query()->where('DiunggahOleh', $pengguna->Id)->firstOrFail();
        $isi = $this->isiAsli($berkas);

        $this->assertStringContainsString('Organisasi Pelaporan (RSUD Kabupaten Sragen)', $isi);
    }

    /** PRD 11.1: CSV ekspor tersimpan gzip, dan pemesannya tetap mengunduh CSV yang persis sama. */
    public function test_ekspor_csv_tersimpan_gzip_dan_terunduh_identik(): void
    {
        Storage::fake('local');
        $pengguna = $this->buatPengguna();
        $this->buatPerintahKerja();

        $this->jalankanJob($pengguna, FormatEkspor::Csv, 'Ringkasan pekerjaan');
        $berkas = Berkas::query()->where('DiunggahOleh', $pengguna->Id)->firstOrFail();
        $asli = $this->isiAsli($berkas);
        $mentah = (string) Storage::disk($berkas->MediaPenyimpanan)->get($berkas->LokasiPenyimpanan);

        $this->assertSame(MetodeKompresi::Gzip, $berkas->MetodeKompresi);
        $this->assertStringStartsWith('ekspor-laporan/'.$this->organisasi->Id.'/', $berkas->LokasiPenyimpanan);
        $this->assertStringEndsWith('.csv.gz', $berkas->LokasiPenyimpanan);
        $this->assertStringStartsWith("\x1f\x8b", $mentah, 'Isi di disk benar-benar gzip.');
        $this->assertSame(strlen($mentah), $berkas->UkuranTersimpanByte);
        $this->assertSame(strlen($asli), $berkas->UkuranAsliByte);

        $unduhan = $this->actingAs($pengguna)->get(route('pelaporan.ekspor.unduh', $berkas));

        $unduhan->assertOk()->assertDownload($berkas->NamaAsli);
        $this->assertStringStartsWith('text/csv', (string) $unduhan->headers->get('Content-Type'));
        $this->assertSame($asli, $unduhan->streamedContent());
        $this->assertStringContainsString('Perintah Kerja Aktif', $unduhan->streamedContent());
    }

    public function test_ekspor_xlsx_tersimpan_apa_adanya(): void
    {
        Storage::fake('local');
        $pengguna = $this->buatPengguna();
        $this->buatPerintahKerja();

        $this->jalankanJob($pengguna, FormatEkspor::Xlsx, 'Ringkasan pekerjaan');
        $berkas = Berkas::query()->where('DiunggahOleh', $pengguna->Id)->firstOrFail();

        $this->assertSame(MetodeKompresi::Tidak, $berkas->MetodeKompresi);
        $this->assertSame(
            $this->isiAsli($berkas),
            $this->actingAs($pengguna)->get(route('pelaporan.ekspor.unduh', $berkas))->streamedContent(),
        );
    }

    /** Mesin kompresi yang dimatikan tidak memutus ekspor: berkasnya tersimpan apa adanya dan tetap terunduh. */
    public function test_ekspor_tetap_jadi_saat_kompresi_dimatikan(): void
    {
        Storage::fake('local');
        config(['amanpoll.kompresi.aktif' => false]);
        $pengguna = $this->buatPengguna();
        $this->buatPerintahKerja();

        $this->jalankanJob($pengguna, FormatEkspor::Csv, 'Ringkasan pekerjaan');
        $berkas = Berkas::query()->where('DiunggahOleh', $pengguna->Id)->firstOrFail();

        $this->assertSame(MetodeKompresi::Tidak, $berkas->MetodeKompresi);
        $this->assertStringEndsWith('.csv', $berkas->LokasiPenyimpanan);
        $this->assertStringContainsString(
            'Perintah Kerja Aktif',
            $this->actingAs($pengguna)->get(route('pelaporan.ekspor.unduh', $berkas))->streamedContent(),
        );
    }

    /**
     * Logo yang diunggah kini tersimpan sebagai WebP; Dompdf harus tetap
     * mencetaknya di kop. Pembandingnya PDF tanpa logo: tanpa gambar sama sekali.
     */
    public function test_pdf_laporan_mencetak_logo_webp_organisasi(): void
    {
        Storage::fake('local');
        Storage::fake('public', ['url' => (string) config('filesystems.disks.public.url')]);
        $pengguna = $this->buatPengguna(['Laporan.Lihat']);
        $this->buatPerintahKerja();

        $this->jalankanJob($pengguna, FormatEkspor::Pdf, 'Tanpa Logo');
        $tanpaLogo = Berkas::query()->where('DataTambahan->Judul', 'Tanpa Logo')->firstOrFail();
        $this->assertStringNotContainsString('/Subtype /Image', $this->isiAsli($tanpaLogo));

        app(UnggahLogoOrganisasi::class)->jalankan(
            $this->organisasi,
            UploadedFile::fake()->createWithContent('logo.png', GambarUji::pngTransparan(400, 200)),
        );
        $this->assertStringEndsWith('.webp', (string) $this->organisasi->LogoUrl);

        $this->jalankanJob($pengguna, FormatEkspor::Pdf, 'Dengan Logo');
        $denganLogo = Berkas::query()->where('DataTambahan->Judul', 'Dengan Logo')->firstOrFail();

        $this->assertStringContainsString('/Subtype /Image', $this->isiAsli($denganLogo));
    }

    /** Isi asli berkas ekspor, gzip sudah dibuka (PRD 11.1). */
    private function isiAsli(Berkas $berkas): string
    {
        $aliran = app(PenyimpanBerkas::class)->bukaAliran($berkas);

        try {
            return (string) stream_get_contents($aliran);
        } finally {
            fclose($aliran);
        }
    }

    /**
     * Teks yang sungguh tercetak di PDF-nya: Dompdf memampatkan alirannya dan
     * menulis teks sebagai UTF-16BE, jadi berkasnya dibuka dulu.
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

    private function jalankanJob(Pengguna $pengguna, FormatEkspor $format, string $judul): void
    {
        $job = new BuatEksporLaporan(
            $pengguna->Id,
            ['perintah_kerja.aktif'],
            FilterMetrik::bawaan('Asia/Jakarta')->keArray(),
            $format->value,
            $judul,
        );

        app()->call([$job, 'handle']);
    }

    private function buatPerintahKerja(): PerintahKerja
    {
        return PerintahKerja::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nomor' => 'WO-'.uniqid(),
            'Judul' => 'Pekerjaan uji',
            'Jenis' => 'Korektif',
            'Status' => StatusPerintahKerja::Dikerjakan->value,
            'Prioritas' => 'Normal',
        ]);
    }
}
