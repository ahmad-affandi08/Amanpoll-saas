<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Idempotensi\LayananIdempotensi;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KunciIdempotensi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * FASE 26.01 — aturan kunci idempotensi yang tidak tercakup test endpoint:
 * lingkup per organisasi dan masa berlaku kunci.
 */
final class LayananIdempotensiTest extends TestCase
{
    use DatabaseTransactions;

    private const RUTE = 'POST api/v1/keluhan';

    private const SIDIK_JARI = 'sidik-jari-muatan-yang-sama';

    private LayananIdempotensi $layanan;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 08:00:00', 'UTC'));
        $this->layanan = app(LayananIdempotensi::class);
    }

    protected function tearDown(): void
    {
        app(KonteksOrganisasi::class)->bersihkan();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_kunci_yang_sama_di_organisasi_lain_tidak_memutar_ulang_respons_milik_organisasi_pertama(): void
    {
        $organisasiA = $this->buatOrganisasi('IDM-A');
        $organisasiB = $this->buatOrganisasi('IDM-B');

        app(KonteksOrganisasi::class)->tetapkan($organisasiA->Id);
        $this->assertNull($this->layanan->daftarkan($organisasiA->Id, 'kunci-klien-1', self::RUTE, self::SIDIK_JARI));
        $this->layanan->simpanRespons($organisasiA->Id, 'kunci-klien-1', self::RUTE, response('{"Id":"milik-a"}', 201));

        // Pembanding: di organisasi A sendiri, kunci itu memang diputar ulang.
        $ulangA = $this->layanan->daftarkan($organisasiA->Id, 'kunci-klien-1', self::RUTE, self::SIDIK_JARI);
        $this->assertSame('{"Id":"milik-a"}', $ulangA?->Respons);

        app(KonteksOrganisasi::class)->tetapkan($organisasiB->Id);
        $this->assertNull(
            $this->layanan->daftarkan($organisasiB->Id, 'kunci-klien-1', self::RUTE, self::SIDIK_JARI),
            'Organisasi B tidak boleh menerima respons tersimpan milik organisasi A.',
        );

        $this->assertSame(2, KunciIdempotensi::query()->withoutGlobalScopes()
            ->where('Kunci', 'kunci-klien-1')
            ->count());
    }

    public function test_kunci_yang_kedaluwarsa_diproses_ulang_sebagai_permintaan_baru(): void
    {
        $organisasi = $this->buatOrganisasi('IDM-TTL');
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $this->assertNull($this->layanan->daftarkan($organisasi->Id, 'kunci-ttl', self::RUTE, self::SIDIK_JARI));
        $this->layanan->simpanRespons($organisasi->Id, 'kunci-ttl', self::RUTE, response('{"Id":"pertama"}', 201));

        // Satu menit sebelum masa berlaku 24 jam habis: masih pengulangan.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-02 07:59:00', 'UTC'));
        $ulang = $this->layanan->daftarkan($organisasi->Id, 'kunci-ttl', self::RUTE, self::SIDIK_JARI);
        $this->assertSame(201, $ulang?->StatusHttp);

        // Lewat masa berlaku: kunci dilepas dan permintaan diproses sebagai yang pertama.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-02 08:01:00', 'UTC'));
        $this->assertNull($this->layanan->daftarkan($organisasi->Id, 'kunci-ttl', self::RUTE, self::SIDIK_JARI));

        $baris = KunciIdempotensi::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasi->Id)
            ->where('Kunci', 'kunci-ttl')
            ->sole();
        $this->assertNull($baris->StatusHttp, 'Respons lama tidak boleh ikut terbawa ke pendaftaran baru.');
        $this->assertSame('2026-09-03 08:01:00', $baris->KadaluarsaPada->utc()->format('Y-m-d H:i:s'));
    }

    private function buatOrganisasi(string $kode): Organisasi
    {
        return Organisasi::create(['Kode' => $kode.'-'.uniqid(), 'Nama' => 'Organisasi '.$kode, 'Status' => 'Aktif']);
    }
}
