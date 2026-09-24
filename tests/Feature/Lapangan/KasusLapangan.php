<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Actions\PasangPeranAwal;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Database\Seeders\IzinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dasar test Mode Lapangan (FASE 39): satu organisasi dengan peran bawaan dari
 * katalog yang sesungguhnya, sehingga test membuktikan akses peran TEKNISI dan
 * PELAPOR seperti yang benar-benar dipasang untuk tenant baru.
 */
abstract class KasusLapangan extends TestCase
{
    use RefreshDatabase;

    protected Organisasi $organisasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IzinSeeder::class);
        $this->organisasi = Organisasi::create(['Kode' => 'ORG-LAP-'.uniqid(), 'Nama' => 'Organisasi Lapangan', 'Status' => 'Aktif']);
        app(PasangPeranAwal::class)->jalankan($this->organisasi->Id);
    }

    protected function tearDown(): void
    {
        app(KonteksOrganisasi::class)->bersihkan();

        parent::tearDown();
    }

    /**
     * Pengguna yang memegang peran bawaan menurut kodenya, mis. `['TEKNISI']`.
     *
     * @param  list<string>  $kodePeran
     */
    protected function penggunaDenganPeran(array $kodePeran, ?string $unitOrganisasiId = null, ?string $lokasiId = null): Pengguna
    {
        $pengguna = $this->buatPengguna();

        $this->dalamOrganisasi(function () use ($pengguna, $kodePeran, $unitOrganisasiId, $lokasiId): void {
            foreach ($kodePeran as $kode) {
                PenggunaPeran::create([
                    'PenggunaId' => $pengguna->Id,
                    'PeranId' => Peran::query()->where('Kode', $kode)->firstOrFail()->Id,
                    'UnitOrganisasiId' => $unitOrganisasiId,
                    'LokasiId' => $lokasiId,
                ]);
            }
        });

        return $pengguna;
    }

    /**
     * Pengguna dengan satu peran meja (tanpa penanda Tampilan Lapangan) berisi izin yang disebut.
     *
     * @param  list<string>  $kodeIzin
     */
    protected function penggunaMeja(array $kodeIzin = ['Aset.Lihat'], ?Pengguna $pengguna = null): Pengguna
    {
        $pengguna ??= $this->buatPengguna();

        $this->dalamOrganisasi(function () use ($pengguna, $kodeIzin): void {
            $peran = Peran::create(['Kode' => 'MEJA-'.uniqid(), 'Nama' => 'Peran Meja']);

            foreach ($kodeIzin as $kode) {
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => Izin::query()->where('Kode', $kode)->firstOrFail()->Id]);
            }

            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
        });

        return $pengguna;
    }

    protected function buatPengguna(): Pengguna
    {
        return Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'lapangan+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
    }

    /**
     * @template T
     *
     * @param  callable():T  $aksi
     * @return T
     */
    protected function dalamOrganisasi(callable $aksi): mixed
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($this->organisasi->Id);

        try {
            return $aksi();
        } finally {
            $konteks->bersihkan();
        }
    }
}
