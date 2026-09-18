<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerangkatPenggunaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_dapat_menghapus_perangkat_miliknya(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna',
            'Email' => 'pengguna@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $perangkat = PerangkatPengguna::create([
            'PenggunaId' => $pengguna->Id,
            'NamaPerangkat' => 'iPhone Teknisi',
            'Platform' => 'iOS',
            'Status' => 'Aktif',
        ]);
        $konteks->bersihkan();

        $response = $this->actingAs($pengguna)->delete("/platform/profil/perangkat/{$perangkat->Id}");

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseMissing('PerangkatPengguna', ['Id' => $perangkat->Id]);
    }

    public function test_pengguna_tidak_dapat_menghapus_perangkat_milik_orang_lain(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pemilik = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pemilik',
            'Email' => 'pemilik@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
        $lain = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Lain',
            'Email' => 'lain@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $perangkat = PerangkatPengguna::create([
            'PenggunaId' => $pemilik->Id,
            'NamaPerangkat' => 'iPhone Pemilik',
            'Status' => 'Aktif',
        ]);
        $konteks->bersihkan();

        $response = $this->actingAs($lain)->delete("/platform/profil/perangkat/{$perangkat->Id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('PerangkatPengguna', ['Id' => $perangkat->Id]);
    }
}
