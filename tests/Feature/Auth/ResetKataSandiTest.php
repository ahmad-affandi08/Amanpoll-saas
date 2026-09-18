<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Notifications\ResetKataSandiNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ResetKataSandiTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(): array
    {
        $organisasi = Organisasi::create(['Kode' => 'AMANPOLL', 'Nama' => 'Amanpoll Demo']);
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna',
            'Email' => 'pengguna@amanpoll.test',
            'KataSandi' => 'rahasia-lama',
            'Status' => 'Aktif',
        ]);

        return [$organisasi, $pengguna];
    }

    public function test_permintaan_reset_mengirim_notifikasi_untuk_akun_valid(): void
    {
        Notification::fake();
        [, $pengguna] = $this->buatPengguna();

        $this->post('/lupa-kata-sandi', ['KodeOrganisasi' => 'AMANPOLL', 'Email' => $pengguna->Email]);

        Notification::assertSentTo($pengguna, ResetKataSandiNotification::class);
        $this->assertDatabaseHas('TokenResetKataSandi', ['PenggunaId' => $pengguna->Id]);
    }

    public function test_permintaan_reset_untuk_email_tidak_terdaftar_tidak_membocorkan_informasi(): void
    {
        Notification::fake();
        $this->buatPengguna();

        $response = $this->post('/lupa-kata-sandi', ['KodeOrganisasi' => 'AMANPOLL', 'Email' => 'tidak-ada@amanpoll.test']);

        $response->assertSessionHas('sukses');
        Notification::assertNothingSent();
    }

    public function test_reset_kata_sandi_dengan_token_valid_berhasil(): void
    {
        Notification::fake();
        [, $pengguna] = $this->buatPengguna();
        $this->post('/lupa-kata-sandi', ['KodeOrganisasi' => 'AMANPOLL', 'Email' => $pengguna->Email]);

        Notification::assertSentTo($pengguna, ResetKataSandiNotification::class, function (ResetKataSandiNotification $notification) use ($pengguna) {
            $token = $notification->tokenMentah();

            $response = $this->post("/reset-kata-sandi/{$pengguna->Id}/{$token}", [
                'KataSandiBaru' => 'kata-sandi-baru-123',
                'KataSandiBaru_confirmation' => 'kata-sandi-baru-123',
            ]);

            $response->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('kata-sandi-baru-123', $pengguna->fresh()->KataSandi));
        $this->assertDatabaseMissing('TokenResetKataSandi', ['PenggunaId' => $pengguna->Id]);
    }

    public function test_reset_kata_sandi_dengan_token_salah_ditolak(): void
    {
        [, $pengguna] = $this->buatPengguna();
        DB::table('TokenResetKataSandi')->insert([
            'PenggunaId' => $pengguna->Id,
            'TokenHash' => hash('sha256', 'token-asli'),
            'DibuatPada' => now(),
        ]);

        $response = $this->post("/reset-kata-sandi/{$pengguna->Id}/token-palsu", [
            'KataSandiBaru' => 'kata-sandi-baru-123',
            'KataSandiBaru_confirmation' => 'kata-sandi-baru-123',
        ]);

        $response->assertStatus(422);
        $this->assertTrue(Hash::check('rahasia-lama', $pengguna->fresh()->KataSandi));
    }

    public function test_reset_kata_sandi_dengan_token_kedaluwarsa_ditolak(): void
    {
        [, $pengguna] = $this->buatPengguna();
        DB::table('TokenResetKataSandi')->insert([
            'PenggunaId' => $pengguna->Id,
            'TokenHash' => hash('sha256', 'token-asli'),
            'DibuatPada' => now()->subMinutes(61),
        ]);

        $response = $this->post("/reset-kata-sandi/{$pengguna->Id}/token-asli", [
            'KataSandiBaru' => 'kata-sandi-baru-123',
            'KataSandiBaru_confirmation' => 'kata-sandi-baru-123',
        ]);

        $response->assertStatus(422);
    }
}
