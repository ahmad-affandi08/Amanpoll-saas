<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Domain\Notifikasi\Notifications\NotifikasiUmum;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Shared\Infrastructure\Persistence\PembacaUkuranBasisData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * FASE 45 temuan #6: pemantau ukuran basis data terhadap batas hosting 3 GB.
 *
 * Ukuran dipalsukan lewat PembacaUkuranBasisData supaya ambangnya dapat diuji
 * tanpa mengisi basis data sungguhan; satu test tetap membaca
 * information_schema asli untuk memastikan kuerinya jalan di MariaDB.
 */
final class PemantauUkuranBasisDataTest extends TestCase
{
    use DatabaseTransactions;

    private const MB = 1024 * 1024;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'amanpoll.retensi.pemantau.batas_byte' => 1000 * self::MB,
            'amanpoll.retensi.pemantau.ambang_peringatan_persen' => 70,
            'amanpoll.retensi.pemantau.ambang_kritis_persen' => 85,
            'amanpoll.retensi.pemantau.kirim_email_admin' => true,
        ]);
        Notification::fake();

        AdminPlatform::create(['Nama' => 'Admin aktif', 'Email' => 'admin@amanpoll.test', 'KataSandi' => 'rahasia', 'Status' => 'Aktif']);
        AdminPlatform::create(['Nama' => 'Admin nonaktif', 'Email' => 'lama@amanpoll.test', 'KataSandi' => 'rahasia', 'Status' => 'Nonaktif']);
    }

    public function test_di_bawah_ambang_hanya_mencatat_ukuran(): void
    {
        $this->palsukanUkuran(['CatatanAudit' => 300, 'Notifikasi' => 200, 'Aset' => 100]);
        Log::spy();

        $this->artisan('basisdata:pantau-ukuran')
            ->expectsOutputToContain('600,0 MB (60% dari 1.000,0 MB)')
            ->assertSuccessful();

        Log::shouldHaveReceived('info')->withArgs(fn (string $pesan, array $konteks): bool => $pesan === 'Ukuran basis data.'
            && $konteks['TotalByte'] === 600 * self::MB
            && $konteks['Persen'] === 60.0
            && $konteks['CatatanAudit']['Byte'] === 300 * self::MB
            && array_column($konteks['Terbesar'], 'Nama') === ['CatatanAudit', 'Notifikasi', 'Aset'])->once();
        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('error');
        Notification::assertNothingSent();
    }

    public function test_ambang_peringatan_menulis_warning_dan_mengirim_email_ke_admin_aktif(): void
    {
        $this->palsukanUkuran(['Notifikasi' => 500, 'CatatanAudit' => 250]);
        Log::spy();

        $this->artisan('basisdata:pantau-ukuran')->assertSuccessful();

        Log::shouldHaveReceived('warning')->withArgs(fn (string $pesan): bool => str_contains($pesan, 'PERINGATAN') && str_contains($pesan, 'Notifikasi'))->once();
        Log::shouldNotHaveReceived('error');
        Notification::assertSentOnDemand(
            NotifikasiUmum::class,
            fn (NotifikasiUmum $notifikasi, array $kanal, AnonymousNotifiable $tujuan): bool => $tujuan->routes['mail'] === ['admin@amanpoll.test'],
        );
    }

    public function test_ambang_kritis_menulis_error(): void
    {
        $this->palsukanUkuran(['KotakKeluarPeristiwa' => 900]);
        Log::spy();

        $this->artisan('basisdata:pantau-ukuran')->assertSuccessful();

        Log::shouldHaveReceived('error')->withArgs(fn (string $pesan): bool => str_contains($pesan, 'KRITIS'))->once();
        Log::shouldNotHaveReceived('warning');
        Notification::assertSentOnDemandTimes(NotifikasiUmum::class, 1);
    }

    public function test_tanpa_admin_beremail_peringatan_tetap_tercatat_sebagai_error(): void
    {
        AdminPlatform::query()->update(['Status' => 'Nonaktif']);
        $this->palsukanUkuran(['Notifikasi' => 750]);
        Log::spy();

        $this->artisan('basisdata:pantau-ukuran')->assertSuccessful();

        Log::shouldHaveReceived('warning')->once();
        Log::shouldHaveReceived('error')->withArgs(fn (string $pesan): bool => str_contains($pesan, 'tidak ada admin platform'))->once();
        Notification::assertNothingSent();
    }

    public function test_membaca_information_schema_sungguhan(): void
    {
        $tabel = app(PembacaUkuranBasisData::class)->ukuranTabel();
        $nama = array_column($tabel, 'Nama');

        $this->assertContains('CatatanAudit', $nama);
        $this->assertContains('Notifikasi', $nama);
        $this->assertNotContains('ViewRingkasanAset', $nama, 'View bukan tabel dan tidak punya ukuran sendiri.');
        $this->assertGreaterThan(0, array_sum(array_column($tabel, 'Byte')));

        $this->artisan('basisdata:pantau-ukuran')->assertSuccessful();
    }

    /** @param array<string, int> $ukuranMb */
    private function palsukanUkuran(array $ukuranMb): void
    {
        $this->app->instance(PembacaUkuranBasisData::class, new class($ukuranMb) extends PembacaUkuranBasisData
        {
            /** @param array<string, int> $ukuranMb */
            public function __construct(private readonly array $ukuranMb) {}

            public function ukuranTabel(): array
            {
                $hasil = [];
                foreach ($this->ukuranMb as $nama => $mb) {
                    $hasil[] = ['Nama' => $nama, 'Byte' => $mb * 1024 * 1024, 'PerkiraanBaris' => $mb * 10];
                }

                return $hasil;
            }
        });
    }
}
