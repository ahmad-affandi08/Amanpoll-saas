<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Organisasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class KunciApiIsolasiTest extends TestCase
{
    use RefreshDatabase;

    private function buatKunciApi(Organisasi $organisasi, string $prefix): string
    {
        $rahasia = 'rahasia-uji-'.$prefix;
        $token = "{$prefix}.{$rahasia}";

        KunciApi::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => "Kunci {$prefix}",
            'AwalanKunci' => $prefix,
            'HashKunci' => hash('sha256', $token),
            'Status' => 'Aktif',
        ]);

        return $token;
    }

    public function test_kunci_api_organisasi_a_tidak_dapat_mengakses_data_organisasi_b(): void
    {
        Route::middleware(['api', 'kunci.api'])->get('/_uji/api/lokasi/{lokasi}', function (Lokasi $lokasi) {
            return response()->json(['Id' => $lokasi->Id]);
        });

        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiB->Id);
        $lokasiB = Lokasi::create(['Kode' => 'LOK-B', 'Nama' => 'Lokasi B']);
        $konteks->bersihkan();

        $tokenA = $this->buatKunciApi($organisasiA, 'PFXA');

        $response = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson("/_uji/api/lokasi/{$lokasiB->Id}");

        $response->assertStatus(404);
    }

    public function test_kunci_api_dapat_mengakses_data_organisasinya_sendiri(): void
    {
        Route::middleware(['api', 'kunci.api'])->get('/_uji/api/lokasi/{lokasi}', function (Lokasi $lokasi) {
            return response()->json(['Id' => $lokasi->Id]);
        });

        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiA->Id);
        $lokasiA = Lokasi::create(['Kode' => 'LOK-A', 'Nama' => 'Lokasi A']);
        $konteks->bersihkan();

        $tokenA = $this->buatKunciApi($organisasiA, 'PFXA');

        $response = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson("/_uji/api/lokasi/{$lokasiA->Id}");

        $response->assertStatus(200);
        $response->assertJson(['Id' => $lokasiA->Id]);
    }

    public function test_kunci_api_tidak_valid_ditolak(): void
    {
        Route::middleware(['api', 'kunci.api'])->get('/_uji/api/status', fn () => response()->json(['ok' => true]));

        $response = $this->withHeader('Authorization', 'Bearer ngasal.token')->getJson('/_uji/api/status');

        $response->assertStatus(401);
    }
}
