<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pelaporan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pelaporan\Application\Services\RegistriKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\RumusKeandalan;
use App\Domain\Pemasaran\Application\Services\KalkulatorKeandalanPublik;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Gate 38.05: angka kalkulator publik harus sama dengan angka KPI yang sama di
 * dalam aplikasi, karena keduanya membaca rumus yang sama.
 */
final class RumusKeandalanDipakaiBersamaTest extends TestCase
{
    use DatabaseTransactions;

    private Organisasi $organisasi;

    private UnitOrganisasi $unit;

    private Lokasi $lokasi;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-06-15 09:00:00');

        $this->organisasi = Organisasi::create([
            'Kode' => 'ORG-RUMUS-'.uniqid(),
            'Nama' => 'Organisasi Rumus',
        ]);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        $this->unit = UnitOrganisasi::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'UNIT-'.uniqid(),
            'Nama' => 'Unit Produksi',
        ]);
        $this->lokasi = Lokasi::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'LOK-'.uniqid(),
            'Nama' => 'Gedung A',
            'Status' => 'Aktif',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /** Inti Gate 38.05: dua pintu masuk, satu angka. */
    public function test_kalkulator_publik_dan_kpi_menghasilkan_angka_yang_sama(): void
    {
        $aset = $this->buatAset();
        $this->buatDowntime($aset, CarbonImmutable::now()->subDays(3), 120);
        $this->buatDowntime($aset, CarbonImmutable::now()->subDays(2), 60);

        $dari = CarbonImmutable::now()->subDays(4)->startOfDay();
        $sampai = CarbonImmutable::now()->endOfDay();
        $filter = new FilterMetrik($dari, $sampai);

        // Kalkulator publik menerima angka mentah; menitnya disamakan dengan rentang filter KPI.
        $menitRentang = (int) $dari->diffInMinutes($sampai);
        $publik = app(KalkulatorKeandalanPublik::class)->hitung(
            jumlahAset: 1,
            hariRentang: intdiv($menitRentang, 1440),
            jumlahKegagalan: 2,
            menitDowntime: 180,
        );

        $this->assertSame($this->kpi('downtime.total_jam', $filter), $publik->totalJam);
        $this->assertSame($this->kpi('keandalan.mttr', $filter), $publik->mttr);
    }

    /** Rentang KPI dan rentang kalkulator yang persis sama panjangnya juga sama hasilnya. */
    public function test_mtbf_dan_ketersediaan_sama_pada_rentang_yang_sama_panjang(): void
    {
        $aset = $this->buatAset();
        $this->buatDowntime($aset, CarbonImmutable::now()->subHours(20), 120);
        $this->buatDowntime($aset, CarbonImmutable::now()->subHours(10), 60);

        $dari = CarbonImmutable::now()->subDay();
        $sampai = CarbonImmutable::now();
        $filter = new FilterMetrik($dari, $sampai);

        $publik = app(KalkulatorKeandalanPublik::class)->hitung(
            jumlahAset: 1,
            hariRentang: 1,
            jumlahKegagalan: 2,
            menitDowntime: 180,
        );

        $this->assertSame($this->kpi('keandalan.mtbf', $filter), $publik->mtbf);
        $this->assertSame($this->kpi('downtime.ketersediaan', $filter), $publik->ketersediaan);
    }

    /** Rumusnya menolak penyebut nol alih-alih membaginya diam-diam. */
    public function test_rumus_menolak_kegagalan_nol(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        RumusKeandalan::mttr(180, 0);
    }

    public function test_rumus_menolak_waktu_operasional_nol(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        RumusKeandalan::ketersediaan(0.0, 180);
    }

    /** Downtime yang melampaui waktu operasional tidak boleh menjadi waktu aktif negatif. */
    public function test_waktu_aktif_tidak_pernah_negatif(): void
    {
        $this->assertSame(0.0, RumusKeandalan::menitAktif(100.0, 500));
    }

    private function kpi(string $kunci, FilterMetrik $filter): float
    {
        return app(RegistriKpi::class)->untuk($kunci)->hitung($kunci, $filter)->nilai;
    }

    private function buatAset(): Aset
    {
        $kategori = KategoriAset::firstOrCreate(
            ['OrganisasiId' => $this->organisasi->Id, 'Kode' => 'KAT-RUMUS'],
            ['Nama' => 'Kategori Rumus'],
        );

        return Aset::create([
            'OrganisasiId' => $this->organisasi->Id,
            'KategoriAsetId' => $kategori->Id,
            'UnitOrganisasiId' => $this->unit->Id,
            'LokasiId' => $this->lokasi->Id,
            'KodeAset' => 'AST-RUMUS-'.uniqid(),
            'Nama' => 'Aset Rumus',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'HargaPerolehan' => 1_000_000,
        ]);
    }

    private function buatDowntime(Aset $aset, CarbonImmutable $mulai, int $menit): WaktuHentiAset
    {
        return WaktuHentiAset::create([
            'OrganisasiId' => $this->organisasi->Id,
            'AsetId' => $aset->Id,
            'MulaiPada' => $mulai,
            'SelesaiPada' => $mulai->addMinutes($menit),
            'DurasiMenit' => $menit,
            'Jenis' => 'TidakTerencana',
            'Alasan' => 'Uji rumus bersama',
        ]);
    }
}
