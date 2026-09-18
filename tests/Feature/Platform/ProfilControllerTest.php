<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilControllerTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(): Pengguna
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);

        return Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna',
            'Email' => 'pengguna@amanpoll.test',
            'KataSandi' => 'rahasia-lama',
            'Status' => 'Aktif',
        ]);
    }

    public function test_pengguna_dapat_memperbarui_profil_sendiri(): void
    {
        $pengguna = $this->buatPengguna();

        $response = $this->actingAs($pengguna)->put('/platform/profil', [
            'Nama' => 'Nama Baru',
            'Email' => $pengguna->Email,
            'Telepon' => '08123456789',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('Pengguna', ['Id' => $pengguna->Id, 'Nama' => 'Nama Baru']);
    }

    public function test_ganti_email_mereset_status_verifikasi(): void
    {
        $pengguna = $this->buatPengguna();
        $pengguna->forceFill(['EmailTerverifikasiPada' => now()])->save();

        $this->actingAs($pengguna)->put('/platform/profil', [
            'Nama' => $pengguna->Nama,
            'Email' => 'email-baru@amanpoll.test',
        ]);

        $this->assertNull($pengguna->fresh()->EmailTerverifikasiPada);
    }

    public function test_ganti_kata_sandi_membutuhkan_kata_sandi_lama_yang_benar(): void
    {
        $pengguna = $this->buatPengguna();

        $response = $this->actingAs($pengguna)->put('/platform/profil/kata-sandi', [
            'KataSandiLama' => 'salah',
            'KataSandiBaru' => 'kata-sandi-baru-123',
            'KataSandiBaru_confirmation' => 'kata-sandi-baru-123',
        ]);

        $response->assertSessionHasErrors('KataSandiLama');
    }

    public function test_ganti_kata_sandi_berhasil(): void
    {
        $pengguna = $this->buatPengguna();

        $response = $this->actingAs($pengguna)->put('/platform/profil/kata-sandi', [
            'KataSandiLama' => 'rahasia-lama',
            'KataSandiBaru' => 'kata-sandi-baru-123',
            'KataSandiBaru_confirmation' => 'kata-sandi-baru-123',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertTrue(Hash::check('kata-sandi-baru-123', $pengguna->fresh()->KataSandi));
    }
}
