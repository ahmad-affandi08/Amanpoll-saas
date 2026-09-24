<?php

declare(strict_types=1);

namespace Tests\Feature\Notifikasi;

use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananPlatform;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Tombol "Kirim email uji" di konsol penyedia layanan platform (PRD 8.23, FASE 44). */
final class KirimEmailUjiPenyediaTest extends TestCase
{
    use DatabaseTransactions;

    private const URL = '/admin-platform/penyedia-layanan/Email/Resend/kirim-uji';

    private const URL_RESEND = 'https://api.resend.com/emails';

    private const KUNCI = 're_kunci_rahasia_sangat_panjang_42ab';

    public function test_mengirim_ke_admin_memakai_kredensial_tersimpan_walau_belum_aktif(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::URL_RESEND => Http::response(['id' => 're-uji'])]);
        $this->simpanResend();
        $admin = $this->buatAdmin(izin: [KatalogPenyediaLayanan::IZIN_KELOLA]);

        $this->actingAs($admin, 'platform')
            ->postJson(self::URL)
            ->assertOk()
            ->assertExactJson([
                'Berhasil' => true,
                'Pesan' => "Email uji dikirim ke {$admin->Email} lewat Resend. Periksa kotak masuk dan folder spam.",
            ]);

        Http::assertSent(fn (Request $permintaan): bool => $permintaan->url() === self::URL_RESEND
            && $permintaan->hasHeader('Authorization', 'Bearer '.self::KUNCI)
            && $permintaan['to'] === [$admin->Email]
            && $permintaan['from'] === '"Klien" <no-reply@klien.test>');
    }

    public function test_galat_penyedia_dilaporkan_tanpa_membocorkan_kunci(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::URL_RESEND => Http::response(['message' => 'API key is invalid: '.self::KUNCI], 403)]);
        $this->simpanResend();

        $respons = $this->actingAs($this->buatAdmin(), 'platform')->postJson(self::URL);

        $respons->assertUnprocessable()->assertJson(['Berhasil' => false]);
        $this->assertStringContainsString('Resend menolak pengiriman email (HTTP 403)', (string) $respons->json('Pesan'));
        $this->assertStringNotContainsString(self::KUNCI, (string) $respons->getContent());
    }

    public function test_kredensial_belum_disimpan_ditolak(): void
    {
        Http::preventStrayRequests();

        $this->actingAs($this->buatAdmin(), 'platform')
            ->postJson(self::URL)
            ->assertUnprocessable()
            ->assertExactJson(['Berhasil' => false, 'Pesan' => 'Simpan kredensialnya dulu sebelum mengirim email uji.']);
    }

    public function test_penyedia_tak_dikenal_ditolak(): void
    {
        $this->actingAs($this->buatAdmin(), 'platform')
            ->postJson('/admin-platform/penyedia-layanan/Email/TidakAda/kirim-uji')
            ->assertNotFound();
    }

    public function test_hanya_admin_berizin_yang_boleh_mengirim(): void
    {
        Http::preventStrayRequests();
        $this->simpanResend();

        $this->post(self::URL)->assertRedirect();
        $this->actingAs($this->buatAdmin(superAdmin: false), 'platform')
            ->postJson(self::URL)
            ->assertForbidden();
    }

    private function simpanResend(): void
    {
        PenyediaLayananPlatform::create([
            'Kategori' => KategoriPenyediaLayanan::Email,
            'Kode' => 'Resend',
            'Aktif' => false,
            'ModeUji' => false,
            'KredensialTerenkripsi' => [
                'KunciApi' => self::KUNCI,
                'AlamatPengirim' => 'no-reply@klien.test',
                'NamaPengirim' => 'Klien',
            ],
        ]);
    }

    /** @param  list<string>  $izin */
    private function buatAdmin(bool $superAdmin = true, array $izin = []): AdminPlatform
    {
        return AdminPlatform::create([
            'Nama' => 'Admin Platform',
            'Email' => uniqid().'@platform.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
            'SuperAdmin' => $superAdmin,
            'Izin' => $izin,
        ]);
    }
}
