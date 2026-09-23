<?php

declare(strict_types=1);

namespace Tests\Feature\Aset;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Application\Services\PenyusunKartuRiwayatAset;
use App\Domain\Aset\Domain\Enums\JenisRiwayatLokasiAset;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kartu Riwayat Alat: dokumen satu aset untuk berkas akreditasi.
 *
 * Rincian isi kartu diperiksa pada HTML yang dirender penyusunnya -- sumber
 * yang sama persis dengan yang dicetak ke PDF, dan jauh lebih murah untuk
 * diperiksa baris demi baris. Namun HTML saja tidak menjaga jalur cetaknya:
 * satu test tambahan mengunduh kartunya lewat rute HTTP-nya lalu membaca teks
 * di dalam PDF yang sungguh dirender, supaya putusnya sambungan antara html()
 * dan pdf() tidak lolos tanpa ada test yang merah.
 */
class KartuRiwayatAsetTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $pengguna;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create([
            'Kode' => 'ORG-KRA',
            'Nama' => 'RSUD Amanpoll',
            'ZonaWaktu' => 'Asia/Jakarta',
        ]);
        $this->pengguna = $this->buatPengguna($this->organisasi);
    }

    /**
     * Angka "dari M yang tercatat" adalah klaim faktual di dokumen yang
     * ditandatangani. Bila kueri hitungannya lepas dari asetnya, jumlahnya
     * ikut memuat riwayat alat lain tanpa ada satu baris pun yang keliru --
     * kesalahan yang tidak terlihat dari isi tabelnya.
     */
    public function test_jumlah_total_riwayat_hanya_menghitung_asetnya_sendiri(): void
    {
        $organisasi = $this->organisasi;
        $satu = $this->buatAset($organisasi, ['Nama' => 'Alat Berhitung']);
        $dua = $this->buatAset($organisasi, ['Nama' => 'Alat Pembanding']);

        $this->konteks()->tetapkan($organisasi->Id);
        $lokasi = Lokasi::create(['Kode' => 'LOK-'.strtoupper(Str::random(6)), 'Nama' => 'Ruang Hitung']);

        $lewatBatas = PenyusunKartuRiwayatAset::MAKS_BARIS + 1;
        foreach (range(1, $lewatBatas) as $ke) {
            RiwayatLokasiAset::create([
                'AsetId' => $satu->Id,
                'LokasiTujuanId' => $lokasi->Id,
                'JenisPerpindahan' => JenisRiwayatLokasiAset::Manual->value,
                'Alasan' => 'Perpindahan ke-'.$ke,
                'DipindahkanPada' => now()->subDays($ke),
            ]);
        }

        // Milik alat lain, yang tidak boleh ikut terhitung.
        RiwayatLokasiAset::create([
            'AsetId' => $dua->Id,
            'LokasiTujuanId' => $lokasi->Id,
            'JenisPerpindahan' => JenisRiwayatLokasiAset::Manual->value,
            'Alasan' => 'Milik alat pembanding',
            'DipindahkanPada' => now()->subDay(),
        ]);
        $this->konteks()->bersihkan();

        $html = $this->htmlKartu($satu);

        $this->assertStringContainsString(
            'dari '.$lewatBatas.' yang tercatat',
            $html,
            'Jumlah total harus menghitung riwayat asetnya sendiri saja.',
        );
        $this->assertStringNotContainsString('dari '.($lewatBatas + 1).' yang tercatat', $html);
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
        $kategori = KategoriAset::firstOrCreate(['Kode' => 'KAT-KRA'], ['Nama' => 'Alat Medis']);
        $aset = Aset::create(array_merge([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-'.strtoupper(Str::random(6)),
            'Nama' => 'Aset '.uniqid(),
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
            'KodeQr' => (string) Str::ulid(),
            'Versi' => 1,
        ], $atribut));
        $this->konteks()->bersihkan();

        return $aset;
    }

    /** Perintah kerja yang menyangkut satu aset, lengkap dengan teknisinya. */
    private function buatPerintahKerja(Aset $aset, string $nomor, string $ringkasan, string $namaTeknisi): PerintahKerja
    {
        $this->konteks()->tetapkan((string) $aset->OrganisasiId);

        $perintah = PerintahKerja::create([
            'Nomor' => $nomor,
            'Jenis' => 'Korektif',
            'Judul' => 'Perbaikan '.$nomor,
            'Prioritas' => 'Tinggi',
            'Status' => 'Ditutup',
            'DimulaiPada' => now()->subDays(3),
            'DiselesaikanPada' => now()->subDays(2),
            'RingkasanPenyelesaian' => $ringkasan,
            'DibuatOleh' => $this->pengguna->Id,
            'Versi' => 1,
        ]);

        PerintahKerjaAset::create([
            'PerintahKerjaId' => $perintah->Id,
            'AsetId' => $aset->Id,
            'Utama' => true,
        ]);

        $teknisi = Pengguna::create([
            'OrganisasiId' => $aset->OrganisasiId,
            'Nama' => $namaTeknisi,
            'Email' => 'teknisi+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        PenugasanPerintahKerja::create([
            'PerintahKerjaId' => $perintah->Id,
            'PenggunaId' => $teknisi->Id,
            'PeranTugas' => 'Teknisi',
            'DitugaskanPada' => now()->subDays(4),
            'Status' => 'Selesai',
        ]);

        $this->konteks()->bersihkan();

        return $perintah;
    }

    private function buatKalibrasi(Aset $aset, string $nomorSertifikat, string $laboratorium): PelaksanaanKalibrasi
    {
        $this->konteks()->tetapkan((string) $aset->OrganisasiId);

        $pelaksanaan = PelaksanaanKalibrasi::create([
            'Nomor' => 'KAL-'.strtoupper(Str::random(5)),
            'AsetId' => $aset->Id,
            'TanggalKalibrasi' => now()->subMonths(2)->toDateString(),
            'TanggalBerlakuSampai' => now()->addMonths(10)->toDateString(),
            'Hasil' => 'Lolos',
            'NomorSertifikat' => $nomorSertifikat,
            'Laboratorium' => $laboratorium,
        ]);

        $this->konteks()->bersihkan();

        return $pelaksanaan;
    }

    private function buatPerpindahan(Aset $aset, string $namaAsal, string $namaTujuan, string $alasan): RiwayatLokasiAset
    {
        $this->konteks()->tetapkan((string) $aset->OrganisasiId);

        $asal = Lokasi::create(['Kode' => 'LOK-'.strtoupper(Str::random(6)), 'Nama' => $namaAsal]);
        $tujuan = Lokasi::create(['Kode' => 'LOK-'.strtoupper(Str::random(6)), 'Nama' => $namaTujuan]);

        $riwayat = RiwayatLokasiAset::create([
            'AsetId' => $aset->Id,
            'LokasiAsalId' => $asal->Id,
            'LokasiTujuanId' => $tujuan->Id,
            'JenisPerpindahan' => JenisRiwayatLokasiAset::Manual->value,
            'Alasan' => $alasan,
            'DipindahkanPada' => now()->subDay(),
        ]);

        $this->konteks()->bersihkan();

        return $riwayat;
    }

    /** HTML yang persis menjadi isi PDF-nya, dirender dalam konteks organisasi asetnya. */
    private function htmlKartu(Aset $aset): string
    {
        $this->konteks()->tetapkan((string) $aset->OrganisasiId);
        $html = app(PenyusunKartuRiwayatAset::class)->html($aset);
        $this->konteks()->bersihkan();

        return $html;
    }

    private function urlKartu(Aset $aset): string
    {
        return '/aset/'.$aset->Id.'/kartu-riwayat';
    }

    public function test_kartu_riwayat_terunduh_sebagai_berkas_pdf(): void
    {
        $aset = $this->buatAset($this->organisasi, ['KodeAset' => 'AST-0001']);

        $respons = $this->actingAs($this->pengguna)->get($this->urlKartu($aset));

        $respons->assertOk();
        $respons->assertHeader('Content-Type', 'application/pdf');
        // Tanpa tanda kutip: HeaderUtils hanya mengutip nama berkas yang memang
        // memerlukannya, dan nama ini sudah berupa token yang sah.
        $respons->assertHeader('Content-Disposition', 'attachment; filename=kartu-riwayat-ast-0001.pdf');
        $this->assertStringStartsWith('%PDF', $respons->getContent());
    }

    /**
     * `KodeAset` diketik pengguna dan ikut ke nama berkas unduhan.
     *
     * Header yang dirakit dengan menyambung string membuat tanda kutip di dalam
     * kode aset menutup parameter `filename` lebih awal lalu membuka parameter
     * kedua -- sehingga yang mengunduh menyimpan berkas dengan nama pilihan
     * penyusun data, bukan nama yang dimaksud aplikasi.
     */
    public function test_kutip_di_kode_aset_tidak_memalsukan_nama_berkas(): void
    {
        $aset = $this->buatAset($this->organisasi, [
            'KodeAset' => 'A"; filename="laporan-keuangan',
        ]);

        $respons = $this->actingAs($this->pengguna)->get($this->urlKartu($aset));

        $respons->assertOk();
        $disposisi = (string) $respons->headers->get('Content-Disposition');

        // Yang dijaga adalah jumlah parameternya, bukan hilangnya kata itu:
        // "laporan-keuangan" memang diketik penggunanya sendiri dan boleh saja
        // tersisa di dalam satu nama berkas. Yang tidak boleh adalah ia menjadi
        // parameter filename KEDUA yang dipilih peramban.
        $this->assertSame(1, substr_count($disposisi, 'filename='), 'Header: '.$disposisi);
        $this->assertStringContainsString('filename=kartu-riwayat-', $disposisi);
        $this->assertStringNotContainsString('"', $disposisi);
    }

    /**
     * Garis miring di kode aset tidak boleh menggagalkan unduhannya.
     *
     * `HeaderUtils::makeDisposition()` melempar untuk nama berkas yang memuat
     * garis miring, jadi kode seperti `AST/2026/001` -- bentuk yang lazim pada
     * penomoran aset -- akan membuat cetak kartunya galat 500, bukan sekadar
     * bernama jelek.
     */
    public function test_garis_miring_di_kode_aset_tetap_dapat_diunduh(): void
    {
        $aset = $this->buatAset($this->organisasi, ['KodeAset' => 'AST/2026/001']);

        $respons = $this->actingAs($this->pengguna)->get($this->urlKartu($aset));

        $respons->assertOk();
        $this->assertStringStartsWith('%PDF', $respons->getContent());
        $this->assertSame(
            'attachment; filename=kartu-riwayat-ast-2026-001.pdf',
            $respons->headers->get('Content-Disposition'),
        );
    }

    /**
     * Penjaga sambungan antara penyusun HTML dan berkas yang benar-benar
     * terkirim.
     *
     * Pemeriksaan isi lain memotong HTTP dan memanggil html() langsung, jadi
     * argumen loadHtml() di pdf() boleh dikosongkan tanpa satu test pun berubah
     * merah: yang terunduh tetap PDF yang sah dan tetap berawalan '%PDF', hanya
     * saja tidak ada isinya. Yang diperiksa di sini adalah teks di dalam PDF
     * yang sungguh dirender, diambil lewat rutenya sendiri.
     */
    public function test_isi_kartu_terbaca_di_dalam_pdf_yang_terunduh(): void
    {
        $aset = $this->buatAset($this->organisasi, [
            'KodeAset' => 'AST-0003',
            'Nama' => 'Ventilator Dewasa',
        ]);
        $this->buatPerintahKerja($aset, 'PK-0301', 'Ganti selang oksigen', 'Rina Teknisi');

        $respons = $this->actingAs($this->pengguna)->get($this->urlKartu($aset));
        $respons->assertOk();

        $teks = $this->teksPdf((string) $respons->getContent());

        $this->assertStringContainsString('RSUD Amanpoll', $teks);
        $this->assertStringContainsString('AST-0003', $teks);
        $this->assertStringContainsString('Ventilator Dewasa', $teks);
        $this->assertStringContainsString('PK-0301', $teks);
        $this->assertStringContainsString('Ganti selang oksigen', $teks);
    }

    /**
     * Teks yang sungguh tercetak di PDF-nya, bukan HTML sebelum dirender.
     *
     * Dompdf memampatkan aliran isinya dan menulis teks sebagai UTF-16BE, jadi
     * berkasnya dibuka dulu: aliran dilepas mampatnya, lalu byte NUL penyela
     * antarhurufnya dibuang. Pola yang sama dipakai Tests\Feature\Shared\EksporDaftarTest.
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

    public function test_kartu_memuat_kop_identitas_dan_ruang_tanda_tangan(): void
    {
        $this->konteks()->tetapkan($this->organisasi->Id);
        $kategori = KategoriAset::firstOrCreate(['Kode' => 'KAT-KRA'], ['Nama' => 'Alat Medis']);
        $merek = Merek::create(['Nama' => 'Dragerwerk']);
        $model = ModelAset::create([
            'KategoriAsetId' => $kategori->Id,
            'MerekId' => $merek->Id,
            'Nama' => 'Evita V300',
        ]);
        $lokasi = Lokasi::create(['Kode' => 'LOK-ICU', 'Nama' => 'Ruang ICU']);
        $this->konteks()->bersihkan();

        $aset = $this->buatAset($this->organisasi, [
            'KodeAset' => 'AST-0002',
            'Nama' => 'Ventilator ICU',
            'ModelAsetId' => $model->Id,
            'LokasiId' => $lokasi->Id,
            'NomorSeri' => 'SN-VENT-77',
            'NomorInventaris' => 'INV-2201',
            'TanggalPerolehan' => '2024-03-15',
            'HargaPerolehan' => 450000000,
            'SumberDana' => 'APBD',
        ]);

        $html = $this->htmlKartu($aset);

        $this->assertStringContainsString('RSUD Amanpoll', $html);
        $this->assertStringContainsString('KARTU RIWAYAT ALAT', $html);
        $this->assertStringContainsString('Dicetak pada', $html);

        $this->assertStringContainsString('AST-0002', $html);
        $this->assertStringContainsString('Ventilator ICU', $html);
        $this->assertStringContainsString('Dragerwerk / Evita V300', $html);
        $this->assertStringContainsString('SN-VENT-77', $html);
        $this->assertStringContainsString('INV-2201', $html);
        $this->assertStringContainsString('Alat Medis', $html);
        $this->assertStringContainsString('Ruang ICU', $html);
        $this->assertStringContainsString('15-03-2024', $html);
        $this->assertStringContainsString('Rp 450.000.000,00', $html);
        $this->assertStringContainsString('APBD', $html);
        $this->assertStringContainsString(StatusAset::Aktif->value, $html);
        $this->assertStringContainsString(KondisiAset::Baik->value, $html);

        // Kartu tanpa ruang tanda tangan tidak dapat dipakai sebagai berkas akreditasi.
        $this->assertStringContainsString('Petugas IPSRS', $html);
        $this->assertStringContainsString('Kepala Unit', $html);
    }

    public function test_kartu_memuat_riwayat_pemeliharaan_kalibrasi_dan_perpindahan(): void
    {
        $aset = $this->buatAset($this->organisasi, ['Nama' => 'Inkubator Bayi']);
        $this->buatPerintahKerja($aset, 'PK-0100', 'Ganti sensor suhu', 'Budi Teknisi');
        $this->buatKalibrasi($aset, 'SERT-0100', 'Lab Kalibrasi Nusantara');
        $this->buatPerpindahan($aset, 'Ruang Perinatologi', 'Ruang NICU', 'Penataan ulang ruang');

        $html = $this->htmlKartu($aset);

        $this->assertStringContainsString('PK-0100', $html);
        $this->assertStringContainsString('Ganti sensor suhu', $html);
        $this->assertStringContainsString('Budi Teknisi', $html);

        $this->assertStringContainsString('SERT-0100', $html);
        $this->assertStringContainsString('Lab Kalibrasi Nusantara', $html);
        $this->assertStringContainsString('Lolos', $html);

        $this->assertStringContainsString('Ruang Perinatologi', $html);
        $this->assertStringContainsString('Ruang NICU', $html);
        $this->assertStringContainsString('Penataan ulang ruang', $html);
    }

    /**
     * Penjaga terpenting kartu ini.
     *
     * Kartu yang membawa riwayat alat lain menjadi bukti palsu di berkas
     * akreditasi: alat yang tidak pernah dikalibrasi tampak sudah dikalibrasi.
     */
    public function test_riwayat_aset_lain_tidak_ikut_masuk_ke_kartu(): void
    {
        $satu = $this->buatAset($this->organisasi, ['Nama' => 'Alat Satu']);
        $dua = $this->buatAset($this->organisasi, ['Nama' => 'Alat Dua']);

        $this->buatPerintahKerja($satu, 'PK-SATU', 'Kalibrasi ulang sensor satu', 'Teknisi Satu');
        $this->buatKalibrasi($satu, 'SERT-SATU', 'Lab Satu');
        $this->buatPerpindahan($satu, 'Gudang Satu', 'Ruang Satu', 'Alasan pindah satu');

        $this->buatPerintahKerja($dua, 'PK-DUA', 'Kalibrasi ulang sensor dua', 'Teknisi Dua');
        $this->buatKalibrasi($dua, 'SERT-DUA', 'Lab Dua');
        $this->buatPerpindahan($dua, 'Gudang Dua', 'Ruang Dua', 'Alasan pindah dua');

        $kartuSatu = $this->htmlKartu($satu);
        $kartuDua = $this->htmlKartu($dua);

        foreach (['PK-SATU', 'Teknisi Satu', 'SERT-SATU', 'Lab Satu', 'Ruang Satu', 'Alasan pindah satu'] as $milikSatu) {
            $this->assertStringContainsString($milikSatu, $kartuSatu);
            $this->assertStringNotContainsString($milikSatu, $kartuDua);
        }

        foreach (['PK-DUA', 'Teknisi Dua', 'SERT-DUA', 'Lab Dua', 'Ruang Dua', 'Alasan pindah dua'] as $milikDua) {
            $this->assertStringContainsString($milikDua, $kartuDua);
            $this->assertStringNotContainsString($milikDua, $kartuSatu);
        }
    }

    /** Riwayat organisasi tetangga tidak boleh menyeberang walau id asetnya ditebak benar. */
    public function test_aset_organisasi_lain_tidak_dapat_dicetak(): void
    {
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'RS Tetangga']);
        $milikTetangga = $this->buatAset($lain, ['Nama' => 'Alat Tetangga']);

        $this->actingAs($this->pengguna)
            ->get($this->urlKartu($milikTetangga))
            ->assertNotFound();
    }

    public function test_pengguna_tanpa_izin_lihat_aset_ditolak(): void
    {
        $aset = $this->buatAset($this->organisasi);
        $tanpaIzin = $this->buatPengguna($this->organisasi, denganIzin: false);

        $this->actingAs($tanpaIzin)->get($this->urlKartu($aset))->assertForbidden();
    }

    /** Bagian yang belum ada isinya dikatakan kosong, bukan dibiarkan sebagai tabel tanpa baris. */
    public function test_bagian_tanpa_riwayat_dinyatakan_kosong(): void
    {
        $aset = $this->buatAset($this->organisasi, ['Nama' => 'Alat Baru']);

        $html = $this->htmlKartu($aset);

        $this->assertStringContainsString('Belum ada perintah kerja untuk alat ini.', $html);
        $this->assertStringContainsString('Belum ada pelaksanaan kalibrasi untuk alat ini.', $html);
        $this->assertStringContainsString('Belum ada perpindahan lokasi untuk alat ini.', $html);
    }
}
