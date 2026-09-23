<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DaftarTersaringTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
    }

    /**
     * @param  array<string, string>  $kueri
     */
    private function daftar(array $kueri): DaftarTersaring
    {
        return DaftarTersaring::untuk(
            Request::create('/suku-cadang', 'GET', $kueri),
            SukuCadang::query(),
        )
            ->cari(['Kode', 'Nama'])
            ->urut(['Nama', 'Status'], bawaan: 'Nama')
            ->faset(['KategoriSukuCadangId']);
    }

    private function buatSukuCadang(string $nama, string $kode, ?string $kategoriId = null): SukuCadang
    {
        return SukuCadang::create([
            'Kode' => $kode,
            'Nama' => $nama,
            'KategoriSukuCadangId' => $kategoriId,
            'SatuanDasar' => 'Pcs',
            'StokMinimum' => 0,
            'Status' => StatusSukuCadang::Aktif->value,
        ]);
    }

    public function test_pencarian_menemukan_baris_yang_jauh_di_belakang_halaman_pertama(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            $this->buatSukuCadang(sprintf('Baut %03d', $i), sprintf('SC-%03d', $i));
        }

        $this->buatSukuCadang('Zeta Paling Buntut', 'SC-999');

        $halaman = $this->daftar([])->halaman();

        // Tanpa pencarian, barisnya memang tidak ada di halaman pertama.
        $this->assertSame(61, $halaman->total());
        $this->assertNotContains('Zeta Paling Buntut', $halaman->getCollection()->pluck('Nama')->all());

        $hasil = $this->daftar(['cari' => 'Zeta'])->halaman();

        $this->assertSame(1, $hasil->total());
        $this->assertSame('Zeta Paling Buntut', $hasil->getCollection()->first()?->Nama);
    }

    public function test_pengurutan_bekerja_lintas_seluruh_data_bukan_hanya_satu_halaman(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            $this->buatSukuCadang(sprintf('Baut %03d', $i), sprintf('SC-%03d', $i));
        }

        $menaik = $this->daftar([])->halaman();
        $menurun = $this->daftar(['urut' => 'Nama', 'arah' => 'desc'])->halaman();

        $this->assertSame('Baut 001', $menaik->getCollection()->first()?->Nama);
        $this->assertSame('Baut 060', $menurun->getCollection()->first()?->Nama);
    }

    public function test_kunci_urut_di_luar_daftar_izin_diabaikan_bukan_diteruskan_ke_sql(): void
    {
        $this->buatSukuCadang('Beta', 'SC-002');
        $this->buatSukuCadang('Alfa', 'SC-001');

        $daftar = $this->daftar(['urut' => 'HargaRataRata', 'arah' => 'desc']);
        $halaman = $daftar->halaman();

        // Jatuh ke urutan bawaan, dan frontend diberi tahu urutan yang benar-benar dipakai.
        $this->assertSame('Alfa', $halaman->getCollection()->first()?->Nama);
        $this->assertSame('Nama', $daftar->filterBerlaku()['urut']);
        $this->assertSame('asc', $daftar->filterBerlaku()['arah']);
    }

    public function test_kunci_urut_berisi_sql_tidak_merusak_kueri(): void
    {
        $this->buatSukuCadang('Alfa', 'SC-001');

        $halaman = $this->daftar(['urut' => 'Nama; DROP TABLE SukuCadang'])->halaman();

        $this->assertSame(1, $halaman->total());
        $this->assertTrue(SukuCadang::query()->exists());
    }

    public function test_faset_menyaring_dan_dilaporkan_balik(): void
    {
        $kategori = KategoriSukuCadang::create(['Kode' => 'KSC-1', 'Nama' => 'Pelumas']);
        $this->buatSukuCadang('Oli Mesin', 'SC-001', $kategori->Id);
        $this->buatSukuCadang('Baut', 'SC-002');

        $daftar = $this->daftar(['KategoriSukuCadangId' => $kategori->Id]);
        $halaman = $daftar->halaman();

        $this->assertSame(1, $halaman->total());
        $this->assertSame('Oli Mesin', $halaman->getCollection()->first()?->Nama);
        $this->assertSame($kategori->Id, $daftar->filterBerlaku()['KategoriSukuCadangId']);
    }

    /**
     * Faset bernilai "0" benar-benar menyaring.
     *
     * `array_filter` tanpa callback membuang seluruh nilai falsy, dan '0'
     * termasuk di dalamnya. Akibatnya pilihan seperti "Nonaktif" (Aktif=0)
     * menyalakan chip penyaring di layar tetapi tidak memasang `whereIn` apa
     * pun: tabelnya menampilkan seluruh baris, dan berkas ekspornya -- yang
     * memakai kueri yang sama -- ikut salah tanpa gejala apa pun.
     */
    public function test_faset_bernilai_nol_tetap_menyaring(): void
    {
        $this->buatSukuCadang('Tanpa Stok Minimum', 'SC-NOL');
        $adaMinimum = $this->buatSukuCadang('Punya Stok Minimum', 'SC-LIMA');
        $adaMinimum->update(['StokMinimum' => 5]);

        $halaman = $this->daftarStokMinimum(['StokMinimum' => '0'])->halaman();

        $this->assertSame(1, $halaman->total());
        $this->assertSame('Tanpa Stok Minimum', $halaman->getCollection()->first()?->Nama);
    }

    /** Nilai "0" yang digabung dengan nilai lain juga tidak boleh hilang dari daftarnya. */
    public function test_faset_nol_tidak_hilang_saat_digabung_dengan_nilai_lain(): void
    {
        $this->buatSukuCadang('Tanpa Stok Minimum', 'SC-NOL');
        $lima = $this->buatSukuCadang('Stok Minimum Lima', 'SC-LIMA');
        $lima->update(['StokMinimum' => 5]);
        $sembilan = $this->buatSukuCadang('Stok Minimum Sembilan', 'SC-SEMBILAN');
        $sembilan->update(['StokMinimum' => 9]);

        $halaman = $this->daftarStokMinimum(['StokMinimum' => '5,0'])->halaman();

        $this->assertSame(2, $halaman->total());
        $this->assertEqualsCanonicalizing(
            ['Stok Minimum Lima', 'Tanpa Stok Minimum'],
            $halaman->getCollection()->pluck('Nama')->all(),
        );
    }

    /**
     * @param  array<string, string>  $kueri
     * @return DaftarTersaring<SukuCadang>
     */
    private function daftarStokMinimum(array $kueri): DaftarTersaring
    {
        return DaftarTersaring::untuk(
            Request::create('/suku-cadang', 'GET', $kueri),
            SukuCadang::query(),
        )
            ->urut(['Nama'], bawaan: 'Nama')
            ->faset(['StokMinimum']);
    }

    public function test_joker_like_dari_pengguna_dinetralkan(): void
    {
        $this->buatSukuCadang('Baut', 'SC-001');
        $this->buatSukuCadang('Mur', 'SC-002');

        // Tanpa pelolosan, '%' mencocokkan semuanya dan pencarian tampak rusak.
        $this->assertSame(0, $this->daftar(['cari' => '%'])->halaman()->total());
        $this->assertSame(0, $this->daftar(['cari' => '_aut'])->halaman()->total());
        $this->assertSame(1, $this->daftar(['cari' => 'Baut'])->halaman()->total());
    }

    /**
     * Paginasi menuntut urutan total yang pasti.
     *
     * Kalau kolom pengurut punya nilai kembar dan tidak ada pemutus seri, MySQL
     * boleh menukar posisi baris kembar antar permintaan. Akibatnya satu baris
     * muncul di dua halaman sementara baris lain tidak pernah muncul -- dan
     * pengguna tidak akan pernah tahu bahwa ada data yang tidak ia lihat.
     */
    public function test_seluruh_baris_muncul_tepat_sekali_walau_kunci_urutnya_kembar(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            // Nama sengaja dibuat kembar semua: hanya pemutus seri yang menyelamatkannya.
            $this->buatSukuCadang('Baut Seragam', sprintf('SC-%03d', $i));
        }

        $terkumpul = [];
        $sql = [];
        DB::listen(function (QueryExecuted $kueri) use (&$sql): void {
            $sql[] = $kueri->sql;
        });

        for ($halaman = 1; $halaman <= 3; $halaman++) {
            $hasil = $this->daftar(['page' => (string) $halaman])->halaman();
            foreach ($hasil->getCollection() as $baris) {
                $terkumpul[] = $baris->Id;
            }
        }

        $this->assertCount(60, $terkumpul);
        $this->assertCount(60, array_unique($terkumpul), 'Ada baris yang muncul di lebih dari satu halaman.');

        // Urutan yang dihasilkan MySQL untuk baris kembar tidak dijanjikan stabil, jadi
        // pemeriksaan di atas saja bisa lolos secara kebetulan. Yang benar-benar dijaga
        // adalah keberadaan pemutus serinya di dalam kueri.
        $pengurut = array_values(array_filter($sql, static fn (string $satu): bool => str_contains($satu, 'order by')));
        $this->assertNotEmpty($pengurut);
        foreach ($pengurut as $satu) {
            $this->assertStringContainsString('`SukuCadang`.`Id` asc', $satu);
        }
    }

    public function test_filter_berlaku_hanya_memuat_yang_benar_benar_dipakai(): void
    {
        $filter = $this->daftar([])->filterBerlaku();

        $this->assertArrayNotHasKey('cari', $filter);
        $this->assertArrayNotHasKey('KategoriSukuCadangId', $filter);
        $this->assertSame(['urut' => 'Nama', 'arah' => 'asc'], $filter);
    }
}
