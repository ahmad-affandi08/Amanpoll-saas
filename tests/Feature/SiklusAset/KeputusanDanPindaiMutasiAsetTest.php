<?php

declare(strict_types=1);

namespace Tests\Feature\SiklusAset;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Application\Services\PencariAsetLewatKode;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
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
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Domain\SiklusAset\Domain\Enums\JenisPermintaanMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusDetailMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Keputusan per aset dan verifikasi pengambilan lewat pemindaian kode.
 */
class KeputusanDanPindaiMutasiAsetTest extends TestCase
{
    use RefreshDatabase;

    private const IZIN_PENUH = ['Aset.Lihat', 'Aset.Buat', 'Aset.Ubah', 'Aset.Hapus'];

    private function konteks(): KonteksOrganisasi
    {
        return app(KonteksOrganisasi::class);
    }

    private function buatOrganisasi(): Organisasi
    {
        return Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
    }

    /** @param  list<string>  $kodeIzin */
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

    private function buatAlur(Organisasi $organisasi, Pengguna $penyetuju): void
    {
        $this->konteks()->tetapkan($organisasi->Id);

        $alur = AlurPersetujuan::create([
            'Kode' => 'ALUR-'.uniqid(),
            'Nama' => 'Alur Mutasi',
            'JenisEntitas' => 'PermintaanMutasiAset',
            'Aktif' => true,
        ]);

        TahapPersetujuan::create([
            'AlurPersetujuanId' => $alur->Id,
            'Urutan' => 1,
            'Nama' => 'Tahap 1',
            'JenisPenyetuju' => 'Pengguna',
            'PenggunaId' => $penyetuju->Id,
            'JumlahMinimumPenyetuju' => 1,
            'BolehMenyetujuiSendiri' => true,
        ]);
    }

    private function buatNomorDokumen(Organisasi $organisasi): void
    {
        $this->konteks()->tetapkan($organisasi->Id);

        NomorDokumen::create([
            'JenisDokumen' => 'PermintaanMutasiAset',
            'Awalan' => 'MUT',
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'NomorTerakhir' => 0,
            'ResetPeriode' => 'Tahunan',
            'PeriodeAktif' => '',
        ]);
    }

    /** @param  array<string, mixed>  $atribut */
    private function buatAset(Organisasi $organisasi, Lokasi $lokasi, array $atribut = []): Aset
    {
        $this->konteks()->tetapkan($organisasi->Id);

        $kategori = KategoriAset::create(['Nama' => 'Kategori '.uniqid(), 'Kode' => 'KAT-'.uniqid()]);

        return Aset::create(array_merge([
            'KategoriAsetId' => $kategori->Id,
            'LokasiId' => $lokasi->Id,
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => 'Aset '.uniqid(),
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
        ], $atribut));
    }

    /**
     * Membawa satu permintaan mutasi sampai berstatus Disetujui.
     *
     * @param  list<Aset>  $aset
     */
    private function permintaanDisetujui(
        Organisasi $organisasi,
        Pengguna $peminta,
        Pengguna $penyetuju,
        Lokasi $lokasiTujuan,
        array $aset,
    ): PermintaanMutasiAset {
        $this->actingAs($peminta)->post('/mutasi-aset', [
            'JenisMutasi' => JenisPermintaanMutasiAset::AntarLokasi->value,
            'LokasiTujuanId' => $lokasiTujuan->Id,
        ])->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $permintaan = PermintaanMutasiAset::query()
            ->where('Status', StatusPermintaanMutasiAset::Draft->value)
            ->firstOrFail();

        foreach ($aset as $satu) {
            $this->actingAs($peminta)
                ->post("/mutasi-aset/{$permintaan->Id}/detail", ['AsetId' => $satu->Id])
                ->assertRedirect();
        }

        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/submit")->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $permintaanPersetujuan = PermintaanPersetujuan::query()
            ->where('EntitasId', $permintaan->Id)
            ->firstOrFail();

        $this->actingAs($penyetuju)
            ->post("/persetujuan/permintaan/{$permintaanPersetujuan->Id}/setujui")
            ->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);

