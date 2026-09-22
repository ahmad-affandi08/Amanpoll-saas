<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

    public function test_joker_like_dari_pengguna_dinetralkan(): void
    {
        $this->buatSukuCadang('Baut', 'SC-001');
        $this->buatSukuCadang('Mur', 'SC-002');

        // Tanpa pelolosan, '%' mencocokkan semuanya dan pencarian tampak rusak.
        $this->assertSame(0, $this->daftar(['cari' => '%'])->halaman()->total());
        $this->assertSame(0, $this->daftar(['cari' => '_aut'])->halaman()->total());
        $this->assertSame(1, $this->daftar(['cari' => 'Baut'])->halaman()->total());
    }

    public function test_filter_berlaku_hanya_memuat_yang_benar_benar_dipakai(): void
    {
        $filter = $this->daftar([])->filterBerlaku();

        $this->assertArrayNotHasKey('cari', $filter);
        $this->assertArrayNotHasKey('KategoriSukuCadangId', $filter);
        $this->assertSame(['urut' => 'Nama', 'arah' => 'asc'], $filter);
    }
}
