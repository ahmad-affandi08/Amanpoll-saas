<?php

declare(strict_types=1);

namespace Tests\Feature\PerencanaanPengadaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Barang datang di gudang, jadi petugas gudang (`Stok.Kelola`) mencatat penerimaannya
 * sendiri. Ia hanya membuka PO yang sudah dikirim ke penyedia, dan tidak mendapat akses
 * ke daftar PO, persetujuan, maupun tagihan.
 */
final class PenerimaanOlehPetugasGudangTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $petugasGudang;

    private Penyedia $penyedia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-GDG', 'Nama' => 'Organisasi Gudang']);
        $this->petugasGudang = $this->buatPengguna(['Stok.Kelola']);
        $this->konteks()->tetapkan($this->organisasi->Id);
        $this->penyedia = Penyedia::create(['Kode' => 'VND-01', 'Nama' => 'PT Servis Mandiri']);
        $this->konteks()->bersihkan();
    }

    public function test_petugas_gudang_melihat_po_menunggu_penerimaan_dan_mencatatnya(): void
    {
        [$dikirim, $detail] = $this->buatPo('PO-001', 'Dikirim');
        $this->buatPo('PO-002', 'Draft');

        $this->actingAs($this->petugasGudang)
            ->get(route('perencanaanPengadaan.penerimaan.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->has('menungguPenerimaan', 1)
                ->where('menungguPenerimaan.0.Nomor', 'PO-001'));

        $this->actingAs($this->petugasGudang)
            ->get(route('perencanaanPengadaan.po.show', $dikirim->Id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('bolehKelola', false));

        $this->actingAs($this->petugasGudang)
            ->post(route('perencanaanPengadaan.penerimaan.store', $dikirim->Id), [
                'TanggalTerima' => '2026-05-04',
                'NomorSuratJalan' => 'SJ-7781',
                'Detail' => [['DetailPesananPembelianId' => $detail->Id, 'JumlahDiterima' => 1, 'Kondisi' => 'Baik']],
            ])
            ->assertSessionHasNoErrors();

        $this->konteks()->tetapkan($this->organisasi->Id);
        $penerimaan = PenerimaanPembelian::query()->sole();
        $this->assertSame($this->petugasGudang->Id, $penerimaan->DiterimaOleh);
        $this->assertSame('DiterimaPenuh', $dikirim->refresh()->Status);
    }

    public function test_petugas_gudang_tidak_membuka_po_yang_belum_dikirim_maupun_mengelola_pengadaan(): void
    {
        [$draf] = $this->buatPo('PO-003', 'Draft');
        [$dikirim] = $this->buatPo('PO-004', 'Dikirim');

        $this->actingAs($this->petugasGudang)->get(route('perencanaanPengadaan.po.show', $draf->Id))->assertForbidden();
        $this->actingAs($this->petugasGudang)->get(route('perencanaanPengadaan.po.index'))->assertForbidden();
        $this->actingAs($this->petugasGudang)->post(route('perencanaanPengadaan.po.kirim', $draf->Id))->assertForbidden();
        $this->actingAs($this->petugasGudang)
            ->post(route('perencanaanPengadaan.tagihan.store', $dikirim->Id), ['NomorTagihan' => 'INV-1', 'TanggalTagihan' => '2026-05-04', 'Subtotal' => 1500000])
            ->assertForbidden();
    }

    public function test_pengguna_tanpa_izin_gudang_atau_pengadaan_tidak_mencatat_penerimaan(): void
    {
        $teknisi = $this->buatPengguna(['PerintahKerja.Kelola']);
        [$dikirim, $detail] = $this->buatPo('PO-005', 'Dikirim');

        $this->actingAs($teknisi)->get(route('perencanaanPengadaan.penerimaan.index'))->assertForbidden();
        $this->actingAs($teknisi)->get(route('perencanaanPengadaan.po.show', $dikirim->Id))->assertForbidden();
        $this->actingAs($teknisi)
            ->post(route('perencanaanPengadaan.penerimaan.store', $dikirim->Id), [
                'TanggalTerima' => '2026-05-04',
                'Detail' => [['DetailPesananPembelianId' => $detail->Id, 'JumlahDiterima' => 1, 'Kondisi' => 'Baik']],
            ])
            ->assertForbidden();

        $this->konteks()->tetapkan($this->organisasi->Id);
        $this->assertSame(0, PenerimaanPembelian::query()->count());
    }

    public function test_bagian_pengadaan_tetap_mengelola_po_penuh(): void
    {
        $pengadaan = $this->buatPengguna(['Pengadaan.Kelola']);
        [$draf] = $this->buatPo('PO-006', 'Draft');

        $this->actingAs($pengadaan)
            ->get(route('perencanaanPengadaan.po.show', $draf->Id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('bolehKelola', true));
        $this->actingAs($pengadaan)->get(route('perencanaanPengadaan.penerimaan.index'))->assertOk();
    }

    /**
     * @return array{0: PesananPembelian, 1: DetailPesananPembelian}
     */
    private function buatPo(string $nomor, string $status): array
    {
        $this->konteks()->tetapkan($this->organisasi->Id);
        $po = PesananPembelian::create([
            'Nomor' => $nomor,
            'PenyediaId' => $this->penyedia->Id,
            'TanggalPesanan' => '2026-04-28',
            'TanggalKirimRencana' => '2026-05-04',
            'Subtotal' => 1500000,
            'Total' => 1500000,
            'Status' => $status,
        ]);
        $detail = DetailPesananPembelian::create([
            'PesananPembelianId' => $po->Id,
            'JenisItem' => 'Jasa',
            'Deskripsi' => 'Jasa overhaul pompa',
            'Jumlah' => 1,
            'Satuan' => 'paket',
            'HargaSatuan' => 1500000,
            'Total' => 1500000,
        ]);
        $this->konteks()->bersihkan();

        return [$po, $detail];
    }

    /**
     * @param  list<string>  $izin
     */
    private function buatPengguna(array $izin): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna.'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $this->konteks()->tetapkan($this->organisasi->Id);
        $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran Uji']);
        foreach ($izin as $kode) {
            $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
        }
        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
        $this->konteks()->bersihkan();

        return $pengguna;
    }

    private function konteks(): KonteksOrganisasi
    {
        return app(KonteksOrganisasi::class);
    }
}