        return $permintaan->refresh();
    }

    private function detailUntuk(PermintaanMutasiAset $permintaan, Aset $aset): DetailMutasiAset
    {
        $this->konteks()->tetapkan($permintaan->OrganisasiId);

        return DetailMutasiAset::query()
            ->where('PermintaanMutasiAsetId', $permintaan->Id)
            ->where('AsetId', $aset->Id)
            ->firstOrFail();
    }

    public function test_aset_yang_ditolak_tidak_ikut_berpindah_saat_eksekusi(): void
    {
        $organisasi = $this->buatOrganisasi();
        $peminta = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $penyetuju = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiAsal = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $asetIkut = $this->buatAset($organisasi, $lokasiAsal);
        $asetDitolak = $this->buatAset($organisasi, $lokasiAsal);
        $this->buatNomorDokumen($organisasi);
        $this->buatAlur($organisasi, $penyetuju);

        $permintaan = $this->permintaanDisetujui($organisasi, $peminta, $penyetuju, $lokasiTujuan, [$asetIkut, $asetDitolak]);

        $detailDitolak = $this->detailUntuk($permintaan, $asetDitolak);
        $this->actingAs($peminta)->post("/mutasi-aset/detail/{$detailDitolak->Id}/putuskan", [
            'Disetujui' => false,
            'AlasanPenolakan' => 'Alat sedang dipakai tindakan.',
        ])->assertRedirect();

        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/eksekusi")->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $asetIkut->refresh();
        $asetDitolak->refresh();

        $this->assertSame($lokasiTujuan->Id, $asetIkut->LokasiId, 'Aset yang disetujui seharusnya pindah.');
        $this->assertSame($lokasiAsal->Id, $asetDitolak->LokasiId, 'Aset yang ditolak seharusnya tetap di lokasi asal.');

        $this->assertSame(
            StatusDetailMutasiAset::Selesai->value,
            $this->detailUntuk($permintaan, $asetIkut)->Status,
        );
        $this->assertSame(
            StatusDetailMutasiAset::Ditolak->value,
            $this->detailUntuk($permintaan, $asetDitolak)->Status,
        );
    }

    public function test_detail_yang_disetujui_eksplisit_tetap_ikut_dieksekusi(): void
    {
        $organisasi = $this->buatOrganisasi();
        $peminta = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $penyetuju = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiAsal = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $aset = $this->buatAset($organisasi, $lokasiAsal);
        $this->buatNomorDokumen($organisasi);
        $this->buatAlur($organisasi, $penyetuju);

        $permintaan = $this->permintaanDisetujui($organisasi, $peminta, $penyetuju, $lokasiTujuan, [$aset]);
        $detail = $this->detailUntuk($permintaan, $aset);

        $this->actingAs($peminta)->post("/mutasi-aset/detail/{$detail->Id}/putuskan", [
            'Disetujui' => true,
        ])->assertRedirect();

        $this->assertSame(StatusDetailMutasiAset::Disetujui->value, $this->detailUntuk($permintaan, $aset)->Status);

        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/eksekusi")->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $aset->refresh();
        $this->assertSame($lokasiTujuan->Id, $aset->LokasiId);
    }

    public function test_penolakan_tanpa_alasan_ditolak_dan_status_detail_tidak_berubah(): void
    {
        $organisasi = $this->buatOrganisasi();
        $peminta = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $penyetuju = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiAsal = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $aset = $this->buatAset($organisasi, $lokasiAsal);
        $this->buatNomorDokumen($organisasi);
        $this->buatAlur($organisasi, $penyetuju);

        $permintaan = $this->permintaanDisetujui($organisasi, $peminta, $penyetuju, $lokasiTujuan, [$aset]);
        $detail = $this->detailUntuk($permintaan, $aset);

        $this->actingAs($peminta)->post("/mutasi-aset/detail/{$detail->Id}/putuskan", [
            'Disetujui' => false,
        ])->assertSessionHasErrors('AlasanPenolakan');

        $this->assertSame(StatusDetailMutasiAset::Menunggu->value, $this->detailUntuk($permintaan, $aset)->Status);
    }

    public function test_pindai_mencatat_pemindai_dan_waktunya(): void
    {
        $organisasi = $this->buatOrganisasi();
        $peminta = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $penyetuju = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiAsal = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $aset = $this->buatAset($organisasi, $lokasiAsal, ['KodeQr' => 'QR-'.uniqid()]);
        $this->buatNomorDokumen($organisasi);
        $this->buatAlur($organisasi, $penyetuju);

        $permintaan = $this->permintaanDisetujui($organisasi, $peminta, $penyetuju, $lokasiTujuan, [$aset]);

        $this->actingAs($peminta)
            ->post("/mutasi-aset/{$permintaan->Id}/pindai", ['Kode' => $aset->KodeQr])
            ->assertRedirect();

        $detail = $this->detailUntuk($permintaan, $aset);
        $this->assertSame($peminta->Id, $detail->DipindaiOleh);
        $this->assertNotNull($detail->DipindaiPada);
    }

    public function test_pindai_kode_di_luar_permintaan_ditolak_dan_tidak_mencatat_apa_pun(): void
    {
        $organisasi = $this->buatOrganisasi();
        $peminta = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $penyetuju = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiAsal = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $asetDiminta = $this->buatAset($organisasi, $lokasiAsal, ['KodeQr' => 'QR-'.uniqid()]);
        $asetLain = $this->buatAset($organisasi, $lokasiAsal, ['KodeQr' => 'QR-'.uniqid()]);
        $this->buatNomorDokumen($organisasi);
        $this->buatAlur($organisasi, $penyetuju);

        $permintaan = $this->permintaanDisetujui($organisasi, $peminta, $penyetuju, $lokasiTujuan, [$asetDiminta]);

        $this->actingAs($peminta)
            ->post("/mutasi-aset/{$permintaan->Id}/pindai", ['Kode' => $asetLain->KodeQr])
            ->assertStatus(422);

        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertSame(
            0,
            DetailMutasiAset::query()
                ->where('PermintaanMutasiAsetId', $permintaan->Id)
                ->whereNotNull('DipindaiPada')
                ->count(),
            'Kode di luar permintaan tidak boleh meninggalkan jejak pemindaian.',
        );
    }

    public function test_pindai_aset_yang_sudah_ditolak_ditahan(): void
    {
        $organisasi = $this->buatOrganisasi();
        $peminta = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $penyetuju = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiAsal = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $aset = $this->buatAset($organisasi, $lokasiAsal, ['KodeQr' => 'QR-'.uniqid()]);
        $this->buatNomorDokumen($organisasi);
        $this->buatAlur($organisasi, $penyetuju);

        $permintaan = $this->permintaanDisetujui($organisasi, $peminta, $penyetuju, $lokasiTujuan, [$aset]);
        $detail = $this->detailUntuk($permintaan, $aset);

        $this->actingAs($peminta)->post("/mutasi-aset/detail/{$detail->Id}/putuskan", [
            'Disetujui' => false,
            'AlasanPenolakan' => 'Tidak boleh keluar ruangan.',
        ])->assertRedirect();

        $this->actingAs($peminta)
            ->post("/mutasi-aset/{$permintaan->Id}/pindai", ['Kode' => $aset->KodeQr])
            ->assertStatus(422);

        $this->assertNull($this->detailUntuk($permintaan, $aset)->DipindaiPada);
    }

    /**
     * Penjaga langsung pada pencarinya.
     *
     * Lewat HTTP kode tenant lain selalu berujung 422 -- ditolak pemeriksaan
     * "tidak termasuk dalam permintaan ini" -- entah asetnya terjaring atau
     * tidak, jadi status jawabannya tidak membuktikan batas tenant. Yang
     * membuktikannya hanya hasil pencarian itu sendiri.
     */
    public function test_pencari_kode_tidak_menembus_batas_organisasi(): void
    {
        $organisasi = $this->buatOrganisasi();
        $organisasiLain = $this->buatOrganisasi();

        $this->konteks()->tetapkan($organisasiLain->Id);
        $lokasiLain = Lokasi::create(['OrganisasiId' => $organisasiLain->Id, 'Nama' => 'X', 'Kode' => 'X-'.uniqid()]);
        $kodeTetangga = 'BC-'.uniqid();
        $asetTetangga = $this->buatAset($organisasiLain, $lokasiLain, ['KodeBatang' => $kodeTetangga]);

        $pencari = app(PencariAsetLewatKode::class);

        // Pembanding: di organisasinya sendiri kode itu memang ketemu, jadi
        // hasil null di bawah benar-benar karena batas tenant.
        $this->konteks()->tetapkan($organisasiLain->Id);
        $this->assertSame($asetTetangga->Id, $pencari->cari($kodeTetangga)?->Id);

        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertNull(
            $pencari->cari($kodeTetangga),
            'Kode barang milik organisasi lain tidak boleh terjaring.',
        );
    }

    /** Jalur HTTP-nya: kode asing ditolak dan tidak meninggalkan jejak pemindaian. */
    public function test_kode_milik_organisasi_lain_tidak_dikenali(): void
    {
        $organisasi = $this->buatOrganisasi();
        $organisasiLain = $this->buatOrganisasi();
        $peminta = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $penyetuju = $this->buatPengguna($organisasi, self::IZIN_PENUH);

        $this->konteks()->tetapkan($organisasiLain->Id);
        $lokasiLain = Lokasi::create(['OrganisasiId' => $organisasiLain->Id, 'Nama' => 'X', 'Kode' => 'X-'.uniqid()]);
        $kodeTetangga = 'BC-'.uniqid();
        $this->buatAset($organisasiLain, $lokasiLain, ['KodeBatang' => $kodeTetangga]);

        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiAsal = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $aset = $this->buatAset($organisasi, $lokasiAsal, ['KodeQr' => 'QR-'.uniqid()]);
        $this->buatNomorDokumen($organisasi);
        $this->buatAlur($organisasi, $penyetuju);

        $permintaan = $this->permintaanDisetujui($organisasi, $peminta, $penyetuju, $lokasiTujuan, [$aset]);

        $this->actingAs($peminta)
            ->post("/mutasi-aset/{$permintaan->Id}/pindai", ['Kode' => $kodeTetangga])
            ->assertStatus(422);

        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertSame(
            0,
            DetailMutasiAset::query()->whereNotNull('DipindaiPada')->count(),
        );
    }

    public function test_reposisi_menolak_perpindahan_ke_unit_lain(): void
    {
        $organisasi = $this->buatOrganisasi();
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $unitAsal = UnitOrganisasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'Unit A', 'Kode' => 'UA-'.uniqid(), 'Status' => 'Aktif']);
        $unitLain = UnitOrganisasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'Unit B', 'Kode' => 'UB-'.uniqid(), 'Status' => 'Aktif']);
        $this->buatNomorDokumen($organisasi);

        $this->actingAs($pengguna)->post('/mutasi-aset', [
            'JenisMutasi' => JenisPermintaanMutasiAset::Reposisi->value,
            'LokasiTujuanId' => $lokasiTujuan->Id,
            'UnitAsalId' => $unitAsal->Id,
            'UnitTujuanId' => $unitLain->Id,
        ])->assertStatus(422);

        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertSame(0, PermintaanMutasiAset::query()->count());

        // Reposisi di dalam unit yang sama tetap diterima.
        $this->actingAs($pengguna)->post('/mutasi-aset', [
            'JenisMutasi' => JenisPermintaanMutasiAset::Reposisi->value,
            'LokasiTujuanId' => $lokasiTujuan->Id,
            'UnitAsalId' => $unitAsal->Id,
            'UnitTujuanId' => $unitAsal->Id,
        ])->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertSame(1, PermintaanMutasiAset::query()->count());
    }

    public function test_akuisisi_wajib_menyebutkan_unit_tujuan(): void
    {
        $organisasi = $this->buatOrganisasi();
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $unit = UnitOrganisasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'Unit A', 'Kode' => 'UA-'.uniqid(), 'Status' => 'Aktif']);
        $this->buatNomorDokumen($organisasi);

        $this->actingAs($pengguna)->post('/mutasi-aset', [
            'JenisMutasi' => JenisPermintaanMutasiAset::Akuisisi->value,
            'LokasiTujuanId' => $lokasiTujuan->Id,
        ])->assertStatus(422);

        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertSame(0, PermintaanMutasiAset::query()->count());

        $this->actingAs($pengguna)->post('/mutasi-aset', [
            'JenisMutasi' => JenisPermintaanMutasiAset::Akuisisi->value,
            'LokasiTujuanId' => $lokasiTujuan->Id,
            'UnitTujuanId' => $unit->Id,
        ])->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertSame(1, PermintaanMutasiAset::query()->count());
    }
}
