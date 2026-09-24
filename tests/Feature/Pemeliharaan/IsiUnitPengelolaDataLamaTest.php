<?php

declare(strict_types=1);

namespace Tests\Feature\Pemeliharaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Application\Actions\IsiUnitPengelolaDataLama;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Perintah `pemeliharaan:isi-unit-pengelola` (PRD 8.21, TASK 40.06): data lama
 * diisi secara eksplisit, per organisasi, idempoten, dan teraudit.
 */
final class IsiUnitPengelolaDataLamaTest extends TestCase
{
    use RefreshDatabase;

    private const PERINTAH = 'pemeliharaan:isi-unit-pengelola';

    /** @var array<string, string> Id baris uji menurut nama pendeknya. */
    private array $id = [];

    private Organisasi $organisasi;

    private UnitOrganisasi $it;

    private UnitOrganisasi $ipsrs;

    protected function tearDown(): void
    {
        app(KonteksOrganisasi::class)->bersihkan();

        parent::tearDown();
    }

    public function test_pratinjau_menampilkan_rencana_tanpa_menulis_apa_pun(): void
    {
        $this->semaiRumahSakit();

        $this->artisan(self::PERINTAH, ['--pratinjau' => true])
            ->expectsOutputToContain('RS Lama: Keluhan 3 kosong, 2 akan diisi (Instalasi IPSRS 1, Instalasi IT 1), 1 tetap kosong')
            ->expectsOutputToContain('RS Lama: PerintahKerja 5 kosong, 4 akan diisi')
            ->expectsOutputToContain('Tidak ada yang ditulis.')
            ->assertSuccessful();

        foreach (['Keluhan', 'PerintahKerja', 'RencanaPemeliharaan', 'RencanaKalibrasi'] as $tabel) {
            $this->assertSame(
                0,
                DB::table($tabel)->where('OrganisasiId', $this->organisasi->Id)->whereNotNull('UnitPengelolaId')->whereNotIn('Id', [$this->id['k-sudah']])->count(),
                "{$tabel} tidak boleh tertulis saat pratinjau.",
            );
        }

        $this->assertSame(0, DB::table('CatatanAudit')->where('Aksi', IsiUnitPengelolaDataLama::AKSI_AUDIT)->count());
    }

    public function test_tanpa_konfirmasi_tidak_ada_yang_ditulis(): void
    {
        $this->semaiRumahSakit();

        $this->artisan(self::PERINTAH)
            ->expectsConfirmation('Isi unit pengelola pada 8 baris di 1 organisasi?', 'no')
            ->expectsOutput('Pengisian dibatalkan.')
            ->assertFailed();

        $this->assertNull($this->unit('Keluhan', 'k-kategori-induk'));
        $this->assertSame(0, DB::table('CatatanAudit')->where('Aksi', IsiUnitPengelolaDataLama::AKSI_AUDIT)->count());
    }

    public function test_mengisi_dari_kategori_aset_keluhan_dan_rencana_tanpa_menimpa_yang_sudah_ada(): void
    {
        $this->semaiRumahSakit();

        $this->artisan(self::PERINTAH)
            ->expectsConfirmation('Isi unit pengelola pada 8 baris di 1 organisasi?', 'yes')
            ->assertSuccessful();

        // Rencana: kalibrasi dari asetnya; preventif hanya bila seluruh asetnya satu unit.
        $this->assertSame($this->ipsrs->Id, $this->unit('RencanaKalibrasi', 'rk-ac'));
        $this->assertSame($this->it->Id, $this->unit('RencanaPemeliharaan', 'rp-it'));
        $this->assertNull($this->unit('RencanaPemeliharaan', 'rp-campuran'));
        $this->assertNull($this->unit('RencanaPemeliharaan', 'rp-sebagian'));

        // Keluhan: kategori (naik ke induk) lebih dulu, lalu aset.
        $this->assertSame($this->it->Id, $this->unit('Keluhan', 'k-kategori-induk'));
        $this->assertSame($this->ipsrs->Id, $this->unit('Keluhan', 'k-aset'));
        $this->assertNull($this->unit('Keluhan', 'k-tanpa-sumber'));
        // Nilai yang sudah ada tidak ditimpa walau kategorinya menunjuk IT.
        $this->assertSame($this->ipsrs->Id, $this->unit('Keluhan', 'k-sudah'));

        // Perintah kerja: keluhan asal → aset utama → rencana asal.
        $this->assertSame($this->it->Id, $this->unit('PerintahKerja', 'pk-dari-keluhan'));
        $this->assertSame($this->ipsrs->Id, $this->unit('PerintahKerja', 'pk-aset'));
        $this->assertSame($this->it->Id, $this->unit('PerintahKerja', 'pk-preventif'));
        $this->assertSame($this->ipsrs->Id, $this->unit('PerintahKerja', 'pk-kalibrasi'));
        $this->assertNull($this->unit('PerintahKerja', 'pk-tanpa-sumber'));

        $audit = DB::table('CatatanAudit')->where('Aksi', IsiUnitPengelolaDataLama::AKSI_AUDIT)->get();
        $this->assertTrue($audit->every(fn (object $satu): bool => $satu->OrganisasiId === $this->organisasi->Id));
        $this->assertNull($audit->first()?->PenggunaId);

        $auditKeluhanIt = $audit->first(fn (object $satu): bool => $satu->JenisEntitas === 'Keluhan'
            && json_decode((string) $satu->DataSesudah, true)['UnitPengelolaId'] === $this->it->Id);
        $this->assertNotNull($auditKeluhanIt);
        $this->assertSame([$this->id['k-kategori-induk']], json_decode((string) $auditKeluhanIt->DataSesudah, true)['Id']);
        $this->assertSame(8, $audit->sum(fn (object $satu): int => json_decode((string) $satu->DataSesudah, true)['Jumlah']));
    }

