<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\PerencanaanPengadaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\PerencanaanPengadaan\Application\Actions\CatatTransaksiAnggaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaAnggaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaPosAnggaran;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaRencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Application\Actions\KelolaUsulanAset;
use App\Domain\PerencanaanPengadaan\Application\Services\LayananSaldoAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\RencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TransaksiAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
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

final class PerencanaanPengadaanFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_15_01_dan_15_02_anggaran_pos_hierarki_dan_aktivasi_opsional(): void
    {
        [$organisasi, $pengguna] = $this->siapkanAktor();
        $this->actingAs($pengguna);

        $kelolaAnggaran = app(KelolaAnggaran::class);
        $kelolaPos = app(KelolaPosAnggaran::class);
        $anggaran = $kelolaAnggaran->buat([
            'Kode' => 'CAPEX-2026',
            'Nama' => 'Belanja Modal 2026',
            'Tahun' => 2026,
            'MataUang' => 'IDR',
            'Jumlah' => '1000000.00',
        ]);

        $induk = $kelolaPos->buat($anggaran, ['Kode' => 'IT', 'Nama' => 'Teknologi Informasi', 'Jumlah' => '750000.00']);
        $anak = $kelolaPos->buat($anggaran, ['IndukId' => $induk->Id, 'Kode' => 'IT-HW', 'Nama' => 'Perangkat Keras', 'Jumlah' => '600000.00']);

        $this->assertSame($induk->Id, $anak->IndukId);
        $this->assertThrows(
            fn () => $kelolaPos->buat($anggaran, ['Kode' => 'OPS', 'Nama' => 'Operasional', 'Jumlah' => '300000.01']),
            AturanBisnisDilanggar::class,
        );

        $diajukan = $kelolaAnggaran->ajukan($anggaran, $pengguna->Id);
        $this->assertSame(Anggaran::STATUS_AKTIF, $diajukan->Status);
        $this->assertDatabaseHas('Anggaran', ['OrganisasiId' => $organisasi->Id, 'Id' => $anggaran->Id, 'Status' => Anggaran::STATUS_AKTIF]);

        $penyetuju = $this->buatPengguna($organisasi, []);
        $alur = $this->buatAlurPersetujuan($organisasi, 'Anggaran', $penyetuju);
        $anggaranDenganApproval = $kelolaAnggaran->buat([
            'Kode' => 'OPEX-2026',
            'Nama' => 'Belanja Operasional 2026',
            'Tahun' => 2026,
            'Jumlah' => '500000.00',
        ]);
        $kelolaPos->buat($anggaranDenganApproval, ['Kode' => 'OPS', 'Nama' => 'Operasional', 'Jumlah' => '500000.00']);
        $menunggu = $kelolaAnggaran->ajukan($anggaranDenganApproval, $pengguna->Id);
        $this->assertSame(Anggaran::STATUS_MENUNGGU_PERSETUJUAN, $menunggu->Status);

        $permintaan = PermintaanPersetujuan::query()->where('AlurPersetujuanId', $alur->Id)->where('EntitasId', $anggaranDenganApproval->Id)->firstOrFail();
        app(SetujuiPermintaanPersetujuan::class)->jalankan($permintaan, $penyetuju, null);
        $this->assertSame(Anggaran::STATUS_AKTIF, $anggaranDenganApproval->refresh()->Status);
    }

    public function test_15_03_ledger_merekonsiliasi_komitmen_realisasi_pelepasan_dan_penyesuaian(): void
    {
        [, $pengguna] = $this->siapkanAktor(['Pengadaan.Kelola', 'Anggaran.Sesuaikan']);
        $this->actingAs($pengguna);
        [$anggaran, $pos] = $this->buatAnggaranAktif($pengguna, '1000.00');
        $catat = app(CatatTransaksiAnggaran::class);

        $catat->jalankan($pos, $this->dataTransaksi(TransaksiAnggaran::JENIS_KOMITMEN, '600.00'));
        $catat->jalankan($pos, $this->dataTransaksi(TransaksiAnggaran::JENIS_REALISASI, '400.00'));
        $catat->jalankan($pos, $this->dataTransaksi(TransaksiAnggaran::JENIS_PELEPASAN_KOMITMEN, '100.00'));
        $catat->jalankan($pos, $this->dataTransaksi(TransaksiAnggaran::JENIS_PENYESUAIAN, '50.00', 'Koreksi pembulatan invoice'));

        $pos->refresh();
        $saldo = app(LayananSaldoAnggaran::class)->hitung($pos);
        $this->assertSame('450.00', $pos->Terpakai);
        $this->assertSame('100.00', $pos->Ditahan);
        $this->assertSame(['jumlah' => '1000.00', 'terpakai' => '450.00', 'ditahan' => '100.00', 'sisa' => '450.00'], $saldo);
        $this->assertSame(5, $pos->transaksi()->count());
        $this->assertSame(Anggaran::STATUS_AKTIF, $anggaran->Status);
    }

    public function test_15_03_transaksi_melebihi_saldo_ditolak_tanpa_mengubah_ledger(): void
    {
        [, $pengguna] = $this->siapkanAktor();
        $this->actingAs($pengguna);
        [, $pos] = $this->buatAnggaranAktif($pengguna, '1000.00');
        $catat = app(CatatTransaksiAnggaran::class);
        $catat->jalankan($pos, $this->dataTransaksi(TransaksiAnggaran::JENIS_KOMITMEN, '800.00'));

        try {
            $catat->jalankan($pos, $this->dataTransaksi(TransaksiAnggaran::JENIS_KOMITMEN, '200.01'));
            $this->fail('Komitmen yang melebihi sisa anggaran seharusnya ditolak.');
        } catch (AturanBisnisDilanggar) {
            $this->assertSame(1, $pos->transaksi()->count());
            $this->assertSame('800.00', $pos->fresh()->Ditahan);
            $this->assertSame('200.00', app(LayananSaldoAnggaran::class)->hitung($pos)['sisa']);
        }
    }

    public function test_15_04_usulan_dinilai_diprioritaskan_dan_disetujui_melalui_mesin_persetujuan(): void
    {
        [$organisasi, $pengaju, $unit] = $this->siapkanAktor();
        $penyetuju = $this->buatPengguna($organisasi, []);
        $this->actingAs($pengaju);
        $alur = $this->buatAlurPersetujuan($organisasi, 'UsulanAset', $penyetuju);
        $aksi = app(KelolaUsulanAset::class);

        $usulan = $aksi->buat([
            'UnitOrganisasiId' => $unit->Id,
            'NamaKebutuhan' => 'Server virtualisasi',
            'Jumlah' => '2.0000',
            'EstimasiHargaSatuan' => '250000.00',
            'Alasan' => 'Kapasitas komputasi produksi telah mencapai batas aman.',
            'TahunKebutuhan' => 2026,
            'Prioritas' => UsulanAset::PRIORITAS_NORMAL,
        ], $pengaju->Id);
        $aksi->submit($usulan);
        $penilaian = $aksi->nilai($usulan, ['Kriteria' => 'Dampak operasional', 'Bobot' => '2.5000', 'Nilai' => '80.0000', 'Prioritas' => UsulanAset::PRIORITAS_TINGGI], $pengaju->Id);

        $this->assertSame('200.0000', $penilaian->Skor);
        $menunggu = $aksi->ajukanPersetujuan($usulan, $pengaju->Id);
        $this->assertSame(UsulanAset::STATUS_MENUNGGU_PERSETUJUAN, $menunggu->Status);

        $permintaan = PermintaanPersetujuan::query()->where('AlurPersetujuanId', $alur->Id)->where('EntitasId', $usulan->Id)->firstOrFail();
        $hasil = app(SetujuiPermintaanPersetujuan::class)->jalankan($permintaan, $penyetuju, 'Layak dan mendesak.');

        $this->assertTrue($hasil['selesai']);
        $this->assertSame(UsulanAset::STATUS_DISETUJUI, $usulan->refresh()->Status);
        $this->assertSame(UsulanAset::PRIORITAS_TINGGI, $usulan->Prioritas);
    }

    public function test_15_05_rencana_dibuat_dari_usulan_dan_total_dihitung_server_side(): void
    {
        [, $pengguna, $unit] = $this->siapkanAktor();
        $this->actingAs($pengguna);
        [, $pos] = $this->buatAnggaranAktif($pengguna, '1000000.00');
        $usulan = $this->buatUsulanDisetujui($unit, $pengguna, '3.0000', '125000.00');
        $aksi = app(KelolaRencanaPengadaan::class);

        $rencana = $aksi->buat([
            'Nama' => 'Pengadaan Infrastruktur Semester I',
            'Tahun' => 2026,
            'PosAnggaranId' => $pos->Id,
            'UsulanAsetIds' => [$usulan->Id],
        ], $pengguna->Id);

        $this->assertSame('375000.00', $rencana->TotalEstimasi);
        $aksi->tambahDetail($rencana, [
            'Deskripsi' => 'Perangkat jaringan tambahan',
            'Jumlah' => '2.0000',
            'Satuan' => 'unit',
            'HargaEstimasi' => '50000.00',
            'BulanRencana' => 6,
        ]);
        $this->assertSame('475000.00', $rencana->refresh()->TotalEstimasi);

        $final = $aksi->finalisasi($rencana);
        $this->assertSame(RencanaPengadaan::STATUS_DIRENCANAKAN, $final->Status);
        $this->assertSame(2, $final->detail()->count());
    }

    public function test_endpoint_menegakkan_izin_penyesuaian_dan_isolasi_tenant(): void
    {
        [$organisasi, $pengguna] = $this->siapkanAktor();
        $this->actingAs($pengguna);
        [, $pos] = $this->buatAnggaranAktif($pengguna, '1000.00');

        $this->post(route('perencanaanPengadaan.pos.transaksi.store', $pos), $this->dataTransaksi(TransaksiAnggaran::JENIS_PENYESUAIAN, '10.00', 'Koreksi'))
            ->assertForbidden();
        $this->assertSame(0, $pos->transaksi()->count());

        $penggunaPenyesuai = $this->buatPengguna($organisasi, ['Pengadaan.Kelola', 'Anggaran.Sesuaikan']);
        $this->actingAs($penggunaPenyesuai)
            ->post(route('perencanaanPengadaan.pos.transaksi.store', $pos), $this->dataTransaksi(TransaksiAnggaran::JENIS_PENYESUAIAN, '10.00', 'Koreksi'))
            ->assertRedirect();
        $this->assertDatabaseHas('TransaksiAnggaran', ['PosAnggaranId' => $pos->Id, 'Jenis' => TransaksiAnggaran::JENIS_PENYESUAIAN, 'Jumlah' => '10.00']);

        $organisasiLain = $this->buatOrganisasi('LAIN');
        app(KonteksOrganisasi::class)->tetapkan($organisasiLain->Id);
        $anggaranLain = Anggaran::create(['Kode' => 'LAIN-2026', 'Nama' => 'Anggaran Tenant Lain', 'Tahun' => 2026, 'MataUang' => 'IDR', 'Jumlah' => '1000.00', 'Status' => Anggaran::STATUS_DRAFT]);

        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $this->get(route('perencanaanPengadaan.anggaran.show', $anggaranLain->Id))->assertNotFound();
    }

    public function test_halaman_operasional_fase_15_dapat_dirender(): void
    {
        [, $pengguna, $unit] = $this->siapkanAktor();
        $this->actingAs($pengguna);
        [$anggaran, $pos] = $this->buatAnggaranAktif($pengguna, '1000000.00');
        $usulan = $this->buatUsulanDisetujui($unit, $pengguna, '1.0000', '100000.00');
        $rencana = app(KelolaRencanaPengadaan::class)->buat([
            'Nama' => 'Rencana Render',
            'Tahun' => 2026,
            'PosAnggaranId' => $pos->Id,
            'UsulanAsetIds' => [$usulan->Id],
        ], $pengguna->Id);

        $this->get(route('perencanaanPengadaan.anggaran.index'))->assertOk();
        $this->get(route('perencanaanPengadaan.anggaran.show', $anggaran))->assertOk();
        $this->get(route('perencanaanPengadaan.usulan.index'))->assertOk();
        $this->get(route('perencanaanPengadaan.usulan.show', $usulan))->assertOk();
        $this->get(route('perencanaanPengadaan.rencana.index'))->assertOk();
        $this->get(route('perencanaanPengadaan.rencana.show', $rencana))->assertOk();
    }

    /**
     * @param  list<string>  $izin
     * @return array{Organisasi, Pengguna, UnitOrganisasi}
     */
    private function siapkanAktor(array $izin = ['Pengadaan.Kelola']): array
    {
        $organisasi = $this->buatOrganisasi('PP');
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $unit = UnitOrganisasi::create(['Kode' => 'UNIT-'.uniqid(), 'Nama' => 'Unit Pengadaan', 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, $izin);

        return [$organisasi, $pengguna, $unit];
    }

    private function buatOrganisasi(string $kode): Organisasi
    {
        return Organisasi::create(['Kode' => $kode.'-'.uniqid(), 'Nama' => 'Organisasi '.$kode.' '.uniqid(), 'Status' => 'Aktif']);
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

    /**
     * @return array{Anggaran, PosAnggaran}
     */
    private function buatAnggaranAktif(Pengguna $pengguna, string $jumlah): array
    {
        $anggaran = app(KelolaAnggaran::class)->buat([
            'Kode' => 'ANG-'.uniqid(),
            'Nama' => 'Anggaran Uji',
            'Tahun' => 2026,
            'MataUang' => 'IDR',
            'Jumlah' => $jumlah,
        ]);
        $pos = app(KelolaPosAnggaran::class)->buat($anggaran, ['Kode' => 'POS-'.uniqid(), 'Nama' => 'Pos Uji', 'Jumlah' => $jumlah]);
        app(KelolaAnggaran::class)->ajukan($anggaran, $pengguna->Id);

        return [$anggaran->refresh(), $pos->refresh()];
    }

    /**
     * @return array<string, string>
     */
    private function dataTransaksi(string $jenis, string $jumlah, ?string $keterangan = null): array
    {
        return array_filter([
            'Jenis' => $jenis,
            'Jumlah' => $jumlah,
            'Tanggal' => '2026-09-21',
            'Keterangan' => $keterangan,
        ], fn (?string $nilai): bool => $nilai !== null);
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

    private function buatUsulanDisetujui(UnitOrganisasi $unit, Pengguna $pengguna, string $jumlah, string $harga): UsulanAset
    {
        return UsulanAset::create([
            'Nomor' => 'USL-'.uniqid(),
            'UnitOrganisasiId' => $unit->Id,
            'NamaKebutuhan' => 'Perangkat Uji',
            'Jumlah' => $jumlah,
            'EstimasiHargaSatuan' => $harga,
            'Alasan' => 'Kebutuhan pengujian rencana.',
            'Prioritas' => UsulanAset::PRIORITAS_TINGGI,
            'Status' => UsulanAset::STATUS_DISETUJUI,
            'DiajukanOleh' => $pengguna->Id,
            'DiajukanPada' => now(),
        ]);
    }
}
