<?php

declare(strict_types=1);

namespace Tests\Feature\Notifikasi;

use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananOrganisasi;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Domain\Langganan\KasusLangganan;

/** Halaman Email & WhatsApp milik organisasi (PRD 8.23). */
final class HalamanPengirimNotifikasiTest extends KasusLangganan
{
    private const URL = '/notifikasi/email-whatsapp';

    private const URL_FONNTE_KIRIM = 'https://api.fonnte.com/send';

    private const URL_FONNTE_PERANGKAT = 'https://api.fonnte.com/device';

    public function test_halaman_hanya_untuk_pemegang_izin_integrasi(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());

        $this->actingAs($this->buatPengguna(['Pengaturan.Kelola']))->get(self::URL)->assertForbidden();

        $this->actingAs($this->buatPengguna(['Integrasi.Kelola']))->get(self::URL)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('PengirimNotifikasi/Index')
                ->where('bolehPenyediaSendiri', true)
                ->where('kategori.0.Kode', 'Email')
                ->where('kategori.1.Kode', 'WhatsApp'));
    }

    public function test_paket_tanpa_fitur_melihat_halaman_tetapi_tidak_dapat_menyimpan(): void
    {
        $this->buatLangganan($this->buatPaket('Dasar'));
        $admin = $this->buatPengguna(['Integrasi.Kelola']);

        $this->actingAs($admin)->get(self::URL)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('bolehPenyediaSendiri', false));

        $this->actingAs($admin)->put(self::URL.'/WhatsApp/Fonnte', ['Aktif' => true, 'Kredensial' => ['Token' => 'token-org']])
            ->assertStatus(402);

        $this->assertSame(0, PenyediaLayananOrganisasi::query()->count());
    }

    public function test_kredensial_tersimpan_terenkripsi_dan_rahasianya_tidak_kembali_ke_peramban(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());
        $admin = $this->buatPengguna(['Integrasi.Kelola']);

        $this->actingAs($admin)->put(self::URL.'/Email/Smtp', [
            'Aktif' => true,
            'Kredensial' => [
                'Host' => 'smtp.rumahsakit.test',
                'Port' => '587',
                'Enkripsi' => 'tls',
                'NamaPengguna' => 'ipsrs@rumahsakit.test',
                'KataSandi' => 'sandi-smtp-rahasia-9876',
                'AlamatPengirim' => 'ipsrs@rumahsakit.test',
                'NamaPengirim' => 'IPSRS RS Sehat',
            ],
        ])->assertSessionHasNoErrors();

        $baris = PenyediaLayananOrganisasi::query()->firstOrFail();
        $this->assertTrue($baris->Aktif);
        $this->assertSame($this->organisasi->Id, $baris->OrganisasiId);
        $this->assertSame('sandi-smtp-rahasia-9876', $baris->keKredensial()->ambil('KataSandi'));
        $this->assertStringNotContainsString(
            'sandi-smtp-rahasia-9876',
            (string) DB::table('PenyediaLayananOrganisasi')->value('KredensialTerenkripsi'),
        );

        $halaman = $this->actingAs($admin)->get(self::URL)->assertOk();
        $this->assertStringNotContainsString('sandi-smtp-rahasia-9876', (string) $halaman->getContent());
        $halaman->assertInertia(fn (AssertableInertia $inertia) => $inertia
            ->where('kategori.0.Penyedia.0.Aktif', true)
            ->where('kategori.0.Penyedia.0.Isian.4.Kunci', 'KataSandi')
            ->where('kategori.0.Penyedia.0.Isian.4.Nilai', null)
            ->where('kategori.0.Penyedia.0.Isian.4.Akhiran', '9876'));
    }

    public function test_isian_khusus_platform_tidak_ditanyakan_dan_template_meta_wajib_bagi_organisasi(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());
        $admin = $this->buatPengguna(['Integrasi.Kelola']);
        $kredensial = ['PhoneNumberId' => '1098765', 'AccessToken' => 'token-meta-organisasi', 'WabaId' => '2024001', 'VerifyToken' => 'titipan'];

        $this->actingAs($admin)->put(self::URL.'/WhatsApp/MetaCloud', ['Aktif' => true, 'Kredensial' => $kredensial])
            ->assertSessionHasErrors(['Kredensial' => 'WhatsApp Cloud API (Meta) belum bisa diaktifkan. Lengkapi dulu: Template notifikasi staf.']);

        $this->actingAs($admin)->put(self::URL.'/WhatsApp/MetaCloud', [
            'Aktif' => true,
            'Kredensial' => [...$kredensial, 'TemplateNotifikasi' => 'notifikasi_staf'],
        ])->assertSessionHasNoErrors();

        $kunci = array_keys(PenyediaLayananOrganisasi::query()->firstOrFail()->nilaiKredensial());
        $this->assertContains('TemplateNotifikasi', $kunci);
        $this->assertNotContains('WabaId', $kunci);
        $this->assertNotContains('VerifyToken', $kunci);
    }

    public function test_mengaktifkan_satu_penyedia_hanya_menonaktifkan_penyedia_lain_milik_organisasi_sendiri(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());
        $admin = $this->buatPengguna(['Integrasi.Kelola']);
        $milikLain = $this->penyediaOrganisasiLain('Fonnte', ['Token' => 'token-lain']);

        $this->actingAs($admin)->put(self::URL.'/WhatsApp/Fonnte', ['Aktif' => true, 'Kredensial' => ['Token' => 'token-org']])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->put(self::URL.'/WhatsApp/Wablas', [
            'Aktif' => true,
            'Kredensial' => ['Domain' => 'https://tegal.wablas.com', 'Token' => 'token-wablas', 'SecretKey' => 'kunci-wablas'],
        ])->assertSessionHasNoErrors();

        $aktif = PenyediaLayananOrganisasi::query()->pluck('Aktif', 'Kode')->map(fn (mixed $nilai): bool => (bool) $nilai)->all();
        $this->assertSame(['Fonnte' => false, 'Wablas' => true], $aktif);
        $this->assertTrue((bool) $milikLain->fresh()?->Aktif, 'Penyedia organisasi lain ikut dinonaktifkan.');
    }

    public function test_organisasi_lain_tidak_melihat_dan_tidak_dapat_menghapus_kredensial(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());
        $admin = $this->buatPengguna(['Integrasi.Kelola']);
        $milikLain = $this->penyediaOrganisasiLain('Fonnte', ['Token' => 'token-organisasi-lain-5555']);

        $halaman = $this->actingAs($admin)->get(self::URL)->assertOk();
        $halaman->assertInertia(fn (AssertableInertia $inertia) => $inertia
            ->where('kategori.1.Penyedia.1.Kode', 'Fonnte')
            ->where('kategori.1.Penyedia.1.Aktif', false)
            ->where('kategori.1.Penyedia.1.Isian.0.Tersimpan', false));
        $this->assertStringNotContainsString('5555', (string) $halaman->getContent());

        $this->actingAs($admin)->delete(self::URL.'/WhatsApp/Fonnte')->assertRedirect();

        $this->assertNotNull($milikLain->fresh(), 'Kredensial organisasi lain ikut terhapus.');
    }

    public function test_payment_gateway_tidak_dapat_dipasang_organisasi(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());

        $this->actingAs($this->buatPengguna(['Integrasi.Kelola']))
            ->put(self::URL.'/Pembayaran/Midtrans', ['Aktif' => false, 'Kredensial' => []])
            ->assertNotFound();

        $this->assertSame(0, PenyediaLayananOrganisasi::query()->count());
    }

    public function test_uji_kredensial_yang_berhasil_menghapus_tanda_bermasalah(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());
        $baris = $this->penyedia('Fonnte', ['Token' => 'token-org']);
        $baris->forceFill(['TerakhirGagalPada' => now()->subHour(), 'GalatTerakhir' => 'Perangkat terputus.'])->save();
        Http::preventStrayRequests();
        Http::fake([self::URL_FONNTE_PERANGKAT => Http::response(['status' => true, 'name' => 'HP IPSRS', 'device' => '6281', 'device_status' => 'connect'])]);

        $this->actingAs($this->buatPengguna(['Integrasi.Kelola']))
            ->postJson(self::URL.'/WhatsApp/Fonnte/uji')
            ->assertOk()
            ->assertJson(['Berhasil' => true]);

        Http::assertSent(fn (Request $permintaan): bool => $permintaan->header('Authorization') === ['token-org']);
        $this->assertFalse($baris->fresh()?->sedangBermasalah());
    }

    public function test_whatsapp_uji_dikirim_ke_nomor_pengguna_dengan_kredensial_organisasi(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());
        $this->penyedia('Fonnte', ['Token' => 'token-org']);
        $admin = $this->buatPengguna(['Integrasi.Kelola']);
        $admin->forceFill(['Telepon' => '0812-3456-7890'])->save();
        Http::preventStrayRequests();
        Http::fake([self::URL_FONNTE_KIRIM => Http::response(['status' => true, 'id' => ['99']])]);

        $jawaban = $this->actingAs($admin)->postJson(self::URL.'/WhatsApp/Fonnte/kirim-uji')->assertOk();

        Http::assertSent(fn (Request $permintaan): bool => $permintaan->header('Authorization') === ['token-org']
            && $permintaan['target'] === '6281234567890');
        $this->assertStringNotContainsString('6281234567890', (string) $jawaban->json('Pesan'));
    }

    public function test_uji_tanpa_kredensial_tersimpan_dijawab_dengan_pesan(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());

        $this->actingAs($this->buatPengguna(['Integrasi.Kelola']))
            ->postJson(self::URL.'/WhatsApp/Fonnte/uji')
            ->assertStatus(422)
            ->assertJson(['Berhasil' => false, 'Pesan' => 'Simpan kredensialnya dulu sebelum diuji.']);
    }

    public function test_kredensial_masih_dapat_dihapus_setelah_paket_turun(): void
    {
        $this->buatLangganan($this->buatPaket('Dasar'));
        $this->penyedia('Fonnte', ['Token' => 'token-org']);

        $this->actingAs($this->buatPengguna(['Integrasi.Kelola']))->delete(self::URL.'/WhatsApp/Fonnte')->assertRedirect();

        $this->assertSame(0, PenyediaLayananOrganisasi::query()->count());
        $this->assertTrue(DB::table('CatatanAudit')->where('Aksi', 'PenyediaLayananOrganisasi.Dihapus')->exists());
    }

    public function test_pemakaian_kuota_whatsapp_tampil_bersama_batas_paket(): void
    {
        $this->buatLangganan($this->buatPaket('Menengah', [
            KatalogFitur::LAYANAN_PENYEDIA_SENDIRI => ['Diizinkan' => false],
            KatalogFitur::BATAS_WHATSAPP_BULANAN => ['Diizinkan' => true, 'BatasNilai' => 300],
        ]));

        $this->actingAs($this->buatPengguna(['Integrasi.Kelola']))->get(self::URL)
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('kuotaWhatsApp.Batas', 300)
                ->where('kuotaWhatsApp.Terpakai', 0)
                ->where('kuotaWhatsApp.Habis', false));
    }

    /** @param  array<string, string>  $kredensial */
    private function penyedia(string $kode, array $kredensial, bool $aktif = true): PenyediaLayananOrganisasi
    {
        return PenyediaLayananOrganisasi::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kategori' => KategoriPenyediaLayanan::WhatsApp,
            'Kode' => $kode,
            'Aktif' => $aktif,
            'KredensialTerenkripsi' => $kredensial,
        ]);
    }

    /** @param  array<string, string>  $kredensial */
    private function penyediaOrganisasiLain(string $kode, array $kredensial): PenyediaLayananOrganisasi
    {
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN-'.uniqid(), 'Nama' => 'Organisasi Lain', 'Status' => 'Aktif']);

        // Disimpan di luar konteks organisasi yang sedang diuji, seperti tenant lain di produksi.
        $baris = new PenyediaLayananOrganisasi;
        $baris->forceFill([
            'OrganisasiId' => $lain->Id,
            'Kategori' => KategoriPenyediaLayanan::WhatsApp,
            'Kode' => $kode,
            'Aktif' => true,
            'KredensialTerenkripsi' => $kredensial,
        ]);
        PenyediaLayananOrganisasi::withoutEvents(fn () => $baris->save());

        return PenyediaLayananOrganisasi::query()->withoutGlobalScope(ScopeOrganisasi::class)->findOrFail($baris->Id);
    }
}
