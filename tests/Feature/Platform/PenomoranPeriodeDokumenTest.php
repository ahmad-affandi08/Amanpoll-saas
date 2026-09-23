<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * FASE 26.01 — mesin nomor dokumen: reset bulanan, placeholder periode, dan
 * urutan yang terpisah per organisasi serta per jenis dokumen.
 *
 * Jam uji dipilih jauh dari tengah malam, baik UTC maupun WIB, kecuali pada
 * test pergantian tahun yang justru menguji jam di antara keduanya.
 */
final class PenomoranPeriodeDokumenTest extends TestCase
{
    use DatabaseTransactions;

    private LayananNomorDokumen $layanan;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-30 03:00:00', 'UTC'));
        $this->layanan = app(LayananNomorDokumen::class);
    }

    protected function tearDown(): void
    {
        app(KonteksOrganisasi::class)->bersihkan();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_reset_bulanan_mengembalikan_nomor_ke_satu_saat_bulan_berganti(): void
    {
        $organisasi = $this->buatOrganisasi('NMR-BLN');
        $this->buatPola($organisasi, 'PerintahKerja', 'SPK', '{Awalan}/{Periode}/{Nomor:4}', 'Bulanan');

        $this->assertSame('SPK/2026-09/0001', $this->layanan->berikutnya($organisasi->Id, 'PerintahKerja'));
        $this->assertSame('SPK/2026-09/0002', $this->layanan->berikutnya($organisasi->Id, 'PerintahKerja'));

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-01 03:00:00', 'UTC'));

        $this->assertSame('SPK/2026-10/0001', $this->layanan->berikutnya($organisasi->Id, 'PerintahKerja'));
        $this->assertSame('SPK/2026-10/0002', $this->layanan->berikutnya($organisasi->Id, 'PerintahKerja'));
    }

    public function test_placeholder_tahun_pendek_dan_bulan_diisi_dari_tanggal_berjalan(): void
    {
        $organisasi = $this->buatOrganisasi('NMR-FMT');
        $this->buatPola($organisasi, 'PesananPembelian', 'PO', '{Awalan}-{TahunPendek}{Bulan}-{Nomor:3}', 'TidakAda');

        $this->assertSame('PO-2609-001', $this->layanan->berikutnya($organisasi->Id, 'PesananPembelian'));
    }

    public function test_urutan_terpisah_per_organisasi_dan_per_jenis_dokumen(): void
    {
        $organisasiA = $this->buatOrganisasi('NMR-A');
        $organisasiB = $this->buatOrganisasi('NMR-B');
        $this->buatPola($organisasiA, 'Keluhan', 'KLH', '{Awalan}-{Nomor:3}', 'TidakAda');
        $this->buatPola($organisasiA, 'PerintahKerja', 'SPK', '{Awalan}-{Nomor:3}', 'TidakAda');
        $this->buatPola($organisasiB, 'Keluhan', 'KLH', '{Awalan}-{Nomor:3}', 'TidakAda');

        // Organisasi A sudah memakai tiga nomor keluhan lebih dulu.
        foreach (['KLH-001', 'KLH-002', 'KLH-003'] as $harapan) {
            $this->assertSame($harapan, $this->layanan->berikutnya($organisasiA->Id, 'Keluhan'));
        }

        $this->assertSame('KLH-001', $this->layanan->berikutnya($organisasiB->Id, 'Keluhan'));
        $this->assertSame('SPK-001', $this->layanan->berikutnya($organisasiA->Id, 'PerintahKerja'));
        $this->assertSame('KLH-004', $this->layanan->berikutnya($organisasiA->Id, 'Keluhan'));
    }

    private function buatOrganisasi(string $kode): Organisasi
    {
        return Organisasi::create(['Kode' => $kode.'-'.uniqid(), 'Nama' => 'Organisasi '.$kode, 'Status' => 'Aktif']);
    }

    private function buatPola(Organisasi $organisasi, string $jenis, string $awalan, string $format, string $reset): void
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        NomorDokumen::create([
            'JenisDokumen' => $jenis,
            'Awalan' => $awalan,
            'FormatNomor' => $format,
            'ResetPeriode' => $reset,
        ]);
        app(KonteksOrganisasi::class)->bersihkan();
    }

    /**
     * Periode penomoran dibaca di kalender rumah sakit.
     *
     * 1 Januari pukul 06:00 WIB masih 31 Desember di UTC. Dengan jam UTC,
     * dokumen pertama tahun baru melanjutkan urutan tahun lalu dan bertahun
     * lama; di Jayapura yang sudah berganti tahun, begitu pula.
     */
    public function test_reset_tahunan_mengikuti_pergantian_tahun_di_zona_organisasi(): void
    {
        $organisasi = $this->buatOrganisasi('NMR-THN');
        $this->buatPola($organisasi, 'Keluhan', 'KLH', '{Awalan}/{Tahun}/{Nomor:4}', 'Tahunan');

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-31 10:00:00', 'UTC'));
        $this->assertSame('KLH/2026/0001', $this->layanan->berikutnya($organisasi->Id, 'Keluhan'));

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-31 23:00:00', 'UTC'));
        $this->assertSame('KLH/2027/0001', $this->layanan->pratinjau($organisasi->Id, 'Keluhan'));
        $this->assertSame('KLH/2027/0001', $this->layanan->berikutnya($organisasi->Id, 'Keluhan'));
    }
}
