<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Pemeliharaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Application\Services\LayananKalkulasiSla;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AturanTingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LayananKalkulasiSlaTest extends TestCase
{
    use RefreshDatabase;

    public function test_deadline_response_dan_resolution_melintasi_akhir_pekan(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-SLA-1', 'Nama' => 'Organisasi SLA']);
        $hasil = $this->layanan()->hitungBatas(
            $organisasi->Id,
            $this->tingkatLayanan(),
            $this->aturan(),
            CarbonImmutable::parse('2026-09-18 16:30:00', 'Asia/Jakarta'),
            'Asia/Jakarta',
        );

        $this->assertSame('2026-09-21 09:30:00', $hasil['respons']?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-22 08:30:00', $hasil['penyelesaian']?->format('Y-m-d H:i:s'));
    }

    public function test_deadline_melewati_hari_libur_organisasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-SLA-2', 'Nama' => 'Organisasi SLA']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        HariLibur::create([
            'Tanggal' => '2026-09-21',
            'Nama' => 'Libur Operasional',
            'BerulangTahunan' => false,
        ]);

        $hasil = $this->layanan()->hitungBatas(
            $organisasi->Id,
            $this->tingkatLayanan(),
            $this->aturan(),
            CarbonImmutable::parse('2026-09-18 16:30:00', 'Asia/Jakarta'),
            'Asia/Jakarta',
        );

        $this->assertSame('2026-09-22 09:30:00', $hasil['respons']?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-23 08:30:00', $hasil['penyelesaian']?->format('Y-m-d H:i:s'));
    }

    private function layanan(): LayananKalkulasiSla
    {
        return app(LayananKalkulasiSla::class);
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

    private function aturan(): AturanTingkatLayanan
    {
        return new AturanTingkatLayanan([
            'Prioritas' => 'Normal',
            'MenitRespons' => 120,
            'MenitPenyelesaian' => 600,
            'MenghitungJamKerja' => true,
        ]);
    }
}
