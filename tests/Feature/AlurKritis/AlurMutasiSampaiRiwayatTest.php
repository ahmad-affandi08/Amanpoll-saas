<?php

declare(strict_types=1);

namespace Tests\Feature\AlurKritis;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Application\Actions\BuatAset;
use App\Domain\Aset\Domain\Enums\JenisRiwayatLokasiAset;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use App\Domain\Persetujuan\Domain\Enums\StatusPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\SiklusAset\Domain\Enums\JenisPermintaanMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusDetailMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusSerahTerimaAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailSerahTerimaAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FASE 26.03 — alur kritis "Mutasi → approval → handover → history".
 *
 * Satu permintaan mutasi berisi dua aset dibawa lewat rute HTTP: diajukan,
 * disetujui lewat mesin persetujuan, satu aset ditolak pemegangnya, aset yang
 * ikut dipindai saat diambil, diserahterimakan, lalu dieksekusi. Di tiap
 * langkah diperiksa bahwa langkah berikutnya tertahan sebelum waktunya dan
 * bahwa riwayat lokasi hanya bertambah untuk aset yang benar-benar pindah.
 */
final class AlurMutasiSampaiRiwayatTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    protected function setUp(): void
    {
        parent::setUp();

        // String polos akan diurai memakai zona waktu aplikasi; UTC ditulis eksplisit.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01 01:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_mutasi_disetujui_diserahterimakan_lalu_dieksekusi_mencatat_riwayat(): void
    {
        $this->organisasi = Organisasi::create(['Kode' => 'ORG-UTM', 'Nama' => 'Organisasi Utama', 'Status' => 'Aktif']);
        $petugas = $this->buatPengguna($this->organisasi, ['Aset.Lihat', 'Aset.Ubah']);
        $kepala = $this->buatPengguna($this->organisasi, []);
        $penerima = $this->buatPengguna($this->organisasi, []);

        $this->konteksUtama();
        $gudangAset = Lokasi::create(['Kode' => 'LOK-GDG', 'Nama' => 'Gudang Aset']);
        $ruangIgd = Lokasi::create(['Kode' => 'LOK-IGD', 'Nama' => 'Ruang IGD']);
        foreach (['PermintaanMutasiAset' => 'MUT', 'SerahTerimaAset' => 'BAST'] as $jenisDokumen => $awalan) {
            NomorDokumen::create([
                'JenisDokumen' => $jenisDokumen,
                'Awalan' => $awalan,
                'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
                'NomorTerakhir' => 0,
                'ResetPeriode' => 'Tahunan',
                'PeriodeAktif' => '',
            ]);
        }
        $alur = AlurPersetujuan::create(['Kode' => 'ALUR-MUT', 'Nama' => 'Persetujuan Mutasi', 'JenisEntitas' => 'PermintaanMutasiAset', 'Aktif' => false]);
        TahapPersetujuan::create([
            'AlurPersetujuanId' => $alur->Id,
            'Urutan' => 1,
            'Nama' => 'Kepala Instalasi',
            'JenisPenyetuju' => 'Pengguna',
            'PenggunaId' => $kepala->Id,
            'JumlahMinimumPenyetuju' => 1,
            'BolehMenyetujuiSendiri' => false,
        ]);
        $alur->update(['Aktif' => true]);

        // Aset didaftarkan lewat use-case supaya riwayat registrasinya ikut tertulis.
        $kategori = KategoriAset::create(['Kode' => 'KAT-MON', 'Nama' => 'Monitor Pasien']);
        $monitor = $this->daftarkanAset($kategori, $gudangAset, 'AST-MON-001', 'Monitor Pasien A', $petugas);
        $defibrilator = $this->daftarkanAset($kategori, $gudangAset, 'AST-DEF-001', 'Defibrilator B', $petugas);

        $pembanding = $this->siapkanOrganisasiPembanding();

        // 1. Draft permintaan mutasi dengan dua aset.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-02 02:00:00', 'UTC'));
        $this->actingAs($petugas)->post(route('mutasiAset.store'), [
            'JenisMutasi' => JenisPermintaanMutasiAset::AntarLokasi->value,
            'LokasiAsalId' => $gudangAset->Id,
            'LokasiTujuanId' => $ruangIgd->Id,
            'Alasan' => 'Penambahan kapasitas IGD.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->konteksUtama();
        $permintaan = PermintaanMutasiAset::query()->firstOrFail();
        $this->assertSame(StatusPermintaanMutasiAset::Draft->value, $permintaan->Status);
        $this->assertSame('MUT-2026-0001', $permintaan->Nomor);

        foreach ([$monitor, $defibrilator] as $aset) {
            $this->actingAs($petugas)->post(route('mutasiAset.detail.store', $permintaan->Id), ['AsetId' => $aset->Id])
                ->assertRedirect()->assertSessionHasNoErrors();
        }

        // Draft belum boleh dipindai maupun dieksekusi.
        $this->actingAs($petugas)->post(route('mutasiAset.pindai', $permintaan->Id), ['Kode' => $monitor->KodeQr])->assertStatus(422);
        $this->actingAs($petugas)->post(route('mutasiAset.eksekusi', $permintaan->Id))->assertStatus(422);

        // 2. Submit → menunggu persetujuan.
        $this->actingAs($petugas)->post(route('mutasiAset.submit', $permintaan->Id))->assertRedirect();
        $this->konteksUtama();
        $this->assertSame(StatusPermintaanMutasiAset::Menunggu->value, $permintaan->refresh()->Status);
        $persetujuan = PermintaanPersetujuan::query()
            ->where('JenisEntitas', 'PermintaanMutasiAset')
            ->where('EntitasId', $permintaan->Id)
            ->firstOrFail();
        $this->assertSame(StatusPermintaanPersetujuan::Menunggu->value, $persetujuan->Status);

        // Selama menunggu, eksekusi tertahan dan tidak ada aset yang bergerak.
        $this->actingAs($petugas)->post(route('mutasiAset.eksekusi', $permintaan->Id))->assertStatus(422);
        $this->konteksUtama();
        $this->assertSame($gudangAset->Id, $monitor->refresh()->LokasiId);
        $this->assertSame(0, RiwayatLokasiAset::query()->where('JenisPerpindahan', JenisRiwayatLokasiAset::Mutasi->value)->count());

        // Peminta tidak berhak menyetujui sendiri; organisasi lain bahkan tidak melihat permintaannya.
        $this->actingAs($petugas)->post(route('persetujuan.permintaan.setujui', $persetujuan->Id))->assertForbidden();
        $this->actingAs($pembanding['pengguna'])->post(route('persetujuan.permintaan.setujui', $persetujuan->Id))->assertNotFound();
        $this->actingAs($pembanding['pengguna'])->get(route('mutasiAset.show', $permintaan->Id))->assertNotFound();
        $this->konteksUtama();
        $this->assertSame(StatusPermintaanMutasiAset::Menunggu->value, $permintaan->refresh()->Status);

        // 3. Persetujuan oleh kepala.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-02 04:00:00', 'UTC'));
        $this->actingAs($kepala)->post(route('persetujuan.permintaan.setujui', $persetujuan->Id), ['Catatan' => 'Silakan.'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->konteksUtama();
        $permintaan->refresh();
        $this->assertSame(StatusPermintaanMutasiAset::Disetujui->value, $permintaan->Status);
        $this->assertSame('2026-04-02 04:00:00', $permintaan->DisetujuiPada?->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(StatusPermintaanPersetujuan::Disetujui->value, $persetujuan->refresh()->Status);

        // Persetujuan hanya otorisasi: belum ada aset yang pindah.
        $this->assertSame($gudangAset->Id, $monitor->refresh()->LokasiId);

        // 4. Pemegang menolak defibrilator; monitor diverifikasi lewat pindai QR saat diambil.
        $detailDefibrilator = $this->detailMutasi($permintaan, $defibrilator);
        $this->actingAs($petugas)->post(route('mutasiAset.detail.putuskan', $detailDefibrilator->Id), [
            'Disetujui' => false,
            'AlasanPenolakan' => 'Masih dipakai di ruang tindakan.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-02 05:00:00', 'UTC'));
        $this->actingAs($petugas)->post(route('mutasiAset.pindai', $permintaan->Id), ['Kode' => $monitor->KodeQr])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($petugas)->post(route('mutasiAset.pindai', $permintaan->Id), ['Kode' => $defibrilator->KodeQr])
            ->assertStatus(422);

        $detailMonitor = $this->detailMutasi($permintaan, $monitor);
        $this->assertSame($petugas->Id, $detailMonitor->DipindaiOleh);
        $this->assertSame('2026-04-02 05:00:00', $detailMonitor->DipindaiPada?->utc()->format('Y-m-d H:i:s'));
        $detailDefibrilator = $this->detailMutasi($permintaan, $defibrilator);
        $this->assertSame(StatusDetailMutasiAset::Ditolak->value, $detailDefibrilator->Status);
        $this->assertNull($detailDefibrilator->DipindaiPada);

        // 5. Berita acara serah terima untuk aset yang diambil.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-02 06:00:00', 'UTC'));
        $this->actingAs($petugas)->post(route('serahTerimaAset.store'), [
            'PermintaanMutasiAsetId' => $permintaan->Id,
            'Jenis' => 'Mutasi',
            'PihakMenyerahkan' => $petugas->Id,
            'PihakMenerima' => $penerima->Id,
            'Catatan' => 'Serah terima ke IGD.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->konteksUtama();
        $serahTerima = SerahTerimaAset::query()->where('PermintaanMutasiAsetId', $permintaan->Id)->firstOrFail();
        $this->assertSame(StatusSerahTerimaAset::Diserahkan->value, $serahTerima->Status);
        $this->assertSame('BAST-2026-0001', $serahTerima->Nomor);

        $this->actingAs($petugas)->post(route('serahTerimaAset.detail.store', $serahTerima->Id), [
            'AsetId' => $monitor->Id,
            'KondisiSaatDiserahkan' => KondisiAset::Baik->value,
        ])->assertRedirect()->assertSessionHasNoErrors();

        // Penerimaan wajib mengonfirmasi kondisi setiap aset di dokumen.
        $this->actingAs($petugas)->post(route('serahTerimaAset.terima', $serahTerima->Id), [
            'Detail' => [['AsetId' => $defibrilator->Id, 'KondisiSaatDiterima' => KondisiAset::Baik->value]],
        ])->assertStatus(422);
        $this->konteksUtama();
        $this->assertSame(StatusSerahTerimaAset::Diserahkan->value, $serahTerima->refresh()->Status);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-02 07:00:00', 'UTC'));
        $this->actingAs($petugas)->post(route('serahTerimaAset.terima', $serahTerima->Id), [
            'Detail' => [['AsetId' => $monitor->Id, 'KondisiSaatDiterima' => KondisiAset::PerluPerhatian->value]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->konteksUtama();
        $serahTerima->refresh();
        $this->assertSame(StatusSerahTerimaAset::Diterima->value, $serahTerima->Status);
        $this->assertSame('2026-04-02 07:00:00', $serahTerima->DiterimaPada?->utc()->format('Y-m-d H:i:s'));
        $this->assertSame($penerima->Id, $serahTerima->PihakMenerima);
        $this->assertSame(
            KondisiAset::PerluPerhatian->value,
            DetailSerahTerimaAset::query()->where('SerahTerimaAsetId', $serahTerima->Id)->where('AsetId', $monitor->Id)->value('KondisiSaatDiterima'),
        );
        $this->assertSame(KondisiAset::PerluPerhatian->value, $monitor->refresh()->Kondisi);
        $this->assertSame(KondisiAset::Baik->value, $defibrilator->refresh()->Kondisi);

        // 6. Eksekusi memindahkan hanya aset yang tidak ditolak.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-02 08:00:00', 'UTC'));
        $this->actingAs($petugas)->post(route('mutasiAset.eksekusi', $permintaan->Id))->assertRedirect()->assertSessionHasNoErrors();

        $this->konteksUtama();
        $permintaan->refresh();
        $this->assertSame(StatusPermintaanMutasiAset::Selesai->value, $permintaan->Status);
        $this->assertSame('2026-04-02 08:00:00', $permintaan->SelesaiPada?->utc()->format('Y-m-d H:i:s'));
        $this->assertSame($ruangIgd->Id, $monitor->refresh()->LokasiId);
        $this->assertSame($gudangAset->Id, $defibrilator->refresh()->LokasiId);
        $this->assertSame(StatusDetailMutasiAset::Selesai->value, $this->detailMutasi($permintaan, $monitor)->Status);
        $this->assertSame(StatusDetailMutasiAset::Ditolak->value, $this->detailMutasi($permintaan, $defibrilator)->Status);

        // 7. Riwayat lokasi terbaca lewat rute kartu riwayat aset, terbaru lebih dulu.
        $riwayatMonitor = $this->actingAs($petugas)->getJson(route('aset.riwayat-lokasi.index', $monitor->Id))->assertOk()->json();
        $this->assertCount(2, $riwayatMonitor);
        $this->assertSame(JenisRiwayatLokasiAset::Mutasi->value, $riwayatMonitor[0]['JenisPerpindahan']);
        $this->assertSame($gudangAset->Id, $riwayatMonitor[0]['LokasiAsalId']);
        $this->assertSame($ruangIgd->Id, $riwayatMonitor[0]['LokasiTujuanId']);
        $this->assertSame('Penambahan kapasitas IGD.', $riwayatMonitor[0]['Alasan']);
        $this->assertSame('2026-04-02T08:00:00+00:00', CarbonImmutable::parse($riwayatMonitor[0]['DipindahkanPada'])->utc()->toIso8601String());
        $this->assertSame(JenisRiwayatLokasiAset::Registrasi->value, $riwayatMonitor[1]['JenisPerpindahan']);
        $this->assertNull($riwayatMonitor[1]['LokasiAsalId']);
        $this->assertSame($gudangAset->Id, $riwayatMonitor[1]['LokasiTujuanId']);

        $riwayatDefibrilator = $this->actingAs($petugas)->getJson(route('aset.riwayat-lokasi.index', $defibrilator->Id))->assertOk()->json();
        $this->assertCount(1, $riwayatDefibrilator);
        $this->assertSame(JenisRiwayatLokasiAset::Registrasi->value, $riwayatDefibrilator[0]['JenisPerpindahan']);

        $this->konteksUtama();
        $riwayatMutasi = RiwayatLokasiAset::query()->where('AsetId', $monitor->Id)->where('JenisPerpindahan', JenisRiwayatLokasiAset::Mutasi->value)->firstOrFail();
        $this->assertSame($petugas->Id, $riwayatMutasi->DipindahkanOleh);

        // Eksekusi ulang tidak menggandakan riwayat.
        $this->actingAs($petugas)->post(route('mutasiAset.eksekusi', $permintaan->Id))->assertRedirect();
        $this->konteksUtama();
        $this->assertSame(1, RiwayatLokasiAset::query()->where('AsetId', $monitor->Id)->where('JenisPerpindahan', JenisRiwayatLokasiAset::Mutasi->value)->count());

        // Organisasi pembanding tidak tersentuh.
        $this->assertSame($pembanding['lokasi']->Id, DB::table('Aset')->where('Id', $pembanding['aset']->Id)->value('LokasiId'));
        $this->assertSame(1, DB::table('RiwayatLokasiAset')->where('AsetId', $pembanding['aset']->Id)->count());
        $this->assertSame(0, DB::table('PermintaanMutasiAset')->where('OrganisasiId', $pembanding['organisasi']->Id)->count());
        $this->assertSame(0, DB::table('SerahTerimaAset')->where('OrganisasiId', $pembanding['organisasi']->Id)->count());
    }

    /**
     * @return array{organisasi: Organisasi, pengguna: Pengguna, lokasi: Lokasi, aset: Aset}
     */
    private function siapkanOrganisasiPembanding(): array
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-PBD', 'Nama' => 'Organisasi Pembanding', 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, ['Aset.Lihat', 'Aset.Ubah']);

        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $lokasi = Lokasi::create(['Kode' => 'LOK-GDG', 'Nama' => 'Gudang Pembanding']);
        $kategori = KategoriAset::create(['Kode' => 'KAT-MON', 'Nama' => 'Monitor Pasien']);
        $aset = $this->daftarkanAset($kategori, $lokasi, 'AST-MON-001', 'Monitor Pembanding', $pengguna);
        app(KonteksOrganisasi::class)->bersihkan();

        return ['organisasi' => $organisasi, 'pengguna' => $pengguna, 'lokasi' => $lokasi, 'aset' => $aset];
    }

    private function daftarkanAset(KategoriAset $kategori, Lokasi $lokasi, string $kode, string $nama, Pengguna $pencatat): Aset
    {
        return app(BuatAset::class)->jalankan([
            'KategoriAsetId' => $kategori->Id,
            'LokasiId' => $lokasi->Id,
            'KodeAset' => $kode,
            'Nama' => $nama,
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Tinggi->value,
        ], $pencatat->Id);
    }

    private function detailMutasi(PermintaanMutasiAset $permintaan, Aset $aset): DetailMutasiAset
    {
        $this->konteksUtama();

        return DetailMutasiAset::query()
            ->where('PermintaanMutasiAsetId', $permintaan->Id)
            ->where('AsetId', $aset->Id)
            ->firstOrFail();
    }

    private function konteksUtama(): void
    {
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
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
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Petugas Aset']);
            foreach ($kodeIzin as $kode) {
                $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Aset']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            }
            PenggunaPeran::create(['OrganisasiId' => $organisasi->Id, 'PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
        }

        return $pengguna;
    }
}
