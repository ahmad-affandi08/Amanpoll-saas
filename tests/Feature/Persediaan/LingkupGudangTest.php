<?php

declare(strict_types=1);

namespace Tests\Feature\Persediaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\PerencanaanPengadaan\Http\Requests\SimpanPenerimaanPembelianRequest;
use App\Domain\Persediaan\Domain\Enums\JenisMutasiStok;
use App\Domain\Persediaan\Domain\Enums\StatusMutasiStok;
use App\Domain\Persediaan\Domain\Enums\StatusReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\PemakaianSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Persediaan mengikuti lingkup gudangnya (PRD 8.21, TASK 40.02).
 *
 * Dua gudang dalam satu organisasi: milik IT dan milik IPSRS. Staf berlingkup IT
 * hanya melihat stok, mutasi, reservasi, dan pemakaian gudang IT, dan setiap
 * kiriman yang menyebut gudang IPSRS ditolak. Master suku cadang tetap bersama.
 */
class LingkupGudangTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private UnitOrganisasi $it;

    private UnitOrganisasi $ipsrs;

    private Gudang $gudangIt;

    private Gudang $gudangIpsrs;

    private SukuCadang $kabel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-LGD', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        $this->dalamOrganisasi(function (): void {
            $this->it = UnitOrganisasi::create(['Kode' => 'IT', 'Nama' => 'Instalasi IT', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
            $this->ipsrs = UnitOrganisasi::create(['Kode' => 'IPS', 'Nama' => 'IPSRS', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
            $this->gudangIt = Gudang::create(['Kode' => 'GDG-IT', 'Nama' => 'Gudang IT', 'Status' => 'Aktif', 'UnitPengelolaId' => $this->it->Id]);
            $this->gudangIpsrs = Gudang::create(['Kode' => 'GDG-IPS', 'Nama' => 'Gudang IPSRS', 'Status' => 'Aktif', 'UnitPengelolaId' => $this->ipsrs->Id]);
            $this->kabel = SukuCadang::create(['Kode' => 'SC-KBL', 'Nama' => 'Kabel LAN', 'SatuanDasar' => 'Meter', 'StokMinimum' => 0, 'Status' => 'Aktif']);
            StokSukuCadang::create(['GudangId' => $this->gudangIt->Id, 'SukuCadangId' => $this->kabel->Id, 'JumlahTersedia' => 10, 'JumlahDipesan' => 0, 'JumlahDitahan' => 0]);
            StokSukuCadang::create(['GudangId' => $this->gudangIpsrs->Id, 'SukuCadangId' => $this->kabel->Id, 'JumlahTersedia' => 90, 'JumlahDipesan' => 0, 'JumlahDitahan' => 0]);
            NomorDokumen::create([
                'JenisDokumen' => 'MutasiStok', 'Awalan' => 'MUT', 'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
                'NomorTerakhir' => 0, 'ResetPeriode' => 'Tahunan', 'PeriodeAktif' => '',
            ]);
        });
    }

    public function test_staf_it_hanya_melihat_stok_mutasi_dan_reservasi_gudang_it(): void
    {
        $this->semaiTransaksi();
        $stafIt = $this->buatPengguna($this->it);

        $this->actingAs($stafIt)->get('/stok-suku-cadang')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->has('stok.data', 1)
                ->where('stok.data.0.NamaGudang', 'Gudang IT')
                ->has('gudang', 1));
        $this->actingAs($stafIt)->get('/mutasi-stok')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->has('mutasiStok.data', 1)
                ->where('mutasiStok.data.0.NamaGudangAsal', 'Gudang IT'));
        $this->actingAs($stafIt)->get('/reservasi-suku-cadang')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->has('reservasi.data', 1)
                ->where('reservasi.data.0.NamaGudang', 'Gudang IT'));
    }

    public function test_ekspor_stok_mutasi_dan_reservasi_mengikuti_lingkup_gudang(): void
    {
        $this->semaiTransaksi();
        $stafIt = $this->buatPengguna($this->it);

        foreach (['/stok-suku-cadang/ekspor', '/mutasi-stok/ekspor', '/reservasi-suku-cadang/ekspor'] as $url) {
            $isi = $this->actingAs($stafIt)->get($url)->streamedContent();

            $this->assertStringContainsString('Gudang IT', $isi, $url);
            $this->assertStringNotContainsString('Gudang IPSRS', $isi, $url);
        }
    }

    /** Organisasi atau pengguna tanpa lingkup: persis seperti sebelumnya, seluruh gudang terlihat. */
    public function test_pengguna_tanpa_batas_melihat_seluruh_gudang(): void
    {
        $this->semaiTransaksi();
        $admin = $this->buatPengguna(null);

        $this->actingAs($admin)->get('/stok-suku-cadang')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->has('stok.data', 2)->has('gudang', 2));
        $this->actingAs($admin)->get('/mutasi-stok')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->has('mutasiStok.data', 2));
        $this->actingAs($admin)->get('/reservasi-suku-cadang')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->has('reservasi.data', 2));
    }

    public function test_detail_dan_daftar_suku_cadang_hanya_menghitung_gudang_terlihat(): void
    {
        $this->semaiTransaksi();
        $stafIt = $this->buatPengguna($this->it);

        $this->actingAs($stafIt)->get('/suku-cadang/'.$this->kabel->Id)
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->has('stok.baris', 1)
                ->where('stok.baris.0.Gudang', 'Gudang IT')
                ->where('stok.TotalTersedia', 10)
                ->where('pemakaian.total', 1)
                ->where('pemakaian.data.0.Gudang', 'Gudang IT')
                ->has('reservasi', 1)
                ->where('reservasi.0.Gudang', 'Gudang IT'));
        $this->actingAs($stafIt)->get('/suku-cadang')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                // Master tetap bersama; angkanya hanya gudang IT (10 tersedia, 1 ditahan).
                ->has('sukuCadang.data', 1)
                ->where('sukuCadang.data.0.JumlahTersediaBersih', 9));
    }

    public function test_mutasi_ke_gudang_ipsrs_ditolak_untuk_staf_it(): void
    {
        $stafIt = $this->buatPengguna($this->it);

        $this->actingAs($stafIt)->post('/mutasi-stok', [
            'Jenis' => JenisMutasiStok::Transfer->value,
            'GudangAsalId' => $this->gudangIt->Id,
            'GudangTujuanId' => $this->gudangIpsrs->Id,
        ])->assertSessionHasErrors(['GudangTujuanId' => 'Gudang yang dipilih tidak ditemukan atau di luar lingkup akses Anda.']);
        $this->actingAs($stafIt)->post('/mutasi-stok', [
            'Jenis' => JenisMutasiStok::Adjustment->value,
            'GudangAsalId' => $this->gudangIpsrs->Id,
            'Catatan' => 'Stok opname',
        ])->assertSessionHasErrors(['GudangAsalId' => 'Gudang yang dipilih tidak ditemukan atau di luar lingkup akses Anda.']);

        $this->assertSame(0, $this->dalamOrganisasi(fn () => MutasiStok::query()->count()));
    }

    public function test_mutasi_di_gudang_sendiri_tetap_diterima(): void
    {
        $stafIt = $this->buatPengguna($this->it);

        $this->actingAs($stafIt)->post('/mutasi-stok', [
            'Jenis' => JenisMutasiStok::Penerimaan->value,
            'GudangTujuanId' => $this->gudangIt->Id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame([$this->gudangIt->Id], $this->dalamOrganisasi(fn () => MutasiStok::query()->pluck('GudangTujuanId')->all()));
    }

    public function test_reservasi_di_gudang_ipsrs_ditolak_dan_stok_tidak_ditahan(): void
    {
        $stafIt = $this->buatPengguna($this->it);

        $this->actingAs($stafIt)->post('/reservasi-suku-cadang', [
            'GudangId' => $this->gudangIpsrs->Id,
            'SukuCadangId' => $this->kabel->Id,
            'Jumlah' => 5,
        ])->assertSessionHasErrors(['GudangId' => 'Gudang yang dipilih tidak ditemukan atau di luar lingkup akses Anda.']);

        $this->dalamOrganisasi(function (): void {
            $this->assertSame(0, ReservasiSukuCadang::query()->count());
            $this->assertEquals(0, StokSukuCadang::query()->where('GudangId', $this->gudangIpsrs->Id)->value('JumlahDitahan'));
        });
    }

    /** Jalur perintah kerja memakai SimpanReservasiSukuCadangRequest yang sama. */
    public function test_reservasi_dari_perintah_kerja_ke_gudang_ipsrs_ditolak(): void
    {
        $koordinatorIt = $this->buatPengguna($this->it, ['Stok.Kelola', 'PerintahKerja.Kelola']);
        $tiket = $this->dalamOrganisasi(fn () => PerintahKerja::create([
            'Nomor' => 'PK-LGD-1', 'Jenis' => 'Korektif', 'Judul' => 'Jaringan putus', 'UnitPengelolaId' => $this->it->Id,
        ]));

        $this->actingAs($koordinatorIt)->post("/pemeliharaan/perintah-kerja/{$tiket->Id}/reservasi-suku-cadang", [
            'GudangId' => $this->gudangIpsrs->Id, 'SukuCadangId' => $this->kabel->Id, 'Jumlah' => 1,
        ])->assertSessionHasErrors('GudangId');
        $this->actingAs($koordinatorIt)->post("/pemeliharaan/perintah-kerja/{$tiket->Id}/reservasi-suku-cadang", [
            'GudangId' => $this->gudangIt->Id, 'SukuCadangId' => $this->kabel->Id, 'Jumlah' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame([$this->gudangIt->Id], $this->dalamOrganisasi(fn () => ReservasiSukuCadang::query()->pluck('GudangId')->all()));
    }

    /** Pilihan stok di halaman perintah kerja sama dengan yang diterima GudangTerlihat saat reservasi. */
    public function test_pilihan_stok_di_perintah_kerja_hanya_dari_gudang_terlihat(): void
    {
        $koordinatorIt = $this->buatPengguna($this->it, ['Stok.Kelola', 'PerintahKerja.Kelola']);
        $tiket = $this->dalamOrganisasi(fn () => PerintahKerja::create([
            'Nomor' => 'PK-LGD-2', 'Jenis' => 'Korektif', 'Judul' => 'Jaringan putus', 'UnitPengelolaId' => $this->it->Id,
        ]));

        $this->actingAs($koordinatorIt)->get('/pemeliharaan/perintah-kerja/'.$tiket->Id)
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->has('stok', 1)
                ->where('stok.0.NamaGudang', 'Gudang IT')
                ->has('gudang', 1));
    }

    public function test_mutasi_dan_reservasi_gudang_ipsrs_tidak_bisa_dibuka_atau_diubah_staf_it(): void
    {
        [$mutasiIpsrs, $reservasiIpsrs] = $this->semaiTransaksi();
        $stafIt = $this->buatPengguna($this->it);

        $this->actingAs($stafIt)->get('/mutasi-stok/'.$mutasiIpsrs->Id)->assertForbidden();
        $this->actingAs($stafIt)->post("/mutasi-stok/{$mutasiIpsrs->Id}/batalkan")->assertForbidden();
        $this->actingAs($stafIt)->post("/reservasi-suku-cadang/{$reservasiIpsrs->Id}/lepaskan")->assertForbidden();

        $this->dalamOrganisasi(function () use ($mutasiIpsrs, $reservasiIpsrs): void {
            $this->assertSame(StatusMutasiStok::Draft->value, MutasiStok::query()->whereKey($mutasiIpsrs->Id)->value('Status'));
            $this->assertSame(StatusReservasiSukuCadang::Aktif->value, ReservasiSukuCadang::query()->whereKey($reservasiIpsrs->Id)->value('Status'));
        });
    }

    /** Transfer lintas bagian (dibuat admin) terbaca oleh staf IT, tetapi memostingnya menggerakkan stok IPSRS. */
    public function test_transfer_lintas_bagian_terbaca_tetapi_tidak_bisa_diposting_staf_it(): void
    {
        $transfer = $this->dalamOrganisasi(fn () => MutasiStok::create([
            'Nomor' => 'MUT-X', 'Jenis' => JenisMutasiStok::Transfer->value, 'Status' => StatusMutasiStok::Draft->value,
            'GudangAsalId' => $this->gudangIpsrs->Id, 'GudangTujuanId' => $this->gudangIt->Id, 'Tanggal' => now(),
        ]));
        $stafIt = $this->buatPengguna($this->it);

        $this->actingAs($stafIt)->get('/mutasi-stok/'.$transfer->Id)
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('mutasiStok.NamaGudangAsal', 'Gudang IPSRS')
                ->where('mutasiStok.NamaGudangTujuan', 'Gudang IT'));
        $this->actingAs($stafIt)->post("/mutasi-stok/{$transfer->Id}/posting")->assertForbidden();

        $this->assertSame(StatusMutasiStok::Draft->value, $this->dalamOrganisasi(fn () => MutasiStok::query()->whereKey($transfer->Id)->value('Status')));
    }

    public function test_detail_mutasi_menolak_rak_milik_gudang_lain(): void
    {
        $stafIt = $this->buatPengguna($this->it);
        [$mutasi, $rakIpsrs, $rakIt] = $this->dalamOrganisasi(fn (): array => [
            MutasiStok::create([
                'Nomor' => 'MUT-1', 'Jenis' => JenisMutasiStok::Penerimaan->value, 'Status' => StatusMutasiStok::Draft->value,
                'GudangTujuanId' => $this->gudangIt->Id, 'Tanggal' => now(),
            ]),
            LokasiGudang::create(['GudangId' => $this->gudangIpsrs->Id, 'Kode' => 'RAK-IPS', 'Nama' => 'Rak IPSRS']),
            LokasiGudang::create(['GudangId' => $this->gudangIt->Id, 'Kode' => 'RAK-IT', 'Nama' => 'Rak IT']),
        ]);

        $this->actingAs($stafIt)->post("/mutasi-stok/{$mutasi->Id}/detail", [
            'SukuCadangId' => $this->kabel->Id, 'Jumlah' => 1, 'LokasiGudangTujuanId' => $rakIpsrs->Id,
        ])->assertSessionHasErrors('LokasiGudangTujuanId');
        $this->actingAs($stafIt)->post("/mutasi-stok/{$mutasi->Id}/detail", [
            'SukuCadangId' => $this->kabel->Id, 'Jumlah' => 1, 'LokasiGudangTujuanId' => $rakIt->Id,
        ])->assertSessionHasNoErrors();

        $this->assertSame([$rakIt->Id], $this->dalamOrganisasi(fn () => $mutasi->detailMutasiStok()->pluck('LokasiGudangTujuanId')->all()));
    }

    /** Penerimaan pembelian memakai aturan yang sama; PO lengkap tidak dibutuhkan untuk membuktikan aturannya terpasang. */
    public function test_penerimaan_pembelian_ke_gudang_ipsrs_ditolak(): void
    {
        $this->actingAs($this->buatPengguna($this->it));
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        $galat = Validator::make(
            ['GudangId' => $this->gudangIpsrs->Id],
            (new SimpanPenerimaanPembelianRequest)->rules(),
        )->errors();
        $galatSendiri = Validator::make(
            ['GudangId' => $this->gudangIt->Id],
            (new SimpanPenerimaanPembelianRequest)->rules(),
        )->errors();

        $this->assertSame(['Gudang yang dipilih tidak ditemukan atau di luar lingkup akses Anda.'], $galat->get('GudangId'));
        $this->assertSame([], $galatSendiri->get('GudangId'));
    }

    public function test_gudang_menyimpan_menyaring_dan_menampilkan_unit_pengelola(): void
    {
        $admin = $this->buatPengguna(null);
        $this->dalamOrganisasi(fn () => Gudang::create(['Kode' => 'GDG-UM', 'Nama' => 'Gudang Umum', 'Status' => 'Aktif']));

        $this->actingAs($admin)->post('/gudang', ['Nama' => 'Gudang IT Lantai 2', 'Status' => 'Aktif', 'UnitPengelolaId' => $this->it->Id])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->it->Id, $this->dalamOrganisasi(fn () => Gudang::query()->where('Nama', 'Gudang IT Lantai 2')->value('UnitPengelolaId')));
        $this->actingAs($admin)->get('/gudang?UnitPengelolaId='.$this->it->Id)
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('unitPengelolaDipakai', true)
                ->has('penyaringUnitPengelola', 2)
                ->has('gudang.data', 2)
                ->where('gudang.data.0.NamaUnitPengelola', 'Instalasi IT'));
        $this->actingAs($admin)->get('/gudang?UnitPengelolaId=tanpa,'.$this->ipsrs->Id)
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->has('gudang.data', 2)
                ->where('gudang.data.0.Nama', 'Gudang IPSRS')
                ->where('gudang.data.1.Nama', 'Gudang Umum'));
        $this->actingAs($admin)->get('/gudang/'.$this->gudangIt->Id)
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('gudang.NamaUnitPengelola', 'Instalasi IT'));
        $this->assertStringContainsString('Unit Pengelola', $this->actingAs($admin)->get('/gudang/ekspor')->streamedContent());
    }

    public function test_gudang_menolak_unit_yang_bukan_unit_pengelola(): void
    {
        $admin = $this->buatPengguna(null);
        $icu = $this->dalamOrganisasi(fn () => UnitOrganisasi::create(['Kode' => 'ICU', 'Nama' => 'ICU', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']));

        $this->actingAs($admin)->put('/gudang/'.$this->gudangIt->Id, ['Nama' => 'Gudang IT', 'Status' => 'Aktif', 'UnitPengelolaId' => $icu->Id])
            ->assertSessionHasErrors(['UnitPengelolaId' => 'Unit yang dipilih belum ditandai Mengelola Aset di halaman Unit Organisasi.']);

        $this->assertSame($this->it->Id, $this->dalamOrganisasi(fn () => Gudang::query()->whereKey($this->gudangIt->Id)->value('UnitPengelolaId')));
    }

    public function test_organisasi_tanpa_unit_pengelola_tidak_melihat_isian_maupun_kolom_ekspor_gudang(): void
    {
        $this->dalamOrganisasi(function (): void {
            Gudang::query()->update(['UnitPengelolaId' => null]);
            UnitOrganisasi::query()->update(['MengelolaAset' => false]);
        });
        $admin = $this->buatPengguna(null);

        $this->actingAs($admin)->get('/gudang')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('unitPengelolaDipakai', false)
                ->where('pilihanUnitPengelola', [])
                ->has('gudang.data', 2));

        $this->assertStringNotContainsString('Unit Pengelola', $this->actingAs($admin)->get('/gudang/ekspor')->streamedContent());
    }

    /**
     * Mutasi, reservasi, dan pemakaian satu-satu di gudang IT dan IPSRS.
     *
     * @return array{0: MutasiStok, 1: ReservasiSukuCadang}
     */
    private function semaiTransaksi(): array
    {
        return $this->dalamOrganisasi(function (): array {
            $hasil = [];

            foreach ([$this->gudangIt, $this->gudangIpsrs] as $urutan => $gudang) {
                $mutasi = MutasiStok::create([
                    'Nomor' => 'MUT-'.$urutan, 'Jenis' => JenisMutasiStok::Adjustment->value, 'Status' => StatusMutasiStok::Draft->value,
                    'GudangAsalId' => $gudang->Id, 'Tanggal' => now(), 'Catatan' => 'Opname',
                ]);
                $reservasi = ReservasiSukuCadang::create([
                    'GudangId' => $gudang->Id, 'SukuCadangId' => $this->kabel->Id, 'Jumlah' => 1, 'Status' => StatusReservasiSukuCadang::Aktif->value,
                ]);
                StokSukuCadang::query()->where('GudangId', $gudang->Id)->update(['JumlahDitahan' => 1]);
                $tiket = PerintahKerja::create(['Nomor' => 'PK-'.$urutan, 'Jenis' => 'Korektif', 'Judul' => 'Tiket '.$urutan]);
                PemakaianSukuCadang::create([
                    'PerintahKerjaId' => $tiket->Id, 'SukuCadangId' => $this->kabel->Id, 'GudangId' => $gudang->Id,
                    'Jumlah' => 1, 'DipakaiPada' => now(),
                ]);
                $hasil = [$mutasi, $reservasi];
            }

            return $hasil;
        });
    }

    /** @param  list<string>  $kodeIzin */
    private function buatPengguna(?UnitOrganisasi $lingkupUnit, array $kodeIzin = ['Stok.Kelola']): Pengguna
    {
        return $this->dalamOrganisasi(function () use ($lingkupUnit, $kodeIzin): Pengguna {
            $pengguna = Pengguna::create([
                'OrganisasiId' => $this->organisasi->Id,
                'Nama' => 'Pengguna '.uniqid(),
                'Email' => 'gudang+'.uniqid().'@amanpoll.test',
                'KataSandi' => 'rahasia',
                'Status' => 'Aktif',
            ]);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);

            foreach ($kodeIzin as $kode) {
                $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            }

            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id, 'UnitOrganisasiId' => $lingkupUnit?->Id]);

            return $pengguna;
        });
    }

    /**
     * Tanpa ScopeLingkup: pembacaan test tidak boleh ikut tersaring pengguna yang masih masuk.
     *
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
            return app('auth')->guard('web')->check()
                ? $this->tanpaPengguna($aksi)
                : $aksi();
        } finally {
            $konteks->bersihkan();
        }
    }

    /**
     * @template T
     *
     * @param  callable(): T  $aksi
     * @return T
     */
    private function tanpaPengguna(callable $aksi): mixed
    {
        $guard = app('auth')->guard('web');
        $pengguna = $guard->user();
        $guard->forgetUser();

        try {
            return $aksi();
        } finally {
            if ($pengguna !== null) {
                $guard->setUser($pengguna);
            }
        }
    }
}
