<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Organisasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteModelBindingTenantTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(Organisasi $organisasi, string $email): Pengguna
    {
        return Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna Uji',
            'Email' => $email,
            'KataSandi' => bcrypt('rahasia'),
            'Status' => 'Aktif',
        ]);
    }

    public function test_route_model_binding_menolak_data_organisasi_lain(): void
    {
        Route::middleware(['web', 'auth', 'organisasi'])->get('/_uji/lokasi/{lokasi}', function (Lokasi $lokasi) {
            return response()->json(['Id' => $lokasi->Id]);
        });

        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);

        app(KonteksOrganisasi::class)->tetapkan($organisasiB->Id);
        $lokasiB = Lokasi::create(['Kode' => 'LOK-B', 'Nama' => 'Lokasi B']);
        app(KonteksOrganisasi::class)->bersihkan();

        $penggunaA = $this->buatPengguna($organisasiA, 'a@amanpoll.test');

        $response = $this->actingAs($penggunaA)->getJson("/_uji/lokasi/{$lokasiB->Id}");

        $response->assertStatus(404);
    }

    public function test_route_model_binding_mengizinkan_data_organisasi_sendiri(): void
    {
        Route::middleware(['web', 'auth', 'organisasi'])->get('/_uji/lokasi/{lokasi}', function (Lokasi $lokasi) {
            return response()->json(['Id' => $lokasi->Id]);
        });

        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penggunaA = $this->buatPengguna($organisasiA, 'a@amanpoll.test');

        app(KonteksOrganisasi::class)->tetapkan($organisasiA->Id);
        $lokasiA = Lokasi::create(['Kode' => 'LOK-A', 'Nama' => 'Lokasi A']);
        app(KonteksOrganisasi::class)->bersihkan();

        $response = $this->actingAs($penggunaA)->getJson("/_uji/lokasi/{$lokasiA->Id}");

        $response->assertStatus(200);
        $response->assertJson(['Id' => $lokasiA->Id]);
    }
}
