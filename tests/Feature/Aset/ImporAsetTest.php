<?php

declare(strict_types=1);

namespace Tests\Feature\Aset;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as PembacaXlsx;
use OpenSpout\Writer\XLSX\Writer as PenulisXlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/**
 * Impor aset dari CSV/XLSX (PRD 8.4 "Impor Aset", TASK 40.07): templat,
 * pratinjau per baris, konfirmasi semua-atau-tidak, lingkup, dan tenancy.
 */
class ImporAsetTest extends TestCase
{
    use RefreshDatabase;

    private const KEPALA = ['Kode Aset', 'Nama', 'Kode Kategori', 'Kode Lokasi', 'Kode Unit Pengelola'];

    private Organisasi $organisasi;

    private KategoriAset $kategori;

    private UnitOrganisasi $it;

    private UnitOrganisasi $icu;

    private Lokasi $ruangIcu;

    private Lokasi $ruangIt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-IMP', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        $this->dalamOrganisasi(function (): void {
            $this->kategori = KategoriAset::create(['Kode' => 'KAT-ALK', 'Nama' => 'Alat Kesehatan']);
            $this->it = UnitOrganisasi::create(['Kode' => 'IT', 'Nama' => 'Instalasi IT', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
            $this->icu = UnitOrganisasi::create(['Kode' => 'ICU', 'Nama' => 'ICU', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
            $this->ruangIcu = Lokasi::create(['Kode' => 'R-ICU', 'Nama' => 'Ruang ICU', 'UnitOrganisasiId' => $this->icu->Id]);
            $this->ruangIt = Lokasi::create(['Kode' => 'R-IT', 'Nama' => 'Ruang Server', 'UnitOrganisasiId' => $this->it->Id]);
        });
    }

    public function test_templat_csv_berisi_kepala_kolom_dan_baris_contoh_yang_sah(): void
    {
        $admin = $this->buatPengguna();

        $respons = $this->actingAs($admin)->get('/aset/impor/templat?format=csv')->assertOk();
        $baris = $this->barisCsv($this->isiUnduhan($respons));

        $this->assertSame('Kode Aset', $baris[0][0]);
        $this->assertContains('Nama', $baris[0]);
        $this->assertContains('Kode Kategori', $baris[0]);
        $this->assertContains('Kode Unit Pengelola', $baris[0]);
        $this->assertNotContains('Versi', $baris[0]);
        $this->assertCount(2, $baris, 'Kepala dan satu baris contoh.');

        // Templat yang diunggah kembali apa adanya lolos pemeriksaan.
        $this->pratinjau($admin, UploadedFile::fake()->createWithContent('templat.csv', $this->isiUnduhan($respons)))
            ->assertOk()
            ->assertJson(['jumlahBaris' => 1, 'jumlahSah' => 1, 'jumlahGalat' => 0]);
    }

    /** Organisasi tanpa unit pengelola tidak melihat kolomnya, sama seperti formulir dan ekspor. */
    public function test_templat_tanpa_kolom_unit_pengelola_bila_fitur_tidak_dipakai(): void
    {
        $admin = $this->buatPengguna();
        $this->dalamOrganisasi(fn () => $this->it->update(['MengelolaAset' => false]));

        $kepala = $this->barisCsv($this->isiUnduhan($this->actingAs($admin)->get('/aset/impor/templat?format=csv')))[0];

        $this->assertNotContains('Kode Unit Pengelola', $kepala);
        $this->assertContains('Kode Lokasi', $kepala);
    }

    public function test_templat_xlsx_punya_lembar_petunjuk_dengan_kode_yang_dinetralkan(): void
    {
        $admin = $this->buatPengguna();
        $this->dalamOrganisasi(fn () => Lokasi::create(['Kode' => '=CMD()', 'Nama' => '=HYPERLINK("http://jahat")']));

        $respons = $this->actingAs($admin)->get('/aset/impor/templat?format=xlsx')->assertOk();
        $lembar = $this->bacaXlsx($this->jalurUnduhan($respons));

        $this->assertSame(['Aset', 'Petunjuk'], array_keys($lembar));
        $this->assertSame('Kode Aset', $lembar['Aset'][0][0]);
        $petunjuk = array_merge(...$lembar['Petunjuk']);
        $this->assertContains('R-ICU', $petunjuk);
        $this->assertContains("'=CMD()", $petunjuk);
        $this->assertContains("'=HYPERLINK(\"http://jahat\")", $petunjuk);
        $this->assertNotContains('=CMD()', $petunjuk);
    }

    public function test_pratinjau_menghitung_baris_sah_dan_galat_per_baris(): void
    {
        $admin = $this->buatPengguna();

        $this->pratinjau($admin, $this->csv([
            ['', 'Monitor Pasien', 'KAT-ALK', 'R-ICU', 'IT'],
            ['', 'Ventilator', 'KAT-SALAH', 'R-ICU', ''],
            ['', '', 'KAT-ALK', 'R-TIDAK-ADA', 'ICU'],
        ]))
            ->assertOk()
            ->assertJson([
                'namaBerkas' => 'aset.csv',
                'jumlahBaris' => 3,
                'jumlahSah' => 1,
                'jumlahGalat' => 4,
                'galat' => [
                    ['baris' => 3, 'kolom' => 'Kode Kategori', 'nilai' => 'KAT-SALAH', 'pesan' => 'Kode Kategori "KAT-SALAH" tidak ditemukan.'],
                    ['baris' => 4, 'kolom' => 'Nama', 'pesan' => 'Nama wajib diisi.'],
                    // Unit organisasi biasa, bukan unit pengelola: ditolak aturan formulir (UnitPengelolaSah).
                    ['baris' => 4, 'kolom' => 'Kode Unit Pengelola', 'pesan' => 'Unit yang dipilih belum ditandai Mengelola Aset di halaman Unit Organisasi.'],
                    ['baris' => 4, 'kolom' => 'Kode Lokasi', 'pesan' => 'Kode Lokasi "R-TIDAK-ADA" tidak ditemukan.'],
                ],
                'contoh' => [
                    ['baris' => 2, 'KodeAset' => null, 'Nama' => 'Monitor Pasien', 'Kategori' => 'Alat Kesehatan', 'Lokasi' => 'Ruang ICU', 'UnitPengelola' => 'Instalasi IT'],
                ],
            ]);

        $this->assertSame(0, $this->jumlahAset(), 'Pratinjau tidak menyimpan apa pun.');
    }

    /** Aturan formulir dipakai apa adanya: enum, angka, dan urutan tanggal. */
    public function test_pratinjau_memakai_aturan_formulir_untuk_isian_biasa(): void
    {
        $admin = $this->buatPengguna();

        $this->pratinjau($admin, $this->csv(
            [['Pompa', 'KAT-ALK', 'perlu perhatian', 'Hancur', 'seribu', '31/12/2024', '01/01/2024']],
            ['Nama', 'Kode Kategori', 'Kondisi', 'Status', 'Harga Perolehan', 'Tanggal Mulai Operasi', 'Tanggal Akhir Operasi'],
        ))
            ->assertOk()
            ->assertJson([
                'jumlahSah' => 0,
                'galat' => [
                    ['kolom' => 'Tanggal Akhir Operasi', 'pesan' => 'Tanggal Akhir Operasi harus tanggal yang sama dengan atau setelah Tanggal Mulai Operasi.'],
                    ['kolom' => 'Harga Perolehan', 'pesan' => 'Harga Perolehan harus berupa angka.'],
                    ['kolom' => 'Status', 'pesan' => 'Status yang dipilih tidak valid.'],
                ],
            ])
            ->assertJsonCount(3, 'galat');
    }

    public function test_pratinjau_xlsx_sama_dengan_csv(): void
    {
        $admin = $this->buatPengguna();

        $xlsx = $this->xlsx(
            ['Harga Perolehan', 'Nama', 'Kode Kategori', 'Tanggal Perolehan', 'Kode Lokasi'],
            [
                [15000000, 'Monitor Pasien', 'KAT-ALK', new DateTimeImmutable('2024-05-31'), 'R-ICU'],
                ['', 'Ventilator', 'KAT-SALAH', '', 'R-ICU'],
            ],
        );

        $this->pratinjau($admin, $xlsx)
            ->assertOk()
            ->assertJson([
                'jumlahBaris' => 2,
                'jumlahSah' => 1,
                'galat' => [['baris' => 3, 'kolom' => 'Kode Kategori']],
            ])
            ->assertJsonCount(1, 'galat');

        $this->actingAs($admin)->post('/aset/impor', ['Berkas' => $this->xlsx(
            ['Harga Perolehan', 'Nama', 'Kode Kategori', 'Tanggal Perolehan'],
            [[15000000, 'Monitor Pasien', 'KAT-ALK', new DateTimeImmutable('2024-05-31')]],
        )], ['Accept' => 'application/json'])->assertCreated();

        $aset = $this->dalamOrganisasi(fn () => Aset::query()->sole());
        $this->assertSame('15000000.00', $aset->HargaPerolehan);
        $this->assertSame('2024-05-31', $aset->TanggalPerolehan?->format('Y-m-d'));
    }

    public function test_kode_kembar_dalam_berkas_dan_kode_yang_sudah_ada_ditolak(): void
    {
        $admin = $this->buatPengguna();
        $this->dalamOrganisasi(function (): void {
            Aset::create(['KodeAset' => 'AST-LAMA', 'Nama' => 'Lama', 'KategoriAsetId' => $this->kategori->Id]);
            Aset::create(['KodeAset' => 'AST-ARSIP', 'Nama' => 'Arsip', 'KategoriAsetId' => $this->kategori->Id])->delete();
        });

        $this->pratinjau($admin, $this->csv([
            ['AST-BARU', 'Satu', 'KAT-ALK', '', ''],
            ['ast-baru', 'Dua', 'KAT-ALK', '', ''],
            ['AST-LAMA', 'Tiga', 'KAT-ALK', '', ''],
            ['AST-ARSIP', 'Empat', 'KAT-ALK', '', ''],
        ]))
            ->assertOk()
            ->assertJson([
                'jumlahSah' => 1,
                'galat' => [
                    ['baris' => 3, 'kolom' => 'Kode Aset', 'pesan' => 'Kode aset "ast-baru" sudah dipakai di baris 2.'],
                    ['baris' => 4, 'kolom' => 'Kode Aset', 'pesan' => 'Kode aset "AST-LAMA" sudah dipakai aset lain.'],
                    ['baris' => 5, 'kolom' => 'Kode Aset', 'pesan' => 'Kode aset "AST-ARSIP" sudah dipakai aset yang diarsipkan.'],
                ],
            ])
            ->assertJsonCount(3, 'galat');
    }

    public function test_satu_baris_salah_membatalkan_seluruh_impor(): void
    {
        $admin = $this->buatPengguna();

        $this->actingAs($admin)->post('/aset/impor', ['Berkas' => $this->csv([
            ['', 'Monitor Pasien', 'KAT-ALK', 'R-ICU', ''],
            ['', 'Ventilator', 'KAT-ALK', 'R-ICU', ''],
            ['', 'Pompa', 'KAT-SALAH', 'R-ICU', ''],
        ])], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Impor dibatalkan: 1 dari 3 baris masih bergalat. Tidak ada aset yang dibuat.',
                'galat' => [['baris' => 4, 'kolom' => 'Kode Kategori']],
            ]);

        $this->assertSame(0, $this->jumlahAset());
        $this->assertSame(0, $this->dalamOrganisasi(fn () => CatatanAudit::query()->where('Aksi', 'Aset.Diimpor')->count()));
    }

