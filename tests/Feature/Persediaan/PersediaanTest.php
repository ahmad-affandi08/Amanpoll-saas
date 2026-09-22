<?php

declare(strict_types=1);

namespace Tests\Feature\Persediaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Persediaan\Domain\Enums\JenisMutasiStok;
use App\Domain\Persediaan\Domain\Enums\StatusGudang;
use App\Domain\Persediaan\Domain\Enums\StatusMutasiStok;
use App\Domain\Persediaan\Domain\Enums\StatusReservasiSukuCadang;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersediaanTest extends TestCase
{
    use RefreshDatabase;

    private function konteks(): KonteksOrganisasi
    {
        return app(KonteksOrganisasi::class);
    }

    /**
     * @param  list<string>  $kodeIzin
     */
    private function buatPengguna(Organisasi $organisasi, array $kodeIzin = []): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== []) {
            $this->konteks()->tetapkan($organisasi->Id);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            foreach ($kodeIzin as $kode) {
                $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            }
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
        }

        return $pengguna;
    }

    private function buatNomorDokumenMutasiStok(Organisasi $organisasi): void
    {
        $this->konteks()->tetapkan($organisasi->Id);

        NomorDokumen::create([
            'JenisDokumen' => 'MutasiStok',
            'Awalan' => 'MUT',
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'NomorTerakhir' => 0,
            'ResetPeriode' => 'Tahunan',
            'PeriodeAktif' => '',
        ]);
    }

    private function buatGudang(Organisasi $organisasi, array $atribut = []): Gudang
    {
        $this->konteks()->tetapkan($organisasi->Id);

        return Gudang::create(array_merge([
            'Kode' => 'GDG-'.uniqid(),
            'Nama' => 'Gudang '.uniqid(),
            'Status' => StatusGudang::Aktif->value,
        ], $atribut));
    }

    private function buatSukuCadang(Organisasi $organisasi, array $atribut = []): SukuCadang
    {
        $this->konteks()->tetapkan($organisasi->Id);

        return SukuCadang::create(array_merge([
            'Kode' => 'SC-'.uniqid(),
            'Nama' => 'Suku Cadang '.uniqid(),
            'SatuanDasar' => 'Pcs',
            'StokMinimum' => 0,
            'Status' => StatusSukuCadang::Aktif->value,
        ], $atribut));
    }

    public function test_gudang_crud_dan_lokasi_gudang_hierarki_sirkular_ditolak(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Stok.Kelola']);
        $this->konteks()->tetapkan($organisasi->Id);

        $this->actingAs($pengguna)->post('/gudang', ['Kode' => 'GDG-01', 'Nama' => 'Gudang Utama', 'Status' => StatusGudang::Aktif->value])->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $gudang = Gudang::query()->firstOrFail();

        $this->actingAs($pengguna)->post("/gudang/{$gudang->Id}/lokasi", ['Kode' => 'RAK-A', 'Nama' => 'Rak A'])->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiA = LokasiGudang::query()->firstOrFail();

        $this->actingAs($pengguna)->post("/gudang/{$gudang->Id}/lokasi", ['Kode' => 'RAK-B', 'Nama' => 'Rak B', 'IndukId' => $lokasiA->Id])->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiB = LokasiGudang::query()->where('Kode', 'RAK-B')->firstOrFail();

        // Menjadikan A sebagai anak dari B (padahal B anak A) harus ditolak sebagai siklus.
        $this->actingAs($pengguna)->put("/lokasi-gudang/{$lokasiA->Id}", [
            'GudangId' => $gudang->Id, 'Kode' => 'RAK-A', 'Nama' => 'Rak A', 'IndukId' => $lokasiB->Id,
        ])->assertStatus(422);

        // Gudang tidak bisa dihapus selagi masih punya lokasi.
        $this->actingAs($pengguna)->delete("/gudang/{$gudang->Id}")->assertStatus(422);
    }

    public function test_kategori_dan_suku_cadang_crud(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Stok.Kelola']);
        $this->konteks()->tetapkan($organisasi->Id);

        $this->actingAs($pengguna)->post('/kategori-suku-cadang', ['Kode' => 'KAT-01', 'Nama' => 'Filter'])->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $kategori = KategoriSukuCadang::query()->firstOrFail();

        $this->actingAs($pengguna)->post('/suku-cadang', [
            'Kode' => 'SC-01', 'Nama' => 'Filter Oli', 'KategoriSukuCadangId' => $kategori->Id,
            'SatuanDasar' => 'Pcs', 'StokMinimum' => 5, 'Status' => StatusSukuCadang::Aktif->value,
        ])->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $sukuCadang = SukuCadang::query()->firstOrFail();
        $this->assertSame('Filter Oli', $sukuCadang->Nama);

        // Kategori tidak bisa dihapus selagi dipakai suku cadang.
        $this->actingAs($pengguna)->delete("/kategori-suku-cadang/{$kategori->Id}")->assertStatus(422);
    }

    public function test_kompatibilitas_suku_cadang_dedupe_dan_lookup_untuk_aset(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Stok.Kelola', 'Aset.Lihat']);
        $this->konteks()->tetapkan($organisasi->Id);
        $kategoriAset = KategoriAset::create(['Nama' => 'Pompa', 'Kode' => 'KAT-'.uniqid()]);
        $aset = Aset::create([
            'KategoriAsetId' => $kategoriAset->Id, 'KodeAset' => 'AST-'.uniqid(), 'Nama' => 'Pompa 1',
            'Status' => StatusAset::Aktif->value, 'Kondisi' => KondisiAset::Baik->value, 'TingkatKritis' => TingkatKritisAset::Normal->value,
        ]);
        $sukuCadang = $this->buatSukuCadang($organisasi);

        $this->actingAs($pengguna)->post('/kompatibilitas-suku-cadang', [
            'SukuCadangId' => $sukuCadang->Id, 'AsetId' => $aset->Id,
        ])->assertRedirect();

        // Duplikat kombinasi yang sama ditolak.
        $this->actingAs($pengguna)->post('/kompatibilitas-suku-cadang', [
            'SukuCadangId' => $sukuCadang->Id, 'AsetId' => $aset->Id,
        ])->assertStatus(409);

        $response = $this->actingAs($pengguna)->get("/aset/{$aset->Id}/suku-cadang-kompatibel");
        $response->assertOk();
        $response->assertJsonFragment(['Id' => $sukuCadang->Id]);
    }

    public function test_mutasi_stok_penerimaan_menaikkan_stok(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Stok.Kelola']);
        $this->buatNomorDokumenMutasiStok($organisasi);
        $gudang = $this->buatGudang($organisasi);
        $sukuCadang = $this->buatSukuCadang($organisasi);

        $this->actingAs($pengguna)->post('/mutasi-stok', [
            'Jenis' => JenisMutasiStok::Penerimaan->value, 'GudangTujuanId' => $gudang->Id,
        ])->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $mutasi = MutasiStok::query()->firstOrFail();
        $this->assertSame(StatusMutasiStok::Draft->value, $mutasi->Status);

        // Posting tanpa detail ditolak.
        $this->actingAs($pengguna)->post("/mutasi-stok/{$mutasi->Id}/posting")->assertStatus(422);

        $this->actingAs($pengguna)->post("/mutasi-stok/{$mutasi->Id}/detail", [
            'SukuCadangId' => $sukuCadang->Id, 'Jumlah' => 10,
        ])->assertRedirect();

        $this->actingAs($pengguna)->post("/mutasi-stok/{$mutasi->Id}/posting")->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $mutasi->refresh();
        $this->assertSame(StatusMutasiStok::Diposting->value, $mutasi->Status);

        $stok = StokSukuCadang::query()->where('GudangId', $gudang->Id)->where('SukuCadangId', $sukuCadang->Id)->firstOrFail();
        $this->assertSame('10.0000', $stok->JumlahTersedia);

        // Idempotent: posting ulang tidak menggandakan efek.
        $this->actingAs($pengguna)->post("/mutasi-stok/{$mutasi->Id}/posting")->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $stok->refresh();
        $this->assertSame('10.0000', $stok->JumlahTersedia);
    }

    public function test_mutasi_stok_pengeluaran_ditolak_saat_stok_tidak_cukup_kecuali_override(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Stok.Kelola']);
        $penggunaOverride = $this->buatPengguna($organisasi, ['Stok.Kelola', 'Stok.Override']);
        $this->buatNomorDokumenMutasiStok($organisasi);
        $gudang = $this->buatGudang($organisasi);
        $sukuCadang = $this->buatSukuCadang($organisasi);

        $this->actingAs($pengguna)->post('/mutasi-stok', ['Jenis' => JenisMutasiStok::Pengeluaran->value, 'GudangAsalId' => $gudang->Id])->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $mutasi = MutasiStok::query()->firstOrFail();
        $this->actingAs($pengguna)->post("/mutasi-stok/{$mutasi->Id}/detail", ['SukuCadangId' => $sukuCadang->Id, 'Jumlah' => 5])->assertRedirect();

        // Tanpa stok sama sekali dan tanpa override, ditolak.
        $this->actingAs($pengguna)->post("/mutasi-stok/{$mutasi->Id}/posting")->assertStatus(422);
        $this->konteks()->tetapkan($organisasi->Id);
        $mutasi->refresh();
        $this->assertSame(StatusMutasiStok::Draft->value, $mutasi->Status);

        // Aktifkan override di level organisasi + pengguna dengan izin Stok.Override.
        $this->konteks()->tetapkan($organisasi->Id);
        KonfigurasiOrganisasi::create(['Kunci' => 'Persediaan.IzinkanStokNegatif', 'Nilai' => ['aktif' => true]]);

        $this->actingAs($penggunaOverride)->post("/mutasi-stok/{$mutasi->Id}/posting")->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $mutasi->refresh();
        $this->assertSame(StatusMutasiStok::Diposting->value, $mutasi->Status);

        $stok = StokSukuCadang::query()->where('GudangId', $gudang->Id)->where('SukuCadangId', $sukuCadang->Id)->firstOrFail();
        $this->assertSame('-5.0000', $stok->JumlahTersedia);
    }

    public function test_mutasi_stok_transfer_memindahkan_saldo_antar_gudang(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Stok.Kelola']);
        $this->buatNomorDokumenMutasiStok($organisasi);
        $gudangAsal = $this->buatGudang($organisasi, ['Kode' => 'GDG-A-'.uniqid()]);
        $gudangTujuan = $this->buatGudang($organisasi, ['Kode' => 'GDG-B-'.uniqid()]);
        $sukuCadang = $this->buatSukuCadang($organisasi);

        // Isi stok awal di gudang asal lewat Penerimaan.
        $this->actingAs($pengguna)->post('/mutasi-stok', ['Jenis' => JenisMutasiStok::Penerimaan->value, 'GudangTujuanId' => $gudangAsal->Id]);
        $this->konteks()->tetapkan($organisasi->Id);
        $penerimaan = MutasiStok::query()->where('Jenis', JenisMutasiStok::Penerimaan->value)->firstOrFail();
        $this->actingAs($pengguna)->post("/mutasi-stok/{$penerimaan->Id}/detail", ['SukuCadangId' => $sukuCadang->Id, 'Jumlah' => 20]);
        $this->actingAs($pengguna)->post("/mutasi-stok/{$penerimaan->Id}/posting");

        // Transfer 8 unit ke gudang tujuan.
        $this->actingAs($pengguna)->post('/mutasi-stok', [
            'Jenis' => JenisMutasiStok::Transfer->value, 'GudangAsalId' => $gudangAsal->Id, 'GudangTujuanId' => $gudangTujuan->Id,
        ]);
        $this->konteks()->tetapkan($organisasi->Id);
        $transfer = MutasiStok::query()->where('Jenis', JenisMutasiStok::Transfer->value)->firstOrFail();
        $this->actingAs($pengguna)->post("/mutasi-stok/{$transfer->Id}/detail", ['SukuCadangId' => $sukuCadang->Id, 'Jumlah' => 8]);
        $this->actingAs($pengguna)->post("/mutasi-stok/{$transfer->Id}/posting")->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $stokAsal = StokSukuCadang::query()->where('GudangId', $gudangAsal->Id)->where('SukuCadangId', $sukuCadang->Id)->firstOrFail();
        $stokTujuan = StokSukuCadang::query()->where('GudangId', $gudangTujuan->Id)->where('SukuCadangId', $sukuCadang->Id)->firstOrFail();
        $this->assertSame('12.0000', $stokAsal->JumlahTersedia);
        $this->assertSame('8.0000', $stokTujuan->JumlahTersedia);
    }

    public function test_mutasi_stok_adjustment_membutuhkan_catatan_alasan(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Stok.Kelola']);
        $this->buatNomorDokumenMutasiStok($organisasi);
        $gudang = $this->buatGudang($organisasi);

        $this->actingAs($pengguna)->post('/mutasi-stok', [
            'Jenis' => JenisMutasiStok::Adjustment->value, 'GudangAsalId' => $gudang->Id,
        ])->assertStatus(422);

        $this->actingAs($pengguna)->post('/mutasi-stok', [
            'Jenis' => JenisMutasiStok::Adjustment->value, 'GudangAsalId' => $gudang->Id, 'Catatan' => 'Stok opname: selisih ditemukan.',
        ])->assertRedirect();
    }

    public function test_reservasi_cegah_over_reservation_dan_konsumsi_mengurangi_stok_fisik(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Stok.Kelola']);
        $this->buatNomorDokumenMutasiStok($organisasi);
        $gudang = $this->buatGudang($organisasi);
        $sukuCadang = $this->buatSukuCadang($organisasi);

        $this->actingAs($pengguna)->post('/mutasi-stok', ['Jenis' => JenisMutasiStok::Penerimaan->value, 'GudangTujuanId' => $gudang->Id]);
        $this->konteks()->tetapkan($organisasi->Id);
        $penerimaan = MutasiStok::query()->firstOrFail();
        $this->actingAs($pengguna)->post("/mutasi-stok/{$penerimaan->Id}/detail", ['SukuCadangId' => $sukuCadang->Id, 'Jumlah' => 10]);
        $this->actingAs($pengguna)->post("/mutasi-stok/{$penerimaan->Id}/posting");

        // Reservasi melebihi stok tersedia ditolak.
        $this->actingAs($pengguna)->post('/reservasi-suku-cadang', [
            'GudangId' => $gudang->Id, 'SukuCadangId' => $sukuCadang->Id, 'Jumlah' => 15,
        ])->assertStatus(422);

        $this->actingAs($pengguna)->post('/reservasi-suku-cadang', [
            'GudangId' => $gudang->Id, 'SukuCadangId' => $sukuCadang->Id, 'Jumlah' => 6,
        ])->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $reservasi = ReservasiSukuCadang::query()->firstOrFail();
        $this->assertSame(StatusReservasiSukuCadang::Aktif->value, $reservasi->Status);

        // Reservasi kedua yang akan membuat total hold melebihi stok tersedia ditolak (cegah over-reservation).
        $this->actingAs($pengguna)->post('/reservasi-suku-cadang', [
            'GudangId' => $gudang->Id, 'SukuCadangId' => $sukuCadang->Id, 'Jumlah' => 5,
        ])->assertStatus(422);

        // Konsumsi reservasi -> stok fisik benar-benar berkurang lewat MutasiStok Pengeluaran.
        $this->actingAs($pengguna)->post("/reservasi-suku-cadang/{$reservasi->Id}/konsumsi")->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $reservasi->refresh();
        $this->assertSame(StatusReservasiSukuCadang::Dipakai->value, $reservasi->Status);

        $stok = StokSukuCadang::query()->where('GudangId', $gudang->Id)->where('SukuCadangId', $sukuCadang->Id)->firstOrFail();
        $this->assertSame('4.0000', $stok->JumlahTersedia);
        $this->assertSame('0.0000', $stok->JumlahDitahan);

        $mutasiPengeluaran = MutasiStok::query()->where('Jenis', JenisMutasiStok::Pengeluaran->value)->first();
        $this->assertNotNull($mutasiPengeluaran);
        $this->assertSame(StatusMutasiStok::Diposting->value, $mutasiPengeluaran->Status);
    }

    public function test_reservasi_lepaskan_mengembalikan_hold(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Stok.Kelola']);
        $this->buatNomorDokumenMutasiStok($organisasi);
        $gudang = $this->buatGudang($organisasi);
        $sukuCadang = $this->buatSukuCadang($organisasi);

        $this->actingAs($pengguna)->post('/mutasi-stok', ['Jenis' => JenisMutasiStok::Penerimaan->value, 'GudangTujuanId' => $gudang->Id]);
        $this->konteks()->tetapkan($organisasi->Id);
        $penerimaan = MutasiStok::query()->firstOrFail();
        $this->actingAs($pengguna)->post("/mutasi-stok/{$penerimaan->Id}/detail", ['SukuCadangId' => $sukuCadang->Id, 'Jumlah' => 10]);
        $this->actingAs($pengguna)->post("/mutasi-stok/{$penerimaan->Id}/posting");

        $this->actingAs($pengguna)->post('/reservasi-suku-cadang', ['GudangId' => $gudang->Id, 'SukuCadangId' => $sukuCadang->Id, 'Jumlah' => 6]);
        $this->konteks()->tetapkan($organisasi->Id);
        $reservasi = ReservasiSukuCadang::query()->firstOrFail();

        $this->actingAs($pengguna)->post("/reservasi-suku-cadang/{$reservasi->Id}/lepaskan")->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $reservasi->refresh();
        $this->assertSame(StatusReservasiSukuCadang::Dilepas->value, $reservasi->Status);

        $stok = StokSukuCadang::query()->where('GudangId', $gudang->Id)->where('SukuCadangId', $sukuCadang->Id)->firstOrFail();
        $this->assertSame('0.0000', $stok->JumlahDitahan);
        $this->assertSame('10.0000', $stok->JumlahTersedia);

        // Sekarang boleh mereservasi ulang sampai penuh karena hold sudah dilepas.
        $this->actingAs($pengguna)->post('/reservasi-suku-cadang', ['GudangId' => $gudang->Id, 'SukuCadangId' => $sukuCadang->Id, 'Jumlah' => 10])->assertRedirect();
    }

    public function test_gate_10_tidak_ada_endpoint_tulis_langsung_untuk_stok_suku_cadang(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Stok.Kelola']);
        $this->konteks()->tetapkan($organisasi->Id);
        $gudang = $this->buatGudang($organisasi);
        $sukuCadang = $this->buatSukuCadang($organisasi);
        $stok = StokSukuCadang::create([
            'GudangId' => $gudang->Id, 'SukuCadangId' => $sukuCadang->Id,
            'JumlahTersedia' => 100, 'JumlahDipesan' => 0, 'JumlahDitahan' => 0,
        ]);

        // POST ke URI yang sama dengan GET index -> 405 (method tidak diizinkan).
        $this->actingAs($pengguna)->post('/stok-suku-cadang', ['JumlahTersedia' => 999])->assertStatus(405);
        // Tidak ada route sama sekali untuk /stok-suku-cadang/{id} -> 404.
        $this->actingAs($pengguna)->put("/stok-suku-cadang/{$stok->Id}", ['JumlahTersedia' => 999])->assertStatus(404);
        $this->actingAs($pengguna)->delete("/stok-suku-cadang/{$stok->Id}")->assertStatus(404);

        $this->konteks()->tetapkan($organisasi->Id);
        $stok->refresh();
        $this->assertSame('100.0000', $stok->JumlahTersedia);
    }

    /**
     * Nama suku cadang dan gudang datang dari tabel lain, jadi pencariannya
     * bergantung pada join di controller. Tanpa join itu kotak cari hanya
     * akan mencocokkan kolom StokSukuCadang dan tampak tidak menemukan apa pun.
     */
    public function test_stok_dapat_dicari_dan_diurutkan_lewat_nama_relasi(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Stok.Kelola']);
        $this->konteks()->tetapkan($organisasi->Id);

        $gudangUtama = $this->buatGudang($organisasi, ['Nama' => 'Gudang Utama']);
        $gudangCabang = $this->buatGudang($organisasi, ['Nama' => 'Gudang Cabang']);
        $baut = $this->buatSukuCadang($organisasi, ['Nama' => 'Baut Hexagonal']);
        $oli = $this->buatSukuCadang($organisasi, ['Nama' => 'Oli Hidrolik']);

        foreach ([[$gudangUtama, $baut], [$gudangCabang, $oli]] as [$gudang, $sukuCadang]) {
            StokSukuCadang::create([
                'GudangId' => $gudang->Id, 'SukuCadangId' => $sukuCadang->Id,
                'JumlahTersedia' => 10, 'JumlahDipesan' => 0, 'JumlahDitahan' => 0,
            ]);
        }

        $this->actingAs($pengguna)->get('/stok-suku-cadang?cari=Hidrolik')
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->has('stok.data', 1)
                ->where('stok.data.0.NamaSukuCadang', 'Oli Hidrolik')
                ->etc());

        $this->actingAs($pengguna)->get('/stok-suku-cadang?cari=Cabang')
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->has('stok.data', 1)
                ->where('stok.data.0.NamaGudang', 'Gudang Cabang')
                ->etc());

        $this->actingAs($pengguna)->get('/stok-suku-cadang?urut=NamaSukuCadang&arah=desc')
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->where('stok.data.0.NamaSukuCadang', 'Oli Hidrolik')
                ->etc());
    }
}
