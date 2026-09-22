<?php

declare(strict_types=1);

namespace Tests\Unit\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PenyusunUlangAttribution;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AttributionPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiPengunjung;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\UtmPemasaran;
use App\Domain\Pemasaran\Jobs\HitungAttribution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Penyusunan ulang attribution dari data mentah (MARKETING.md 14, 36). */
final class HitungAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sentuhan_pertama_diambil_dari_sesi_paling_awal(): void
    {
        $pengenal = (string) Str::ulid();

        $this->buatSesi($pengenal, 'google', 'awal', now()->subDays(3));
        $this->buatSesi($pengenal, 'linkedin', 'akhir', now());

        $attribution = app(PenyusunUlangAttribution::class)->susunUlang($pengenal);

        $this->assertNotNull($attribution);
        $this->assertSame('google', $attribution->SumberPertama);
        $this->assertSame('linkedin', $attribution->SumberTerakhir);
    }

    public function test_pengunjung_tanpa_sesi_tidak_menghasilkan_baris(): void
    {
        $this->assertNull(app(PenyusunUlangAttribution::class)->susunUlang((string) Str::ulid()));
        $this->assertSame(0, AttributionPemasaran::query()->count());
    }

    public function test_pekerjaan_menautkan_kampanye_yang_baru_didaftarkan(): void
    {
        $pengenal = (string) Str::ulid();
        $this->buatSesi($pengenal, 'google', 'menyusul', now());

        $kampanye = Kampanye::create([
            'Kode' => 'menyusul',
            'Nama' => 'Menyusul',
            'Objective' => 'Lead',
            'Status' => 'Aktif',
        ]);

        (new HitungAttribution($pengenal))->handle(app(PenyusunUlangAttribution::class));

        $this->assertSame(
            $kampanye->Id,
            AttributionPemasaran::query()->where('PengenalPengunjung', $pengenal)->value('KampanyeIdPertama'),
        );
    }

    public function test_satu_pengunjung_hanya_punya_satu_baris_attribution(): void
    {
        $pengenal = (string) Str::ulid();
        $this->buatSesi($pengenal, 'google', null, now());

        $penyusun = app(PenyusunUlangAttribution::class);
        $penyusun->susunUlang($pengenal);
        $penyusun->susunUlang($pengenal);

        $this->assertSame(1, AttributionPemasaran::query()->count());
    }

    private function buatSesi(string $pengenal, ?string $source, ?string $campaign, \DateTimeInterface $pada): void
    {
        $sesi = SesiPengunjung::create([
            'PengenalPengunjung' => $pengenal,
            'LandingUrl' => 'http://publik.localhost/',
            'DimulaiPada' => $pada,
            'TerakhirAktifPada' => $pada,
        ]);

        UtmPemasaran::create([
            'SesiPengunjungId' => $sesi->Id,
            'Source' => $source,
            'Campaign' => $campaign,
        ]);
    }
}