    public function test_konfirmasi_membuat_aset_lengkap_seperti_formulir_dan_mencatat_audit(): void
    {
        $admin = $this->buatPengguna();

        $respons = $this->actingAs($admin)->post('/aset/impor', ['Berkas' => $this->csv([
            // Kode kosong di atas kode eksplisit yang kebetulan berpola sama dengan mesin kode.
            ['', 'Monitor Pasien', 'KAT-ALK', 'R-ICU', 'IT'],
            ['AST-0001', 'Ventilator', 'KAT-ALK', '', ''],
        ])], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJson(['jumlah' => 2]);

        $this->dalamOrganisasi(function () use ($respons, $admin): void {
            $monitor = Aset::query()->where('Nama', 'Monitor Pasien')->sole();
            $ventilator = Aset::query()->where('Nama', 'Ventilator')->sole();

            $this->assertSame([$monitor->Id, $ventilator->Id], $respons->json('asetId'));
            $this->assertSame('AST-0002', $monitor->KodeAset, 'Kode kosong diisi mesin kode dan melewati kode eksplisit di berkas.');
            $this->assertSame('AST-0001', $ventilator->KodeAset);
            $this->assertSame($this->ruangIcu->Id, $monitor->LokasiId);
            $this->assertSame($this->it->Id, $monitor->UnitPengelolaId);
            $this->assertSame('IDR', $monitor->refresh()->MataUang, 'Isian kosong memakai bawaan, seperti formulir.');
            $this->assertSame('Aktif', $monitor->Status);
            $this->assertNotNull($monitor->KodeQr);
            $this->assertSame($admin->Id, $monitor->DibuatOleh);

            $riwayat = RiwayatLokasiAset::query()->where('AsetId', $monitor->Id)->sole();
            $this->assertSame($this->ruangIcu->Id, $riwayat->LokasiTujuanId);
            $this->assertSame(0, RiwayatLokasiAset::query()->where('AsetId', $ventilator->Id)->count());

            $audit = CatatanAudit::query()->where('Aksi', 'Aset.Diimpor')->sole();
            $this->assertSame(['NamaBerkas' => 'aset.csv', 'Jumlah' => 2, 'AsetId' => [$monitor->Id, $ventilator->Id]], $audit->DataSesudah);
        });
    }

    public function test_pengguna_tanpa_izin_buat_aset_ditolak(): void
    {
        $pengamat = $this->buatPengguna(['Aset.Lihat']);

        $this->actingAs($pengamat)->get('/aset/impor/templat?format=csv')->assertForbidden();
        $this->pratinjau($pengamat, $this->csv([['', 'Monitor', 'KAT-ALK', '', '']]))->assertForbidden();
        $this->actingAs($pengamat)->post('/aset/impor', ['Berkas' => $this->csv([['', 'Monitor', 'KAT-ALK', '', '']])], ['Accept' => 'application/json'])
            ->assertForbidden();

        $this->assertSame(0, $this->jumlahAset());
    }

    public function test_pengguna_berlingkup_tidak_bisa_mengimpor_ke_lokasi_di_luar_lingkupnya(): void
    {
        $perawatIcu = $this->buatPengguna(lingkupLokasi: $this->ruangIcu);

        $this->pratinjau($perawatIcu, $this->csv([
            ['', 'Monitor ICU', 'KAT-ALK', 'R-ICU', ''],
            ['', 'Server', 'KAT-ALK', 'R-IT', ''],
            ['', 'Tanpa Lokasi', 'KAT-ALK', '', ''],
        ]))
            ->assertOk()
            ->assertJson([
                'jumlahSah' => 1,
                'galat' => [
                    ['baris' => 3, 'kolom' => 'Kode Lokasi', 'pesan' => 'Di luar lingkup akses Anda. Lokasi, unit organisasi, atau unit pengelolanya harus termasuk lingkup Anda.'],
                    ['baris' => 4, 'kolom' => 'Kode Lokasi'],
                ],
            ])
            ->assertJsonCount(2, 'galat');

        $this->actingAs($perawatIcu)->post('/aset/impor', ['Berkas' => $this->csv([['', 'Server', 'KAT-ALK', 'R-IT', '']])], ['Accept' => 'application/json'])
            ->assertUnprocessable();
        $this->assertSame(0, $this->jumlahAset());
    }

    /** Semantik lingkup sama dengan daftar aset: staf IT boleh mengimpor aset kelolaan IT di ruangan mana pun (PRD 8.21). */
    public function test_staf_berlingkup_unit_pengelola_boleh_mengimpor_aset_kelolaannya_di_ruangan_lain(): void
    {
        $stafIt = $this->buatPengguna(lingkupUnit: $this->it);

        $this->pratinjau($stafIt, $this->csv([
            ['', 'Printer ICU', 'KAT-ALK', 'R-ICU', 'IT'],
            ['', 'Monitor ICU', 'KAT-ALK', 'R-ICU', ''],
        ]))
            ->assertOk()
            ->assertJson(['jumlahSah' => 1, 'galat' => [['baris' => 3, 'kolom' => 'Kode Lokasi']]])
            ->assertJsonCount(1, 'galat');
    }

    public function test_berkas_lebih_dari_seribu_baris_ditolak_utuh(): void
    {
        $admin = $this->buatPengguna();
        $baris = array_fill(0, 1001, ['', 'Monitor', 'KAT-ALK', '', '']);

        $this->pratinjau($admin, $this->csv($baris))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['Berkas' => 'Berkas berisi lebih dari 1.000 baris data.']);

        $this->pratinjau($admin, $this->csv(array_slice($baris, 0, 1000)))
            ->assertOk()
            ->assertJson(['jumlahSah' => 1000]);
    }

