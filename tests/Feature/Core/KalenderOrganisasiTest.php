<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Kalender lokal organisasi.
 *
 * Setiap kasus dipasang pada jam ketika tanggal lokal dan tanggal UTC
 * BERBEDA. Di luar jam itu keduanya kebetulan sama, sehingga helper yang
 * keliru memakai tanggal UTC akan tetap lulus.
 */
final class KalenderOrganisasiTest extends TestCase
{
    use DatabaseTransactions;

    private Organisasi $jakarta;

    private Organisasi $jayapura;

    protected function setUp(): void
    {
        parent::setUp();

        // Senin 21 September 18:30 UTC = Selasa 22 September 01:30 WIB = 03:30 WIT.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-21 18:30:00', 'UTC'));

        $this->jakarta = Organisasi::create(['Kode' => 'KAL-JKT-'.uniqid(), 'Nama' => 'RS Jakarta', 'ZonaWaktu' => 'Asia/Jakarta']);
        $this->jayapura = Organisasi::create(['Kode' => 'KAL-JPR-'.uniqid(), 'Nama' => 'RS Jayapura', 'ZonaWaktu' => 'Asia/Jayapura']);
    }

    protected function tearDown(): void
    {
        app(KonteksOrganisasi::class)->bersihkan();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function kalender(): KalenderOrganisasi
    {
        return app(KalenderOrganisasi::class);
    }

    public function test_hari_ini_mengikuti_tanggal_lokal_organisasi_bukan_tanggal_utc(): void
    {
        $this->assertSame('2026-09-21', CarbonImmutable::now()->toDateString(), 'Pembanding: di UTC masih tanggal 21.');

        $this->assertSame('2026-09-22', $this->kalender()->hariIni($this->jakarta->Id)->toDateString());
        $this->assertSame('2026-09-22', $this->kalender()->hariIni($this->jayapura->Id)->toDateString());
    }

    public function test_hari_ini_berbentuk_seperti_kolom_tanggal_yang_dibaca_eloquent(): void
    {
        $hariIni = $this->kalender()->hariIni($this->jakarta->Id);

        // Tengah malam UTC, supaya dapat dibandingkan langsung dengan nilai kolom `date`.
        $this->assertSame('2026-09-22 00:00:00 UTC', $hariIni->format('Y-m-d H:i:s T'));
        $this->assertSame(3, (int) $hariIni->diffInDays(CarbonImmutable::parse('2026-09-25'), false));
    }

    public function test_hari_ini_memakai_konteks_organisasi_bila_id_tidak_disebut(): void
    {
        // 16:30 UTC: di Jakarta masih 21 September 23:30, di Jayapura sudah 22 September 01:30.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-21 16:30:00', 'UTC'));

        app(KonteksOrganisasi::class)->tetapkan($this->jayapura->Id);
        $this->assertSame('2026-09-22', $this->kalender()->hariIni()->toDateString());

        app(KonteksOrganisasi::class)->tetapkan($this->jakarta->Id);
        $this->assertSame('2026-09-21', app(KalenderOrganisasi::class)->hariIni()->toDateString());
    }

    public function test_tanpa_konteks_organisasi_dipakai_zona_bawaan(): void
    {
        config(['amanpoll.zona_waktu_default' => 'Asia/Jakarta']);

        $this->assertSame('Asia/Jakarta', $this->kalender()->zona());
        $this->assertSame('2026-09-22', $this->kalender()->hariIni()->toDateString());
    }

    public function test_awal_hari_adalah_tengah_malam_lokal_dalam_utc(): void
    {
        $this->assertSame('2026-09-21 17:00:00', $this->kalender()->awalHari('2026-09-22', $this->jakarta->Id)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-21 15:00:00', $this->kalender()->awalHari('2026-09-22', $this->jayapura->Id)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-22 17:00:00', $this->kalender()->awalHariBerikutnya('2026-09-22', $this->jakarta->Id)->format('Y-m-d H:i:s'));

        // Nilai kolom `date` (tengah malam UTC) diperlakukan sebagai tanggal kalendernya.
        $kolomTanggal = CarbonImmutable::parse('2026-09-22', 'UTC');
        $this->assertSame('2026-09-21 17:00:00', $this->kalender()->awalHari($kolomTanggal, $this->jakarta->Id)->format('Y-m-d H:i:s'));
    }

    public function test_tanggal_lokal_dan_offset_sql(): void
    {
        $momen = CarbonImmutable::parse('2026-09-21 16:30:00', 'UTC');

        $this->assertSame('2026-09-21', $this->kalender()->tanggalLokal($momen, $this->jakarta->Id));
        $this->assertSame('2026-09-22', $this->kalender()->tanggalLokal($momen, $this->jayapura->Id));
        $this->assertSame('+07:00', $this->kalender()->offsetSql($this->jakarta->Id));
        $this->assertSame('+09:00', $this->kalender()->offsetSql($this->jayapura->Id));
    }
}
