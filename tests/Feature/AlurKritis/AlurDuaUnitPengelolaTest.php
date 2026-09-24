<?php

declare(strict_types=1);

namespace Tests\Feature\AlurKritis;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Persediaan\Domain\Enums\StatusGudang;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Alur kritis FASE 40 (PRD 8.21, TASK 40.06): dua unit pengelola dalam satu rumah sakit.
 *
 * IPSRS dan IT masing-masing punya koordinator, teknisi, dan gudang yang
 * berlingkup unitnya sendiri. Printer di ruang ICU milik ICU tetapi dipelihara
 * IT. Test merangkai langkahnya lewat rute HTTP sebagaimana perawat,
 * koordinator, dan manajer menjalankannya: keluhan masuk antrian IT dan tidak
 * terlihat IPSRS, perintah kerja mewarisi IT, teknisi IPSRS tidak ditawarkan
 * dan ditolak server, stok gudang IT tersembunyi dari IPSRS, dan laporan
 * keluhan bisa disaring per unit pengelola. Keluhan IPSRS disemai lewat alur
 * yang sama supaya "tidak terlihat" membandingkan baris nyata.
 */
final class AlurDuaUnitPengelolaTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private UnitOrganisasi $ipsrs;

    private UnitOrganisasi $it;

    private Lokasi $icu;

    private KategoriKeluhan $kategoriPrinter;

    private KategoriKeluhan $kategoriAlatMedis;

    private Aset $printer;

    private Aset $monitor;

    private Gudang $gudangIt;

    private Gudang $gudangIpsrs;

    /** @var array<string, Pengguna> */
    private array $orang = [];

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-21 02:00:00', 'UTC'));
        $this->semaiRumahSakit();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        app(KonteksOrganisasi::class)->bersihkan();

        parent::tearDown();
    }

    public function test_keluhan_printer_icu_ditangani_it_dari_antrian_sampai_laporan(): void
    {
        // 1. Perawat ICU melaporkan printer rusak dan monitor pasien bermasalah.
        $this->actingAs($this->orang['perawat'])->post('/pemeliharaan/keluhan', [
            'KategoriKeluhanId' => $this->kategoriPrinter->Id,
            'AsetId' => $this->printer->Id,
            'LokasiId' => $this->icu->Id,
            'Judul' => 'Printer ICU tidak mencetak',
            'Deskripsi' => 'Kertas tersangkut dan lampu merah berkedip.',
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->actingAs($this->orang['perawat'])->post('/pemeliharaan/keluhan', [
            'KategoriKeluhanId' => $this->kategoriAlatMedis->Id,
            'AsetId' => $this->monitor->Id,
            'LokasiId' => $this->icu->Id,
            'Judul' => 'Monitor pasien bed 3 mati',
            'Deskripsi' => 'Layar gelap sejak pagi.',
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->tetapkanKonteks();
        $keluhanPrinter = Keluhan::query()->where('Judul', 'Printer ICU tidak mencetak')->sole();
        $keluhanMonitor = Keluhan::query()->where('Judul', 'Monitor pasien bed 3 mati')->sole();

        // Antrian ditentukan kategori, bukan pelapor atau ruangannya.
        $this->assertSame($this->it->Id, $keluhanPrinter->UnitPengelolaId);
        $this->assertSame($this->ipsrs->Id, $keluhanMonitor->UnitPengelolaId);

        // 2. Keluhan printer masuk antrian IT dan tidak terlihat koordinator IPSRS, di daftar maupun lewat URL.
        $this->assertSame([$keluhanPrinter->Id], $this->idKeluhanTerlihat('koordinatorIt'));
        $this->assertSame([$keluhanMonitor->Id], $this->idKeluhanTerlihat('koordinatorIpsrs'));
        $this->actingAs($this->orang['koordinatorIpsrs'])->get('/pemeliharaan/keluhan/'.$keluhanPrinter->Id)->assertNotFound();
        $this->actingAs($this->orang['koordinatorIt'])->get('/pemeliharaan/keluhan/'.$keluhanPrinter->Id)->assertOk();

        // 3. Koordinator IT membuat perintah kerja dari keluhan itu; unit pengelolanya mewarisi IT,
        //    unit organisasinya tetap ICU dari aset.
        $this->actingAs($this->orang['koordinatorIt'])->post('/pemeliharaan/perintah-kerja', [
            'KeluhanId' => $keluhanPrinter->Id,
            'Jenis' => 'Korektif',
            'Prioritas' => 'Normal',
            'MembutuhkanWaktuHenti' => false,
            'MembutuhkanPersetujuan' => false,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->tetapkanKonteks();
        $perintahKerja = PerintahKerja::query()->where('KeluhanId', $keluhanPrinter->Id)->sole();
        $this->assertSame($this->it->Id, $perintahKerja->UnitPengelolaId);
        $this->assertSame($this->printer->UnitOrganisasiId, $perintahKerja->UnitOrganisasiId);

        $this->actingAs($this->orang['koordinatorIpsrs'])->get('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id)->assertNotFound();

        // 4. Pilihan teknisi hanya berisi pengguna yang dapat melihat tiket ini.
        $pilihanTeknisi = [];
        $this->actingAs($this->orang['koordinatorIt'])
            ->get('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id)
            ->assertOk()
            ->assertInertia(function (AssertableInertia $halaman) use (&$pilihanTeknisi): void {
                $halaman->component('PerintahKerja/Show');
                $pilihanTeknisi = array_column($halaman->toArray()['props']['teknisi'], 'Id');
            });

        $this->assertContains($this->orang['teknisiIt']->Id, $pilihanTeknisi);
        $this->assertNotContains($this->orang['teknisiIpsrs']->Id, $pilihanTeknisi);
        $this->assertNotContains($this->orang['koordinatorIpsrs']->Id, $pilihanTeknisi);

        // 5. Server menolak teknisi IPSRS walau dikirim langsung; teknisi IT diterima.
        $this->actingAs($this->orang['koordinatorIt'])->post('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id.'/penugasan', [
            'PenggunaIds' => [$this->orang['teknisiIpsrs']->Id],
            'PeranTugas' => 'Ketua',
            'GantiPenugasanAktif' => false,
        ])->assertSessionHasErrors('PenggunaIds');
        $this->assertSame(0, DB::table('PenugasanPerintahKerja')->where('PerintahKerjaId', $perintahKerja->Id)->count());

        $this->actingAs($this->orang['koordinatorIt'])->post('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id.'/penugasan', [
            'PenggunaIds' => [$this->orang['teknisiIt']->Id],
            'PeranTugas' => 'Ketua',
            'GantiPenugasanAktif' => false,
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame(
            [$this->orang['teknisiIt']->Id],
            DB::table('PenugasanPerintahKerja')->where('PerintahKerjaId', $perintahKerja->Id)->pluck('PenggunaId')->all(),
        );

        // 6. Stok gudang IT tidak terlihat koordinator IPSRS, dan sebaliknya.
        $this->assertSame([$this->gudangIpsrs->Id], $this->gudangStokTerlihat('koordinatorIpsrs'));
        $this->assertSame([$this->gudangIt->Id], $this->gudangStokTerlihat('koordinatorIt'));

        // 7. Laporan keluhan manajemen (tanpa lingkup) bisa disaring per unit pengelola.
        $this->assertSame(1.0, $this->keluhanTerbuka([$this->it->Id]));
        $this->assertSame(1.0, $this->keluhanTerbuka([$this->ipsrs->Id]));
        $this->assertSame(2.0, $this->keluhanTerbuka([]));
    }

    /** @return list<string> */
    private function idKeluhanTerlihat(string $siapa): array
    {
        $id = [];

        $this->actingAs($this->orang[$siapa])
            ->get('/pemeliharaan/keluhan')
            ->assertOk()
            ->assertInertia(function (AssertableInertia $halaman) use (&$id): void {
                $id = array_column($halaman->toArray()['props']['keluhan']['data'], 'Id');
            });

        return $id;
    }

    /** @return list<string> */
    private function gudangStokTerlihat(string $siapa): array
    {
        $gudang = [];

        $this->actingAs($this->orang[$siapa])
            ->get('/stok-suku-cadang')
            ->assertOk()
            ->assertInertia(function (AssertableInertia $halaman) use (&$gudang): void {
                $gudang = array_values(array_unique(array_column($halaman->toArray()['props']['stok']['data'], 'GudangId')));
            });

        return $gudang;
    }

    /** @param list<string> $unitPengelolaId */
    private function keluhanTerbuka(array $unitPengelolaId): float
    {
        $nilai = -1.0;

        $this->actingAs($this->orang['manajer'])
            ->get('/?'.http_build_query(['UnitPengelolaId' => $unitPengelolaId]))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $halaman) use (&$nilai): void {
                $nilai = (float) $halaman->toArray()['props']['metrik']['keluhan.terbuka']['Nilai'];
            });

        return $nilai;
    }

    private function semaiRumahSakit(): void
    {
        $this->organisasi = Organisasi::create(['Kode' => 'RS-DUA', 'Nama' => 'RS Dua Bagian', 'Status' => 'Aktif']);
        $this->tetapkanKonteks();

        foreach (['Keluhan' => 'KLH', 'PerintahKerja' => 'WO'] as $jenis => $awalan) {
            NomorDokumen::create([
                'JenisDokumen' => $jenis,
                'Awalan' => $awalan,
                'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
                'ResetPeriode' => 'Tahunan',
            ]);
        }

        $this->ipsrs = UnitOrganisasi::create(['Kode' => 'IPSRS', 'Nama' => 'IPSRS', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
        $this->it = UnitOrganisasi::create(['Kode' => 'IT', 'Nama' => 'Instalasi IT', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
        $unitIcu = UnitOrganisasi::create(['Kode' => 'ICU', 'Nama' => 'ICU', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $this->icu = Lokasi::create(['Kode' => 'R-ICU', 'Nama' => 'Ruang ICU', 'UnitOrganisasiId' => $unitIcu->Id, 'ZonaWaktu' => 'Asia/Jakarta', 'Status' => 'Aktif']);

        $kategoriAset = KategoriAset::create(['Kode' => 'KAT-UMUM', 'Nama' => 'Peralatan']);
        $this->printer = Aset::create([
            'KategoriAsetId' => $kategoriAset->Id, 'KodeAset' => 'AST-PRN-ICU', 'Nama' => 'Printer ICU',
            'UnitOrganisasiId' => $unitIcu->Id, 'LokasiId' => $this->icu->Id, 'UnitPengelolaId' => $this->it->Id,
            'Status' => StatusAset::Aktif->value,
        ]);
        $this->monitor = Aset::create([
            'KategoriAsetId' => $kategoriAset->Id, 'KodeAset' => 'AST-MON-ICU', 'Nama' => 'Monitor Pasien Bed 3',
            'UnitOrganisasiId' => $unitIcu->Id, 'LokasiId' => $this->icu->Id, 'UnitPengelolaId' => $this->ipsrs->Id,
            'Status' => StatusAset::Aktif->value,
        ]);

        $this->kategoriPrinter = KategoriKeluhan::create(['Kode' => 'KK-IT', 'Nama' => 'Komputer & Printer', 'UnitPengelolaId' => $this->it->Id, 'Aktif' => true]);
        $this->kategoriAlatMedis = KategoriKeluhan::create(['Kode' => 'KK-MED', 'Nama' => 'Alat Medis', 'UnitPengelolaId' => $this->ipsrs->Id, 'Aktif' => true]);

        $toner = SukuCadang::create([
            'Kode' => 'SC-TONER', 'Nama' => 'Toner Printer', 'SatuanDasar' => 'Pcs',
            'HargaRataRata' => 350000, 'StokMinimum' => 1, 'Status' => StatusSukuCadang::Aktif->value,
        ]);
        $this->gudangIt = Gudang::create(['Kode' => 'GDG-IT', 'Nama' => 'Gudang IT', 'UnitPengelolaId' => $this->it->Id, 'Status' => StatusGudang::Aktif->value]);
        $this->gudangIpsrs = Gudang::create(['Kode' => 'GDG-IPS', 'Nama' => 'Gudang IPSRS', 'UnitPengelolaId' => $this->ipsrs->Id, 'Status' => StatusGudang::Aktif->value]);
        foreach ([$this->gudangIt, $this->gudangIpsrs] as $gudang) {
            StokSukuCadang::create([
                'GudangId' => $gudang->Id, 'SukuCadangId' => $toner->Id,
                'JumlahTersedia' => 5, 'JumlahDipesan' => 0, 'JumlahDitahan' => 0,
            ]);
        }

        $koordinator = $this->peran('KOORDINATOR', ['Keluhan.Kelola', 'PerintahKerja.Kelola', 'Stok.Kelola']);
        $teknisi = $this->peran('TEKNISI', ['Aset.Lihat']);
        $pelapor = $this->peran('PELAPOR', []);
        $manajer = $this->peran('MANAJER', ['Keluhan.Kelola']);

        $this->orang = [
            'perawat' => $this->pengguna('Perawat ICU', $pelapor, lokasi: $this->icu),
            'koordinatorIt' => $this->pengguna('Koordinator IT', $koordinator, unit: $this->it),
            'teknisiIt' => $this->pengguna('Teknisi IT', $teknisi, unit: $this->it),
            'koordinatorIpsrs' => $this->pengguna('Koordinator IPSRS', $koordinator, unit: $this->ipsrs),
            'teknisiIpsrs' => $this->pengguna('Teknisi IPSRS', $teknisi, unit: $this->ipsrs),
            'manajer' => $this->pengguna('Manajer', $manajer),
        ];

        app(KonteksOrganisasi::class)->bersihkan();
    }

    /** @param list<string> $kodeIzin */
    private function peran(string $kode, array $kodeIzin): Peran
    {
        $peran = Peran::create(['Kode' => $kode, 'Nama' => ucfirst(strtolower($kode))]);

        foreach ($kodeIzin as $satu) {
            $izin = Izin::firstOrCreate(['Kode' => $satu], ['Nama' => $satu, 'Modul' => 'Uji']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        }

        return $peran;
    }

    private function pengguna(string $nama, Peran $peran, ?UnitOrganisasi $unit = null, ?Lokasi $lokasi = null): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => $nama,
            'Email' => strtolower(str_replace(' ', '.', $nama)).'@rs-dua.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        PenggunaPeran::create([
            'PenggunaId' => $pengguna->Id,
            'PeranId' => $peran->Id,
            'UnitOrganisasiId' => $unit?->Id,
            'LokasiId' => $lokasi?->Id,
        ]);

        return $pengguna;
    }

    private function tetapkanKonteks(): void
    {
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
    }
}
