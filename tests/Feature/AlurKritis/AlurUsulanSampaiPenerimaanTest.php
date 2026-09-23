<?php

declare(strict_types=1);

namespace Tests\Feature\AlurKritis;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Penyedia\Domain\Enums\StatusPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaAnggaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPosAnggaran;
use App\Domain\PerencanaanPengadaan\Application\Services\LayananSaldoAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\JenisTransaksiAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPermintaanPenawaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPesananPembelian;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusRencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusUsulanAset;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPenawaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\RencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TransaksiAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
use App\Domain\Persediaan\Domain\Enums\JenisMutasiStok;
use App\Domain\Persediaan\Domain\Enums\StatusGudang;
use App\Domain\Persediaan\Domain\Enums\StatusMutasiStok;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FASE 26.03 — alur kritis "Usulan → procurement → receipt → asset/stock".
 *
 * Satu test merangkai seluruh langkah lewat rute HTTP seperti pengguna
 * sungguhan: usulan aset disetujui, masuk rencana, menjadi permintaan
 * pembelian, RFQ, pesanan pembelian, lalu diterima dua kali. Di setiap
 * sambungan diperiksa bahwa langkah berikutnya tertahan sebelum
 * persetujuannya ada, dan bahwa stok serta aset bertambah tepat sebesar yang
 * diterima — bukan hanya keadaan akhirnya.
 */
final class AlurUsulanSampaiPenerimaanTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $petugas;

    private Pengguna $penyetuju;

    private UnitOrganisasi $unit;

    private PosAnggaran $pos;

    private Penyedia $penyedia;

    private Gudang $gudang;

    private SukuCadang $sukuCadang;

    private Aset $asetReferensi;

    protected function setUp(): void
    {
        parent::setUp();

        // String polos akan diurai memakai zona waktu aplikasi; UTC ditulis eksplisit.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-10 03:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_usulan_disetujui_sampai_penerimaan_menambah_stok_dan_mendaftarkan_aset(): void
    {
        $this->siapkanOrganisasiUtama();
        $pembanding = $this->siapkanOrganisasiPembanding();

        // Stok awal disemai supaya pertambahannya terukur sebagai selisih, bukan kebetulan dari nol.
        $this->konteksUtama();
        StokSukuCadang::create([
            'GudangId' => $this->gudang->Id,
            'LokasiGudangId' => null,
            'SukuCadangId' => $this->sukuCadang->Id,
            'KelompokSukuCadangId' => null,
            'JumlahTersedia' => 3,
            'JumlahDipesan' => 0,
            'JumlahDitahan' => 0,
        ]);
        $this->assertSame('3.0000', $this->stokTersedia());
        $jumlahAsetAwal = Aset::query()->count();

        // 1. Usulan aset: draft → diajukan → dinilai → menunggu persetujuan.
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.usulan.store'), [
            'UnitOrganisasiId' => $this->unit->Id,
            'NamaKebutuhan' => 'Switch akses 24 port',
            'Jumlah' => '2',
            'EstimasiHargaSatuan' => '5000000',
            'Alasan' => 'Switch lantai dua sudah melewati umur pakai.',
            'TahunKebutuhan' => 2026,
            'Prioritas' => 'Normal',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->konteksUtama();
        $usulan = UsulanAset::query()->where('NamaKebutuhan', 'Switch akses 24 port')->firstOrFail();
        $this->assertSame(StatusUsulanAset::Draft->value, $usulan->Status);

        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.usulan.submit', $usulan->Id))->assertRedirect();
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.usulan.penilaian.store', $usulan->Id), [
            'Kriteria' => 'Dampak operasional',
            'Bobot' => '2',
            'Nilai' => '90',
            'Prioritas' => 'Tinggi',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.usulan.ajukan-persetujuan', $usulan->Id))->assertRedirect();

        $this->konteksUtama();
        $this->assertSame(StatusUsulanAset::MenungguPersetujuan->value, $usulan->refresh()->Status);

        // Usulan yang belum disetujui tidak boleh masuk rencana, dan rencananya tidak tertulis sebagian.
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.rencana.store'), [
            'Nama' => 'Rencana prematur',
            'Tahun' => 2026,
            'PosAnggaranId' => $this->pos->Id,
            'UsulanAsetIds' => [$usulan->Id],
        ])->assertStatus(422);
        $this->konteksUtama();
        $this->assertSame(0, RencanaPengadaan::query()->count());

        // Pengusul tidak boleh menyetujui usulannya sendiri.
        $persetujuanUsulan = $this->permintaanPersetujuan('UsulanAset', $usulan->Id);
        $this->actingAs($this->petugas)
            ->post(route('persetujuan.permintaan.setujui', $persetujuanUsulan->Id))
            ->assertForbidden();
        $this->konteksUtama();
        $this->assertSame(StatusUsulanAset::MenungguPersetujuan->value, $usulan->refresh()->Status);

        $this->setujuiSebagaiPenyetuju('UsulanAset', $usulan->Id);
        $this->assertSame(StatusUsulanAset::Disetujui->value, $usulan->refresh()->Status);
        $this->assertSame('Tinggi', $usulan->Prioritas);

        // 2. Rencana pengadaan dari usulan + satu baris suku cadang, lalu difinalisasi.
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.rencana.store'), [
            'Nama' => 'Pengadaan Jaringan Semester I',
            'Tahun' => 2026,
            'PosAnggaranId' => $this->pos->Id,
            'UsulanAsetIds' => [$usulan->Id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->konteksUtama();
        $rencana = RencanaPengadaan::query()->firstOrFail();
        $this->assertSame('10000000.00', $rencana->TotalEstimasi);

        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.rencana.detail.store', $rencana->Id), [
            'SukuCadangId' => $this->sukuCadang->Id,
            'Deskripsi' => 'Kabel fiber 10 meter',
            'Jumlah' => '10',
            'Satuan' => 'Pcs',
            'HargaEstimasi' => '100000',
            'BulanRencana' => 4,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.rencana.finalisasi', $rencana->Id))->assertRedirect();

        $this->konteksUtama();
        $rencana->refresh();
        $this->assertSame(StatusRencanaPengadaan::Direncanakan->value, $rencana->Status);
        $this->assertSame('11000000.00', $rencana->TotalEstimasi);

        // 3. Permintaan pembelian merujuk rencana; total dihitung server.
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.permintaan.store'), [
            'UnitOrganisasiId' => $this->unit->Id,
            'RencanaPengadaanId' => $rencana->Id,
            'PosAnggaranId' => $this->pos->Id,
            'TanggalPermintaan' => '2026-03-10',
            'Prioritas' => 'Tinggi',
            'Alasan' => 'Realisasi rencana pengadaan jaringan.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->konteksUtama();
        $permintaan = PermintaanPembelian::query()->where('RencanaPengadaanId', $rencana->Id)->firstOrFail();
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.permintaan.detail.store', $permintaan->Id), [
            'JenisItem' => 'Aset',
            'AsetReferensiId' => $this->asetReferensi->Id,
            'Deskripsi' => 'Switch akses 24 port',
            'Jumlah' => '2',
            'Satuan' => 'Unit',
            'HargaEstimasi' => '5000000',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.permintaan.detail.store', $permintaan->Id), [
            'JenisItem' => 'SukuCadang',
            'SukuCadangId' => $this->sukuCadang->Id,
            'Deskripsi' => 'Kabel fiber 10 meter',
            'Jumlah' => '10',
            'Satuan' => 'Pcs',
            'HargaEstimasi' => '100000',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.permintaan.submit', $permintaan->Id))->assertRedirect();

        $this->konteksUtama();
        $permintaan->refresh();
        $this->assertSame('11000000.00', $permintaan->TotalEstimasi);
        $this->assertSame(StatusPermintaanPembelian::MenungguPersetujuan->value, $permintaan->Status);

        // RFQ tertahan selama permintaan pembelian belum disetujui.
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.rfq.store'), $this->dataRfq($permintaan))->assertStatus(422);
        $this->konteksUtama();
        $this->assertSame(0, PermintaanPenawaran::query()->count());

        $this->setujuiSebagaiPenyetuju('PermintaanPembelian', $permintaan->Id);
        $this->assertSame(StatusPermintaanPembelian::Disetujui->value, $permintaan->refresh()->Status);

        // 4. RFQ → dibuka → penawaran → dipilih.
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.rfq.store'), $this->dataRfq($permintaan))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->konteksUtama();
        $rfq = PermintaanPenawaran::query()->where('PermintaanPembelianId', $permintaan->Id)->firstOrFail();
        $this->assertSame(StatusPermintaanPenawaran::Draft->value, $rfq->Status);

        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.rfq.buka', $rfq->Id))->assertRedirect();
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.rfq.penawaran.store', $rfq->Id), $this->dataPenawaran($permintaan))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->konteksUtama();
        $penawaran = PenawaranPenyedia::query()->where('PermintaanPenawaranId', $rfq->Id)->firstOrFail();
        $this->assertSame('10500000.00', $penawaran->Total);

        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.rfq.penawaran.pilih', [$rfq->Id, $penawaran->Id]))->assertRedirect();
        $this->konteksUtama();
        $this->assertSame(StatusPenawaranPenyedia::Terpilih->value, $penawaran->refresh()->Status);
        $this->assertSame(StatusPermintaanPenawaran::Ditutup->value, $rfq->refresh()->Status);

        // 5. Pesanan pembelian dari penawaran terpilih → diajukan → disetujui → dikirim.
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.po.store', $penawaran->Id), [
            'TanggalPesanan' => '2026-03-10',
            'TanggalKirimRencana' => '2026-03-24',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->konteksUtama();
        $po = PesananPembelian::query()->where('PenawaranPenyediaId', $penawaran->Id)->firstOrFail();
        $this->assertSame(StatusPesananPembelian::Draft->value, $po->Status);
        $this->assertSame('10500000.00', $po->Total);

        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.po.ajukan', $po->Id))->assertRedirect();
        $this->konteksUtama();
        $this->assertSame(StatusPesananPembelian::MenungguPersetujuan->value, $po->refresh()->Status);

        // PO belum boleh dikirim sebelum disetujui, dan komitmen anggaran belum boleh tercatat.
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.po.kirim', $po->Id))->assertStatus(422);
        $this->konteksUtama();
        $this->assertSame(StatusPesananPembelian::MenungguPersetujuan->value, $po->refresh()->Status);
        $this->assertSame(0, $this->jumlahKomitmen($po));

        $this->setujuiSebagaiPenyetuju('PesananPembelian', $po->Id);
        $this->assertSame(StatusPesananPembelian::Disetujui->value, $po->refresh()->Status);

        // PO yang disetujui tetapi belum dikirim belum dapat diterima; stok tidak bergerak.
        $detailKabel = $this->detailPo($po, 'SukuCadang');
        $detailSwitch = $this->detailPo($po, 'Aset');
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.penerimaan.store', $po->Id), [
            'GudangId' => $this->gudang->Id,
            'TanggalTerima' => '2026-03-20 09:00:00',
            'Detail' => [['DetailPesananPembelianId' => $detailKabel->Id, 'JumlahDiterima' => '1', 'Kondisi' => 'Baik']],
        ])->assertStatus(422);
        $this->konteksUtama();
        $this->assertSame('3.0000', $this->stokTersedia());

        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.po.kirim', $po->Id))->assertRedirect();
        $this->konteksUtama();
        $this->assertSame(StatusPesananPembelian::Dikirim->value, $po->refresh()->Status);
        $this->assertSame(1, $this->jumlahKomitmen($po));
        $saldo = app(LayananSaldoAnggaran::class)->hitung($this->pos->refresh());
        $this->assertSame('10500000.00', $saldo['ditahan']);
        $this->assertSame('89500000.00', $saldo['sisa']);

        // 6a. Penerimaan pertama (sebagian): 4 kabel + 1 switch.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 02:00:00', 'UTC'));
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.penerimaan.store', $po->Id), [
            'GudangId' => $this->gudang->Id,
            'TanggalTerima' => '2026-03-20 09:00:00',
            'NomorSuratJalan' => 'SJ-001',
            'Detail' => [
                ['DetailPesananPembelianId' => $detailKabel->Id, 'JumlahDiterima' => '4', 'Kondisi' => 'Baik'],
                ['DetailPesananPembelianId' => $detailSwitch->Id, 'JumlahDiterima' => '1', 'Kondisi' => 'Baik', 'NomorSeri' => ['SN-SW-001']],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->konteksUtama();
        $this->assertSame(StatusPesananPembelian::DiterimaSebagian->value, $po->refresh()->Status);
        $this->assertSame('7.0000', $this->stokTersedia(), 'Stok harus bertambah tepat 4 dari saldo awal 3.');
        $this->assertSame($jumlahAsetAwal + 1, Aset::query()->count());

        $penerimaanPertama = PenerimaanPembelian::query()->where('NomorSuratJalan', 'SJ-001')->firstOrFail();
        $mutasiStok = MutasiStok::query()
            ->where('ReferensiJenis', 'PenerimaanPembelian')
            ->where('ReferensiId', $penerimaanPertama->Id)
            ->get();
        $this->assertCount(1, $mutasiStok);
        $this->assertSame(JenisMutasiStok::Penerimaan->value, $mutasiStok->first()->Jenis);
        $this->assertSame(StatusMutasiStok::Diposting->value, $mutasiStok->first()->Status);
        $this->assertSame($this->gudang->Id, $mutasiStok->first()->GudangTujuanId);

        $asetBaru = Aset::query()->where('NomorSeri', 'SN-SW-001')->firstOrFail();
        $this->assertSame('Switch akses 24 port', $asetBaru->Nama);
        $this->assertSame($this->asetReferensi->KategoriAsetId, $asetBaru->KategoriAsetId);
        $this->assertSame($this->unit->Id, $asetBaru->UnitOrganisasiId);
        $this->assertSame($this->penyedia->Id, $asetBaru->PenyediaId);
        $this->assertSame(StatusAset::Aktif->value, $asetBaru->Status);
        $this->assertSame('2026-03-20', $asetBaru->TanggalPerolehan?->toDateString());
        $this->assertSame('4800000.00', $asetBaru->HargaPerolehan);

        // Organisasi lain tidak dapat mencatat penerimaan atas PO ini.
        $this->actingAs($pembanding['pengguna'])->post(route('perencanaanPengadaan.penerimaan.store', $po->Id), [
            'GudangId' => $pembanding['gudang']->Id,
            'TanggalTerima' => '2026-03-20 10:00:00',
            'Detail' => [['DetailPesananPembelianId' => $detailKabel->Id, 'JumlahDiterima' => '1', 'Kondisi' => 'Baik']],
        ])->assertNotFound();
        $this->actingAs($pembanding['pengguna'])->get(route('perencanaanPengadaan.po.show', $po->Id))->assertNotFound();

        // Kelebihan terima ditolak utuh: tidak ada penerimaan baru, stok tidak bergerak.
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.penerimaan.store', $po->Id), [
            'GudangId' => $this->gudang->Id,
            'TanggalTerima' => '2026-03-21 09:00:00',
            'Detail' => [['DetailPesananPembelianId' => $detailKabel->Id, 'JumlahDiterima' => '7', 'Kondisi' => 'Baik']],
        ])->assertStatus(422);
        $this->konteksUtama();
        $this->assertSame(1, PenerimaanPembelian::query()->where('PesananPembelianId', $po->Id)->count());
        $this->assertSame('7.0000', $this->stokTersedia());

        // 6b. Penerimaan kedua melunasi sisa pesanan.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-22 02:00:00', 'UTC'));
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.penerimaan.store', $po->Id), [
            'GudangId' => $this->gudang->Id,
            'TanggalTerima' => '2026-03-22 09:00:00',
            'NomorSuratJalan' => 'SJ-002',
            'Detail' => [
                ['DetailPesananPembelianId' => $detailKabel->Id, 'JumlahDiterima' => '6', 'Kondisi' => 'Baik'],
                ['DetailPesananPembelianId' => $detailSwitch->Id, 'JumlahDiterima' => '1', 'Kondisi' => 'Baik', 'NomorSeri' => ['SN-SW-002']],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->konteksUtama();
        $this->assertSame(StatusPesananPembelian::DiterimaPenuh->value, $po->refresh()->Status);
        $this->assertSame('13.0000', $this->stokTersedia(), 'Stok harus bertambah tepat 10 dari saldo awal 3.');
        $this->assertSame($jumlahAsetAwal + 2, Aset::query()->count());
        $this->assertSame(2, Aset::query()->whereIn('NomorSeri', ['SN-SW-001', 'SN-SW-002'])->where('PenyediaId', $this->penyedia->Id)->count());

        // PO yang sudah diterima penuh tidak menerima barang lagi.
        $this->actingAs($this->petugas)->post(route('perencanaanPengadaan.penerimaan.store', $po->Id), [
            'GudangId' => $this->gudang->Id,
            'TanggalTerima' => '2026-03-23 09:00:00',
            'Detail' => [['DetailPesananPembelianId' => $detailKabel->Id, 'JumlahDiterima' => '1', 'Kondisi' => 'Baik']],
        ])->assertStatus(422);
        $this->konteksUtama();
        $this->assertSame('13.0000', $this->stokTersedia());

        // Komitmen anggaran tetap satu baris sepanjang alur.
        $this->assertSame(1, $this->jumlahKomitmen($po));

        // Data organisasi pembanding tidak tersentuh sama sekali.
        $this->assertSame(7.0, (float) DB::table('StokSukuCadang')->where('OrganisasiId', $pembanding['organisasi']->Id)->sum('JumlahTersedia'));
        $this->assertSame(1, DB::table('Aset')->where('OrganisasiId', $pembanding['organisasi']->Id)->count());
        $this->assertSame(0, DB::table('PenerimaanPembelian')->where('OrganisasiId', $pembanding['organisasi']->Id)->count());
        $this->assertSame(0, DB::table('MutasiStok')->where('OrganisasiId', $pembanding['organisasi']->Id)->count());
    }

    private function siapkanOrganisasiUtama(): void
    {
        $this->organisasi = $this->buatOrganisasi('UTM');
        $this->konteksUtama();
        $this->unit = UnitOrganisasi::create(['Kode' => 'UNIT-JAR', 'Nama' => 'Unit Jaringan', 'Status' => 'Aktif']);
        $this->petugas = $this->buatPengguna($this->organisasi, ['Pengadaan.Kelola']);
        $this->penyetuju = $this->buatPengguna($this->organisasi, []);

        $this->konteksUtama();
        $this->actingAs($this->petugas);
        $anggaran = app(KelolaAnggaran::class)->buat([
            'Kode' => 'CAPEX-2026',
            'Nama' => 'Belanja Modal 2026',
            'Tahun' => 2026,
            'MataUang' => 'IDR',
            'Jumlah' => '100000000.00',
        ]);
        $this->pos = app(KelolaPosAnggaran::class)->buat($anggaran, [
            'Kode' => 'POS-JAR',
            'Nama' => 'Perangkat Jaringan',
            'Jumlah' => '100000000.00',
        ]);
        app(KelolaAnggaran::class)->ajukan($anggaran, $this->petugas->Id);
        $this->assertSame(StatusAnggaran::Aktif->value, $anggaran->refresh()->Status);

        foreach (['UsulanAset', 'PermintaanPembelian', 'PesananPembelian'] as $jenisEntitas) {
            $this->buatAlurPersetujuan($jenisEntitas);
        }

        $this->konteksUtama();
        $this->penyedia = Penyedia::create(['Kode' => 'PNY-001', 'Nama' => 'PT Jaringan Andal', 'Status' => StatusPenyedia::Aktif->value]);
        $this->gudang = Gudang::create(['Kode' => 'GDG-PST', 'Nama' => 'Gudang Pusat', 'Status' => StatusGudang::Aktif->value]);
        $this->sukuCadang = SukuCadang::create([
            'Kode' => 'SC-FIBER',
            'Nama' => 'Kabel Fiber',
            'SatuanDasar' => 'Pcs',
            'StokMinimum' => 0,
            'Status' => StatusSukuCadang::Aktif->value,
        ]);
        $kategori = KategoriAset::create(['Kode' => 'KAT-JAR', 'Nama' => 'Perangkat Jaringan']);
        $this->asetReferensi = Aset::create([
            'UnitOrganisasiId' => $this->unit->Id,
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-REF-001',
            'Nama' => 'Switch Referensi',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
        ]);
    }

    /**
     * @return array{organisasi: Organisasi, pengguna: Pengguna, gudang: Gudang}
     */
    private function siapkanOrganisasiPembanding(): array
    {
        $organisasi = $this->buatOrganisasi('PBD');
        $pengguna = $this->buatPengguna($organisasi, ['Pengadaan.Kelola']);

        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $gudang = Gudang::create(['Kode' => 'GDG-PST', 'Nama' => 'Gudang Pembanding', 'Status' => StatusGudang::Aktif->value]);
        $sukuCadang = SukuCadang::create([
            'Kode' => 'SC-FIBER',
            'Nama' => 'Kabel Fiber Pembanding',
            'SatuanDasar' => 'Pcs',
            'StokMinimum' => 0,
            'Status' => StatusSukuCadang::Aktif->value,
        ]);
        StokSukuCadang::create([
            'GudangId' => $gudang->Id,
            'LokasiGudangId' => null,
            'SukuCadangId' => $sukuCadang->Id,
            'KelompokSukuCadangId' => null,
            'JumlahTersedia' => 7,
            'JumlahDipesan' => 0,
            'JumlahDitahan' => 0,
        ]);
        $kategori = KategoriAset::create(['Kode' => 'KAT-JAR', 'Nama' => 'Perangkat Jaringan']);
        Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-REF-001',
            'Nama' => 'Switch Pembanding',
            'NomorSeri' => 'SN-SW-001',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
        ]);
        app(KonteksOrganisasi::class)->bersihkan();

        return ['organisasi' => $organisasi, 'pengguna' => $pengguna, 'gudang' => $gudang];
    }

    private function konteksUtama(): void
    {
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
    }

    private function permintaanPersetujuan(string $jenisEntitas, string $entitasId): PermintaanPersetujuan
    {
        $this->konteksUtama();

        return PermintaanPersetujuan::query()
            ->where('JenisEntitas', $jenisEntitas)
            ->where('EntitasId', $entitasId)
            ->firstOrFail();
    }

    private function setujuiSebagaiPenyetuju(string $jenisEntitas, string $entitasId): void
    {
        $permintaan = $this->permintaanPersetujuan($jenisEntitas, $entitasId);

        $this->actingAs($this->penyetuju)
            ->post(route('persetujuan.permintaan.setujui', $permintaan->Id), ['Catatan' => 'Disetujui.'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->konteksUtama();
    }

    /**
     * @return array<string, mixed>
     */
    private function dataRfq(PermintaanPembelian $permintaan): array
    {
        return [
            'PermintaanPembelianId' => $permintaan->Id,
            'BatasPenawaran' => CarbonImmutable::now()->addDays(7)->toDateTimeString(),
            'PenyediaIds' => [$this->penyedia->Id],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dataPenawaran(PermintaanPembelian $permintaan): array
    {
        $this->konteksUtama();
        $detail = $permintaan->detail()->orderBy('Deskripsi')->get();

        return [
            'PenyediaId' => $this->penyedia->Id,
            'NomorPenawaran' => 'QT-001',
            'TanggalPenawaran' => '2026-03-11',
            'MataUang' => 'IDR',
            'Detail' => $detail->map(fn ($baris): array => [
                'DetailPermintaanPembelianId' => $baris->Id,
                'Jumlah' => (string) $baris->Jumlah,
                'HargaSatuan' => $baris->JenisItem === 'Aset' ? '4800000' : '90000',
            ])->all(),
        ];
    }

    private function detailPo(PesananPembelian $po, string $jenisItem): DetailPesananPembelian
    {
        $this->konteksUtama();

        return $po->detail()->where('JenisItem', $jenisItem)->firstOrFail();
    }

    private function jumlahKomitmen(PesananPembelian $po): int
    {
        return TransaksiAnggaran::query()
            ->where('ReferensiJenis', 'PesananPembelian')
            ->where('ReferensiId', $po->Id)
            ->where('Jenis', JenisTransaksiAnggaran::Komitmen->value)
            ->count();
    }

    private function stokTersedia(): string
    {
        return (string) StokSukuCadang::query()
            ->where('GudangId', $this->gudang->Id)
            ->where('SukuCadangId', $this->sukuCadang->Id)
            ->value('JumlahTersedia');
    }

    private function buatOrganisasi(string $kode): Organisasi
    {
        return Organisasi::create(['Kode' => 'ORG-'.$kode, 'Nama' => 'Organisasi '.$kode, 'Status' => 'Aktif']);
    }

    /**
     * @param  list<string>  $kodeIzin
     */
    private function buatPengguna(Organisasi $organisasi, array $kodeIzin): Pengguna
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna.'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== []) {
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Petugas Pengadaan']);
            foreach ($kodeIzin as $kode) {
                $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'PerencanaanPengadaan']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            }
            PenggunaPeran::create(['OrganisasiId' => $organisasi->Id, 'PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
        }

        return $pengguna;
    }

    private function buatAlurPersetujuan(string $jenisEntitas): void
    {
        $this->konteksUtama();
        $alur = AlurPersetujuan::create([
            'Kode' => 'ALUR-'.$jenisEntitas,
            'Nama' => 'Persetujuan '.$jenisEntitas,
            'JenisEntitas' => $jenisEntitas,
            'Aktif' => false,
        ]);
        TahapPersetujuan::create([
            'AlurPersetujuanId' => $alur->Id,
            'Urutan' => 1,
            'Nama' => 'Persetujuan Kepala',
            'JenisPenyetuju' => 'Pengguna',
            'PenggunaId' => $this->penyetuju->Id,
            'JumlahMinimumPenyetuju' => 1,
            'BolehMenyetujuiSendiri' => false,
        ]);
        $alur->update(['Aktif' => true]);
    }
}
