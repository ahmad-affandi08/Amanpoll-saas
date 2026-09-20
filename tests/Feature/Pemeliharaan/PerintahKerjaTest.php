<?php

declare(strict_types=1);

namespace Tests\Feature\Pemeliharaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\BiayaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KodeKegagalan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\PemakaianSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\VersiDataBerubah;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class PerintahKerjaTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_gate_12_aliran_lengkap_keluhan_ke_perintah_kerja_teknisi_sparepart_selesai_dan_ditutup(): void
    {
        CarbonImmutable::setTestNow('2026-03-20 08:00:00');

        $organisasi = Organisasi::create(['Kode' => 'ORG-PK-01', 'Nama' => 'Organisasi Perintah Kerja']);
        $manajer = $this->buatPengguna($organisasi, ['PerintahKerja.Kelola', 'Pemeliharaan.Kelola']);
        $teknisi = $this->buatPengguna($organisasi);

        $this->siapkanNomorDokumen($organisasi);

        $lokasi = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Kode' => 'LOK-01', 'Nama' => 'Pabrik Utama', 'Status' => 'Aktif']);
        $kategoriAset = KategoriAset::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'Mesin Produksi', 'Kode' => 'KAT-MESIN-'.uniqid()]);
        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategoriAset->Id,
            'KodeAset' => 'AST-PMP-01',
            'Nama' => 'Pompa Sentrifugal Utama',
            'Status' => Aset::STATUS_AKTIF,
            'Kondisi' => Aset::KONDISI_BAIK,
            'TingkatKritis' => Aset::KRITIS_TINGGI,
            'LokasiId' => $lokasi->Id,
        ]);

        $tingkatLayanan = TingkatLayanan::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'SLA-STD-'.uniqid(),
            'Nama' => 'SLA Standar',
            'HariKerja' => [1, 2, 3, 4, 5],
            'JamKerjaMulai' => '08:00',
            'JamKerjaSelesai' => '17:00',
            'MemperhitungkanHariLibur' => true,
            'Aktif' => true,
        ]);
        $kategoriKeluhan = KategoriKeluhan::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'KAT-PMP-'.uniqid(),
            'Nama' => 'Mekanikal Pompa',
            'TingkatLayananId' => $tingkatLayanan->Id,
            'PrioritasBawaan' => 'Tinggi',
            'AsetWajib' => true,
            'Aktif' => true,
        ]);

        $keluhan = Keluhan::create([
            'OrganisasiId' => $organisasi->Id,
            'Nomor' => 'KLH-2026-0001',
            'KategoriKeluhanId' => $kategoriKeluhan->Id,
            'AsetId' => $aset->Id,
            'LokasiId' => $lokasi->Id,
            'Judul' => 'Pompa Sentrifugal Overheat',
            'Deskripsi' => 'Temperatur pompa melebihi ambang batas normal dan bergetar.',
            'Prioritas' => 'Tinggi',
            'Status' => 'Diterima',
            'Sumber' => 'Internal',
            'PelaporId' => $manajer->Id,
            'DilaporkanPada' => now(),
            'Versi' => 1,
        ]);

        $gudang = Gudang::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'GDG-SP-'.uniqid(),
            'Nama' => 'Gudang Sparepart Sentral',
            'Status' => Gudang::STATUS_AKTIF,
        ]);
        $sukuCadang = SukuCadang::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'BRG-6205-'.uniqid(),
            'Nama' => 'Bearing 6205 ZZ',
            'SatuanDasar' => 'Pcs',
            'HargaRataRata' => 75000,
            'StokMinimum' => 5,
            'Status' => SukuCadang::STATUS_AKTIF,
        ]);
        StokSukuCadang::create([
            'OrganisasiId' => $organisasi->Id,
            'GudangId' => $gudang->Id,
            'SukuCadangId' => $sukuCadang->Id,
            'JumlahTersedia' => 10,
            'JumlahDipesan' => 0,
            'JumlahDitahan' => 0,
        ]);

        $kodeMasalah = KodeKegagalan::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'MSL-VIB-'.uniqid(),
            'Nama' => 'Vibrasi Tinggi dan Panas',
            'Jenis' => 'Masalah',
            'Aktif' => true,
        ]);
        $kodePenyebab = KodeKegagalan::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'PYB-WEAR-'.uniqid(),
            'Nama' => 'Keausan Bantalan Bearing',
            'Jenis' => 'Penyebab',
            'Aktif' => true,
        ]);
        $kodeTindakan = KodeKegagalan::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'TND-REP-'.uniqid(),
            'Nama' => 'Penggantian Komponen Bearing',
            'Jenis' => 'Tindakan',
            'Aktif' => true,
        ]);

        // 1. Manajer membuat Perintah Kerja dari Keluhan
        $responsStore = $this->actingAs($manajer)->post('/pemeliharaan/perintah-kerja', [
            'KeluhanId' => $keluhan->Id,
            'Jenis' => 'Korektif',
            'Judul' => 'Perbaikan Pompa Overheat',
            'Deskripsi' => 'Penggantian bearing dan alignment poros motor.',
            'Prioritas' => 'Tinggi',
            'LokasiId' => $lokasi->Id,
            'AsetIds' => [$aset->Id],
            'MembutuhkanWaktuHenti' => true,
            'MembutuhkanPersetujuan' => true,
        ]);
        $responsStore->assertSessionDoesntHaveErrors();
        $this->tetapkanKonteks($organisasi);

        /** @var PerintahKerja $perintahKerja */
        $perintahKerja = PerintahKerja::query()->where('KeluhanId', $keluhan->Id)->firstOrFail();
        $this->assertSame(StatusPerintahKerja::Draf->value, $perintahKerja->Status);
        $this->assertTrue($perintahKerja->MembutuhkanWaktuHenti);
        $this->assertTrue($perintahKerja->MembutuhkanPersetujuan);
        $this->assertDatabaseHas('PerintahKerjaAset', [
            'PerintahKerjaId' => $perintahKerja->Id,
            'AsetId' => $aset->Id,
            'Utama' => true,
        ]);

        // 2. Manajer menugaskan Teknisi
        $this->actingAs($manajer)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan", [
            'PenggunaIds' => [$teknisi->Id],
            'PeranTugas' => 'Ketua',
            'GantiPenugasanAktif' => false,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $perintahKerja->refresh();
        $this->assertSame(StatusPerintahKerja::Ditugaskan->value, $perintahKerja->Status);

        /** @var PenugasanPerintahKerja $penugasan */
        $penugasan = $perintahKerja->penugasan()->where('PenggunaId', $teknisi->Id)->firstOrFail();
        $this->assertSame(StatusPenugasanPerintahKerja::Ditugaskan->value, $penugasan->Status);

        // 3. Teknisi menerima penugasan
        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan/{$penugasan->Id}/respons", [
            'Respons' => 'Terima',
            'Catatan' => 'Siap mengerjakan sesuai SOP.',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $perintahKerja->refresh();
        $penugasan->refresh();
        $this->assertSame(StatusPerintahKerja::Diterima->value, $perintahKerja->Status);
        $this->assertSame(StatusPenugasanPerintahKerja::Diterima->value, $penugasan->Status);
        $this->assertNotNull($perintahKerja->DiterimaPada);

        // 4. Perintah Kerja beralih ke Dikerjakan
        $this->actingAs($teknisi)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => StatusPerintahKerja::Dikerjakan->value,
            'Catatan' => 'Mulai persiapan alat kerja dan safety lock out.',
            'Versi' => $perintahKerja->Versi,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $perintahKerja->refresh();
        $this->assertSame(StatusPerintahKerja::Dikerjakan->value, $perintahKerja->Status);
        $this->assertNotNull($perintahKerja->DimulaiPada);

        // 5. Reservasi Suku Cadang dari Gudang
        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/reservasi-suku-cadang", [
            'GudangId' => $gudang->Id,
            'SukuCadangId' => $sukuCadang->Id,
            'Jumlah' => 2,
            'Catatan' => '2 unit bearing untuk sisi depan dan belakang.',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $reservasi = ReservasiSukuCadang::query()->where('PerintahKerjaId', $perintahKerja->Id)->firstOrFail();
        $this->assertSame('2.0000', $reservasi->Jumlah);
        $this->assertSame('Aktif', $reservasi->Status);

        $stok = StokSukuCadang::query()->where('GudangId', $gudang->Id)->where('SukuCadangId', $sukuCadang->Id)->firstOrFail();
        $this->assertSame('2.0000', $stok->JumlahDitahan);

        // 6. Konsumsi Suku Cadang (Pakai)
        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/suku-cadang", [
            'ReservasiSukuCadangId' => $reservasi->Id,
            'Aksi' => 'Pakai',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $reservasi->refresh();
        $this->assertSame('Dipakai', $reservasi->Status);

        $stok->refresh();
        $this->assertSame('8.0000', $stok->JumlahTersedia);
        $this->assertSame('0.0000', $stok->JumlahDitahan);

        $pemakaian = PemakaianSukuCadang::query()->where('PerintahKerjaId', $perintahKerja->Id)->firstOrFail();
        $this->assertSame('2.0000', $pemakaian->Jumlah);
        $this->assertSame('75000.00', $pemakaian->HargaSatuan);

        $biayaSparepart = BiayaPerintahKerja::query()
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->where('JenisBiaya', 'Sparepart')
            ->firstOrFail();
        $this->assertSame('150000.00', $biayaSparepart->Jumlah);

        // 7. Mulai dan Selesai Waktu Kerja
        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/waktu-kerja", [
            'Aksi' => 'Mulai',
            'Catatan' => 'Mulai pembongkaran bearing.',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $waktuKerja = WaktuKerja::query()->where('PerintahKerjaId', $perintahKerja->Id)->whereNull('SelesaiPada')->firstOrFail();

        CarbonImmutable::setTestNow('2026-03-20 10:30:00'); // 150 menit kemudian

        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/waktu-kerja", [
            'Aksi' => 'Selesai',
            'Catatan' => 'Bearing selesai dipasang dan disetel.',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $waktuKerja->refresh();
        $this->assertNotNull($waktuKerja->SelesaiPada);
        $this->assertSame(150, $waktuKerja->DurasiMenit);

        // 8. Mulai dan Selesai Downtime Aset
        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/waktu-henti", [
            'AsetId' => $aset->Id,
            'Aksi' => 'Mulai',
            'Jenis' => 'TidakTerencana',
            'Alasan' => 'Penggantian komponen mekanik pompa.',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $downtime = WaktuHentiAset::query()->where('PerintahKerjaId', $perintahKerja->Id)->whereNull('SelesaiPada')->firstOrFail();

        CarbonImmutable::setTestNow('2026-03-20 11:00:00'); // 30 menit kemudian

        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/waktu-henti", [
            'AsetId' => $aset->Id,
            'Aksi' => 'Selesai',
            'Jenis' => 'TidakTerencana',
            'Alasan' => 'Pompa siap dihidupkan kembali.',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $downtime->refresh();
        $this->assertNotNull($downtime->SelesaiPada);
        $this->assertSame(30, $downtime->DurasiMenit);

        // 9. Input Analisis Kegagalan (Problem / Cause / Remedy)
        $responsAnalisis = $this->actingAs($teknisi)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/analisis-kegagalan", [
            'KodeMasalahId' => $kodeMasalah->Id,
            'KodePenyebabId' => $kodePenyebab->Id,
            'KodeTindakanId' => $kodeTindakan->Id,
            'AkarMasalah' => 'Pelumasan bearing kering akibat jadwal grease terlewat.',
            'TindakanKorektif' => 'Ganti bearing baru dan isi grease Lithium NLGI 2.',
            'TindakanPencegahan' => 'Tambahkan inspeksi pelumasan berkala pada checklist mingguan.',
        ]);
        $responsAnalisis->assertSessionDoesntHaveErrors();
        $responsAnalisis->assertRedirect();

        $this->tetapkanKonteks($organisasi);
        $perintahKerja->refresh();
        $this->assertNotNull($perintahKerja->analisisKegagalan);
        $this->assertSame($kodeMasalah->Id, $perintahKerja->analisisKegagalan->KodeMasalahId);

        // 10. Catat Biaya Tambahan Jasa Vendor
        $this->actingAs($manajer)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/biaya", [
            'JenisBiaya' => 'Vendor',
            'Deskripsi' => 'Jasa laser alignment dari PT Presisi Mekanika',
            'Jumlah' => 350000,
            'MataUang' => 'IDR',
            'TanggalBiaya' => '2026-03-20',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $this->assertDatabaseHas('BiayaPerintahKerja', [
            'PerintahKerjaId' => $perintahKerja->Id,
            'JenisBiaya' => 'Vendor',
            'Jumlah' => '350000.00',
        ]);

        // 11. Beralih ke MenungguVerifikasi
        $perintahKerja->refresh();
        $this->actingAs($teknisi)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => StatusPerintahKerja::MenungguVerifikasi->value,
            'Catatan' => 'Pekerjaan selesai, mohon diverifikasi oleh supervisor.',
            'RingkasanPenyelesaian' => 'Pompa telah ditest running selama 30 menit. Temperatur stabil 42°C, vibrasi 1.2 mm/s.',
            'Versi' => $perintahKerja->Versi,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $perintahKerja->refresh();
        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $perintahKerja->Status);
        $this->assertSame(100.0, (float) $perintahKerja->PersentaseSelesai);

        // 12. Manajer menyelesaikan pekerjaan (Selesai)
        $this->actingAs($manajer)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => StatusPerintahKerja::Selesai->value,
            'Catatan' => 'Hasil verifikasi baik dan disetujui.',
            'RingkasanPenyelesaian' => 'Pompa telah diverifikasi beroperasi normal.',
            'Versi' => $perintahKerja->Versi,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $perintahKerja->refresh();
        $this->assertSame(StatusPerintahKerja::Selesai->value, $perintahKerja->Status);
        $this->assertNotNull($perintahKerja->DiselesaikanPada);

        // Penugasan teknisi otomatis berstatus Selesai
        $penugasan->refresh();
        $this->assertSame(StatusPenugasanPerintahKerja::Selesai->value, $penugasan->Status);
        $this->assertNotNull($penugasan->SelesaiPada);

        // 13. Manajer menutup perintah kerja (Ditutup)
        $this->actingAs($manajer)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => StatusPerintahKerja::Ditutup->value,
            'Catatan' => 'Pekerjaan administratif selesai dan dokumen ditutup.',
            'Versi' => $perintahKerja->Versi,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $perintahKerja->refresh();
        $this->assertSame(StatusPerintahKerja::Ditutup->value, $perintahKerja->Status);
        $this->assertNotNull($perintahKerja->DitutupPada);

        // 14. Buka kembali (Reopen) ke Dikerjakan
        $this->actingAs($manajer)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => StatusPerintahKerja::Dikerjakan->value,
            'Catatan' => 'Perlu pengecekan tambahan seal oli.',
            'Versi' => $perintahKerja->Versi,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $perintahKerja->refresh();
        $this->assertSame(StatusPerintahKerja::Dikerjakan->value, $perintahKerja->Status);
        $this->assertNull($perintahKerja->DitutupPada);
        $this->assertNull($perintahKerja->DiselesaikanPada);
    }

    public function test_state_machine_menolak_transisi_ilegal(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-PK-02', 'Nama' => 'Organisasi PK 2']);
        $manajer = $this->buatPengguna($organisasi, ['PerintahKerja.Kelola']);
        $this->siapkanNomorDokumen($organisasi);

        $lokasi = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Kode' => 'LOK-02', 'Nama' => 'Lokasi 2', 'Status' => 'Aktif']);
        $kategoriAset = KategoriAset::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'Kategori 2', 'Kode' => 'KAT-02-'.uniqid()]);
        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategoriAset->Id,
            'KodeAset' => 'AST-02',
            'Nama' => 'Aset 2',
            'Status' => Aset::STATUS_AKTIF,
            'Kondisi' => Aset::KONDISI_BAIK,
            'TingkatKritis' => Aset::KRITIS_NORMAL,
        ]);

        $this->actingAs($manajer)->post('/pemeliharaan/perintah-kerja', [
            'Jenis' => 'Korektif',
            'Judul' => 'Pekerjaan Uji Coba Ilegal',
            'Prioritas' => 'Normal',
            'LokasiId' => $lokasi->Id,
            'AsetIds' => [$aset->Id],
            'MembutuhkanWaktuHenti' => false,
            'MembutuhkanPersetujuan' => false,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);

        /** @var PerintahKerja $perintahKerja */
        $perintahKerja = PerintahKerja::query()->latest('DibuatPada')->firstOrFail();
        $this->assertSame(StatusPerintahKerja::Draf->value, $perintahKerja->Status);

        // Mencoba melompat langsung dari Draf ke Selesai -> ditolak dengan 422 / AturanBisnisDilanggar
        $this->withoutExceptionHandling();
        $this->expectException(AturanBisnisDilanggar::class);
        $this->actingAs($manajer)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => StatusPerintahKerja::Selesai->value,
            'Versi' => $perintahKerja->Versi,
        ]);
    }

    public function test_optimistic_locking_mencegah_perubahan_status_jika_versi_berubah(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-PK-03', 'Nama' => 'Organisasi PK 3']);
        $manajer = $this->buatPengguna($organisasi, ['PerintahKerja.Kelola']);
        $this->siapkanNomorDokumen($organisasi);

        $lokasi = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Kode' => 'LOK-03', 'Nama' => 'Lokasi 3', 'Status' => 'Aktif']);
        $kategoriAset = KategoriAset::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'Kategori 3', 'Kode' => 'KAT-03-'.uniqid()]);
        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategoriAset->Id,
            'KodeAset' => 'AST-03',
            'Nama' => 'Aset 3',
            'Status' => Aset::STATUS_AKTIF,
            'Kondisi' => Aset::KONDISI_BAIK,
            'TingkatKritis' => Aset::KRITIS_NORMAL,
        ]);

        $this->actingAs($manajer)->post('/pemeliharaan/perintah-kerja', [
            'Jenis' => 'Korektif',
            'Judul' => 'Pekerjaan Uji Coba Versi',
            'Prioritas' => 'Normal',
            'LokasiId' => $lokasi->Id,
            'AsetIds' => [$aset->Id],
            'MembutuhkanWaktuHenti' => false,
            'MembutuhkanPersetujuan' => false,
            'DijadwalkanMulaiPada' => '2026-04-01 09:00:00',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);

        /** @var PerintahKerja $perintahKerja */
        $perintahKerja = PerintahKerja::query()->latest('DibuatPada')->firstOrFail();

        // Mengirim versi basi (misal 999 bukan versi asli 1) -> ditolak dengan VersiDataBerubah
        $this->withoutExceptionHandling();
        $this->expectException(VersiDataBerubah::class);
        $this->actingAs($manajer)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => StatusPerintahKerja::Terjadwal->value,
            'Versi' => 999,
        ]);
    }

    public function test_tidak_bisa_menyelesaikan_perintah_kerja_jika_masih_ada_sesi_waktu_kerja_atau_downtime_aktif(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-PK-04', 'Nama' => 'Organisasi PK 4']);
        $manajer = $this->buatPengguna($organisasi, ['PerintahKerja.Kelola']);
        $teknisi = $this->buatPengguna($organisasi);
        $this->siapkanNomorDokumen($organisasi);

        $lokasi = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Kode' => 'LOK-04', 'Nama' => 'Lokasi 4', 'Status' => 'Aktif']);
        $kategoriAset = KategoriAset::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'Kategori 4', 'Kode' => 'KAT-04-'.uniqid()]);
        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategoriAset->Id,
            'KodeAset' => 'AST-04',
            'Nama' => 'Aset 4',
            'Status' => Aset::STATUS_AKTIF,
            'Kondisi' => Aset::KONDISI_BAIK,
            'TingkatKritis' => Aset::KRITIS_NORMAL,
        ]);

        $this->actingAs($manajer)->post('/pemeliharaan/perintah-kerja', [
            'Jenis' => 'Korektif',
            'Judul' => 'Pekerjaan Uji Coba Aktivitas Terbuka',
            'Prioritas' => 'Normal',
            'LokasiId' => $lokasi->Id,
            'AsetIds' => [$aset->Id],
            'MembutuhkanWaktuHenti' => true,
            'MembutuhkanPersetujuan' => false,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);

        /** @var PerintahKerja $perintahKerja */
        $perintahKerja = PerintahKerja::query()->latest('DibuatPada')->firstOrFail();

        $this->actingAs($manajer)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan", [
            'PenggunaIds' => [$teknisi->Id],
            'PeranTugas' => 'Ketua',
            'GantiPenugasanAktif' => false,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $penugasan = $perintahKerja->penugasan()->firstOrFail();

        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan/{$penugasan->Id}/respons", [
            'Respons' => 'Terima',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $perintahKerja->refresh();

        $this->actingAs($teknisi)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => StatusPerintahKerja::Dikerjakan->value,
            'Catatan' => 'Mulai penanganan.',
            'Versi' => $perintahKerja->Versi,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $perintahKerja->refresh();

        // Mulai sesi kerja tanpa diselesaikan
        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/waktu-kerja", [
            'Aksi' => 'Mulai',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);

        // Coba beralih ke MenungguVerifikasi saat waktu kerja aktif -> harus ditolak
        $this->withoutExceptionHandling();
        $this->expectException(AturanBisnisDilanggar::class);
        $this->expectExceptionMessage('Akhiri semua sesi waktu kerja sebelum menyelesaikan pekerjaan.');
        $this->actingAs($teknisi)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => StatusPerintahKerja::MenungguVerifikasi->value,
            'RingkasanPenyelesaian' => 'Mencoba verifikasi sebelum selesai.',
            'Versi' => $perintahKerja->Versi,
        ]);
    }

    public function test_cegah_sesi_waktu_kerja_ganda_dan_downtime_tumpang_tindih(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-PK-05', 'Nama' => 'Organisasi PK 5']);
        $manajer = $this->buatPengguna($organisasi, ['PerintahKerja.Kelola']);
        $teknisi = $this->buatPengguna($organisasi);
        $this->siapkanNomorDokumen($organisasi);

        $lokasi = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Kode' => 'LOK-05', 'Nama' => 'Lokasi 5', 'Status' => 'Aktif']);
        $kategoriAset = KategoriAset::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'Kategori 5', 'Kode' => 'KAT-05-'.uniqid()]);
        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategoriAset->Id,
            'KodeAset' => 'AST-05',
            'Nama' => 'Aset 5',
            'Status' => Aset::STATUS_AKTIF,
            'Kondisi' => Aset::KONDISI_BAIK,
            'TingkatKritis' => Aset::KRITIS_NORMAL,
        ]);

        $this->actingAs($manajer)->post('/pemeliharaan/perintah-kerja', [
            'Jenis' => 'Korektif',
            'Judul' => 'Pekerjaan Uji Tumpang Tindih',
            'Prioritas' => 'Normal',
            'LokasiId' => $lokasi->Id,
            'AsetIds' => [$aset->Id],
            'MembutuhkanWaktuHenti' => true,
            'MembutuhkanPersetujuan' => false,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);

        /** @var PerintahKerja $perintahKerja */
        $perintahKerja = PerintahKerja::query()->latest('DibuatPada')->firstOrFail();

        $this->actingAs($manajer)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan", [
            'PenggunaIds' => [$teknisi->Id],
            'PeranTugas' => 'Ketua',
            'GantiPenugasanAktif' => false,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $penugasan = $perintahKerja->penugasan()->firstOrFail();

        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan/{$penugasan->Id}/respons", [
            'Respons' => 'Terima',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $perintahKerja->refresh();

        $this->actingAs($teknisi)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => StatusPerintahKerja::Dikerjakan->value,
            'Catatan' => 'Mulai pekerjaan.',
            'Versi' => $perintahKerja->Versi,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);

        // Mulai sesi kerja pertama
        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/waktu-kerja", [
            'Aksi' => 'Mulai',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);

        // Mulai sesi kerja kedua untuk teknisi yang sama -> ditolak
        $this->withoutExceptionHandling();
        $this->expectException(AturanBisnisDilanggar::class);
        $this->expectExceptionMessage('Teknisi masih memiliki sesi waktu kerja aktif. Akhiri atau jeda sesi tersebut terlebih dahulu.');
        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/waktu-kerja", [
            'Aksi' => 'Mulai',
        ]);
    }

    public function test_pengembalian_reservasi_suku_cadang_melepaskan_tahanan_stok(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-PK-06', 'Nama' => 'Organisasi PK 6']);
        $manajer = $this->buatPengguna($organisasi, ['PerintahKerja.Kelola']);
        $teknisi = $this->buatPengguna($organisasi);
        $this->siapkanNomorDokumen($organisasi);

        $lokasi = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Kode' => 'LOK-06', 'Nama' => 'Lokasi 6', 'Status' => 'Aktif']);
        $kategoriAset = KategoriAset::create(['OrganisasiId' => $organisasi->Id, 'Nama' => 'Kategori 6', 'Kode' => 'KAT-06-'.uniqid()]);
        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategoriAset->Id,
            'KodeAset' => 'AST-06',
            'Nama' => 'Aset 6',
            'Status' => Aset::STATUS_AKTIF,
            'Kondisi' => Aset::KONDISI_BAIK,
            'TingkatKritis' => Aset::KRITIS_NORMAL,
        ]);

        $gudang = Gudang::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'GDG-06-'.uniqid(),
            'Nama' => 'Gudang 6',
            'Status' => Gudang::STATUS_AKTIF,
        ]);
        $sukuCadang = SukuCadang::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'SC-06-'.uniqid(),
            'Nama' => 'Kabel Power',
            'SatuanDasar' => 'Meter',
            'HargaRataRata' => 20000,
            'StokMinimum' => 1,
            'Status' => SukuCadang::STATUS_AKTIF,
        ]);
        StokSukuCadang::create([
            'OrganisasiId' => $organisasi->Id,
            'GudangId' => $gudang->Id,
            'SukuCadangId' => $sukuCadang->Id,
            'JumlahTersedia' => 50,
            'JumlahDipesan' => 0,
            'JumlahDitahan' => 0,
        ]);

        $this->actingAs($manajer)->post('/pemeliharaan/perintah-kerja', [
            'Jenis' => 'Korektif',
            'Judul' => 'Pekerjaan Uji Kembalikan Suku Cadang',
            'Prioritas' => 'Normal',
            'LokasiId' => $lokasi->Id,
            'AsetIds' => [$aset->Id],
            'MembutuhkanWaktuHenti' => false,
            'MembutuhkanPersetujuan' => false,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);

        /** @var PerintahKerja $perintahKerja */
        $perintahKerja = PerintahKerja::query()->latest('DibuatPada')->firstOrFail();

        $this->actingAs($manajer)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan", [
            'PenggunaIds' => [$teknisi->Id],
            'PeranTugas' => 'Ketua',
            'GantiPenugasanAktif' => false,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $penugasan = $perintahKerja->penugasan()->firstOrFail();

        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan/{$penugasan->Id}/respons", [
            'Respons' => 'Terima',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $perintahKerja->refresh();

        $this->actingAs($teknisi)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => StatusPerintahKerja::Dikerjakan->value,
            'Catatan' => 'Mulai pekerjaan.',
            'Versi' => $perintahKerja->Versi,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);

        // Reservasi 10 unit
        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/reservasi-suku-cadang", [
            'GudangId' => $gudang->Id,
            'SukuCadangId' => $sukuCadang->Id,
            'Jumlah' => 10,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $reservasi = ReservasiSukuCadang::query()->where('PerintahKerjaId', $perintahKerja->Id)->firstOrFail();
        $stok = StokSukuCadang::query()->where('GudangId', $gudang->Id)->where('SukuCadangId', $sukuCadang->Id)->firstOrFail();
        $this->assertSame('10.0000', $stok->JumlahDitahan);

        // Kembalikan reservasi (tidak jadi dipakai)
        $this->actingAs($teknisi)->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/suku-cadang", [
            'ReservasiSukuCadangId' => $reservasi->Id,
            'Aksi' => 'Kembalikan',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $reservasi->refresh();
        $stok->refresh();

        $this->assertSame('Dilepas', $reservasi->Status);
        $this->assertSame('0.0000', $stok->JumlahDitahan);
        $this->assertSame('50.0000', $stok->JumlahTersedia);
    }

    public function test_crud_kode_kegagalan(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-PK-07', 'Nama' => 'Organisasi PK 7']);
        $manajer = $this->buatPengguna($organisasi, ['PerintahKerja.Kelola']);
        $this->tetapkanKonteks($organisasi);

        // GET index
        $this->actingAs($manajer)->get('/pemeliharaan/kode-kegagalan')->assertOk();

        // POST store
        $this->actingAs($manajer)->post('/pemeliharaan/kode-kegagalan', [
            'Kode' => 'MSL-BOCOR-'.uniqid(),
            'Nama' => 'Kebocoran Fluida',
            'Jenis' => 'Masalah',
            'Keterangan' => 'Terjadi rembesan atau bocor pada pipa atau seal.',
            'Aktif' => true,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $kode = KodeKegagalan::query()->where('Nama', 'Kebocoran Fluida')->firstOrFail();
        $this->assertSame('Kebocoran Fluida', $kode->Nama);

        // PUT update
        $this->actingAs($manajer)->put("/pemeliharaan/kode-kegagalan/{$kode->Id}", [
            'Kode' => $kode->Kode,
            'Nama' => 'Kebocoran Fluida & Gas',
            'Jenis' => 'Masalah',
            'Keterangan' => 'Rembesan pada seal gasket pipa.',
            'Aktif' => true,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);
        $this->assertSame('Kebocoran Fluida & Gas', $kode->fresh()->Nama);
    }

    /** @param list<string> $izin */
    private function buatPengguna(Organisasi $organisasi, array $izin = []): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($izin !== []) {
            $this->tetapkanKonteks($organisasi);
            $peran = Peran::create([
                'OrganisasiId' => $organisasi->Id,
                'Kode' => 'PERAN-'.uniqid(),
                'Nama' => 'Peran Uji',
            ]);
            foreach ($izin as $kode) {
                $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Pemeliharaan']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
            }
            PenggunaPeran::create([
                'OrganisasiId' => $organisasi->Id,
                'PenggunaId' => $pengguna->Id,
                'PeranId' => $peran->Id,
            ]);
        }

        return $pengguna;
    }

    private function siapkanNomorDokumen(Organisasi $organisasi): void
    {
        $this->tetapkanKonteks($organisasi);
        NomorDokumen::firstOrCreate([
            'OrganisasiId' => $organisasi->Id,
            'JenisDokumen' => 'PerintahKerja',
        ], [
            'Awalan' => 'WO',
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'ResetPeriode' => 'Tahunan',
        ]);

        NomorDokumen::firstOrCreate([
            'OrganisasiId' => $organisasi->Id,
            'JenisDokumen' => 'MutasiStok',
        ], [
            'Awalan' => 'MS',
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'ResetPeriode' => 'Tahunan',
        ]);
    }

    private function tetapkanKonteks(Organisasi $organisasi): void
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
    }
}
