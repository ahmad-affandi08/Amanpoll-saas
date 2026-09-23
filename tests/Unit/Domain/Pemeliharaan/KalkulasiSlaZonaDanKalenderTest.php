<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Pemeliharaan;

use App\Domain\Pemeliharaan\Application\Services\LayananKalkulasiSla;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AturanTingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pelengkap LayananKalkulasiSlaTest: jalur yang belum dijaga di sana, yaitu
 * zona waktu lokasi yang berbeda dari zona aplikasi, aturan berjam kalender
 * penuh, batas yang sengaja tidak diisi, dan laporan sebelum jam buka.
 */
final class KalkulasiSlaZonaDanKalenderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Jumat 16:30 WIB sudah 17:30 WITA: bagi lokasi di Makassar jam kerja
     * hari itu telah usai, jadi hitungan mulai Senin 08:00 WITA. Hasilnya
     * disimpan kembali dalam zona aplikasi (WIB), sejam lebih awal dari
     * hitungan yang keliru memakai jam kerja WIB (09:30 dan 08:30).
     */
    public function test_jam_kerja_dihitung_pada_zona_lokasi_lalu_disimpan_pada_zona_aplikasi(): void
    {
        $hasil = $this->layanan()->hitungBatas(
            $this->organisasi()->Id,
            $this->tingkatLayanan(),
            $this->aturan(120, 600, true),
            CarbonImmutable::parse('2026-09-18 09:30:00', 'UTC'),
            'Asia/Makassar',
        );

        $this->assertSame('2026-09-21 09:00:00', $hasil['respons']?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-22 08:00:00', $hasil['penyelesaian']?->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Jakarta', $hasil['respons']?->timezoneName);
    }

    public function test_aturan_tanpa_jam_kerja_menghitung_menit_kalender_termasuk_akhir_pekan(): void
    {
        $hasil = $this->layanan()->hitungBatas(
            $this->organisasi()->Id,
            $this->tingkatLayanan(),
            $this->aturan(120, 600, false),
            CarbonImmutable::parse('2026-09-18 16:30:00', 'Asia/Jakarta'),
            'Asia/Jakarta',
        );

        $this->assertSame('2026-09-18 18:30:00', $hasil['respons']?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-19 02:30:00', $hasil['penyelesaian']?->format('Y-m-d H:i:s'));
    }

    public function test_batas_tanpa_menit_dibiarkan_kosong_sementara_batas_lain_tetap_dihitung(): void
    {
        $hasil = $this->layanan()->hitungBatas(
            $this->organisasi()->Id,
            $this->tingkatLayanan(),
            $this->aturan(null, 60, true),
            CarbonImmutable::parse('2026-09-21 10:00:00', 'Asia/Jakarta'),
            'Asia/Jakarta',
        );

        $this->assertNull($hasil['respons']);
        $this->assertSame('2026-09-21 11:00:00', $hasil['penyelesaian']?->format('Y-m-d H:i:s'));
    }

    public function test_laporan_sebelum_jam_buka_mulai_dihitung_dari_jam_kerja_mulai(): void
    {
        $hasil = $this->layanan()->hitungBatas(
            $this->organisasi()->Id,
            $this->tingkatLayanan(),
            $this->aturan(120, 540, true),
            CarbonImmutable::parse('2026-09-21 06:00:00', 'Asia/Jakarta'),
            'Asia/Jakarta',
        );

        $this->assertSame('2026-09-21 10:00:00', $hasil['respons']?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-21 17:00:00', $hasil['penyelesaian']?->format('Y-m-d H:i:s'));
    }

    private function layanan(): LayananKalkulasiSla
    {
        return app(LayananKalkulasiSla::class);
    }

    private function organisasi(): Organisasi
    {
        return Organisasi::create(['Kode' => 'ORG-SLA-ZONA', 'Nama' => 'Organisasi SLA Zona']);
    }

    private function tingkatLayanan(): TingkatLayanan
    {
        return new TingkatLayanan([
            'HariKerja' => [1, 2, 3, 4, 5],
            'JamKerjaMulai' => '08:00:00',
            'JamKerjaSelesai' => '17:00:00',
            'MemperhitungkanHariLibur' => true,
        ]);
    }

    private function aturan(?int $menitRespons, ?int $menitPenyelesaian, bool $menghitungJamKerja): AturanTingkatLayanan
    {
        return new AturanTingkatLayanan([
            'Prioritas' => 'Normal',
            'MenitRespons' => $menitRespons,
            'MenitPenyelesaian' => $menitPenyelesaian,
            'MenghitungJamKerja' => $menghitungJamKerja,
        ]);
    }
}