    public function test_berkas_selain_csv_atau_xlsx_ditolak(): void
    {
        $admin = $this->buatPengguna();

        $this->pratinjau($admin, UploadedFile::fake()->create('aset.pdf', 10, 'application/pdf'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['Berkas' => 'Berkas harus CSV atau XLSX.']);

        // Bernama .xlsx tetapi isinya teks: dikenali sebagai berkas rusak, bukan meledak.
        $this->pratinjau($admin, UploadedFile::fake()->createWithContent('aset.xlsx', "Nama,Kode Kategori\nA,B\n"))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['Berkas' => 'Berkas tidak dapat dibaca.']);
    }

    public function test_kepala_kolom_yang_tidak_dikenali_atau_kurang_ditolak_utuh(): void
    {
        $admin = $this->buatPengguna();

        $respons = $this->pratinjau($admin, $this->csv([['Monitor', 'R-ICU']], ['Nama Barang', 'Kode Lokasi']))
            ->assertUnprocessable();

        $this->assertSame([
            'Kolom "Nama Barang" tidak dikenali. Gunakan judul kolom dari templat.',
            'Kolom wajib "Nama" tidak ada.',
            'Kolom wajib "Kode Kategori" tidak ada.',
        ], $respons->json('errors.Berkas'));
    }

