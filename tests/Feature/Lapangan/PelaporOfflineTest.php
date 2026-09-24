<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Testing\TestResponse;

/**
 * Laporan pelapor saat offline (PRD 8.17, Gate 39: "alur lapor pelapor berjalan
 * offline → online tanpa transaksi ganda") lewat antrean FASE 20, operasi `Keluhan.Buat`.
 */
final class PelaporOfflineTest extends KasusPelapor
{
    private Lokasi $lantai;

    private Lokasi $lantaiLain;

    private KategoriKeluhan $kategori;

    private Pengguna $pelapor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lantai = $this->lokasi('Lt. 12');
        $this->lantaiLain = $this->lokasi('Lt. 11');
        $this->kategori = $this->kategori('Listrik');
        $this->pelapor = $this->pelaporDi($this->lantai);
    }

    public function test_laporan_offline_menjadi_keluhan_sekali_walau_antrean_dikirim_ulang(): void
    {
        $this->actingAs($this->pelapor);

        $this->dorong('mutasi-1', $this->muatan('kunci-offline'))->assertOk()->assertJsonPath('Antrean.0.Status', 'Selesai');
        $this->dorong('mutasi-1', $this->muatan('kunci-offline'))->assertOk()->assertJsonPath('Antrean.0.Status', 'Selesai');

        $keluhan = $this->dalamOrganisasi(fn () => Keluhan::query()->sole());
        $this->assertSame($this->pelapor->Id, $keluhan->PelaporId);
        $this->assertSame('Lapangan', $keluhan->Sumber);
        $this->assertStringEndsWith('Seberapa mendesak (menurut pelapor): Kerja terhenti.', $keluhan->Deskripsi);
    }

    public function test_kiriman_online_yang_ikut_diantrekan_tidak_menjadi_keluhan_kedua(): void
    {
        $this->actingAs($this->pelapor);

        // Jawaban online hilang di jalan, lalu perangkat mengantrekan laporan yang sama.
        $this->post('/lapangan/pelapor/lapor', $this->muatan('kunci-sama'))->assertSessionDoesntHaveErrors();
        $this->dorong('mutasi-ulang', $this->muatan('kunci-sama'))->assertJsonPath('Antrean.0.Status', 'Selesai');

        $this->assertSame(1, $this->dalamOrganisasi(fn () => Keluhan::query()->count()));
    }

    public function test_laporan_offline_untuk_lokasi_di_luar_lingkup_gagal_tanpa_menulis_keluhan(): void
    {
        $this->actingAs($this->pelapor);

        $this->dorong('mutasi-luar', [...$this->muatan('kunci-luar'), 'LokasiId' => $this->lantaiLain->Id])
            ->assertJsonPath('Antrean.0.Status', 'Gagal')
            ->assertJsonPath('Antrean.0.Konflik.Pesan', 'Lokasi ini di luar area yang bisa kamu laporkan.');

        $this->assertSame(0, $this->dalamOrganisasi(fn () => Keluhan::query()->count()));
    }

    public function test_muatan_offline_yang_tidak_lengkap_ditolak_server(): void
    {
        $this->actingAs($this->pelapor);
        $muatan = $this->muatan('kunci-rusak');
        unset($muatan['KategoriKeluhanId']);

        $this->dorong('mutasi-rusak', $muatan)
            ->assertJsonPath('Antrean.0.Status', 'Gagal')
            ->assertJsonPath('Antrean.0.Konflik.Alasan', 'MuatanTidakValid');

        $this->assertSame(0, $this->dalamOrganisasi(fn () => Keluhan::query()->count()));
    }

    /** @param array<string, mixed> $muatan */
    private function dorong(string $kunciOperasi, array $muatan): TestResponse
    {
        return $this->postJson('/offline/antrian', [
            'IdentitasPerangkat' => 'hp-pelapor-uji',
            'Mutasi' => [[
                'KunciOperasi' => $kunciOperasi,
                'Operasi' => 'Keluhan.Buat',
                'EntitasId' => null,
                'VersiKlien' => null,
                'MuatanData' => $muatan,
            ]],
        ]);
    }

    /** @return array<string, string> */
    private function muatan(string $kunciLaporan): array
    {
        return [
            'KategoriKeluhanId' => $this->kategori->Id,
            'LokasiId' => $this->lantai->Id,
            'Judul' => 'Lampu koridor mati',
            'Deskripsi' => 'Mati total sejak pagi.',
            'Urgensi' => 'KerjaTerhenti',
            'KunciLaporan' => $kunciLaporan,
        ];
    }
}
