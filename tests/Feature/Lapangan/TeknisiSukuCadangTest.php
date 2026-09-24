<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Persediaan\Domain\Enums\StatusGudang;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\PemakaianSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use Inertia\Testing\AssertableInertia;

/**
 * "Minta suku cadang" teknisi (DESIGN §36.6 layar 09, PRD 8.20): stok bersih per gudang
 * dibaca lewat tiket yang ditugaskan (`operate`), permintaannya reservasi di endpoint
 * dasbor, dan stok tersedia tidak pernah berubah karena permintaan.
 */
final class TeknisiSukuCadangTest extends KasusTeknisi
{
    public function test_cari_suku_cadang_menampilkan_stok_bersih_per_gudang_untuk_tiket_sendiri(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $tiket = $this->buatTiket($teknisi, StatusPerintahKerja::Dikerjakan);
        [$kontaktor] = $this->stokKontaktor();

        $this->actingAs($teknisi)
            ->getJson("/lapangan/teknisi/tugas/{$tiket->Id}/suku-cadang?cari=kontaktor")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.Id', $kontaktor->Id)
            ->assertJsonPath('data.0.Stok.0.NamaGudang', 'Gudang Utama')
            ->assertJsonPath('data.0.Stok.0.TersediaBersih', 3)
            ->assertJsonPath('data.0.Stok.1.NamaGudang', 'Menara A B1')
            ->assertJsonPath('data.0.Stok.1.TersediaBersih', 0);
    }

    public function test_kata_kunci_berjoker_tidak_mencocokkan_semua_suku_cadang(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $tiket = $this->buatTiket($teknisi, StatusPerintahKerja::Dikerjakan);
        $this->stokKontaktor();

        $this->actingAs($teknisi)
            ->getJson("/lapangan/teknisi/tugas/{$tiket->Id}/suku-cadang?cari=%25")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_cari_suku_cadang_ditolak_untuk_tiket_teknisi_lain_dan_tenant_lain(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $milikRekan = $this->buatTiket($this->penggunaDenganPeran(['TEKNISI']), StatusPerintahKerja::Dikerjakan);
        $tenantLain = $this->tiketOrganisasiLain();

        $this->actingAs($teknisi)->getJson("/lapangan/teknisi/tugas/{$milikRekan->Id}/suku-cadang")->assertForbidden();
        $this->actingAs($teknisi)->getJson("/lapangan/teknisi/tugas/{$tenantLain->Id}/suku-cadang")->assertNotFound();
    }

    public function test_permintaan_dari_lapangan_hanya_menahan_stok_dan_tampil_di_layar_kerja(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $tiket = $this->buatTiket($teknisi, StatusPerintahKerja::Dikerjakan);
        [$kontaktor, $gudang, $stok] = $this->stokKontaktor();

        // Persis seperti lembar "Minta suku cadang": JSON ke endpoint reservasi dasbor.
        $this->actingAs($teknisi)
            ->postJson("/pemeliharaan/perintah-kerja/{$tiket->Id}/reservasi-suku-cadang", [
                'GudangId' => $gudang->Id, 'SukuCadangId' => $kontaktor->Id, 'Jumlah' => 2,
            ])
            ->assertRedirect();

        $this->dalamOrganisasi(function () use ($stok, $tiket): void {
            $segar = $stok->fresh();
            $this->assertEquals(3, $segar?->JumlahTersedia, 'Permintaan teknisi tidak boleh mengurangi stok tersedia.');
            $this->assertEquals(2, $segar?->JumlahDitahan);
            $this->assertSame(0, MutasiStok::query()->count());
            $this->assertSame(0, PemakaianSukuCadang::query()->where('PerintahKerjaId', $tiket->Id)->count());
        });

        $this->actingAs($teknisi)->get("/lapangan/teknisi/tugas/{$tiket->Id}/kerjakan")
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->has('permintaanSukuCadang', 1)
                ->where('permintaanSukuCadang.0.Status', 'Aktif')
                ->where('permintaanSukuCadang.0.NamaGudang', 'Gudang Utama'));
        $this->actingAs($teknisi)->get('/lapangan/teknisi/suku-cadang')
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->component('Lapangan/Teknisi/SukuCadang')
                ->has('permintaan', 1)
                ->where('permintaan.0.PerintahKerjaId', $tiket->Id));
    }

    public function test_daftar_permintaan_tidak_memuat_permintaan_tiket_teknisi_lain(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $rekan = $this->penggunaDenganPeran(['TEKNISI']);
        $tiketRekan = $this->buatTiket($rekan, StatusPerintahKerja::Dikerjakan);
        [$kontaktor, $gudang] = $this->stokKontaktor();

        $this->actingAs($rekan)->postJson("/pemeliharaan/perintah-kerja/{$tiketRekan->Id}/reservasi-suku-cadang", [
            'GudangId' => $gudang->Id, 'SukuCadangId' => $kontaktor->Id, 'Jumlah' => 1,
        ])->assertRedirect();

        $this->actingAs($teknisi)->get('/lapangan/teknisi/suku-cadang')
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia->has('permintaan', 0));
    }

    /** @return array{0: SukuCadang, 1: Gudang, 2: StokSukuCadang} */
    private function stokKontaktor(): array
    {
        return $this->dalamOrganisasi(function (): array {
            $utama = Gudang::create(['Kode' => 'GDG-U-'.uniqid(), 'Nama' => 'Gudang Utama', 'Status' => StatusGudang::Aktif->value]);
            $menara = Gudang::create(['Kode' => 'GDG-M-'.uniqid(), 'Nama' => 'Menara A B1', 'Status' => StatusGudang::Aktif->value]);
            $kontaktor = SukuCadang::create([
                'Kode' => 'SC-'.uniqid(), 'Nama' => 'Kontaktor Schneider LC1D32', 'SatuanDasar' => 'Buah',
                'HargaRataRata' => 850000, 'StokMinimum' => 1, 'Status' => StatusSukuCadang::Aktif->value,
            ]);
            SukuCadang::create([
                'Kode' => 'SC-'.uniqid(), 'Nama' => 'Filter oli', 'SatuanDasar' => 'Buah',
                'HargaRataRata' => 90000, 'StokMinimum' => 1, 'Status' => StatusSukuCadang::Aktif->value,
            ]);
            $stok = StokSukuCadang::create(['GudangId' => $utama->Id, 'SukuCadangId' => $kontaktor->Id, 'JumlahTersedia' => 3, 'JumlahDipesan' => 0, 'JumlahDitahan' => 0]);
            StokSukuCadang::create(['GudangId' => $menara->Id, 'SukuCadangId' => $kontaktor->Id, 'JumlahTersedia' => 0, 'JumlahDipesan' => 0, 'JumlahDitahan' => 0]);

            return [$kontaktor, $utama, $stok];
        });
    }
}
