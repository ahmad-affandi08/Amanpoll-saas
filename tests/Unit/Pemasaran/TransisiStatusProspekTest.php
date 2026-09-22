<?php

declare(strict_types=1);

namespace Tests\Unit\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\PindahkanTahapProspek;
use App\Domain\Pemasaran\Domain\KatalogTahapPipeline;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RiwayatTahapProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Aturan transisi tahap prospek (MARKETING.md 5.3, 36). */
final class TransisiStatusProspekTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (KatalogTahapPipeline::bawaan() as $tahap) {
            TahapPipeline::query()->firstOrCreate(['Kode' => $tahap['Kode']], [
                'Nama' => $tahap['Nama'],
                'Urutan' => $tahap['Urutan'],
                'TahapAkhir' => $tahap['TahapAkhir'],
                'DianggapMenang' => $tahap['DianggapMenang'],
            ]);
        }
    }

    public function test_transisi_ke_tahap_lain_diterima(): void
    {
        $prospek = $this->prospek(KatalogTahapPipeline::BARU);

        app(PindahkanTahapProspek::class)->jalankan($prospek, $this->tahap(KatalogTahapPipeline::DEMO));

        $this->assertSame(
            $this->tahap(KatalogTahapPipeline::DEMO)->Id,
            $prospek->fresh()?->TahapPipelineId,
        );
    }

    public function test_transisi_ke_tahap_yang_sama_ditolak(): void
    {
        $prospek = $this->prospek(KatalogTahapPipeline::BARU);

        $this->expectException(AturanBisnisDilanggar::class);

        app(PindahkanTahapProspek::class)->jalankan($prospek, $this->tahap(KatalogTahapPipeline::BARU));
    }

    public function test_transisi_ke_tahap_akhir_tetap_diizinkan(): void
    {
        $prospek = $this->prospek(KatalogTahapPipeline::TRIAL);

        app(PindahkanTahapProspek::class)->jalankan($prospek, $this->tahap(KatalogTahapPipeline::HILANG));

        $this->assertTrue($this->tahap(KatalogTahapPipeline::HILANG)->TahapAkhir);
        $this->assertSame(
            $this->tahap(KatalogTahapPipeline::HILANG)->Id,
            $prospek->fresh()?->TahapPipelineId,
        );
    }

    public function test_setiap_transisi_menyisakan_tepat_satu_baris_riwayat(): void
    {
        $prospek = $this->prospek(KatalogTahapPipeline::BARU);
        $aksi = app(PindahkanTahapProspek::class);

        $aksi->jalankan($prospek, $this->tahap(KatalogTahapPipeline::DIHUBUNGI));
        $aksi->jalankan($prospek, $this->tahap(KatalogTahapPipeline::DEMO));

        $this->assertSame(2, RiwayatTahapProspek::query()->where('ProspekId', $prospek->Id)->count());
    }

    public function test_tahap_asal_tercatat_pada_riwayat(): void
    {
        $prospek = $this->prospek(KatalogTahapPipeline::BARU);
        $asal = $this->tahap(KatalogTahapPipeline::BARU);

        app(PindahkanTahapProspek::class)->jalankan($prospek, $this->tahap(KatalogTahapPipeline::DEMO));

        $riwayat = RiwayatTahapProspek::query()->where('ProspekId', $prospek->Id)->firstOrFail();

        $this->assertSame($asal->Id, $riwayat->TahapSebelumId);
    }

    private function prospek(string $kodeTahap): Prospek
    {
        return Prospek::create([
            'Nama' => 'Prospek Uji',
            'Sumber' => 'Manual',
            'TahapPipelineId' => $this->tahap($kodeTahap)->Id,
        ]);
    }

    private function tahap(string $kode): TahapPipeline
    {
        return TahapPipeline::query()->where('Kode', $kode)->firstOrFail();
    }
}
