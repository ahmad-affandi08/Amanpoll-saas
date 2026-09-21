<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class KasusKeamanan extends TestCase
{
    use RefreshDatabase;

    protected KonteksOrganisasi $konteks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->konteks = app(KonteksOrganisasi::class);
    }

    protected function tearDown(): void
    {
        $this->konteks->bersihkan();
        parent::tearDown();
    }

    protected function buatOrganisasi(string $kode): Organisasi
    {
        return Organisasi::create(['Kode' => $kode, 'Nama' => 'Organisasi '.$kode, 'Status' => 'Aktif']);
    }

    /** @param list<string> $kodeIzin */
    protected function buatPengguna(Organisasi $organisasi, array $kodeIzin = []): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== []) {
            $this->dalamOrganisasi($organisasi, function () use ($pengguna, $kodeIzin): void {
                $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran Uji']);
                foreach ($kodeIzin as $kode) {
                    $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
                    PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
                }
                PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            });
        }

        return $pengguna;
    }

    /**
     * Menjalankan sesuatu di bawah konteks satu organisasi. Dibutuhkan karena
     * global scope bersifat fail-closed: tanpa konteks, query tidak
     * mengembalikan apa pun.
     *
     * @template T
     *
     * @param  callable():T  $aksi
     * @return T
     */
    protected function dalamOrganisasi(Organisasi $organisasi, callable $aksi): mixed
    {
        $this->konteks->tetapkan($organisasi->Id);

        try {
            return $aksi();
        } finally {
            $this->konteks->bersihkan();
        }
    }
}
