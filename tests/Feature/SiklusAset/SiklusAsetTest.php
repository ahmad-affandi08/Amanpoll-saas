<?php

declare(strict_types=1);

namespace Tests\Feature\SiklusAset;

use App\Core\Organisasi\KonteksOrganisasi;
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
use App\Domain\SiklusAset\Domain\Enums\MetodePengajuanPenghapusanAset;
use App\Domain\SiklusAset\Domain\Enums\StatusDetailPenghapusanAset;
use App\Domain\SiklusAset\Domain\Enums\StatusPengajuanPenghapusanAset;
use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusSerahTerimaAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailPenghapusanAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiklusAsetTest extends TestCase
{
    use RefreshDatabase;

    private const IZIN_PENUH = ['Aset.Lihat', 'Aset.Buat', 'Aset.Ubah', 'Aset.Hapus'];

    private function konteks(): KonteksOrganisasi
    {
        return app(KonteksOrganisasi::class);
    }

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

    private function buatAlur(Organisasi $organisasi, string $jenisEntitas, Pengguna $penyetuju): AlurPersetujuan
    {
        $this->konteks()->tetapkan($organisasi->Id);

        $alur = AlurPersetujuan::create([
            'Kode' => 'ALUR-'.uniqid(),
            'Nama' => 'Alur '.$jenisEntitas,
            'JenisEntitas' => $jenisEntitas,
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

        return $alur;
    }

    private function buatNomorDokumen(Organisasi $organisasi, string $jenisDokumen): NomorDokumen
    {
        $this->konteks()->tetapkan($organisasi->Id);

        return NomorDokumen::create([
            'JenisDokumen' => $jenisDokumen,
            'Awalan' => strtoupper(substr($jenisDokumen, 0, 3)),
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'NomorTerakhir' => 0,
            'ResetPeriode' => 'Tahunan',
            'PeriodeAktif' => '',
        ]);
    }

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

    /** Organisasi yang belum mengatur pola nomor tetap bisa membuat permintaan: pola bawaan dipasang otomatis. */
    public function test_buat_permintaan_mutasi_tanpa_nomor_dokumen_diatur_memakai_pola_bawaan(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiAsal = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);

        $response = $this->actingAs($pengguna)->post('/mutasi-aset', [
            'JenisMutasi' => JenisPermintaanMutasiAset::AntarLokasi->value,
            'LokasiAsalId' => $lokasiAsal->Id,
            'LokasiTujuanId' => $lokasiTujuan->Id,
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->konteks()->tetapkan($organisasi->Id);
        $nomor = (string) PermintaanMutasiAset::query()->where('OrganisasiId', $organisasi->Id)->value('Nomor');
        $this->assertMatchesRegularExpression('#^MUT/\d{4}/0001$#', $nomor);
    }

    public function test_alur_lengkap_mutasi_aset_dari_draft_sampai_eksekusi(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $peminta = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $penyetuju = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiAsal = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $aset = $this->buatAset($organisasi, $lokasiAsal);
        $this->buatNomorDokumen($organisasi, 'PermintaanMutasiAset');
        $this->buatAlur($organisasi, 'PermintaanMutasiAset', $penyetuju);

        // Buat draft
        $responseBuat = $this->actingAs($peminta)->post('/mutasi-aset', [
            'JenisMutasi' => JenisPermintaanMutasiAset::AntarLokasi->value,
            'LokasiAsalId' => $lokasiAsal->Id,
            'LokasiTujuanId' => $lokasiTujuan->Id,
        ]);
        $responseBuat->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $permintaan = PermintaanMutasiAset::query()->firstOrFail();
        $this->assertSame(StatusPermintaanMutasiAset::Draft->value, $permintaan->Status);
        $this->assertNotEmpty($permintaan->Nomor);

        // Submit tanpa detail ditolak
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/submit")->assertStatus(422);

        // Tambah detail
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/detail", ['AsetId' => $aset->Id])->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertSame(1, DetailMutasiAset::query()->where('PermintaanMutasiAsetId', $permintaan->Id)->count());

        // Duplikat aset ditolak
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/detail", ['AsetId' => $aset->Id])->assertStatus(409);

        // Submit
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/submit")->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $permintaan->refresh();
        $this->assertSame(StatusPermintaanMutasiAset::Menunggu->value, $permintaan->Status);
        $permintaanPersetujuan = PermintaanPersetujuan::query()->where('EntitasId', $permintaan->Id)->firstOrFail();
        $this->assertSame(StatusPermintaanPersetujuan::Menunggu->value, $permintaanPersetujuan->Status);

        // Approve via mesin Persetujuan generik
        $this->actingAs($penyetuju)->post("/persetujuan/permintaan/{$permintaanPersetujuan->Id}/setujui")->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $permintaan->refresh();
        $this->assertSame(StatusPermintaanMutasiAset::Disetujui->value, $permintaan->Status);
        $this->assertNotNull($permintaan->DisetujuiPada);

        // Eksekusi memindahkan aset + menulis riwayat
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/eksekusi")->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $permintaan->refresh();
        $aset->refresh();
        $this->assertSame(StatusPermintaanMutasiAset::Selesai->value, $permintaan->Status);
        $this->assertSame($lokasiTujuan->Id, $aset->LokasiId);
        $riwayat = RiwayatLokasiAset::query()->where('AsetId', $aset->Id)->where('JenisPerpindahan', JenisRiwayatLokasiAset::Mutasi->value)->first();
        $this->assertNotNull($riwayat);
        $this->assertSame($lokasiAsal->Id, $riwayat->LokasiAsalId);
        $this->assertSame($lokasiTujuan->Id, $riwayat->LokasiTujuanId);

        // Idempotency: eksekusi kedua kali tidak error dan tidak mengubah apa pun
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/eksekusi")->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertSame(1, RiwayatLokasiAset::query()->where('AsetId', $aset->Id)->where('JenisPerpindahan', JenisRiwayatLokasiAset::Mutasi->value)->count());
    }

    public function test_mutasi_aset_ditolak_menyinkronkan_status(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $peminta = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $penyetuju = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasiAsal = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $aset = $this->buatAset($organisasi, $lokasiAsal);
        $this->buatNomorDokumen($organisasi, 'PermintaanMutasiAset');
        $this->buatAlur($organisasi, 'PermintaanMutasiAset', $penyetuju);

        $this->actingAs($peminta)->post('/mutasi-aset', [
            'JenisMutasi' => JenisPermintaanMutasiAset::AntarLokasi->value,
            'LokasiTujuanId' => $lokasiTujuan->Id,
        ]);
        $this->konteks()->tetapkan($organisasi->Id);
        $permintaan = PermintaanMutasiAset::query()->firstOrFail();
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/detail", ['AsetId' => $aset->Id]);
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/submit");

        $this->konteks()->tetapkan($organisasi->Id);
        $permintaanPersetujuan = PermintaanPersetujuan::query()->where('EntitasId', $permintaan->Id)->firstOrFail();
        $this->actingAs($penyetuju)->post("/persetujuan/permintaan/{$permintaanPersetujuan->Id}/tolak", ['Catatan' => 'Tidak sesuai'])->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $permintaan->refresh();
        $this->assertSame(StatusPermintaanMutasiAset::Ditolak->value, $permintaan->Status);

        // Eksekusi ditolak karena status bukan Disetujui
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/eksekusi")->assertStatus(422);
    }

    public function test_batalkan_permintaan_mutasi_draft_dan_menunggu(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $peminta = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $penyetuju = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasi = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $aset = $this->buatAset($organisasi, $lokasi);
        $this->buatNomorDokumen($organisasi, 'PermintaanMutasiAset');
        $this->buatAlur($organisasi, 'PermintaanMutasiAset', $penyetuju);

        $this->actingAs($peminta)->post('/mutasi-aset', ['JenisMutasi' => JenisPermintaanMutasiAset::AntarLokasi->value, 'LokasiTujuanId' => $lokasiTujuan->Id]);
        $this->konteks()->tetapkan($organisasi->Id);
        $permintaan = PermintaanMutasiAset::query()->firstOrFail();

        // Batalkan langsung dari draft
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan->Id}/batalkan")->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $permintaan->refresh();
        $this->assertSame(StatusPermintaanMutasiAset::Dibatalkan->value, $permintaan->Status);

        // Skenario kedua: batalkan setelah submit ikut membatalkan PermintaanPersetujuan
        $this->actingAs($peminta)->post('/mutasi-aset', ['JenisMutasi' => JenisPermintaanMutasiAset::AntarLokasi->value, 'LokasiTujuanId' => $lokasiTujuan->Id]);
        $this->konteks()->tetapkan($organisasi->Id);
        $permintaan2 = PermintaanMutasiAset::query()->where('Status', StatusPermintaanMutasiAset::Draft->value)->firstOrFail();
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan2->Id}/detail", ['AsetId' => $aset->Id]);
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan2->Id}/submit");
        $this->actingAs($peminta)->post("/mutasi-aset/{$permintaan2->Id}/batalkan")->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $permintaan2->refresh();
        $this->assertSame(StatusPermintaanMutasiAset::Dibatalkan->value, $permintaan2->Status);
        $permintaanPersetujuan = PermintaanPersetujuan::query()->where('EntitasId', $permintaan2->Id)->firstOrFail();
        $this->assertSame(StatusPermintaanPersetujuan::Dibatalkan->value, $permintaanPersetujuan->Status);
    }

    public function test_serah_terima_aset_diserahkan_lalu_diterima_memperbarui_kondisi_aset(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasi = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $aset = $this->buatAset($organisasi, $lokasi, ['Kondisi' => KondisiAset::Baik->value]);
        $this->buatNomorDokumen($organisasi, 'SerahTerimaAset');

        $response = $this->actingAs($pengguna)->post('/serah-terima-aset', ['Jenis' => 'Peminjaman']);
        $response->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $serahTerima = SerahTerimaAset::query()->firstOrFail();
        $this->assertSame(StatusSerahTerimaAset::Diserahkan->value, $serahTerima->Status);
        $this->assertNotNull($serahTerima->DiserahkanPada);

        // Terima tanpa detail ditolak
        $this->actingAs($pengguna)->post("/serah-terima-aset/{$serahTerima->Id}/terima", [
            'Detail' => [['AsetId' => $aset->Id, 'KondisiSaatDiterima' => KondisiAset::Rusak->value]],
        ])->assertStatus(422);

        $this->actingAs($pengguna)->post("/serah-terima-aset/{$serahTerima->Id}/detail", [
            'AsetId' => $aset->Id,
            'KondisiSaatDiserahkan' => KondisiAset::Baik->value,
        ])->assertRedirect();

        $this->actingAs($pengguna)->post("/serah-terima-aset/{$serahTerima->Id}/terima", [
            'Detail' => [['AsetId' => $aset->Id, 'KondisiSaatDiterima' => KondisiAset::Rusak->value]],
        ])->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $serahTerima->refresh();
        $aset->refresh();
        $this->assertSame(StatusSerahTerimaAset::Diterima->value, $serahTerima->Status);
        $this->assertNotNull($serahTerima->DiterimaPada);
        $this->assertSame(KondisiAset::Rusak->value, $aset->Kondisi);

        // Tidak bisa diterima dua kali
        $this->actingAs($pengguna)->post("/serah-terima-aset/{$serahTerima->Id}/terima", [
            'Detail' => [['AsetId' => $aset->Id, 'KondisiSaatDiterima' => KondisiAset::Baik->value]],
        ])->assertStatus(422);
    }

    public function test_alur_lengkap_penghapusan_aset_mengarsipkan_dan_soft_delete_tanpa_hard_delete(): void
    {
        $organisasi = Organisasi::create(['Nama' => 'Org', 'Kode' => 'ORG-'.uniqid(), 'Status' => 'Aktif']);
        $pengaju = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $penyetuju = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasi->Id);
        $lokasi = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'A', 'Kode' => 'A-'.uniqid()]);
        $aset = $this->buatAset($organisasi, $lokasi);
        $this->buatNomorDokumen($organisasi, 'PengajuanPenghapusanAset');
        $this->buatAlur($organisasi, 'PengajuanPenghapusanAset', $penyetuju);

        $response = $this->actingAs($pengaju)->post('/penghapusan-aset', [
            'Alasan' => 'Rusak berat, tidak ekonomis diperbaiki.',
            'MetodePenghapusan' => MetodePengajuanPenghapusanAset::Dimusnahkan->value,
        ]);
        $response->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $pengajuan = PengajuanPenghapusanAset::query()->firstOrFail();
        $this->assertSame(StatusPengajuanPenghapusanAset::Draft->value, $pengajuan->Status);

        $this->actingAs($pengaju)->post("/penghapusan-aset/{$pengajuan->Id}/detail", ['AsetId' => $aset->Id])->assertRedirect();
        $this->actingAs($pengaju)->post("/penghapusan-aset/{$pengajuan->Id}/submit")->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $permintaanPersetujuan = PermintaanPersetujuan::query()->where('EntitasId', $pengajuan->Id)->firstOrFail();
        $this->actingAs($penyetuju)->post("/persetujuan/permintaan/{$permintaanPersetujuan->Id}/setujui")->assertRedirect();

        $this->konteks()->tetapkan($organisasi->Id);
        $pengajuan->refresh();
        $this->assertSame(StatusPengajuanPenghapusanAset::Disetujui->value, $pengajuan->Status);

        $this->actingAs($pengaju)->post("/penghapusan-aset/{$pengajuan->Id}/eksekusi")->assertRedirect();
        $this->konteks()->tetapkan($organisasi->Id);
        $pengajuan->refresh();
        $this->assertSame(StatusPengajuanPenghapusanAset::Selesai->value, $pengajuan->Status);
        $this->assertNotNull($pengajuan->DiselesaikanPada);

        $detail = DetailPenghapusanAset::query()->where('PengajuanPenghapusanAsetId', $pengajuan->Id)->firstOrFail();
        $this->assertSame(StatusDetailPenghapusanAset::Selesai->value, $detail->Status);

        // Aset diarsipkan, soft-deleted, TIDAK hard-deleted (masih ada lewat withTrashed)
        $this->assertNull(Aset::query()->find($aset->Id));
        $asetTerhapus = Aset::withTrashed()->find($aset->Id);
        $this->assertNotNull($asetTerhapus);
        $this->assertSame(StatusAset::Diarsipkan->value, $asetTerhapus->Status);
        $this->assertNotNull($asetTerhapus->DihapusPada);
    }

    public function test_cross_tenant_permintaan_mutasi_aset_404(): void
    {
        $organisasiA = Organisasi::create(['Nama' => 'Org A', 'Kode' => 'ORGA-'.uniqid(), 'Status' => 'Aktif']);
        $organisasiB = Organisasi::create(['Nama' => 'Org B', 'Kode' => 'ORGB-'.uniqid(), 'Status' => 'Aktif']);
        $penggunaB = $this->buatPengguna($organisasiB, self::IZIN_PENUH);
        $penggunaA = $this->buatPengguna($organisasiA, self::IZIN_PENUH);
        $this->konteks()->tetapkan($organisasiA->Id);
        $lokasiTujuan = Lokasi::create(['OrganisasiId' => $organisasiA->Id, 'Nama' => 'B', 'Kode' => 'B-'.uniqid()]);
        $this->buatNomorDokumen($organisasiA, 'PermintaanMutasiAset');

        $this->actingAs($penggunaA)->post('/mutasi-aset', ['JenisMutasi' => JenisPermintaanMutasiAset::AntarLokasi->value, 'LokasiTujuanId' => $lokasiTujuan->Id]);
        $this->konteks()->tetapkan($organisasiA->Id);
        $permintaan = PermintaanMutasiAset::query()->firstOrFail();

        $this->actingAs($penggunaB)->get("/mutasi-aset/{$permintaan->Id}")->assertNotFound();
    }
}