    public function test_menjalankan_ulang_tidak_mengubah_apa_pun_dan_tidak_menambah_audit(): void
    {
        $this->semaiRumahSakit();
        $this->artisan(self::PERINTAH, ['--paksa' => true])->assertSuccessful();
        $jumlahAudit = DB::table('CatatanAudit')->where('Aksi', IsiUnitPengelolaDataLama::AKSI_AUDIT)->count();
        $this->assertGreaterThan(0, $jumlahAudit);

        $this->artisan(self::PERINTAH, ['--paksa' => true])
            ->expectsOutput('Tidak ada unit pengelola kosong yang dapat diturunkan dari kategori atau aset.')
            ->assertSuccessful();

        $this->assertSame($jumlahAudit, DB::table('CatatanAudit')->where('Aksi', IsiUnitPengelolaDataLama::AKSI_AUDIT)->count());
    }

    public function test_hanya_organisasi_yang_dipilih_yang_diisi(): void
    {
        $this->semaiRumahSakit();
        $dipilih = $this->organisasi;
        $idDipilih = $this->id;

        $this->semaiRumahSakit('RS Lain');
        $lain = $this->organisasi;

        $this->artisan(self::PERINTAH, ['--organisasi' => $dipilih->Id, '--paksa' => true])->assertSuccessful();

        $this->assertNotNull(DB::table('Keluhan')->where('Id', $idDipilih['k-kategori-induk'])->value('UnitPengelolaId'));
        $this->assertNull($this->unit('Keluhan', 'k-kategori-induk'));
        $this->assertSame(0, DB::table('CatatanAudit')->where('Aksi', IsiUnitPengelolaDataLama::AKSI_AUDIT)->where('OrganisasiId', $lain->Id)->count());
    }

    /** Nilai yang diisi orang lain di antara pembacaan dan penulisan tidak tertimpa. */
    public function test_nilai_yang_terisi_di_tengah_jalan_tidak_ditimpa(): void
    {
        $this->semaiRumahSakit();
        $sasaran = $this->id['k-kategori-induk'];
        $sudahDisela = false;

        // Meniru pengalihan oleh koordinator tepat sesudah potongan keluhan dibaca.
        DB::listen(function ($kueri) use ($sasaran, &$sudahDisela): void {
            if (! $sudahDisela && str_contains($kueri->sql, 'from `Keluhan`') && str_contains($kueri->sql, '`UnitPengelolaId` is null')) {
                $sudahDisela = true;
                DB::table('Keluhan')->where('Id', $sasaran)->update(['UnitPengelolaId' => $this->ipsrs->Id]);
            }
        });

        $hasil = app(IsiUnitPengelolaDataLama::class)->jalankan($this->organisasi->Id);

        $this->assertTrue($sudahDisela);
        $this->assertSame(1, $hasil['Keluhan']['Diisi'], 'Hanya keluhan dari aset yang terisi; yang disela tidak dihitung.');
        $this->assertSame($this->ipsrs->Id, $this->unit('Keluhan', 'k-kategori-induk'));
    }

