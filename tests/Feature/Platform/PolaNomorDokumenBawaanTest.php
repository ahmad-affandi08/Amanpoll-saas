<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tenant baru tidak pernah dibekali pola nomor dokumen, sehingga keluhan dan
 * perintah kerja pertamanya dulu gagal dengan "pola belum diatur". Pola bawaan
 * kini dipasang saat pertama kali dibutuhkan, tanpa menimpa pola buatan admin.
 */
final class PolaNomorDokumenBawaanTest extends TestCase
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

    public function test_organisasi_tanpa_pola_mendapat_pola_bawaan_saat_nomor_pertama_diminta(): void
    {
        $organisasi = $this->buatOrganisasi();

        $this->assertSame('KLH/2026/0001', $this->layanan->berikutnya($organisasi->Id, 'Keluhan'));
        $this->assertSame('KLH/2026/0002', $this->layanan->berikutnya($organisasi->Id, 'Keluhan'));
        $this->assertSame('PK/2026/0001', $this->layanan->berikutnya($organisasi->Id, 'PerintahKerja'));

        $this->assertSame(1, $this->jumlahPola($organisasi, 'Keluhan'));
    }

    public function test_pratinjau_memasang_pola_bawaan_tanpa_memakai_nomor(): void
    {
        $organisasi = $this->buatOrganisasi();

        $this->assertSame('MS/2026/0001', $this->layanan->pratinjau($organisasi->Id, 'MutasiStok'));
        $this->assertSame('MS/2026/0001', $this->layanan->berikutnya($organisasi->Id, 'MutasiStok'));
    }

    public function test_pola_buatan_admin_tidak_ditimpa_pola_bawaan(): void
    {
        $organisasi = $this->buatOrganisasi();
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        NomorDokumen::create([
            'JenisDokumen' => 'Keluhan',
            'Awalan' => 'TIKET',
            'FormatNomor' => '{Awalan}-{Nomor:3}',
            'ResetPeriode' => 'TidakAda',
        ]);
        app(KonteksOrganisasi::class)->bersihkan();

        $this->assertSame('TIKET-001', $this->layanan->berikutnya($organisasi->Id, 'Keluhan'));
        $this->assertSame(1, $this->jumlahPola($organisasi, 'Keluhan'));
    }

    public function test_jenis_dokumen_yang_tidak_dikenal_tetap_ditolak(): void
    {
        $organisasi = $this->buatOrganisasi();

        try {
            $this->layanan->berikutnya($organisasi->Id, 'DokumenKarangan');
            $this->fail('Jenis dokumen tanpa pola bawaan seharusnya ditolak.');
        } catch (DataTidakDitemukan) {
            // diharapkan
        }

        $this->assertSame(0, $this->jumlahPola($organisasi, 'DokumenKarangan'));
    }

    private function buatOrganisasi(): Organisasi
    {
        return Organisasi::create(['Kode' => 'POLA-'.uniqid(), 'Nama' => 'Organisasi Pola Bawaan', 'Status' => 'Aktif']);
    }

    private function jumlahPola(Organisasi $organisasi, string $jenis): int
    {
        return DB::table('NomorDokumen')
            ->where('OrganisasiId', $organisasi->Id)
            ->where('JenisDokumen', $jenis)
            ->count();
    }
}
