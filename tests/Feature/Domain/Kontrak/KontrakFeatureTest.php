<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Kontrak;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kontrak\Application\Actions\KelolaCakupanAsetKontrak;
use App\Domain\Kontrak\Application\Actions\KelolaKontrak;
use App\Domain\Kontrak\Application\Actions\KelolaLayananKontrak;
use App\Domain\Kontrak\Application\Services\LayananCakupanKontrak;
use App\Domain\Kontrak\Application\Services\LayananPeringatanKontrak;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FASE 17 — kontrak penyedia, cakupan aset, layanan, dan pengingat masa berlaku.
 */
final class KontrakFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_17_01_kontrak_dibuat_diubah_dan_periode_divalidasi(): void
    {
        $konteks = $this->siapkanKonteks();
        $aksi = app(KelolaKontrak::class);

        $kontrak = $aksi->buat($this->dataKontrak($konteks));
        $this->assertSame(Kontrak::STATUS_AKTIF, $kontrak->Status);
        $this->assertSame('120000000.00', $kontrak->Nilai);

        $diubah = $aksi->ubah($kontrak, array_merge($this->dataKontrak($konteks), ['Nama' => 'Kontrak Pemeliharaan Revisi']));
        $this->assertSame('Kontrak Pemeliharaan Revisi', $diubah->Nama);

        // Tanggal berakhir mendahului tanggal mulai ditolak.
        $this->assertThrows(
            fn () => $aksi->buat(array_merge($this->dataKontrak($konteks), [
                'Nomor' => 'KTR-SALAH',
                'MulaiPada' => '2026-06-01',
                'BerakhirPada' => '2026-05-31',
            ])),
            AturanBisnisDilanggar::class,
        );
    }

    public function test_17_01_kontrak_dibatalkan_tidak_dapat_diubah_lagi(): void
    {
        $konteks = $this->siapkanKonteks();
        $aksi = app(KelolaKontrak::class);
        $kontrak = $aksi->buat($this->dataKontrak($konteks));

        $dibatalkan = $aksi->batalkan($kontrak, 'Penyedia mengundurkan diri.');
        $this->assertSame(Kontrak::STATUS_DIBATALKAN, $dibatalkan->Status);
        $this->assertStringContainsString('Penyedia mengundurkan diri.', (string) $dibatalkan->Catatan);

        $this->assertThrows(
            fn () => $aksi->ubah($dibatalkan, $this->dataKontrak($konteks)),
            AturanBisnisDilanggar::class,
        );
        $this->assertThrows(
            fn () => $aksi->batalkan($dibatalkan->refresh(), 'Dibatalkan lagi.'),
            AturanBisnisDilanggar::class,
        );
    }

    public function test_17_02_cakupan_aset_dilampirkan_dengan_periode_di_dalam_kontrak(): void
    {
        $konteks = $this->siapkanKonteks();
        $kontrak = app(KelolaKontrak::class)->buat($this->dataKontrak($konteks));
        $aksi = app(KelolaCakupanAsetKontrak::class);

        $cakupan = $aksi->lampirkan($kontrak, ['AsetId' => $konteks['aset']->Id]);
        $this->assertSame($konteks['aset']->Id, $cakupan->AsetId);
        $this->assertSame('2026-01-01', $cakupan->MulaiPada->toDateString());

        // Aset yang sama tidak boleh dilampirkan dua kali.
        $this->assertThrows(
            fn () => $aksi->lampirkan($kontrak, ['AsetId' => $konteks['aset']->Id]),
            AturanBisnisDilanggar::class,
        );

        // Periode cakupan di luar periode kontrak ditolak.
        $asetLain = $this->buatAset($konteks, 'AST-LAIN');
        $this->assertThrows(
            fn () => $aksi->lampirkan($kontrak, [
                'AsetId' => $asetLain->Id,
                'MulaiPada' => '2026-01-01',
                'BerakhirPada' => '2027-06-30',
            ]),
            AturanBisnisDilanggar::class,
        );

        $aksi->lepaskan($kontrak, $cakupan);
        $this->assertSame(0, $kontrak->kontrakAset()->count());
    }

    public function test_17_02_aset_organisasi_lain_tidak_dapat_dilampirkan(): void
    {
        $konteks = $this->siapkanKonteks();
        $kontrak = app(KelolaKontrak::class)->buat($this->dataKontrak($konteks));

        $organisasiLain = $this->buatOrganisasi('LAIN');
        app(KonteksOrganisasi::class)->tetapkan($organisasiLain->Id);
        $asetLain = Aset::create([
            'KategoriAsetId' => KategoriAset::create(['Kode' => 'KAT-'.uniqid(), 'Nama' => 'Kategori Lain'])->Id,
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => 'Aset Tenant Lain',
            'Status' => Aset::STATUS_AKTIF,
            'Kondisi' => Aset::KONDISI_BAIK,
            'TingkatKritis' => Aset::KRITIS_NORMAL,
        ]);

        app(KonteksOrganisasi::class)->tetapkan($konteks['organisasi']->Id);
        $this->assertThrows(
            fn () => app(KelolaCakupanAsetKontrak::class)->lampirkan($kontrak, ['AsetId' => $asetLain->Id]),
            AturanBisnisDilanggar::class,
        );
    }

    public function test_17_03_layanan_kontrak_mencatat_pemakaian_dan_menahan_kelebihan_kuota(): void
    {
        $konteks = $this->siapkanKonteks();
        $kontrak = app(KelolaKontrak::class)->buat($this->dataKontrak($konteks));
        $aksi = app(KelolaLayananKontrak::class);

        $layanan = $aksi->tambah($kontrak, ['Nama' => 'Kunjungan preventif', 'Kuota' => '4', 'Satuan' => 'kunjungan']);
        $this->assertSame('0.0000', $layanan->Terpakai);

        $aksi->catatPemakaian($layanan, '3');
        $this->assertSame('3.0000', $layanan->refresh()->Terpakai);

        // Pemakaian yang melampaui kuota ditolak dan tidak mengubah catatan.
        $this->assertThrows(fn () => $aksi->catatPemakaian($layanan->refresh(), '1.5'), AturanBisnisDilanggar::class);
        $this->assertSame('3.0000', $layanan->refresh()->Terpakai);

        $aksi->catatPemakaian($layanan->refresh(), '1');
        $this->assertSame('4.0000', $layanan->refresh()->Terpakai);

        // Layanan yang sudah terpakai tidak boleh dihapus.
        $this->assertThrows(fn () => $aksi->hapus($kontrak, $layanan->refresh()), AturanBisnisDilanggar::class);

        $kosong = $aksi->tambah($kontrak, ['Nama' => 'Layanan tanpa pemakaian']);
        $aksi->hapus($kontrak, $kosong);
        $this->assertSame(1, $kontrak->layanan()->count());
    }

    public function test_17_03_gate_pekerjaan_vendor_dapat_ditelusuri_ke_kontrak_aktif(): void
    {
        $konteks = $this->siapkanKonteks();
        $kontrak = app(KelolaKontrak::class)->buat($this->dataKontrak($konteks));
        app(KelolaCakupanAsetKontrak::class)->lampirkan($kontrak, ['AsetId' => $konteks['aset']->Id]);
        $layanan = app(LayananCakupanKontrak::class);

        $hasil = $layanan->telusuriPekerjaanVendor(
            $konteks['aset']->Id,
            $konteks['penyedia']->Id,
            CarbonImmutable::parse('2026-03-15'),
        );
        $this->assertTrue($hasil['tercakup']);
        $this->assertSame($kontrak->Id, $hasil['kontrak']?->Id);

        // Di luar periode kontrak tidak tercakup.
        $this->assertFalse($layanan->telusuriPekerjaanVendor(
            $konteks['aset']->Id,
            $konteks['penyedia']->Id,
            CarbonImmutable::parse('2027-03-15'),
        )['tercakup']);

        // Penyedia lain tidak boleh ikut tercakup kontrak ini.
        $penyediaLain = $this->buatPenyedia($konteks['organisasi']);
        $this->assertFalse($layanan->telusuriPekerjaanVendor(
            $konteks['aset']->Id,
            $penyediaLain->Id,
            CarbonImmutable::parse('2026-03-15'),
        )['tercakup']);
    }

    public function test_17_04_peringatan_dikirim_pada_ambang_dan_tidak_digandakan_dalam_sehari(): void
    {
        $konteks = $this->siapkanKonteks();
        $kontrak = app(KelolaKontrak::class)->buat(array_merge($this->dataKontrak($konteks), [
            'MulaiPada' => '2026-01-01',
            'BerakhirPada' => '2026-04-01',
        ]));
        $layanan = app(LayananPeringatanKontrak::class);

        // H-90 tepat memicu satu peringatan untuk setiap penerima berizin.
        $hasil = $layanan->kirimPeringatan($konteks['organisasi']->Id, CarbonImmutable::parse('2026-01-01'));
        $this->assertSame(1, $hasil['akanBerakhir']);
        $this->assertSame(1, $this->jumlahNotifikasi($konteks, 'Kontrak.AkanBerakhir.H90'));

        // Dipanggil ulang pada hari yang sama tidak menambah notifikasi.
        $ulang = $layanan->kirimPeringatan($konteks['organisasi']->Id, CarbonImmutable::parse('2026-01-01'));
        $this->assertSame(1, $ulang['dilewati']);
        $this->assertSame(1, $this->jumlahNotifikasi($konteks, 'Kontrak.AkanBerakhir.H90'));

        // Hari yang bukan ambang tidak memicu apa pun.
        $sepi = $layanan->kirimPeringatan($konteks['organisasi']->Id, CarbonImmutable::parse('2026-01-15'));
        $this->assertSame(0, $sepi['akanBerakhir']);

        // H-30 memakai ambang berbeda sehingga notifikasinya terpisah.
        $layanan->kirimPeringatan($konteks['organisasi']->Id, CarbonImmutable::parse('2026-03-02'));
        $this->assertSame(1, $this->jumlahNotifikasi($konteks, 'Kontrak.AkanBerakhir.H30'));
        $this->assertSame(Kontrak::STATUS_AKTIF, $kontrak->refresh()->Status);
    }

    public function test_17_04_kontrak_lewat_masa_berlaku_ditutup_otomatis(): void
    {
        $konteks = $this->siapkanKonteks();
        $kontrak = app(KelolaKontrak::class)->buat(array_merge($this->dataKontrak($konteks), [
            'MulaiPada' => '2026-01-01',
            'BerakhirPada' => '2026-04-01',
        ]));

        $hasil = app(LayananPeringatanKontrak::class)
            ->kirimPeringatan($konteks['organisasi']->Id, CarbonImmutable::parse('2026-04-02'));

        $this->assertSame(1, $hasil['ditutup']);
        $this->assertSame(1, $hasil['kedaluwarsa']);
        $this->assertSame(Kontrak::STATUS_BERAKHIR, $kontrak->refresh()->Status);
        $this->assertSame(1, $this->jumlahNotifikasi($konteks, 'Kontrak.Kedaluwarsa'));
    }

    public function test_17_04_ringkasan_dasbor_menghitung_kontrak_per_kondisi(): void
    {
        $konteks = $this->siapkanKonteks();
        $aksi = app(KelolaKontrak::class);
        $aksi->buat(array_merge($this->dataKontrak($konteks), [
            'Nomor' => 'KTR-JAUH',
            'MulaiPada' => CarbonImmutable::today()->toDateString(),
            'BerakhirPada' => CarbonImmutable::today()->addYears(2)->toDateString(),
        ]));
        $aksi->buat(array_merge($this->dataKontrak($konteks), [
            'Nomor' => 'KTR-DEKAT',
            'PenyediaId' => null,
            'MulaiPada' => CarbonImmutable::today()->subMonths(6)->toDateString(),
            'BerakhirPada' => CarbonImmutable::today()->addDays(10)->toDateString(),
        ]));

        $ringkasan = app(LayananPeringatanKontrak::class)->ringkasan($konteks['organisasi']->Id);

        $this->assertSame(2, $ringkasan['total']);
        $this->assertSame(2, $ringkasan['aktif']);
        $this->assertSame(1, $ringkasan['akanBerakhir']);
        $this->assertSame(0, $ringkasan['kedaluwarsa']);
        $this->assertSame(1, $ringkasan['tanpaPenyedia']);
    }

    public function test_endpoint_kontrak_menegakkan_izin_dan_isolasi_tenant(): void
    {
        $konteks = $this->siapkanKonteks();

        $tanpaIzin = $this->buatPengguna($konteks['organisasi'], []);
        $this->actingAs($tanpaIzin)->get(route('kontrak.index'))->assertForbidden();

        $organisasiLain = $this->buatOrganisasi('LAIN');
        app(KonteksOrganisasi::class)->tetapkan($organisasiLain->Id);
        $kontrakLain = Kontrak::create([
            'Nomor' => 'KTR-LAIN',
            'Nama' => 'Kontrak Tenant Lain',
            'Jenis' => 'Layanan',
            'MulaiPada' => '2026-01-01',
            'BerakhirPada' => '2026-12-31',
            'MataUang' => 'IDR',
            'PeringatanHariSebelum' => 30,
            'Status' => Kontrak::STATUS_AKTIF,
        ]);

        app(KonteksOrganisasi::class)->tetapkan($konteks['organisasi']->Id);
        $this->actingAs($konteks['pengguna'])
            ->get(route('kontrak.show', $kontrakLain->Id))
            ->assertNotFound();
    }

    public function test_halaman_operasional_fase_17_dapat_dirender(): void
    {
        $konteks = $this->siapkanKonteks();
        $kontrak = app(KelolaKontrak::class)->buat($this->dataKontrak($konteks));
        app(KelolaCakupanAsetKontrak::class)->lampirkan($kontrak, ['AsetId' => $konteks['aset']->Id]);
        app(KelolaLayananKontrak::class)->tambah($kontrak, ['Nama' => 'Kunjungan', 'Kuota' => '2']);

        $this->actingAs($konteks['pengguna']);
        $this->get(route('kontrak.index'))->assertOk();
        $this->get(route('kontrak.show', $kontrak))->assertOk();
    }

    /**
     * @return array{organisasi: Organisasi, pengguna: Pengguna, penyedia: Penyedia, aset: Aset}
     */
    private function siapkanKonteks(): array
    {
        $organisasi = $this->buatOrganisasi('KTR');
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $pengguna = $this->buatPengguna($organisasi, ['Kontrak.Kelola']);
        $this->actingAs($pengguna);

        $konteks = [
            'organisasi' => $organisasi,
            'pengguna' => $pengguna,
            'penyedia' => $this->buatPenyedia($organisasi),
        ];
        $konteks['aset'] = $this->buatAset($konteks, 'AST-UTAMA');

        return $konteks;
    }

    /**
     * @param  array<string, mixed>  $konteks
     * @return array<string, mixed>
     */
    private function dataKontrak(array $konteks): array
    {
        return [
            'PenyediaId' => $konteks['penyedia']->Id,
            'Nomor' => 'KTR-2026-001',
            'Nama' => 'Kontrak Pemeliharaan Genset',
            'Jenis' => 'Pemeliharaan',
            'MulaiPada' => '2026-01-01',
            'BerakhirPada' => '2026-12-31',
            'Nilai' => '120000000.00',
            'MataUang' => 'IDR',
            'PeringatanHariSebelum' => 30,
            'Catatan' => 'Mencakup kunjungan preventif triwulanan.',
        ];
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function buatAset(array $konteks, string $prefiks): Aset
    {
        app(KonteksOrganisasi::class)->tetapkan($konteks['organisasi']->Id);
        $kategori = KategoriAset::create(['Kode' => 'KAT-'.uniqid(), 'Nama' => 'Genset']);

        return Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => $prefiks.'-'.uniqid(),
            'Nama' => 'Genset '.uniqid(),
            'Status' => Aset::STATUS_AKTIF,
            'Kondisi' => Aset::KONDISI_BAIK,
            'TingkatKritis' => Aset::KRITIS_NORMAL,
        ]);
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function jumlahNotifikasi(array $konteks, string $jenisPeristiwa): int
    {
        return DB::table('Notifikasi')
            ->where('OrganisasiId', $konteks['organisasi']->Id)
            ->where('JenisEntitas', 'Kontrak')
            ->where('JenisPeristiwa', $jenisPeristiwa)
            ->count();
    }

    private function buatOrganisasi(string $kode): Organisasi
    {
        return Organisasi::create([
            'Kode' => $kode.'-'.uniqid(),
            'Nama' => 'Organisasi '.$kode.' '.uniqid(),
            'Status' => 'Aktif',
        ]);
    }

    private function buatPenyedia(Organisasi $organisasi): Penyedia
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        return Penyedia::create([
            'Kode' => 'PNY-'.uniqid(),
            'Nama' => 'Penyedia '.uniqid(),
            'Status' => Penyedia::STATUS_AKTIF,
        ]);
    }

    /**
     * @param  list<string>  $izin
     */
    private function buatPengguna(Organisasi $organisasi, array $izin): Pengguna
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna.'.uniqid().'@amanpoll.test',
            'KataSandi' => 'password',
            'Status' => 'Aktif',
        ]);

        if ($izin === []) {
            return $pengguna;
        }

        $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran Kontrak']);
        foreach ($izin as $kodeIzin) {
            $modelIzin = Izin::firstOrCreate(['Kode' => $kodeIzin], ['Nama' => $kodeIzin, 'Modul' => 'Kontrak']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
        }
        PenggunaPeran::create(['OrganisasiId' => $organisasi->Id, 'PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        return $pengguna;
    }
}
