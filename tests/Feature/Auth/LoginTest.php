<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(string $statusOrganisasi = 'Aktif', string $statusPengguna = 'Aktif'): Pengguna
    {
        $organisasi = Organisasi::create([
            'Kode' => 'AMANPOLL',
            'Nama' => 'Amanpoll Demo',
            'Status' => $statusOrganisasi,
        ]);

        return Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Admin Demo',
            'Email' => 'admin@amanpoll.test',
            'KataSandi' => 'kata-sandi-benar',
            'Status' => $statusPengguna,
        ]);
    }

    private function payloadLogin(array $override = []): array
    {
        return array_merge([
            'KodeOrganisasi' => 'AMANPOLL',
            'Email' => 'admin@amanpoll.test',
            'KataSandi' => 'kata-sandi-benar',
        ], $override);
    }

    public function test_login_berhasil_dengan_kredensial_benar(): void
    {
        $pengguna = $this->buatPengguna();

        $response = $this->post('/login', $this->payloadLogin());

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($pengguna);
    }

    public function test_login_meregenerasi_session(): void
    {
        $this->buatPengguna();

        $this->get('/login');
        $idSessionSebelum = session()->getId();

        $this->post('/login', $this->payloadLogin());

        $this->assertNotSame($idSessionSebelum, session()->getId());
    }

    public function test_login_gagal_dengan_pesan_generik_saat_password_salah(): void
    {
        $this->buatPengguna();

        $response = $this->post('/login', $this->payloadLogin(['KataSandi' => 'salah']));

        $response->assertSessionHasErrors(['Email' => 'Organisasi, email, atau kata sandi tidak sesuai.']);
        $this->assertGuest();
    }

    public function test_login_gagal_dengan_pesan_generik_saat_kode_organisasi_salah(): void
    {
        $this->buatPengguna();

        $response = $this->post('/login', $this->payloadLogin(['KodeOrganisasi' => 'TIDAK-ADA']));

        $response->assertSessionHasErrors(['Email' => 'Organisasi, email, atau kata sandi tidak sesuai.']);
        $this->assertGuest();
    }

    public function test_login_ditolak_saat_organisasi_tidak_aktif(): void
    {
        $this->buatPengguna(statusOrganisasi: 'Nonaktif');

        $response = $this->post('/login', $this->payloadLogin());

        $response->assertSessionHasErrors('Email');
        $this->assertGuest();
    }

    public function test_login_ditolak_saat_pengguna_tidak_aktif(): void
    {
        $this->buatPengguna(statusPengguna: 'Nonaktif');

        $response = $this->post('/login', $this->payloadLogin());

        $response->assertSessionHasErrors('Email');
        $this->assertGuest();
    }

    public function test_login_dibatasi_setelah_percobaan_berulang(): void
    {
        $this->buatPengguna();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', $this->payloadLogin(['KataSandi' => 'salah']));
        }

        $response = $this->post('/login', $this->payloadLogin());

        $response->assertSessionHasErrors('Email');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan',
            session('errors')->get('Email')[0],
        );
        $this->assertGuest();
    }

    public function test_logout_menghapus_session(): void
    {
        $pengguna = $this->buatPengguna();
        $this->actingAs($pengguna)->post('/login', $this->payloadLogin());

        $response = $this->actingAs($pengguna)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
