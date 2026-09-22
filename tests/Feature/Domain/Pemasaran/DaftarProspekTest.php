<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Domain\KatalogTahapPipeline;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;

/** Daftar prospek dipaginasi server; pencarian dan tahap harus tetap mencakup seluruh data. */
final class DaftarProspekTest extends KasusProspek
{
    private function buatProspek(string $nama, ?string $kodeTahap = null, int $skor = 0): Prospek
    {
        $tahap = TahapPipeline::query()
            ->where('Kode', $kodeTahap ?? KatalogTahapPipeline::BARU)
            ->firstOrFail();

        return Prospek::create([
            'Nama' => $nama,
            'Email' => strtolower(str_replace(' ', '.', $nama)).'@contoh.test',
            'Sumber' => SumberProspek::Manual->value,
            'TahapPipelineId' => $tahap->Id,
            'Skor' => $skor,
        ]);
    }

    /** @return array<string, mixed> */
    private function props(string $kueri = ''): array
    {
        $this->withoutExceptionHandling();

        return $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_LIHAT]), 'platform')
            ->get(route('pemasaran.prospek.index').$kueri)
            ->viewData('page')['props'];
    }

    public function test_prospek_di_luar_halaman_pertama_tetap_dapat_ditemukan(): void
    {
        for ($ke = 1; $ke <= 40; $ke++) {
            $this->buatProspek(sprintf('Calon %02d', $ke), skor: 100 - $ke);
        }

        $this->buatProspek('Zulfikar Terakhir', skor: 0);

        $awal = $this->props();
        $this->assertCount(25, $awal['prospek']['data']);
        $this->assertSame(41, $awal['prospek']['meta']['total']);
        $this->assertNotContains('Zulfikar Terakhir', array_column($awal['prospek']['data'], 'Nama'));

        $hasil = $this->props('?cari=Zulfikar');
        $this->assertCount(1, $hasil['prospek']['data']);
        $this->assertSame('Zulfikar Terakhir', $hasil['prospek']['data'][0]['Nama']);
    }

    public function test_penyaring_tahap_bekerja_lewat_relasi_dan_dilaporkan_balik(): void
    {
        $this->buatProspek('Prospek Baru');
        $this->buatProspek('Prospek Dihubungi', KatalogTahapPipeline::DIHUBUNGI);

        $props = $this->props('?tahap='.KatalogTahapPipeline::DIHUBUNGI);

        $this->assertCount(1, $props['prospek']['data']);
        $this->assertSame('Prospek Dihubungi', $props['prospek']['data'][0]['Nama']);
        $this->assertSame(KatalogTahapPipeline::DIHUBUNGI, $props['filter']['tahap']);
    }

    public function test_tahap_yang_tidak_dikenal_tidak_mengembalikan_apa_apa(): void
    {
        $this->buatProspek('Prospek Baru');

        $props = $this->props('?tahap=TAHAP-KARANGAN');

        $this->assertCount(0, $props['prospek']['data']);
    }

    public function test_urutan_bawaan_skor_tertinggi_lebih_dulu(): void
    {
        $this->buatProspek('Skor Rendah', skor: 10);
        $this->buatProspek('Skor Tinggi', skor: 90);

        $props = $this->props();

        $this->assertSame('Skor Tinggi', $props['prospek']['data'][0]['Nama']);
        $this->assertSame('Skor', $props['filter']['urut']);
        $this->assertSame('desc', $props['filter']['arah']);
    }
}
