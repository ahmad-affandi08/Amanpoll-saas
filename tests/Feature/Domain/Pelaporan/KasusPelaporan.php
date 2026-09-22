<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pelaporan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** Dasar bersama untuk tes HTTP Pelaporan (21.03–21.05). */
abstract class KasusPelaporan extends TestCase
{
    use DatabaseTransactions;

    protected Organisasi $organisasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create([
            'Kode' => 'ORG-LAP-'.uniqid(),
            'Nama' => 'Organisasi Pelaporan',
        ]);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
    }

    /**
     * Kernel HTTP melepas instance scoped setelah permintaan selesai, sehingga
     * konteks organisasi milik tes ikut hilang dan asersi berikutnya tidak
     * melihat satu baris pun. Konteks ditetapkan ulang di sini agar tiap tes
     * dapat memeriksa hasilnya tanpa melumpuhkan global scope tenant.
     *
     * {@inheritDoc}
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $respons = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        return $respons;
    }

    /**
     * @param  list<string>  $izin
     */
    protected function buatPengguna(array $izin = [], ?Organisasi $organisasi = null): Pengguna
    {
        $organisasi ??= $this->organisasi;

        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($izin === []) {
            return $pengguna;
        }

        $peran = Peran::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'PERAN-'.uniqid(),
            'Nama' => 'Peran Pelaporan',
        ]);

        foreach ($izin as $kode) {
            $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Pelaporan']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
        }

        PenggunaPeran::create([
            'OrganisasiId' => $organisasi->Id,
            'PenggunaId' => $pengguna->Id,
            'PeranId' => $peran->Id,
        ]);

        return $pengguna;
    }
}
