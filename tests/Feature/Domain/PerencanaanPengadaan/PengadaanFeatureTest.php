<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\PerencanaanPengadaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Penyedia\Domain\Enums\StatusPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\CatatPembayaranPenyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\CatatPenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaAnggaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPermintaanPenawaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPesananPembelian;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPosAnggaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaTagihanPenyedia;
use App\Domain\PerencanaanPengadaan\Application\Services\LayananSaldoAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\JenisTransaksiAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPermintaanPenawaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPesananPembelian;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusTagihanPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TransaksiAnggaran;
use App\Domain\Persediaan\Domain\Enums\StatusGudang;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Persetujuan\Application\Actions\SetujuiPermintaanPersetujuan;
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
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * FASE 16 — alur pengadaan dari permintaan pembelian sampai pembayaran penyedia.
 */
final class PengadaanFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_16_01_permintaan_pembelian_menghitung_total_server_side_dan_memvalidasi_anggaran(): void
    {
        $konteks = $this->siapkanKonteks('1000000.00');
        $aksi = app(KelolaPermintaanPembelian::class);
        $permintaan = $this->buatPermintaanDenganDuaItem($konteks);

        $this->assertSame('11000000.00', $permintaan->refresh()->TotalEstimasi);
        $this->assertSame(2, $permintaan->detail()->count());

        // Total estimasi 11 juta melampaui pos anggaran 1 juta.
        $this->assertThrows(
            fn () => $aksi->submit($permintaan, $konteks['pengguna']->Id),
            AturanBisnisDilanggar::class,
        );
        $this->assertSame(StatusPermintaanPembelian::Draft->value, $permintaan->refresh()->Status);
    }

    public function test_16_01_permintaan_pembelian_menolak_deskripsi_item_duplikat(): void
    {
        $konteks = $this->siapkanKonteks();
        $aksi = app(KelolaPermintaanPembelian::class);
        $permintaan = $aksi->buat($this->dataPermintaan($konteks), $konteks['pengguna']->Id);
        $aksi->tambahDetail($permintaan, $this->dataItemSukuCadang($konteks));

        $this->assertThrows(
            fn () => $aksi->tambahDetail($permintaan, $this->dataItemSukuCadang($konteks)),
            AturanBisnisDilanggar::class,
        );
        $this->assertSame(1, $permintaan->detail()->count());
    }

    public function test_16_01_permintaan_pembelian_disetujui_melalui_mesin_persetujuan(): void
    {
        $konteks = $this->siapkanKonteks();
        $permintaan = $this->buatPermintaanDisetujui($konteks);

        $this->assertSame(StatusPermintaanPembelian::Disetujui->value, $permintaan->Status);
    }

    public function test_16_02_dan_16_03_rfq_mengumpulkan_penawaran_lalu_memilih_pemenang(): void
    {
        $konteks = $this->siapkanKonteks();
        $permintaan = $this->buatPermintaanDisetujui($konteks);
        $penyediaLain = $this->buatPenyedia($konteks['organisasi']);
        $kelolaRfq = app(KelolaPermintaanPenawaran::class);
        $kelolaPenawaran = app(KelolaPenawaranPenyedia::class);

        $rfq = $kelolaRfq->buat($permintaan, [
            'BatasPenawaran' => now()->addDays(7),
            'PenyediaIds' => [$konteks['penyedia']->Id, $penyediaLain->Id],
        ], $konteks['pengguna']->Id);
        $this->assertSame(StatusPermintaanPenawaran::Draft->value, $rfq->Status);
        $this->assertSame(2, $rfq->penyediaDiundang()->count());

        // Penawaran hanya boleh masuk setelah RFQ dibuka.
        $this->assertThrows(
            fn () => $kelolaPenawaran->catat($rfq, $this->dataPenawaran($permintaan, $konteks['penyedia']->Id, '90000', '4800000')),
            AturanBisnisDilanggar::class,
        );

        $kelolaRfq->buka($rfq);
        $this->assertSame(StatusPermintaanPenawaran::Dibuka->value, $rfq->refresh()->Status);

        $penawaranMurah = $kelolaPenawaran->catat($rfq, $this->dataPenawaran($permintaan, $konteks['penyedia']->Id, '90000', '4800000'));
        $penawaranMahal = $kelolaPenawaran->catat($rfq, $this->dataPenawaran($permintaan, $penyediaLain->Id, '95000', '5000000'));

        $this->assertSame('10500000.00', $penawaranMurah->Total);
        $this->assertSame('10950000.00', $penawaranMahal->Total);

        // Penyedia di luar undangan ditolak.
        $penyediaAsing = $this->buatPenyedia($konteks['organisasi']);
        $this->assertThrows(
            fn () => $kelolaPenawaran->catat($rfq, $this->dataPenawaran($permintaan, $penyediaAsing->Id, '80000', '4000000')),
            AturanBisnisDilanggar::class,
        );

        $kelolaPenawaran->pilih($penawaranMurah);

        $this->assertSame(StatusPenawaranPenyedia::Terpilih->value, $penawaranMurah->refresh()->Status);
        $this->assertSame(StatusPenawaranPenyedia::Ditolak->value, $penawaranMahal->refresh()->Status);
        $this->assertSame(StatusPermintaanPenawaran::Ditutup->value, $rfq->refresh()->Status);
    }

    public function test_16_04_pesanan_pembelian_menyalin_penawaran_dan_mencatat_komitmen_anggaran(): void
    {
        $konteks = $this->siapkanKonteks();
        $po = $this->buatPesananDikirim($konteks);

        $this->assertSame(StatusPesananPembelian::Dikirim->value, $po->Status);
        $this->assertSame('10500000.00', $po->Total);
        $this->assertSame(2, $po->detail()->count());

        $komitmen = TransaksiAnggaran::query()
            ->where('ReferensiJenis', 'PesananPembelian')
            ->where('ReferensiId', $po->Id)
            ->where('Jenis', JenisTransaksiAnggaran::Komitmen->value)
            ->get();
        $this->assertCount(1, $komitmen);
        $this->assertSame('10500000.00', $komitmen->first()->Jumlah);

        // Pengiriman ulang PO tidak menggandakan komitmen anggaran.
        $this->assertThrows(
            fn () => app(KelolaPesananPembelian::class)->kirim($po->refresh()),
            AturanBisnisDilanggar::class,
        );
        $this->assertSame(1, TransaksiAnggaran::query()
            ->where('ReferensiJenis', 'PesananPembelian')
            ->where('ReferensiId', $po->Id)
            ->where('Jenis', JenisTransaksiAnggaran::Komitmen->value)
            ->count());
    }

    public function test_16_04_pesanan_pembelian_hanya_boleh_dibuat_sekali_dari_penawaran_terpilih(): void
    {
        $konteks = $this->siapkanKonteks();
        $penawaran = $this->buatPenawaranTerpilih($konteks);
        $aksi = app(KelolaPesananPembelian::class);
        $aksi->buatDariPenawaran($penawaran, ['TanggalPesanan' => now()->toDateString()], $konteks['pengguna']->Id);

        $this->assertThrows(
            fn () => $aksi->buatDariPenawaran($penawaran->refresh(), ['TanggalPesanan' => now()->toDateString()], $konteks['pengguna']->Id),
            AturanBisnisDilanggar::class,
        );
    }

    public function test_16_05_penerimaan_sebagian_menambah_stok_mendaftarkan_aset_dan_menolak_kelebihan(): void
    {
        $konteks = $this->siapkanKonteks();
        $po = $this->buatPesananDikirim($konteks);
        $aksi = app(CatatPenerimaanPembelian::class);
        [$detailSukuCadang, $detailAset] = $this->detailPesanan($po);

        $penerimaan = $aksi->jalankan($po, [
            'TanggalTerima' => now()->toDateTimeString(),
            'GudangId' => $konteks['gudang']->Id,
            'Detail' => [
                ['DetailPesananPembelianId' => $detailSukuCadang->Id, 'JumlahDiterima' => '4', 'Kondisi' => 'Baik'],
                ['DetailPesananPembelianId' => $detailAset->Id, 'JumlahDiterima' => '1', 'Kondisi' => 'Baik', 'NomorSeri' => ['SN-001']],
            ],
        ], $konteks['pengguna']->Id);

        $this->assertSame(2, $penerimaan->detail()->count());
        $this->assertSame(StatusPesananPembelian::DiterimaSebagian->value, $po->refresh()->Status);
        $this->assertSame('4.0000', $this->stokTersedia($konteks));
        $this->assertSame(1, Aset::query()->where('NomorSeri', 'SN-001')->count());

        // Menerima 7 unit lagi melebihi 10 unit yang dipesan.
        $this->assertThrows(
            fn () => $aksi->jalankan($po->refresh(), [
                'TanggalTerima' => now()->toDateTimeString(),
                'GudangId' => $konteks['gudang']->Id,
                'Detail' => [
                    ['DetailPesananPembelianId' => $detailSukuCadang->Id, 'JumlahDiterima' => '7', 'Kondisi' => 'Baik'],
                ],
            ], $konteks['pengguna']->Id),
            AturanBisnisDilanggar::class,
        );
        $this->assertSame('4.0000', $this->stokTersedia($konteks));

        // Setiap unit aset wajib memiliki tepat satu nomor seri.
        $this->assertThrows(
            fn () => $aksi->jalankan($po->refresh(), [
                'TanggalTerima' => now()->toDateTimeString(),
                'GudangId' => $konteks['gudang']->Id,
                'Detail' => [
                    ['DetailPesananPembelianId' => $detailAset->Id, 'JumlahDiterima' => '1', 'Kondisi' => 'Baik', 'NomorSeri' => []],
                ],
            ], $konteks['pengguna']->Id),
            AturanBisnisDilanggar::class,
        );

        $aksi->jalankan($po->refresh(), [
            'TanggalTerima' => now()->toDateTimeString(),
            'GudangId' => $konteks['gudang']->Id,
            'Detail' => [
                ['DetailPesananPembelianId' => $detailSukuCadang->Id, 'JumlahDiterima' => '6', 'Kondisi' => 'Baik'],
                ['DetailPesananPembelianId' => $detailAset->Id, 'JumlahDiterima' => '1', 'Kondisi' => 'Baik', 'NomorSeri' => ['SN-002']],
            ],
        ], $konteks['pengguna']->Id);

        $this->assertSame(StatusPesananPembelian::DiterimaPenuh->value, $po->refresh()->Status);
        $this->assertSame('10.0000', $this->stokTersedia($konteks));
        $this->assertSame(2, Aset::query()->whereIn('NomorSeri', ['SN-001', 'SN-002'])->count());
    }

    public function test_16_06_dan_16_07_tagihan_dicocokkan_ke_penerimaan_dan_pembayaran_mengurangi_sisa(): void
    {
        $konteks = $this->siapkanKonteks();
        $po = $this->buatPesananDikirim($konteks);
        [$detailSukuCadang, $detailAset] = $this->detailPesanan($po);
        app(CatatPenerimaanPembelian::class)->jalankan($po, [
            'TanggalTerima' => now()->toDateTimeString(),
            'GudangId' => $konteks['gudang']->Id,
            'Detail' => [
                ['DetailPesananPembelianId' => $detailSukuCadang->Id, 'JumlahDiterima' => '4', 'Kondisi' => 'Baik'],
                ['DetailPesananPembelianId' => $detailAset->Id, 'JumlahDiterima' => '1', 'Kondisi' => 'Baik', 'NomorSeri' => ['SN-010']],
            ],
        ], $konteks['pengguna']->Id);

        $kelolaTagihan = app(KelolaTagihanPenyedia::class);
        $po->refresh();

        // Nilai barang diterima 5.160.000; tagihan 6 juta harus ditolak.
        $this->assertThrows(
            fn () => $kelolaTagihan->buat($po, [
                'NomorTagihan' => 'INV-TOLAK',
                'TanggalTagihan' => now()->toDateString(),
                'Subtotal' => '6000000.00',
            ]),
            AturanBisnisDilanggar::class,
        );

        $tagihan = $kelolaTagihan->buat($po, [
            'NomorTagihan' => 'INV-001',
            'TanggalTagihan' => now()->toDateString(),
            'Subtotal' => '5000000.00',
            'Pajak' => '100000.00',
        ]);

        $this->assertSame('5100000.00', $tagihan->Total);
        $this->assertSame('5100000.00', $tagihan->Sisa);
        $this->assertSame(StatusTagihanPenyedia::BelumDibayar->value, $tagihan->Status);

        $catatPembayaran = app(CatatPembayaranPenyedia::class);
        $catatPembayaran->jalankan($tagihan, [
            'NomorPembayaran' => 'PAY-001',
            'TanggalBayar' => now()->toDateString(),
            'Jumlah' => '2000000.00',
            'Metode' => 'Transfer',
        ], $konteks['pengguna']->Id);

        $tagihan->refresh();
        $this->assertSame('3100000.00', $tagihan->Sisa);
        $this->assertSame(StatusTagihanPenyedia::DibayarSebagian->value, $tagihan->Status);

        // Pembayaran menjadi realisasi dan melepas komitmen PO senilai yang sama.
        $saldo = app(LayananSaldoAnggaran::class)->hitung($konteks['pos']->refresh());
        $this->assertSame('2000000.00', $saldo['terpakai']);
        $this->assertSame('8500000.00', $saldo['ditahan']);

        $this->assertThrows(
            fn () => $catatPembayaran->jalankan($tagihan, [
                'NomorPembayaran' => 'PAY-LEBIH',
                'TanggalBayar' => now()->toDateString(),
                'Jumlah' => '3100000.01',
                'Metode' => 'Transfer',
            ], $konteks['pengguna']->Id),
            AturanBisnisDilanggar::class,
        );

        $catatPembayaran->jalankan($tagihan->refresh(), [
            'NomorPembayaran' => 'PAY-002',
            'TanggalBayar' => now()->toDateString(),
            'Jumlah' => '3100000.00',
            'Metode' => 'Transfer',
        ], $konteks['pengguna']->Id);

        $tagihan->refresh();
        $this->assertSame('0.00', $tagihan->Sisa);
        $this->assertSame(StatusTagihanPenyedia::Dibayar->value, $tagihan->Status);
        $this->assertSame(2, $tagihan->pembayaran()->count());

        $saldoAkhir = app(LayananSaldoAnggaran::class)->hitung($konteks['pos']->refresh());
        $this->assertSame('5100000.00', $saldoAkhir['terpakai']);
        $this->assertSame('5400000.00', $saldoAkhir['ditahan']);
        $this->assertSame('89500000.00', $saldoAkhir['sisa']);
    }

    public function test_endpoint_pengadaan_menegakkan_izin_dan_isolasi_tenant(): void
    {
        $konteks = $this->siapkanKonteks();

        $tanpaIzin = $this->buatPengguna($konteks['organisasi'], []);
        $this->actingAs($tanpaIzin)
            ->get(route('perencanaanPengadaan.permintaan.index'))
            ->assertForbidden();

        // Tagihan milik organisasi lain tidak boleh terlihat oleh organisasi ini.
        $organisasiLain = $this->buatOrganisasi('LAIN');
        $penyediaLain = $this->buatPenyedia($organisasiLain);
        app(KonteksOrganisasi::class)->tetapkan($organisasiLain->Id);
        $tagihanLain = TagihanPenyedia::create([
            'PenyediaId' => $penyediaLain->Id,
            'NomorTagihan' => 'INV-LAIN',
            'TanggalTagihan' => now()->toDateString(),
            'Subtotal' => '100000.00',
            'Pajak' => '0.00',
            'Total' => '100000.00',
            'Sisa' => '100000.00',
            'Status' => StatusTagihanPenyedia::BelumDibayar->value,
        ]);

        app(KonteksOrganisasi::class)->tetapkan($konteks['organisasi']->Id);
        $this->actingAs($konteks['pengguna'])
            ->get(route('perencanaanPengadaan.tagihan.show', $tagihanLain->Id))
            ->assertNotFound();
    }

    public function test_halaman_operasional_fase_16_dapat_dirender(): void
    {
        $konteks = $this->siapkanKonteks();
        $po = $this->buatPesananDikirim($konteks);
        $permintaan = PermintaanPembelian::query()->findOrFail($po->PermintaanPembelianId);
        $rfq = $permintaan->permintaanPenawaran()->firstOrFail();
        [$detailSukuCadang] = $this->detailPesanan($po);
        app(CatatPenerimaanPembelian::class)->jalankan($po, [
            'TanggalTerima' => now()->toDateTimeString(),
            'GudangId' => $konteks['gudang']->Id,
            'Detail' => [
                ['DetailPesananPembelianId' => $detailSukuCadang->Id, 'JumlahDiterima' => '4', 'Kondisi' => 'Baik'],
            ],
        ], $konteks['pengguna']->Id);
        $tagihan = app(KelolaTagihanPenyedia::class)->buat($po->refresh(), [
            'NomorTagihan' => 'INV-RENDER',
            'TanggalTagihan' => now()->toDateString(),
            'Subtotal' => '300000.00',
        ]);

        $this->actingAs($konteks['pengguna']);
        $this->get(route('perencanaanPengadaan.permintaan.index'))->assertOk();
        $this->get(route('perencanaanPengadaan.permintaan.show', $permintaan))->assertOk();
        $this->get(route('perencanaanPengadaan.rfq.index'))->assertOk();
        $this->get(route('perencanaanPengadaan.rfq.show', $rfq))->assertOk();
        $this->get(route('perencanaanPengadaan.po.index'))->assertOk();
        $this->get(route('perencanaanPengadaan.po.show', $po))->assertOk();
        $this->get(route('perencanaanPengadaan.penerimaan.index'))->assertOk();
        $this->get(route('perencanaanPengadaan.tagihan.index'))->assertOk();
        $this->get(route('perencanaanPengadaan.tagihan.show', $tagihan))->assertOk();
    }

    /**
     * @return array{organisasi: Organisasi, pengguna: Pengguna, unit: UnitOrganisasi, pos: PosAnggaran, penyedia: Penyedia, gudang: Gudang, sukuCadang: SukuCadang, asetReferensi: Aset}
     */
    private function siapkanKonteks(string $jumlahAnggaran = '100000000.00'): array
    {
        $organisasi = $this->buatOrganisasi('PGD');
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $unit = UnitOrganisasi::create(['Kode' => 'UNIT-'.uniqid(), 'Nama' => 'Unit Pengadaan', 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Pengadaan.Kelola', 'Stok.Kelola']);
        $this->actingAs($pengguna);

        $anggaran = app(KelolaAnggaran::class)->buat([
            'Kode' => 'ANG-'.uniqid(),
            'Nama' => 'Anggaran Pengadaan',
            'Tahun' => (int) now()->format('Y'),
            'MataUang' => 'IDR',
            'Jumlah' => $jumlahAnggaran,
        ]);
        $pos = app(KelolaPosAnggaran::class)->buat($anggaran, [
            'Kode' => 'POS-'.uniqid(),
            'Nama' => 'Pos Pengadaan',
            'Jumlah' => $jumlahAnggaran,
        ]);
        app(KelolaAnggaran::class)->ajukan($anggaran, $pengguna->Id);
        $this->assertSame(StatusAnggaran::Aktif->value, $anggaran->refresh()->Status);

        $kategoriAset = KategoriAset::create(['Kode' => 'KAT-'.uniqid(), 'Nama' => 'Perangkat Jaringan']);

        return [
            'organisasi' => $organisasi,
            'pengguna' => $pengguna,
            'unit' => $unit,
            'pos' => $pos->refresh(),
            'penyedia' => $this->buatPenyedia($organisasi),
            'gudang' => Gudang::create(['Kode' => 'GDG-'.uniqid(), 'Nama' => 'Gudang Pusat', 'Status' => StatusGudang::Aktif->value]),
            'sukuCadang' => SukuCadang::create([
                'Kode' => 'SC-'.uniqid(),
                'Nama' => 'Kabel Fiber',
                'SatuanDasar' => 'Pcs',
                'StokMinimum' => 0,
                'Status' => StatusSukuCadang::Aktif->value,
            ]),
            'asetReferensi' => Aset::create([
                'UnitOrganisasiId' => $unit->Id,
                'KategoriAsetId' => $kategoriAset->Id,
                'KodeAset' => 'AST-'.uniqid(),
                'Nama' => 'Switch Referensi',
                'Status' => StatusAset::Aktif->value,
                'Kondisi' => KondisiAset::Baik->value,
                'TingkatKritis' => TingkatKritisAset::Normal->value,
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $konteks
     * @return array<string, mixed>
     */
    private function dataPermintaan(array $konteks): array
    {
        return [
            'UnitOrganisasiId' => $konteks['unit']->Id,
            'PosAnggaranId' => $konteks['pos']->Id,
            'TanggalPermintaan' => now()->toDateString(),
            'Prioritas' => 'Normal',
            'Alasan' => 'Kebutuhan pengadaan pengujian.',
        ];
    }

    /**
     * @param  array<string, mixed>  $konteks
     * @return array<string, mixed>
     */
    private function dataItemSukuCadang(array $konteks): array
    {
        return [
            'JenisItem' => 'SukuCadang',
            'SukuCadangId' => $konteks['sukuCadang']->Id,
            'Deskripsi' => 'Kabel fiber 10 meter',
            'Jumlah' => '10',
            'Satuan' => 'Pcs',
            'HargaEstimasi' => '100000',
        ];
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function buatPermintaanDenganDuaItem(array $konteks): PermintaanPembelian
    {
        $aksi = app(KelolaPermintaanPembelian::class);
        $permintaan = $aksi->buat($this->dataPermintaan($konteks), $konteks['pengguna']->Id);
        $aksi->tambahDetail($permintaan, $this->dataItemSukuCadang($konteks));
        $aksi->tambahDetail($permintaan, [
            'JenisItem' => 'Aset',
            'AsetReferensiId' => $konteks['asetReferensi']->Id,
            'Deskripsi' => 'Switch akses 24 port',
            'Jumlah' => '2',
            'Satuan' => 'Unit',
            'HargaEstimasi' => '5000000',
        ]);

        return $permintaan->refresh();
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function buatPermintaanDisetujui(array $konteks): PermintaanPembelian
    {
        $penyetuju = $this->buatPengguna($konteks['organisasi'], []);
        $alur = $this->buatAlurPersetujuan($konteks['organisasi'], 'PermintaanPembelian', $penyetuju);
        $permintaan = $this->buatPermintaanDenganDuaItem($konteks);
        app(KelolaPermintaanPembelian::class)->submit($permintaan, $konteks['pengguna']->Id);
        $this->assertSame(StatusPermintaanPembelian::MenungguPersetujuan->value, $permintaan->refresh()->Status);

        $permintaanPersetujuan = PermintaanPersetujuan::query()
            ->where('AlurPersetujuanId', $alur->Id)
            ->where('EntitasId', $permintaan->Id)
            ->firstOrFail();
        app(SetujuiPermintaanPersetujuan::class)->jalankan($permintaanPersetujuan, $penyetuju, 'Disetujui untuk pengujian.');
        $this->actingAs($konteks['pengguna']);

        return $permintaan->refresh();
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function buatPenawaranTerpilih(array $konteks): PenawaranPenyedia
    {
        $permintaan = $this->buatPermintaanDisetujui($konteks);
        $rfq = app(KelolaPermintaanPenawaran::class)->buat($permintaan, [
            'BatasPenawaran' => now()->addDays(7),
            'PenyediaIds' => [$konteks['penyedia']->Id],
        ], $konteks['pengguna']->Id);
        app(KelolaPermintaanPenawaran::class)->buka($rfq);
        $penawaran = app(KelolaPenawaranPenyedia::class)->catat(
            $rfq->refresh(),
            $this->dataPenawaran($permintaan, $konteks['penyedia']->Id, '90000', '4800000'),
        );

        return app(KelolaPenawaranPenyedia::class)->pilih($penawaran);
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function buatPesananDikirim(array $konteks): PesananPembelian
    {
        $penawaran = $this->buatPenawaranTerpilih($konteks);
        $penyetuju = $this->buatPengguna($konteks['organisasi'], []);
        $alur = $this->buatAlurPersetujuan($konteks['organisasi'], 'PesananPembelian', $penyetuju);
        $aksi = app(KelolaPesananPembelian::class);

        $po = $aksi->buatDariPenawaran($penawaran, [
            'TanggalPesanan' => now()->toDateString(),
            'TanggalKirimRencana' => now()->addDays(14)->toDateString(),
        ], $konteks['pengguna']->Id);
        $aksi->ajukan($po, $konteks['pengguna']->Id);

        $permintaanPersetujuan = PermintaanPersetujuan::query()
            ->where('AlurPersetujuanId', $alur->Id)
            ->where('EntitasId', $po->Id)
            ->firstOrFail();
        app(SetujuiPermintaanPersetujuan::class)->jalankan($permintaanPersetujuan, $penyetuju, 'PO disetujui.');
        $this->actingAs($konteks['pengguna']);
        $this->assertSame(StatusPesananPembelian::Disetujui->value, $po->refresh()->Status);

        return $aksi->kirim($po);
    }

    /**
     * @return array{0: DetailPesananPembelian, 1: DetailPesananPembelian}
     */
    private function detailPesanan(PesananPembelian $po): array
    {
        return [
            $po->detail()->where('JenisItem', 'SukuCadang')->firstOrFail(),
            $po->detail()->where('JenisItem', 'Aset')->firstOrFail(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dataPenawaran(PermintaanPembelian $permintaan, string $penyediaId, string $hargaSukuCadang, string $hargaAset): array
    {
        $detail = $permintaan->detail()->orderBy('DibuatPada')->get();

        return [
            'PenyediaId' => $penyediaId,
            'NomorPenawaran' => 'QT-'.uniqid(),
            'TanggalPenawaran' => now()->toDateString(),
            'MataUang' => 'IDR',
            'Detail' => $detail->map(fn ($baris): array => [
                'DetailPermintaanPembelianId' => $baris->Id,
                'Jumlah' => $baris->Jumlah,
                'HargaSatuan' => $baris->JenisItem === 'Aset' ? $hargaAset : $hargaSukuCadang,
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function stokTersedia(array $konteks): string
    {
        return (string) StokSukuCadang::query()
            ->where('GudangId', $konteks['gudang']->Id)
            ->where('SukuCadangId', $konteks['sukuCadang']->Id)
            ->value('JumlahTersedia');
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
            'Status' => StatusPenyedia::Aktif->value,
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

        $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran Pengadaan']);
        foreach ($izin as $kodeIzin) {
            $modelIzin = Izin::firstOrCreate(['Kode' => $kodeIzin], ['Nama' => $kodeIzin, 'Modul' => 'PerencanaanPengadaan']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
        }
        PenggunaPeran::create(['OrganisasiId' => $organisasi->Id, 'PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        return $pengguna;
    }

    private function buatAlurPersetujuan(Organisasi $organisasi, string $jenisEntitas, Pengguna $penyetuju): AlurPersetujuan
    {
        $alur = AlurPersetujuan::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'ALUR-'.uniqid(),
            'Nama' => 'Persetujuan '.$jenisEntitas,
            'JenisEntitas' => $jenisEntitas,
            'Aktif' => false,
        ]);
        TahapPersetujuan::create([
            'OrganisasiId' => $organisasi->Id,
            'AlurPersetujuanId' => $alur->Id,
            'Urutan' => 1,
            'Nama' => 'Persetujuan Manajer',
            'JenisPenyetuju' => 'Pengguna',
            'PenggunaId' => $penyetuju->Id,
            'JumlahMinimumPenyetuju' => 1,
            'BolehMenyetujuiSendiri' => false,
        ]);
        $alur->update(['Aktif' => true]);

        return $alur;
    }
}
