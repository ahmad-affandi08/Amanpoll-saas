<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Pemeliharaan\Domain\Enums\UrgensiPelapor;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Inertia\Testing\AssertableInertia;

/**
 * Urgensi pelapor sebagai usulan terstruktur (TASK 39.10 butir 1, PRD 8.20):
 * koordinator melihatnya di halaman keluhan dasbor dan formulir prioritasnya
 * terisi darinya, tetapi prioritas tetap hanya diubah pemegang `Keluhan.Kelola`.
 */
final class PelaporUsulanUrgensiTest extends KasusPelapor
{
    private Lokasi $lantai;

    private KategoriKeluhan $kategori;

    private Pengguna $pelapor;

    private Pengguna $koordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lantai = $this->lokasi('Lt. 12');
        $this->kategori = $this->kategori('Listrik');
        $this->pelapor = $this->pelaporDi($this->lantai);
        $this->koordinator = $this->penggunaMeja(['Keluhan.Kelola']);
    }

    public function test_koordinator_melihat_usulan_pelapor_dan_formulir_prioritas_terisi_darinya(): void
    {
        $keluhan = $this->laporkan('KerjaTerhenti');
        $this->assertSame('Normal', $keluhan->Prioritas);

        $this->actingAs($this->koordinator)->get("/pemeliharaan/keluhan/{$keluhan->Id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Keluhan/Show')
                ->where('keluhan.Prioritas', 'Normal')
                ->where('keluhan.UsulanUrgensi', 'KerjaTerhenti')
                ->where('keluhan.LabelUsulanUrgensi', 'Kerja terhenti')
                ->where('keluhan.PrioritasUsulan', 'Tinggi')
                ->where('prioritasAwal', 'Tinggi'));
    }

    public function test_sesudah_prioritas_ditetapkan_formulir_menampilkan_prioritas_yang_berlaku(): void
    {
        $keluhan = $this->laporkan('Berbahaya');

        $this->actingAs($this->koordinator)->put("/pemeliharaan/keluhan/{$keluhan->Id}/prioritas", [
            'Prioritas' => 'Rendah',
            'Alasan' => 'Hanya satu lampu, koridor masih terang.',
            'Versi' => $keluhan->Versi,
        ])->assertSessionDoesntHaveErrors();

        $this->get("/pemeliharaan/keluhan/{$keluhan->Id}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('keluhan.Prioritas', 'Rendah')
                // Usulannya tetap terlihat sebagai catatan, tetapi tidak lagi mengisi formulir.
                ->where('keluhan.LabelUsulanUrgensi', 'Berbahaya')
                ->where('prioritasAwal', 'Rendah'));
    }

    public function test_keluhan_tanpa_usulan_memakai_prioritas_yang_berlaku(): void
    {
        $keluhan = $this->keluhan($this->pelapor, $this->kategori, $this->lantai, ['Prioritas' => 'Tinggi']);

        $this->actingAs($this->koordinator)->get("/pemeliharaan/keluhan/{$keluhan->Id}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('keluhan.UsulanUrgensi', null)
                ->where('keluhan.LabelUsulanUrgensi', null)
                ->where('prioritasAwal', 'Tinggi'));
    }

    public function test_pelapor_tidak_bisa_menetapkan_prioritas_dari_usulannya_sendiri(): void
    {
        $keluhan = $this->laporkan('Berbahaya');

        $this->actingAs($this->pelapor)->put("/pemeliharaan/keluhan/{$keluhan->Id}/prioritas", [
            'Prioritas' => 'Kritis',
            'Alasan' => 'Menurut saya berbahaya.',
            'Versi' => $keluhan->Versi,
        ])->assertForbidden();

        $this->assertSame('Normal', $this->dalamOrganisasi(fn () => Keluhan::query()->findOrFail($keluhan->Id)->Prioritas));
    }

    private function laporkan(string $urgensi): Keluhan
    {
        $this->actingAs($this->pelapor)->post('/lapangan/pelapor/lapor', [
            'KategoriKeluhanId' => $this->kategori->Id,
            'LokasiId' => $this->lantai->Id,
            'Judul' => 'Lampu koridor mati',
            'Deskripsi' => 'Mati total sejak pagi.',
            'Urgensi' => $urgensi,
            'KunciLaporan' => 'kunci-'.$urgensi,
        ])->assertSessionDoesntHaveErrors();

        $keluhan = $this->dalamOrganisasi(fn () => Keluhan::query()->where('PelaporId', $this->pelapor->Id)->latest('DilaporkanPada')->firstOrFail());
        $this->assertSame(UrgensiPelapor::from($urgensi), $keluhan->UsulanUrgensi);

        return $keluhan;
    }
}
