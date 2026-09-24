<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Izin\PemeriksaIzin;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Actions\PasangPeranAwal;
use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use App\Domain\Platform\Domain\Enums\ModeLapangan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Database\Seeders\IzinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Perintah `platform:terapkan-peran-lapangan` (TASK 39.01): tenant lama
 * diselaraskan secara eksplisit dan teraudit, tidak pernah diam-diam.
 */
final class TerapkanPeranLapanganTest extends TestCase
{
    use RefreshDatabase;

    private const AKSI_AUDIT = 'Peran.KatalogLapanganDiterapkan';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IzinSeeder::class);
    }

    protected function tearDown(): void
    {
        app(KonteksOrganisasi::class)->bersihkan();

        parent::tearDown();
    }

    public function test_pratinjau_menampilkan_rencana_tanpa_mengubah_apa_pun(): void
    {
        $organisasi = $this->organisasiDenganKatalogLama('ORG-PRATINJAU');

        $this->artisan('platform:terapkan-peran-lapangan', ['--pratinjau' => true])
            ->expectsOutputToContain('TEKNISI (Teknisi) cabut Keluhan.Kelola, PerintahKerja.Kelola; tandai Tampilan Lapangan Teknisi')
            ->expectsOutputToContain('PELAPOR (Pelapor Keluhan) cabut Keluhan.Kelola; tandai Tampilan Lapangan Pelapor')
            ->assertSuccessful();

        $this->assertSame(['Aset.Lihat', 'Keluhan.Kelola', 'Laporan.Lihat', 'Pemeliharaan.Kelola', 'PerintahKerja.Kelola'], $this->izinPeran($organisasi, 'TEKNISI'));
        $this->assertNull($this->tampilanPeran($organisasi, 'TEKNISI'));
        $this->assertSame(0, DB::table('CatatanAudit')->where('Aksi', self::AKSI_AUDIT)->count());
    }

    public function test_tanpa_konfirmasi_tidak_ada_yang_diubah(): void
    {
        $organisasi = $this->organisasiDenganKatalogLama('ORG-BATAL');

        $this->artisan('platform:terapkan-peran-lapangan')
            ->expectsConfirmation('Terapkan perubahan pada 2 peran di 1 organisasi?', 'no')
            ->expectsOutput('Penerapan dibatalkan.')
            ->assertFailed();

        $this->assertContains('PerintahKerja.Kelola', $this->izinPeran($organisasi, 'TEKNISI'));
        $this->assertSame(0, DB::table('CatatanAudit')->where('Aksi', self::AKSI_AUDIT)->count());
    }

    public function test_menerapkan_katalog_mencabut_kelola_menandai_peran_dan_mencatat_audit(): void
    {
        $organisasi = $this->organisasiDenganKatalogLama('ORG-TERAPKAN');
        $teknisi = $this->pemegang($organisasi, 'TEKNISI');
        // Hasil lama sempat tercache; penerapan harus langsung berlaku.
        $this->assertTrue($this->boleh($organisasi, $teknisi, 'PerintahKerja.Kelola'));
        $this->assertNull(app(PenentuModeLapangan::class)->mode($teknisi));

        $this->artisan('platform:terapkan-peran-lapangan')
            ->expectsConfirmation('Terapkan perubahan pada 2 peran di 1 organisasi?', 'yes')
            ->assertSuccessful();

        // Izin tambahan milik tenant (Laporan.Lihat) tidak ikut hilang.
        $this->assertSame(['Aset.Lihat', 'Laporan.Lihat', 'Pemeliharaan.Kelola'], $this->izinPeran($organisasi, 'TEKNISI'));
        $this->assertSame(['Aset.Lihat'], $this->izinPeran($organisasi, 'PELAPOR'));
        $this->assertSame('Teknisi', $this->tampilanPeran($organisasi, 'TEKNISI'));
        $this->assertSame('Pelapor', $this->tampilanPeran($organisasi, 'PELAPOR'));
        // Peran lain milik tenant tidak disentuh.
        $this->assertContains('PerintahKerja.Kelola', $this->izinPeran($organisasi, 'KOORDINATOR-PEMELIHARAAN'));

        $this->assertFalse($this->boleh($organisasi, $teknisi, 'PerintahKerja.Kelola'));
        $this->assertSame(ModeLapangan::Teknisi, app(PenentuModeLapangan::class)->mode($teknisi));

        $audit = DB::table('CatatanAudit')->where('Aksi', self::AKSI_AUDIT)->where('OrganisasiId', $organisasi->Id)->get();
        $this->assertCount(2, $audit);
        $teknisiAudit = $audit->firstWhere('EntitasId', $this->peran($organisasi, 'TEKNISI')->Id);
        $this->assertNotNull($teknisiAudit);
        $this->assertSame(['Keluhan.Kelola', 'PerintahKerja.Kelola'], json_decode((string) $teknisiAudit->DataSebelum, true)['IzinDicabut']);
    }

    public function test_menjalankan_ulang_tidak_mengubah_apa_pun_dan_tidak_menambah_audit(): void
    {
        $this->organisasiDenganKatalogLama('ORG-ULANG');
        $this->artisan('platform:terapkan-peran-lapangan', ['--paksa' => true])->assertSuccessful();

        $this->artisan('platform:terapkan-peran-lapangan', ['--paksa' => true])
            ->expectsOutput('Seluruh peran lapangan bawaan sudah selaras dengan katalog.')
            ->assertSuccessful();

        $this->assertSame(2, DB::table('CatatanAudit')->where('Aksi', self::AKSI_AUDIT)->count());
    }

    public function test_penanda_yang_sudah_dipilih_tenant_dibiarkan(): void
    {
        $organisasi = $this->organisasiDenganKatalogLama('ORG-PILIHAN');
        $this->dalamOrganisasi($organisasi, fn () => Peran::query()->where('Kode', 'TEKNISI')->update(['TampilanLapangan' => 'Pelapor']));

        $this->artisan('platform:terapkan-peran-lapangan', ['--paksa' => true])
            ->expectsOutputToContain('TEKNISI (Teknisi) cabut Keluhan.Kelola, PerintahKerja.Kelola; Tampilan Lapangan Pelapor pilihan organisasi dibiarkan')
            ->assertSuccessful();

        $this->assertSame('Pelapor', $this->tampilanPeran($organisasi, 'TEKNISI'));
        $this->assertNotContains('PerintahKerja.Kelola', $this->izinPeran($organisasi, 'TEKNISI'));
    }

    public function test_dapat_dibatasi_ke_satu_organisasi(): void
    {
        $satu = $this->organisasiDenganKatalogLama('ORG-SATU');
        $dua = $this->organisasiDenganKatalogLama('ORG-DUA');

        $this->artisan('platform:terapkan-peran-lapangan', ['--organisasi' => $satu->Id, '--paksa' => true])->assertSuccessful();

        $this->assertNotContains('PerintahKerja.Kelola', $this->izinPeran($satu, 'TEKNISI'));
        $this->assertContains('PerintahKerja.Kelola', $this->izinPeran($dua, 'TEKNISI'));
    }

    /**
     * Organisasi yang memasang katalog sebelum FASE 39: TEKNISI dan PELAPOR
     * masih memegang Kelola dan belum bertanda. TEKNISI juga diberi satu izin
     * tambahan oleh tenant sendiri.
     */
    private function organisasiDenganKatalogLama(string $kode): Organisasi
    {
        $organisasi = Organisasi::create(['Kode' => $kode, 'Nama' => $kode, 'Status' => 'Aktif']);
        app(PasangPeranAwal::class)->jalankan($organisasi->Id);

        $this->dalamOrganisasi($organisasi, function (): void {
            $izinLama = [
                'TEKNISI' => ['Keluhan.Kelola', 'PerintahKerja.Kelola', 'Laporan.Lihat'],
                'PELAPOR' => ['Keluhan.Kelola'],
            ];

            foreach ($izinLama as $kodePeran => $daftar) {
                $peran = Peran::query()->where('Kode', $kodePeran)->firstOrFail();
                $peran->update(['TampilanLapangan' => null]);

                foreach ($daftar as $kodeIzin) {
                    PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => Izin::query()->where('Kode', $kodeIzin)->firstOrFail()->Id]);
                }
            }
        });

        return $organisasi;
    }

    private function pemegang(Organisasi $organisasi, string $kodePeran): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pemegang '.$kodePeran,
            'Email' => 'pemegang+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $peranId = $this->peran($organisasi, $kodePeran)->Id;
        $this->dalamOrganisasi($organisasi, fn () => PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peranId]));

        return $pengguna;
    }

    private function boleh(Organisasi $organisasi, Pengguna $pengguna, string $kodeIzin): bool
    {
        return $this->dalamOrganisasi($organisasi, fn (): bool => app(PemeriksaIzin::class)->boleh($pengguna->Id, $kodeIzin));
    }

    private function peran(Organisasi $organisasi, string $kode): Peran
    {
        return $this->dalamOrganisasi($organisasi, fn (): Peran => Peran::query()->where('Kode', $kode)->firstOrFail());
    }

    /** @return list<string> */
    private function izinPeran(Organisasi $organisasi, string $kodePeran): array
    {
        return array_values(DB::table('PeranIzin as pi')
            ->join('Izin as i', 'i.Id', '=', 'pi.IzinId')
            ->where('pi.PeranId', $this->peran($organisasi, $kodePeran)->Id)
            ->orderBy('i.Kode')
            ->pluck('i.Kode')
            ->map(fn (mixed $kode): string => (string) $kode)
            ->all());
    }

    private function tampilanPeran(Organisasi $organisasi, string $kodePeran): ?string
    {
        return $this->peran($organisasi, $kodePeran)->TampilanLapangan?->value;
    }

    /**
     * @template T
     *
     * @param  callable():T  $aksi
     * @return T
     */
    private function dalamOrganisasi(Organisasi $organisasi, callable $aksi): mixed
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);

        try {
            return $aksi();
        } finally {
            $konteks->bersihkan();
        }
    }
}