    /** Organisasi yang tidak memakai unit pengelola tidak dipindai dan tidak berubah. */
    public function test_organisasi_tanpa_unit_pengelola_dilewati(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-POLOS', 'Nama' => 'Organisasi Polos', 'Status' => 'Aktif']);
        DB::table('Keluhan')->insert($this->barisKeluhan($organisasi->Id, 'Keluhan polos'));

        $this->artisan(self::PERINTAH, ['--organisasi' => $organisasi->Id, '--paksa' => true])
            ->expectsOutput('Tidak ada unit pengelola kosong yang dapat diturunkan dari kategori atau aset.')
            ->assertSuccessful();

        $this->assertSame(0, DB::table('CatatanAudit')->where('Aksi', IsiUnitPengelolaDataLama::AKSI_AUDIT)->count());
    }

    /** Lebih dari satu potongan: seluruh baris terisi walau UPDATE menggeser himpunan `IS NULL` di tengah pembacaan. */
    public function test_data_besar_diisi_per_potongan_sampai_habis(): void
    {
        $this->semaiRumahSakit();
        $kategori = $this->id['kat-printer'];

        $baris = [];
        foreach (range(1, 1100) as $ke) {
            $baris[] = [...$this->barisKeluhan($this->organisasi->Id, 'Massal '.$ke), 'KategoriKeluhanId' => $kategori];
        }
        foreach (array_chunk($baris, 250) as $potongan) {
            DB::table('Keluhan')->insert($potongan);
        }

        $this->artisan(self::PERINTAH, ['--paksa' => true])->assertSuccessful();

        $this->assertSame(0, DB::table('Keluhan')->where('KategoriKeluhanId', $kategori)->whereNull('UnitPengelolaId')->count());
        $this->assertSame(1101, DB::table('Keluhan')->where('KategoriKeluhanId', $kategori)->where('UnitPengelolaId', $this->it->Id)->count());
    }

    /**
     * Satu organisasi rumah sakit dengan data sebelum FASE 40: unit pengelola
     * sudah ditandai dan diisi di aset serta kategori, tetapi tiket dan rencana lama masih kosong.
     */
    private function semaiRumahSakit(string $nama = 'RS Lama'): void
    {
        $this->id = [];
        $this->organisasi = Organisasi::create(['Kode' => 'ORG-'.Str::upper(Str::random(6)), 'Nama' => $nama, 'Status' => 'Aktif']);
        $organisasiId = $this->organisasi->Id;
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiId);

