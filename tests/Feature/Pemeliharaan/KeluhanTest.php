<?php

declare(strict_types=1);

namespace Tests\Feature\Pemeliharaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AturanTingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class KeluhanTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_manajer_dapat_mengelola_tingkat_layanan_dan_kategori_keluhan(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-CRUD', 'Nama' => 'Organisasi CRUD']);
        $manajer = $this->buatPengguna($organisasi, ['Pemeliharaan.Kelola']);
        $dataSla = [
            'Kode' => 'SLA-CRUD',
            'Nama' => 'SLA CRUD',
            'Deskripsi' => 'SLA untuk pengujian CRUD.',
            'HariKerja' => [1, 2, 3, 4, 5],
            'JamKerjaMulai' => '08:00',
            'JamKerjaSelesai' => '17:00',
            'MemperhitungkanHariLibur' => true,
            'Aktif' => true,
            'Aturan' => [[
                'Prioritas' => 'Normal',
                'MenitRespons' => 60,
                'MenitPenyelesaian' => 240,
                'MenghitungJamKerja' => true,
            ]],
            'Eskalasi' => [],
        ];

        $this->actingAs($manajer)->get('/pemeliharaan/tingkat-layanan')->assertOk();
        $this->actingAs($manajer)->post('/pemeliharaan/tingkat-layanan', $dataSla)->assertSessionDoesntHaveErrors();
        $this->tetapkanKonteks($organisasi);
        $tingkatLayanan = TingkatLayanan::query()->where('Kode', 'SLA-CRUD')->firstOrFail();

        $dataKategori = [
            'IndukId' => null,
            'Kode' => 'KAT-CRUD',
            'Nama' => 'Kategori CRUD',
            'TingkatLayananId' => $tingkatLayanan->Id,
            'PrioritasBawaan' => 'Normal',
            'AsetWajib' => false,
            'PeranPenanggungJawabId' => null,
            'Aktif' => true,
        ];
        $this->actingAs($manajer)->get('/pemeliharaan/kategori-keluhan')->assertOk();
        $this->actingAs($manajer)->post('/pemeliharaan/kategori-keluhan', $dataKategori)->assertSessionDoesntHaveErrors();
        $this->tetapkanKonteks($organisasi);
        $kategori = KategoriKeluhan::query()->where('Kode', 'KAT-CRUD')->firstOrFail();

        $this->actingAs($manajer)->put("/pemeliharaan/kategori-keluhan/{$kategori->Id}", [
            ...$dataKategori,
            'Nama' => 'Kategori Diperbarui',
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame('Kategori Diperbarui', $kategori->fresh()->Nama);

        $this->actingAs($manajer)->delete("/pemeliharaan/kategori-keluhan/{$kategori->Id}")->assertSessionDoesntHaveErrors();
        $this->actingAs($manajer)->put("/pemeliharaan/tingkat-layanan/{$tingkatLayanan->Id}", [
            ...$dataSla,
            'Nama' => 'SLA Diperbarui',
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame('SLA Diperbarui', $tingkatLayanan->fresh()->Nama);
        $this->actingAs($manajer)->delete("/pemeliharaan/tingkat-layanan/{$tingkatLayanan->Id}")->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('KategoriKeluhan', ['Id' => $kategori->Id]);
        $this->assertDatabaseMissing('TingkatLayanan', ['Id' => $tingkatLayanan->Id]);
    }

    public function test_pelapor_dapat_membuat_keluhan_dengan_prioritas_bawaan_dan_deadline_sla(): void
    {
        Storage::fake('local');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-18 16:30:00', 'Asia/Jakarta'));
        [$organisasi, $pelapor, $lokasi, $kategori] = $this->siapkanDataKeluhan();

        $respons = $this->actingAs($pelapor)->post('/pemeliharaan/keluhan', [
            'KategoriKeluhanId' => $kategori->Id,
            'LokasiId' => $lokasi->Id,
            'Judul' => 'Pendingin ruang server tidak stabil',
            'Deskripsi' => 'Suhu ruang server meningkat sejak sore.',
            'Prioritas' => 'Kritis',
            'Lampiran' => [UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf')],
        ]);

        $respons->assertSessionDoesntHaveErrors()->assertRedirect();
        $this->tetapkanKonteks($organisasi);
        $keluhan = Keluhan::query()->sole();
        $this->assertSame('Normal', $keluhan->Prioritas);
        $this->assertSame('Baru', $keluhan->Status);
        $this->assertSame('2026-09-21 09:30:00', $keluhan->BatasResponsPada?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-22 08:30:00', $keluhan->BatasPenyelesaianPada?->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('RiwayatStatusKeluhan', [
            'KeluhanId' => $keluhan->Id,
            'StatusSebelum' => null,
            'StatusSesudah' => 'Baru',
            'DiubahOleh' => $pelapor->Id,
        ]);
        $this->assertDatabaseHas('LampiranEntitas', [
            'JenisEntitas' => 'Keluhan',
            'EntitasId' => $keluhan->Id,
            'DibuatOleh' => $pelapor->Id,
        ]);
    }

    public function test_kategori_yang_mewajibkan_aset_menolak_keluhan_tanpa_aset(): void
    {
        [, $pelapor, $lokasi, $kategori] = $this->siapkanDataKeluhan(['AsetWajib' => true]);

        $this->actingAs($pelapor)->post('/pemeliharaan/keluhan', [
            'KategoriKeluhanId' => $kategori->Id,
            'LokasiId' => $lokasi->Id,
            'Judul' => 'Aset bermasalah',
            'Deskripsi' => 'Aset wajib dipilih.',
        ])->assertSessionHasErrors('AsetId');

        $this->assertDatabaseCount('Keluhan', 0);
    }

    public function test_keluhan_baru_dirutekan_ke_peran_penanggung_jawab(): void
    {
        Queue::fake();
        [$organisasi, $pelapor, $lokasi, $kategori] = $this->siapkanDataKeluhan();
        $penerima = $this->buatPengguna($organisasi);
        $this->tetapkanKonteks($organisasi);
        $peran = Peran::create(['Kode' => 'TRIAGE', 'Nama' => 'Tim Triage']);
        PenggunaPeran::create(['PenggunaId' => $penerima->Id, 'PeranId' => $peran->Id]);
        $kategori->update(['PeranPenanggungJawabId' => $peran->Id]);

        $this->buatKeluhanMelaluiHttp($pelapor, $lokasi, $kategori, $organisasi);

        $this->assertDatabaseHas('Notifikasi', [
            'PenggunaId' => $penerima->Id,
            'JenisPeristiwa' => 'Keluhan.Baru',
            'JenisEntitas' => 'Keluhan',
        ]);
    }

    public function test_manajer_dapat_menjalankan_alur_status_lengkap_dengan_histori_utuh(): void
    {
        [$organisasi, $pelapor, $lokasi, $kategori] = $this->siapkanDataKeluhan();
        $manajer = $this->buatPengguna($organisasi, ['Keluhan.Kelola']);
        $keluhan = $this->buatKeluhanMelaluiHttp($pelapor, $lokasi, $kategori, $organisasi);

        foreach (['Ditinjau', 'Diterima', 'Diproses', 'Selesai', 'Ditutup'] as $status) {
            $keluhan->refresh();
            $this->actingAs($manajer)->put("/pemeliharaan/keluhan/{$keluhan->Id}/status", [
                'Status' => $status,
                'Catatan' => "Berpindah ke {$status}",
                'Versi' => $keluhan->Versi,
            ])->assertSessionDoesntHaveErrors();
        }

        $this->tetapkanKonteks($organisasi);
        $keluhan->refresh();
        $this->assertSame('Ditutup', $keluhan->Status);
        $this->assertNotNull($keluhan->DiresponsPada);
        $this->assertNotNull($keluhan->DiresolusikanPada);
        $this->assertNotNull($keluhan->DitutupPada);
        $this->assertSame(6, $keluhan->riwayatStatus()->count());
        $this->assertSame(
            ['Baru', 'Ditinjau', 'Diterima', 'Diproses', 'Selesai', 'Ditutup'],
            $keluhan->riwayatStatus()->pluck('StatusSesudah')->all(),
        );
    }

    public function test_pelapor_hanya_dapat_membatalkan_keluhan_sendiri_pada_tahap_awal(): void
    {
        [$organisasi, $pelapor, $lokasi, $kategori] = $this->siapkanDataKeluhan();
        $keluhan = $this->buatKeluhanMelaluiHttp($pelapor, $lokasi, $kategori, $organisasi);
        $penggunaLain = $this->buatPengguna($organisasi);

        $this->actingAs($penggunaLain)->put("/pemeliharaan/keluhan/{$keluhan->Id}/status", [
            'Status' => 'Dibatalkan',
            'Catatan' => 'Bukan keluhan saya.',
            'Versi' => 1,
        ])->assertForbidden();

        $this->actingAs($pelapor)->put("/pemeliharaan/keluhan/{$keluhan->Id}/status", [
            'Status' => 'Dibatalkan',
            'Catatan' => 'Masalah sudah tidak terjadi.',
            'Versi' => 1,
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('Dibatalkan', $keluhan->fresh()->Status);
    }

    public function test_manajer_dapat_mengubah_prioritas_dan_melampirkan_berkas(): void
    {
        Storage::fake('local');
        [$organisasi, $pelapor, $lokasi, $kategori] = $this->siapkanDataKeluhan();
        $manajer = $this->buatPengguna($organisasi, ['Keluhan.Kelola']);
        $keluhan = $this->buatKeluhanMelaluiHttp($pelapor, $lokasi, $kategori, $organisasi);

        $this->actingAs($manajer)->put("/pemeliharaan/keluhan/{$keluhan->Id}/prioritas", [
            'Prioritas' => 'Kritis',
            'Alasan' => 'Berdampak pada layanan utama.',
            'Versi' => 1,
        ])->assertSessionDoesntHaveErrors();

        $keluhan->refresh();
        $this->assertSame('Kritis', $keluhan->Prioritas);
        $this->assertNull($keluhan->BatasResponsPada);
        $this->assertNull($keluhan->BatasPenyelesaianPada);

        $this->actingAs($manajer)->post('/kolaborasi/berkas', [
            'Berkas' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            'JenisEntitas' => 'Keluhan',
            'EntitasId' => $keluhan->Id,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('LampiranEntitas', [
            'JenisEntitas' => 'Keluhan',
            'EntitasId' => $keluhan->Id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $atributKategori
     * @return array{Organisasi, Pengguna, Lokasi, KategoriKeluhan}
     */
    private function siapkanDataKeluhan(array $atributKategori = []): array
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi Uji']);
        $pelapor = $this->buatPengguna($organisasi);
        $this->tetapkanKonteks($organisasi);
        $lokasi = Lokasi::create([
            'Kode' => 'LOK-'.uniqid(),
            'Nama' => 'Gedung Utama',
            'ZonaWaktu' => 'Asia/Jakarta',
            'Status' => 'Aktif',
        ]);
        NomorDokumen::create([
            'JenisDokumen' => 'Keluhan',
            'Awalan' => 'KLH',
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'ResetPeriode' => 'Tahunan',
        ]);
        $tingkatLayanan = TingkatLayanan::create([
            'Kode' => 'SLA-'.uniqid(),
            'Nama' => 'SLA Standar',
            'HariKerja' => [1, 2, 3, 4, 5],
            'JamKerjaMulai' => '08:00',
            'JamKerjaSelesai' => '17:00',
            'MemperhitungkanHariLibur' => true,
            'Aktif' => true,
        ]);
        AturanTingkatLayanan::create([
            'TingkatLayananId' => $tingkatLayanan->Id,
            'Prioritas' => 'Normal',
            'MenitRespons' => 120,
            'MenitPenyelesaian' => 600,
            'MenghitungJamKerja' => true,
        ]);
        $kategori = KategoriKeluhan::create(array_merge([
            'Kode' => 'KAT-'.uniqid(),
            'Nama' => 'Fasilitas',
            'TingkatLayananId' => $tingkatLayanan->Id,
            'PrioritasBawaan' => 'Normal',
            'AsetWajib' => false,
            'Aktif' => true,
        ], $atributKategori));

        return [$organisasi, $pelapor, $lokasi, $kategori];
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
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Manajer Keluhan']);
            foreach ($izin as $kode) {
                $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Pemeliharaan']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
            }
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
        }

        return $pengguna;
    }

    private function buatKeluhanMelaluiHttp(
        Pengguna $pelapor,
        Lokasi $lokasi,
        KategoriKeluhan $kategori,
        Organisasi $organisasi,
    ): Keluhan {
        $this->actingAs($pelapor)->post('/pemeliharaan/keluhan', [
            'KategoriKeluhanId' => $kategori->Id,
            'LokasiId' => $lokasi->Id,
            'Judul' => 'Keluhan pengujian',
            'Deskripsi' => 'Deskripsi keluhan untuk pengujian.',
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($organisasi);

        return Keluhan::query()->latest('DibuatPada')->firstOrFail();
    }

    private function tetapkanKonteks(Organisasi $organisasi): void
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
    }
}
