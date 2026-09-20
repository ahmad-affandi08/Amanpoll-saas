<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class KunciApiControllerTest extends TestCase
{
    use RefreshDatabase;

    private function buatAdmin(Organisasi $organisasi): Pengguna
    {
        $admin = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Admin',
            'Email' => 'admin+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $izin = Izin::firstOrCreate(['Kode' => 'Integrasi.Kelola'], ['Nama' => 'Kelola Integrasi', 'Modul' => 'Integrasi']);
        $peran = Peran::create(['Kode' => 'ADMIN', 'Nama' => 'Administrator']);
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $admin->Id, 'PeranId' => $peran->Id]);
        $konteks->bersihkan();

        return $admin;
    }

    public function test_admin_dapat_membuat_kunci_api_dan_token_hanya_tampil_sekali(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->post('/platform/kunci-api', ['Nama' => 'Integrasi ERP']);

        $response->assertSessionDoesntHaveErrors();
        $response->assertSessionHas('tokenKunciApi');
        $this->assertDatabaseHas('KunciApi', ['Nama' => 'Integrasi ERP', 'OrganisasiId' => $organisasi->Id]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $kunciApi = KunciApi::where('Nama', 'Integrasi ERP')->first();
        $konteks->bersihkan();

        $this->assertNotSame($kunciApi->HashKunci, session('tokenKunciApi'));
    }

    public function test_token_yang_dihasilkan_benar_benar_bisa_dipakai_autentikasi(): void
    {
        Route::middleware(['api', 'kunci.api'])->get('/_uji/status-kunci', fn () => response()->json(['ok' => true]));

        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->post('/platform/kunci-api', ['Nama' => 'Integrasi ERP']);
        $token = $response->getSession()->get('tokenKunciApi');

        $cekApi = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/_uji/status-kunci');

        $cekApi->assertOk();
    }

    public function test_kunci_yang_dicabut_tidak_bisa_dipakai_lagi(): void
    {
        Route::middleware(['api', 'kunci.api'])->get('/_uji/status-kunci', fn () => response()->json(['ok' => true]));

        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $buat = $this->actingAs($admin)->post('/platform/kunci-api', ['Nama' => 'Integrasi ERP']);
        $token = $buat->getSession()->get('tokenKunciApi');

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $kunciApi = KunciApi::where('Nama', 'Integrasi ERP')->first();
        $konteks->bersihkan();

        $this->actingAs($admin)->delete("/platform/kunci-api/{$kunciApi->Id}");

        $cekApi = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/_uji/status-kunci');

        $cekApi->assertStatus(401);
    }

    public function test_cakupan_kunci_api_membatasi_endpoint(): void
    {
        Route::middleware(['api', 'kunci.api', 'cakupan.kunci:Aset.Lihat'])->get('/_uji/aset', fn () => response()->json(['ok' => true]));

        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->post('/platform/kunci-api', [
            'Nama' => 'Tanpa Cakupan Aset',
            'Cakupan' => ['Integrasi.Kelola'],
        ]);
        $token = $response->getSession()->get('tokenKunciApi');

        $cekApi = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/_uji/aset');

        $cekApi->assertStatus(403);
    }

    public function test_pengguna_tanpa_izin_tidak_dapat_membuat_kunci_api(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $biasa = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($biasa)->post('/platform/kunci-api', ['Nama' => 'Coba Bikin']);

        $response->assertForbidden();
    }

    public function test_organisasi_a_tidak_dapat_mencabut_kunci_api_organisasi_b(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $adminA = $this->buatAdmin($organisasiA);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiB->Id);
        $kunciB = KunciApi::create([
            'Nama' => 'Kunci B',
            'AwalanKunci' => 'PFXB',
            'HashKunci' => hash('sha256', 'apapun'),
            'Status' => 'Aktif',
        ]);
        $konteks->bersihkan();

        $response = $this->actingAs($adminA)->delete("/platform/kunci-api/{$kunciB->Id}");

        $response->assertStatus(404);
    }
}