        $this->it = UnitOrganisasi::create(['Kode' => 'IT', 'Nama' => 'Instalasi IT', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
        $this->ipsrs = UnitOrganisasi::create(['Kode' => 'IPS', 'Nama' => 'Instalasi IPSRS', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);

        $kategoriAset = KategoriAset::create(['Nama' => 'Umum']);
        $printer = $this->aset($kategoriAset, 'Printer ICU', $this->it);
        $ac = $this->aset($kategoriAset, 'AC ICU', $this->ipsrs);
        $kursi = $this->aset($kategoriAset, 'Kursi', null);

        // Kategori anak tanpa unit; induknya menunjuk IT.
        $this->id['kat-komputer'] = $this->sisip('KategoriKeluhan', ['Kode' => 'KOMP', 'Nama' => 'Komputer', 'UnitPengelolaId' => $this->it->Id]);
        $this->id['kat-printer'] = $this->sisip('KategoriKeluhan', ['Kode' => 'PRN', 'Nama' => 'Printer', 'IndukId' => $this->id['kat-komputer']]);
        $this->id['kat-umum'] = $this->sisip('KategoriKeluhan', ['Kode' => 'UMUM', 'Nama' => 'Umum']);

        $this->id['k-kategori-induk'] = $this->sisip('Keluhan', [...$this->barisKeluhan($organisasiId, 'Printer macet'), 'KategoriKeluhanId' => $this->id['kat-printer']]);
        $this->id['k-aset'] = $this->sisip('Keluhan', [...$this->barisKeluhan($organisasiId, 'AC bocor'), 'KategoriKeluhanId' => $this->id['kat-umum'], 'AsetId' => $ac->Id]);
        $this->id['k-tanpa-sumber'] = $this->sisip('Keluhan', [...$this->barisKeluhan($organisasiId, 'Lainnya'), 'KategoriKeluhanId' => $this->id['kat-umum']]);
        $this->id['k-sudah'] = $this->sisip('Keluhan', [...$this->barisKeluhan($organisasiId, 'Sudah dialihkan'), 'KategoriKeluhanId' => $this->id['kat-printer'], 'UnitPengelolaId' => $this->ipsrs->Id]);

        $this->id['rp-it'] = $this->rencanaPemeliharaan('RP-IT', [$printer]);
        $this->id['rp-campuran'] = $this->rencanaPemeliharaan('RP-CAMPUR', [$printer, $ac]);
        $this->id['rp-sebagian'] = $this->rencanaPemeliharaan('RP-SEBAGIAN', [$printer, $kursi]);
        $this->id['rk-ac'] = $this->sisip('RencanaKalibrasi', [
            'AsetId' => $ac->Id, 'IntervalHari' => 365, 'TanggalMulai' => '2026-01-01', 'TanggalBerikutnya' => '2027-01-01',
        ]);

        $this->id['pk-dari-keluhan'] = $this->perintahKerja('PK dari keluhan', ['KeluhanId' => $this->id['k-kategori-induk']]);
        $this->id['pk-aset'] = $this->perintahKerja('PK aset');
        $this->sisip('PerintahKerjaAset', ['PerintahKerjaId' => $this->id['pk-aset'], 'AsetId' => $kursi->Id, 'Utama' => false]);
        $this->sisip('PerintahKerjaAset', ['PerintahKerjaId' => $this->id['pk-aset'], 'AsetId' => $ac->Id, 'Utama' => true]);

        $this->id['pk-preventif'] = $this->perintahKerja('PK preventif');
        $this->sisip('JadwalPemeliharaan', [
            'RencanaPemeliharaanAsetId' => $this->id['rpa-RP-IT-0'], 'PerintahKerjaId' => $this->id['pk-preventif'], 'TanggalJadwal' => '2026-02-01',
        ]);

        $this->id['pk-kalibrasi'] = $this->perintahKerja('PK kalibrasi');
        $this->sisip('PelaksanaanKalibrasi', [
            'Nomor' => 'KAL-'.Str::random(6), 'RencanaKalibrasiId' => $this->id['rk-ac'], 'AsetId' => $kursi->Id,
            'PerintahKerjaId' => $this->id['pk-kalibrasi'], 'TanggalKalibrasi' => '2026-01-15', 'Hasil' => 'Lulus',
        ]);

        $this->id['pk-tanpa-sumber'] = $this->perintahKerja('PK tanpa sumber');

        $konteks->bersihkan();
    }

    private function aset(KategoriAset $kategori, string $nama, ?UnitOrganisasi $pengelola): Aset
    {
        return Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'UnitPengelolaId' => $pengelola?->Id,
            'Nama' => $nama,
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
        ]);
    }

    /** @param list<Aset> $daftarAset */
    private function rencanaPemeliharaan(string $kode, array $daftarAset): string
    {
        $rencanaId = $this->sisip('RencanaPemeliharaan', ['Kode' => $kode, 'Nama' => 'Rencana '.$kode]);

        foreach ($daftarAset as $ke => $aset) {
            $this->id["rpa-{$kode}-{$ke}"] = $this->sisip('RencanaPemeliharaanAset', [
                'RencanaPemeliharaanId' => $rencanaId, 'AsetId' => $aset->Id, 'TanggalMulai' => '2026-01-01',
            ]);
        }

        return $rencanaId;
    }

    /** @param array<string, mixed> $tambahan */
    private function perintahKerja(string $judul, array $tambahan = []): string
    {
        return $this->sisip('PerintahKerja', ['Nomor' => 'PK-'.Str::random(8), 'Jenis' => 'Korektif', 'Judul' => $judul, ...$tambahan]);
    }

    /** @return array<string, mixed> */
    private function barisKeluhan(string $organisasiId, string $judul): array
    {
        return [
            'Id' => (string) Str::ulid(),
            'OrganisasiId' => $organisasiId,
            'Nomor' => 'KLH-'.Str::random(10),
            'Judul' => $judul,
            'Deskripsi' => 'Data lama',
        ];
    }

    /**
     * Disisipkan langsung ke tabel, seperti data yang tercatat sebelum FASE 40:
     * tidak melewati Action yang kini menurunkan unit pengelola.
     *
     * @param  array<string, mixed>  $kolom
     */
    private function sisip(string $tabel, array $kolom): string
    {
        $id = (string) ($kolom['Id'] ?? Str::ulid());
        DB::table($tabel)->insert(['OrganisasiId' => $this->organisasi->Id, ...$kolom, 'Id' => $id]);

        return $id;
    }

    private function unit(string $tabel, string $nama): ?string
    {
        $nilai = DB::table($tabel)->where('Id', $this->id[$nama])->value('UnitPengelolaId');

        return is_string($nilai) ? $nilai : null;
    }
}
