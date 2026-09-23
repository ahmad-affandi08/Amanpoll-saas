<?php

declare(strict_types=1);

namespace Tests\Feature\AlurKritis;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AturanTingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\BiayaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use App\Domain\Persediaan\Domain\Enums\StatusGudang;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\DetailMutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Alur kritis FASE 26.03: keluhan -> perintah kerja -> suku cadang -> tutup.
 *
 * Satu test merangkai seluruh langkah lewat rute HTTP, sebagaimana pelapor,
 * manajer, dan teknisi menjalankannya, dan memeriksa invarian di setiap
 * persinggahan: batas SLA, pewarisan data keluhan ke perintah kerja, stok
 * yang ditahan lalu benar-benar berkurang, urutan status, dan jejak riwayat.
 * Organisasi kedua disemai lebih dulu agar "tidak tersentuh" membandingkan
 * angka nyata, bukan nol dengan nol.
 */
final class AlurKeluhanSampaiTutupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Senin 21 September 2026 pukul 09:00 WIB.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-21 02:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_keluhan_diproses_menjadi_perintah_kerja_memakai_suku_cadang_lalu_keduanya_ditutup(): void
    {
        $a = $this->semaiOrganisasi('ORG-ALUR-A');
        $b = $this->semaiOrganisasi('ORG-ALUR-B');

        // 1. Pelapor melaporkan keluhan. Prioritas kiriman pelapor biasa diabaikan; yang berlaku bawaan kategori.
        $this->actingAs($a['pelapor'])->post('/pemeliharaan/keluhan', [
            'KategoriKeluhanId' => $a['kategori']->Id,
            'AsetId' => $a['aset']->Id,
            'LokasiId' => $a['lokasi']->Id,
            'Judul' => 'Kompresor ruang operasi berisik dan panas',
            'Deskripsi' => 'Bunyi kasar dari bantalan motor sejak pagi.',
            'Prioritas' => 'Kritis',
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->tetapkanKonteks($a['organisasi']);
        $keluhan = Keluhan::query()->sole();
        $this->assertSame('KLH-2026-0001', $keluhan->Nomor);
        $this->assertSame('Baru', $keluhan->Status);
        $this->assertSame('Tinggi', $keluhan->Prioritas);
        $this->assertSame($a['pelapor']->Id, $keluhan->PelaporId);
        $this->assertSame('2026-09-21 10:00:00', $keluhan->BatasResponsPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-21 17:00:00', $keluhan->BatasPenyelesaianPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));

        // 2. Manajer meninjau lalu menerima keluhan. Respons pertama tercatat di dalam batas SLA.
        $this->ubahStatusKeluhan($a, $keluhan, 'Ditinjau');
        $this->ubahStatusKeluhan($a, $keluhan, 'Diterima');

        $keluhan->refresh();
        $this->assertSame('Diterima', $keluhan->Status);
        $this->assertSame('2026-09-21 09:05:00', $keluhan->DiresponsPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertTrue($keluhan->DiresponsPada->lessThanOrEqualTo($keluhan->BatasResponsPada));

        // 3. Manajer membuat perintah kerja dari keluhan; judul, lokasi, aset, dan batas SLA diwarisi.
        $this->majukan(5);
        $this->actingAs($a['manajer'])->post('/pemeliharaan/perintah-kerja', [
            'KeluhanId' => $keluhan->Id,
            'Jenis' => 'Korektif',
            'Prioritas' => 'Tinggi',
            'MembutuhkanWaktuHenti' => false,
            'MembutuhkanPersetujuan' => false,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->tetapkanKonteks($a['organisasi']);
        $perintahKerja = PerintahKerja::query()->where('KeluhanId', $keluhan->Id)->sole();
        $this->assertSame('WO-2026-0001', $perintahKerja->Nomor);
        $this->assertSame('Draf', $perintahKerja->Status);
        $this->assertSame($keluhan->Judul, $perintahKerja->Judul);
        $this->assertSame($a['lokasi']->Id, $perintahKerja->LokasiId);
        $this->assertSame($keluhan->TingkatLayananId, $perintahKerja->TingkatLayananId);
        $this->assertSame('2026-09-21 17:00:00', $perintahKerja->BatasPenyelesaianPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('PerintahKerjaAset', [
            'PerintahKerjaId' => $perintahKerja->Id,
            'AsetId' => $a['aset']->Id,
            'Utama' => true,
        ]);
        $this->assertDatabaseHas('RiwayatStatusPerintahKerja', [
            'PerintahKerjaId' => $perintahKerja->Id,
            'StatusSebelum' => null,
            'StatusSesudah' => 'Draf',
            'Catatan' => 'Dibuat dari keluhan KLH-2026-0001.',
        ]);

        $this->ubahStatusKeluhan($a, $keluhan, 'Diproses');

        // 4. Teknisi ditugaskan, menerima, dan mulai mengerjakan.
        $this->majukan(5);
        $this->actingAs($a['manajer'])->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan", [
            'PenggunaIds' => [$a['teknisi']->Id],
            'PeranTugas' => 'Ketua',
            'GantiPenugasanAktif' => false,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($a['organisasi']);
        $penugasan = $perintahKerja->penugasan()->where('PenggunaId', $a['teknisi']->Id)->sole();
        $this->assertSame('Ditugaskan', $perintahKerja->fresh()?->Status);

        $this->majukan(5);
        $this->actingAs($a['teknisi'])->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan/{$penugasan->Id}/respons", [
            'Respons' => 'Terima',
        ])->assertSessionDoesntHaveErrors();
        $this->tetapkanKonteks($a['organisasi']);
        $this->assertSame('Diterima', $perintahKerja->fresh()?->Status);

        $this->ubahStatusPerintahKerja($a['teknisi'], $a, $perintahKerja, 'Dikerjakan');

        // 5. Reservasi suku cadang hanya menahan stok; jumlah tersedia belum berubah.
        $this->majukan(5);
        $this->actingAs($a['teknisi'])->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/reservasi-suku-cadang", [
            'GudangId' => $a['gudang']->Id,
            'SukuCadangId' => $a['sukuCadang']->Id,
            'Jumlah' => 3,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($a['organisasi']);
        $reservasi = ReservasiSukuCadang::query()->where('PerintahKerjaId', $perintahKerja->Id)->sole();
        $this->assertSame('Aktif', $reservasi->Status);
        $stok = $a['stok']->fresh();
        $this->assertSame('10.0000', $stok?->JumlahTersedia);
        $this->assertSame('3.0000', $stok?->JumlahDitahan);

        // 6. Pemakaian mengeluarkan stok sungguhan lewat mutasi yang diposting, lalu membebankan biayanya.
        $this->majukan(5);
        $this->actingAs($a['teknisi'])->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/suku-cadang", [
            'ReservasiSukuCadangId' => $reservasi->Id,
            'Aksi' => 'Pakai',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($a['organisasi']);
        $this->assertSame('Dipakai', $reservasi->fresh()?->Status);
        $stok = $a['stok']->fresh();
        $this->assertSame('7.0000', $stok?->JumlahTersedia);
        $this->assertSame('0.0000', $stok?->JumlahDitahan);

        $mutasi = MutasiStok::query()->where('ReferensiJenis', 'ReservasiSukuCadang')->where('ReferensiId', $reservasi->Id)->sole();
        $this->assertSame('Pengeluaran', $mutasi->Jenis);
        $this->assertSame('Diposting', $mutasi->Status);
        $this->assertSame($a['gudang']->Id, $mutasi->GudangAsalId);
        $detail = DetailMutasiStok::query()->where('MutasiStokId', $mutasi->Id)->sole();
        $this->assertSame($a['sukuCadang']->Id, $detail->SukuCadangId);
        $this->assertSame(3.0, (float) $detail->Jumlah);

        $pemakaian = PemakaianSukuCadang::query()->where('PerintahKerjaId', $perintahKerja->Id)->sole();
        $this->assertSame($mutasi->Id, $pemakaian->MutasiStokId);
        $this->assertSame('3.0000', $pemakaian->Jumlah);
        $this->assertSame('255000.00', BiayaPerintahKerja::query()
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->where('JenisBiaya', 'Sparepart')
            ->sole()->Jumlah);

        // 7. Sesi kerja 90 menit.
        $this->majukan(5);
        $this->actingAs($a['teknisi'])->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/waktu-kerja", [
            'Aksi' => 'Mulai',
        ])->assertSessionDoesntHaveErrors();
        $this->majukan(90);
        $this->actingAs($a['teknisi'])->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/waktu-kerja", [
            'Aksi' => 'Selesai',
            'Catatan' => 'Bantalan diganti, motor diuji jalan.',
        ])->assertSessionDoesntHaveErrors();
        $this->tetapkanKonteks($a['organisasi']);
        $this->assertSame(90, WaktuKerja::query()->where('PerintahKerjaId', $perintahKerja->Id)->sole()->DurasiMenit);

        // 8. Verifikasi, lalu tutup. Melompati Selesai ditolak dan tidak meninggalkan jejak.
        $this->ubahStatusPerintahKerja($a['teknisi'], $a, $perintahKerja, 'MenungguVerifikasi', 'Kompresor normal, suhu 41 °C.');

        $this->majukan(5);
        $perintahKerja->refresh();
        $this->actingAs($a['manajer'])->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => 'Ditutup',
            'Versi' => $perintahKerja->Versi,
        ])->assertStatus(422);
        $this->tetapkanKonteks($a['organisasi']);
        $this->assertSame('MenungguVerifikasi', $perintahKerja->fresh()?->Status);

        $this->ubahStatusPerintahKerja($a['manajer'], $a, $perintahKerja, 'Selesai', null, 0);
        $this->assertSame('Selesai', $penugasan->fresh()?->Status);
        $this->ubahStatusPerintahKerja($a['manajer'], $a, $perintahKerja, 'Ditutup');

        $perintahKerja->refresh();
        $this->assertSame('Ditutup', $perintahKerja->Status);
        $this->assertSame('2026-09-21 11:30:00', $perintahKerja->DiselesaikanPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-21 11:35:00', $perintahKerja->DitutupPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));

        // 9. Keluhan diselesaikan dan ditutup, di dalam batas penyelesaian SLA.
        $this->ubahStatusKeluhan($a, $keluhan, 'Selesai');
        $this->ubahStatusKeluhan($a, $keluhan, 'Ditutup');

        $keluhan->refresh();
        $this->assertSame('Ditutup', $keluhan->Status);
        $this->assertSame('2026-09-21 11:40:00', $keluhan->DiresolusikanPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-21 11:45:00', $keluhan->DitutupPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertTrue($keluhan->DiresolusikanPada->lessThanOrEqualTo($keluhan->BatasPenyelesaianPada));

        // 10. Jejak lengkap dan berurutan.
        $this->assertSame(
            ['Baru', 'Ditinjau', 'Diterima', 'Diproses', 'Selesai', 'Ditutup'],
            RiwayatStatusKeluhan::query()->where('KeluhanId', $keluhan->Id)->orderBy('DiubahPada')->orderBy('Id')->pluck('StatusSesudah')->all(),
        );
        $this->assertSame(
            ['Draf', 'Ditugaskan', 'Diterima', 'Dikerjakan', 'MenungguVerifikasi', 'Selesai', 'Ditutup'],
            RiwayatStatusPerintahKerja::query()->where('PerintahKerjaId', $perintahKerja->Id)->orderBy('DiubahPada')->orderBy('Id')->pluck('StatusSesudah')->all(),
        );
        $this->assertSame(1, DB::table('CatatanAudit')
            ->where('OrganisasiId', $a['organisasi']->Id)
            ->where('JenisEntitas', 'PerintahKerja')
            ->where('EntitasId', $perintahKerja->Id)
            ->where('Aksi', 'SukuCadang.Pakai')
            ->count());

        // 11. Pengguna organisasi lain tidak dapat membuka kembali perintah kerja ini,
        //     walau Ditutup -> Dikerjakan adalah transisi sah bagi pemiliknya.
        $perintahKerja->refresh();
        $this->actingAs($b['manajer'])->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => 'Dikerjakan',
            'Catatan' => 'Percobaan lintas organisasi.',
            'Versi' => $perintahKerja->Versi,
        ])->assertNotFound();
        $this->tetapkanKonteks($a['organisasi']);
        $this->assertSame('Ditutup', $perintahKerja->fresh()?->Status);

        // 12. Data organisasi kedua tidak tersentuh.
        $this->tetapkanKonteks($b['organisasi']);
        $stokB = $b['stok']->fresh();
        $this->assertSame('10.0000', $stokB?->JumlahTersedia);
        $this->assertSame('0.0000', $stokB?->JumlahDitahan);
        $this->assertSame(0, Keluhan::query()->count());
        $this->assertSame(0, PerintahKerja::query()->count());
        $this->assertSame(0, MutasiStok::query()->count());
    }

    /**
     * @return array{organisasi: Organisasi, pelapor: Pengguna, manajer: Pengguna, teknisi: Pengguna, lokasi: Lokasi, aset: Aset, kategori: KategoriKeluhan, gudang: Gudang, sukuCadang: SukuCadang, stok: StokSukuCadang}
     */
    private function semaiOrganisasi(string $kode): array
    {
        $organisasi = Organisasi::create(['Kode' => $kode, 'Nama' => "Rumah Sakit {$kode}"]);
        $pelapor = $this->buatPengguna($organisasi, 'pelapor');
        $manajer = $this->buatPengguna($organisasi, 'manajer', ['Keluhan.Kelola', 'PerintahKerja.Kelola']);
        $teknisi = $this->buatPengguna($organisasi, 'teknisi');

        $this->tetapkanKonteks($organisasi);
        foreach (['Keluhan' => 'KLH', 'PerintahKerja' => 'WO', 'MutasiStok' => 'MS'] as $jenis => $awalan) {
            NomorDokumen::create([
                'JenisDokumen' => $jenis,
                'Awalan' => $awalan,
                'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
                'ResetPeriode' => 'Tahunan',
            ]);
        }

        $lokasi = Lokasi::create([
            'Kode' => 'LOK-OK',
            'Nama' => 'Instalasi Bedah Sentral',
            'ZonaWaktu' => 'Asia/Jakarta',
            'Status' => 'Aktif',
        ]);
        $kategoriAset = KategoriAset::create(['Kode' => 'KAT-UTL', 'Nama' => 'Utilitas Medis']);
        $aset = Aset::create([
            'KategoriAsetId' => $kategoriAset->Id,
            'KodeAset' => 'AST-KMP-01',
            'Nama' => 'Kompresor Udara Medis',
            'Status' => StatusAset::Aktif->value,
            'LokasiId' => $lokasi->Id,
        ]);

        $tingkatLayanan = TingkatLayanan::create([
            'Kode' => 'SLA-MEDIS',
            'Nama' => 'SLA Peralatan Medis',
            'HariKerja' => [1, 2, 3, 4, 5],
            'JamKerjaMulai' => '08:00',
            'JamKerjaSelesai' => '17:00',
            'MemperhitungkanHariLibur' => true,
            'Aktif' => true,
        ]);
        AturanTingkatLayanan::create([
            'TingkatLayananId' => $tingkatLayanan->Id,
            'Prioritas' => 'Tinggi',
            'MenitRespons' => 60,
            'MenitPenyelesaian' => 480,
            'MenghitungJamKerja' => true,
        ]);
        $kategori = KategoriKeluhan::create([
            'Kode' => 'KAT-UTL-MED',
            'Nama' => 'Utilitas Medis',
            'TingkatLayananId' => $tingkatLayanan->Id,
            'PrioritasBawaan' => 'Tinggi',
            'AsetWajib' => true,
            'Aktif' => true,
        ]);

        $gudang = Gudang::create([
            'Kode' => 'GDG-TEK',
            'Nama' => 'Gudang Teknik',
            'Status' => StatusGudang::Aktif->value,
        ]);
        $sukuCadang = SukuCadang::create([
            'Kode' => 'BRG-6305',
            'Nama' => 'Bearing 6305 2RS',
            'SatuanDasar' => 'Pcs',
            'HargaRataRata' => 85000,
            'StokMinimum' => 2,
            'Status' => StatusSukuCadang::Aktif->value,
        ]);
        $stok = StokSukuCadang::create([
            'GudangId' => $gudang->Id,
            'SukuCadangId' => $sukuCadang->Id,
            'JumlahTersedia' => 10,
            'JumlahDipesan' => 0,
            'JumlahDitahan' => 0,
        ]);

        return compact('organisasi', 'pelapor', 'manajer', 'teknisi', 'lokasi', 'aset', 'kategori', 'gudang', 'sukuCadang', 'stok');
    }

    /** @param list<string> $izin */
    private function buatPengguna(Organisasi $organisasi, string $sebutan, array $izin = []): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => ucfirst($sebutan).' '.$organisasi->Kode,
            'Email' => strtolower("{$sebutan}.{$organisasi->Kode}@amanpoll.test"),
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($izin !== []) {
            $this->tetapkanKonteks($organisasi);
            $peran = Peran::create(['Kode' => 'PERAN-'.strtoupper($sebutan), 'Nama' => 'Peran '.ucfirst($sebutan)]);
            foreach ($izin as $kode) {
                $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Pemeliharaan']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
            }
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
        }

        return $pengguna;
    }

    /** @param array{organisasi: Organisasi, manajer: Pengguna} $organisasi */
    private function ubahStatusKeluhan(array $organisasi, Keluhan $keluhan, string $status): void
    {
        $this->majukan(5);
        $this->tetapkanKonteks($organisasi['organisasi']);
        $keluhan->refresh();

        $this->actingAs($organisasi['manajer'])->put("/pemeliharaan/keluhan/{$keluhan->Id}/status", [
            'Status' => $status,
            'Catatan' => "Berpindah ke {$status}.",
            'Versi' => $keluhan->Versi,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->tetapkanKonteks($organisasi['organisasi']);
        $this->assertSame($status, $keluhan->fresh()?->Status);
    }

    /** @param array{organisasi: Organisasi} $organisasi */
    private function ubahStatusPerintahKerja(
        Pengguna $pelaku,
        array $organisasi,
        PerintahKerja $perintahKerja,
        string $status,
        ?string $ringkasan = null,
        int $menit = 5,
    ): void {
        $this->majukan($menit);
        $this->tetapkanKonteks($organisasi['organisasi']);
        $perintahKerja->refresh();

        $this->actingAs($pelaku)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => $status,
            'Catatan' => "Berpindah ke {$status}.",
            'RingkasanPenyelesaian' => $ringkasan,
            'Versi' => $perintahKerja->Versi,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->tetapkanKonteks($organisasi['organisasi']);
        $this->assertSame($status, $perintahKerja->fresh()?->Status);
    }

    private function majukan(int $menit): void
    {
        if ($menit > 0) {
            CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes($menit));
        }
    }

    private function tetapkanKonteks(Organisasi $organisasi): void
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
    }
}