    public function test_berkas_galat_menetralkan_sel_berawalan_rumus(): void
    {
        $admin = $this->buatPengguna();

        $respons = $this->actingAs($admin)->post('/aset/impor/galat', ['Berkas' => $this->csv([
            ['', 'Monitor', '=HYPERLINK("http://jahat")', '', ''],
        ])])->assertOk();

        $baris = $this->barisCsv($this->isiUnduhan($respons));

        $this->assertSame(['Baris', 'Kolom', 'Isi Sel', 'Pesan'], $baris[0]);
        $this->assertSame(['2', 'Kode Kategori', "'=HYPERLINK(\"http://jahat\")"], array_slice($baris[1], 0, 3));
        $this->assertStringStartsWith('Kode Kategori', $baris[1][3]);
    }

    public function test_kode_milik_organisasi_lain_tidak_dikenali(): void
    {
        $admin = $this->buatPengguna();
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'RS Lain', 'Status' => 'Aktif']);
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($lain->Id);
        KategoriAset::create(['Kode' => 'KAT-LAIN', 'Nama' => 'Kategori Lain']);
        Lokasi::create(['Kode' => 'R-LAIN', 'Nama' => 'Ruang Lain']);
        $konteks->bersihkan();

        $this->pratinjau($admin, $this->csv([['', 'Monitor', 'KAT-LAIN', 'R-LAIN', '']]))
            ->assertOk()
            ->assertJson([
                'jumlahSah' => 0,
                'galat' => [
                    ['kolom' => 'Kode Kategori', 'pesan' => 'Kode Kategori "KAT-LAIN" tidak ditemukan.'],
                    ['kolom' => 'Kode Lokasi', 'pesan' => 'Kode Lokasi "R-LAIN" tidak ditemukan.'],
                ],
            ]);
    }

    private function pratinjau(Pengguna $pengguna, UploadedFile $berkas): TestResponse
    {
        return $this->actingAs($pengguna)->post('/aset/impor/pratinjau', ['Berkas' => $berkas], ['Accept' => 'application/json']);
    }

    /**
     * @param  list<list<string>>  $baris
     * @param  list<string>  $kepala
     */
    private function csv(array $baris, array $kepala = self::KEPALA): UploadedFile
    {
        $pegangan = fopen('php://temp', 'w+b');
        $this->assertNotFalse($pegangan);

        foreach ([$kepala, ...$baris] as $satu) {
            fputcsv($pegangan, $satu, escape: '');
        }

        rewind($pegangan);
        $isi = (string) stream_get_contents($pegangan);
        fclose($pegangan);

        return UploadedFile::fake()->createWithContent('aset.csv', $isi);
    }

    /**
     * @param  list<string>  $kepala
     * @param  list<list<string|int|float|DateTimeImmutable>>  $baris
     */
    private function xlsx(array $kepala, array $baris): UploadedFile
    {
        $jalur = tempnam(sys_get_temp_dir(), 'uji-impor').'.xlsx';
        $penulis = new PenulisXlsx;
        $penulis->openToFile($jalur);

        foreach ([$kepala, ...$baris] as $satu) {
            $penulis->addRow(Row::fromValues($satu));
        }

        $penulis->close();

        return new UploadedFile($jalur, 'aset.xlsx', null, null, true);
    }

    private function jalurUnduhan(TestResponse $respons): string
    {
        $dasar = $respons->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $dasar);

        return $dasar->getFile()->getPathname();
    }

    private function isiUnduhan(TestResponse $respons): string
    {
        return (string) file_get_contents($this->jalurUnduhan($respons));
    }

    /** @return list<list<string>> */
    private function barisCsv(string $isi): array
    {
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi) ?? $isi;
        $baris = [];

        foreach (preg_split('/\r\n|\n/', trim($isi)) ?: [] as $satu) {
            $baris[] = array_map(strval(...), str_getcsv($satu, escape: ''));
        }

        return $baris;
    }

    /** @return array<string, list<list<string>>> nama lembar => baris */
    private function bacaXlsx(string $jalur): array
    {
        $pembaca = new PembacaXlsx;
        $pembaca->open($jalur);
        $hasil = [];

        foreach ($pembaca->getSheetIterator() as $lembar) {
            foreach ($lembar->getRowIterator() as $baris) {
                $hasil[$lembar->getName()][] = array_map(strval(...), $baris->toArray());
            }
        }

        $pembaca->close();

        return $hasil;
    }

    private function jumlahAset(): int
    {
        return $this->dalamOrganisasi(fn (): int => Aset::query()->withTrashed()->count());
    }

    /** @param  list<string>  $kodeIzin */
    private function buatPengguna(array $kodeIzin = ['Aset.Lihat', 'Aset.Buat'], ?UnitOrganisasi $lingkupUnit = null, ?Lokasi $lingkupLokasi = null): Pengguna
    {
        return $this->dalamOrganisasi(function () use ($kodeIzin, $lingkupUnit, $lingkupLokasi): Pengguna {
            $pengguna = Pengguna::create([
                'OrganisasiId' => $this->organisasi->Id,
                'Nama' => 'Pengguna '.uniqid(),
                'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
                'KataSandi' => 'rahasia',
                'Status' => 'Aktif',
            ]);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);

            foreach ($kodeIzin as $kode) {
                $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            }

            PenggunaPeran::create([
                'PenggunaId' => $pengguna->Id,
                'PeranId' => $peran->Id,
                'UnitOrganisasiId' => $lingkupUnit?->Id,
                'LokasiId' => $lingkupLokasi?->Id,
            ]);

            return $pengguna;
        });
    }

    /**
     * @template T
     *
     * @param  callable(): T  $aksi
     * @return T
     */
    private function dalamOrganisasi(callable $aksi): mixed
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($this->organisasi->Id);

        try {
            return $aksi();
        } finally {
            $konteks->bersihkan();
        }
    }
}
