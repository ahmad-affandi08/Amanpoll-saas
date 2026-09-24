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

        $this->post('/lupa-kata-sandi', ['Email' => $pengguna->Email]);

        Notification::assertSentTo($pengguna, ResetKataSandiNotification::class);
        $this->assertDatabaseHas('TokenResetKataSandi', ['PenggunaId' => $pengguna->Id]);
    }

    public function test_permintaan_reset_untuk_email_tidak_terdaftar_tidak_membocorkan_informasi(): void
    {
        Notification::fake();
        $this->buatPengguna();

        $response = $this->post('/lupa-kata-sandi', ['Email' => 'tidak-ada@amanpoll.test']);

        $response->assertSessionHas('sukses', 'Bila akun ditemukan, tautan reset kata sandi sudah dikirim.');
        Notification::assertNothingSent();
    }

    public function test_reset_kata_sandi_dengan_token_valid_berhasil(): void
    {
        Notification::fake();
        [, $pengguna] = $this->buatPengguna();
        $this->post('/lupa-kata-sandi', ['Email' => $pengguna->Email]);

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

    public function test_email_di_dua_organisasi_menerima_satu_tautan_per_akun_dan_tautan_mengubah_akun_yang_benar(): void
    {
        Notification::fake();
        [, $diAmanpoll] = $this->buatPengguna();
        $klinik = Organisasi::create(['Kode' => 'KLINIK', 'Nama' => 'Klinik Prima']);
        $diKlinik = Pengguna::create([
            'OrganisasiId' => $klinik->Id,
            'Nama' => 'Pengguna',
            'Email' => 'pengguna@amanpoll.test',
            'KataSandi' => 'rahasia-klinik',
            'Status' => 'Aktif',
        ]);

        $this->post('/lupa-kata-sandi', ['Email' => 'PENGGUNA@amanpoll.test'])
            ->assertSessionHas('sukses', 'Bila akun ditemukan, tautan reset kata sandi sudah dikirim.');

        Notification::assertSentTimes(ResetKataSandiNotification::class, 2);
        Notification::assertSentTo($diAmanpoll, ResetKataSandiNotification::class);
        $tokenKlinik = null;
        Notification::assertSentTo($diKlinik, ResetKataSandiNotification::class, function (ResetKataSandiNotification $notifikasi, array $saluran, Pengguna $penerima) use (&$tokenKlinik): bool {
            $surel = $notifikasi->toMail($penerima);
            $this->assertStringContainsString('Klinik Prima', (string) $surel->subject);
            $this->assertStringContainsString('Klinik Prima', implode(' ', $surel->introLines));
            $tokenKlinik = $notifikasi->tokenMentah();

            return true;
        });

        $this->post("/reset-kata-sandi/{$diKlinik->Id}/{$tokenKlinik}", [
            'KataSandiBaru' => 'kata-sandi-baru-123',
            'KataSandiBaru_confirmation' => 'kata-sandi-baru-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('kata-sandi-baru-123', $diKlinik->fresh()->KataSandi));
        $this->assertTrue(Hash::check('rahasia-lama', $diAmanpoll->fresh()->KataSandi));
        $this->assertDatabaseHas('TokenResetKataSandi', ['PenggunaId' => $diAmanpoll->Id]);
    }

    public function test_akun_nonaktif_dan_organisasi_nonaktif_tidak_menerima_tautan(): void
    {
        Notification::fake();
        [, $pengguna] = $this->buatPengguna();
        $pengguna->update(['Status' => 'Nonaktif']);
        $organisasiNonaktif = Organisasi::create(['Kode' => 'MATI', 'Nama' => 'Organisasi Mati', 'Status' => 'Nonaktif']);
        Pengguna::create([
            'OrganisasiId' => $organisasiNonaktif->Id,
            'Nama' => 'Pengguna',
            'Email' => 'pengguna@amanpoll.test',
            'KataSandi' => 'rahasia-lama',
            'Status' => 'Aktif',
        ]);

        $this->post('/lupa-kata-sandi', ['Email' => 'pengguna@amanpoll.test'])
            ->assertSessionHas('sukses', 'Bila akun ditemukan, tautan reset kata sandi sudah dikirim.');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('TokenResetKataSandi', 0);
    }
}
