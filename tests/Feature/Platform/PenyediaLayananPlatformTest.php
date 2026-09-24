<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Application\Services\PembacaKredensialPenyedia;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananPlatform;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\Dukungan\PenyediaLayananUji;
use Tests\TestCase;

/** Pengaturan payment gateway dan WhatsApp di konsol platform (PRD 8.23, TASK 44.01). */
final class PenyediaLayananPlatformTest extends TestCase
{
    use DatabaseTransactions;

    private const RAHASIA = 'sk_rahasia_sangat_panjang_9f3a';

    private PenyediaLayananUji $bayarA;

    private PenyediaLayananUji $waResmi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bayarA = new PenyediaLayananUji(KategoriPenyediaLayanan::Pembayaran, 'UjiBayarA');
        $this->waResmi = new PenyediaLayananUji(KategoriPenyediaLayanan::WhatsApp, 'UjiWaResmi');

        $penyedia = [
            'uji.bayar-a' => $this->bayarA,
            'uji.bayar-b' => new PenyediaLayananUji(KategoriPenyediaLayanan::Pembayaran, 'UjiBayarB'),
            'uji.wa-resmi' => $this->waResmi,
            'uji.wa-tidak-resmi' => new PenyediaLayananUji(KategoriPenyediaLayanan::WhatsApp, 'UjiWaTakResmi', resmi: false),
        ];

        foreach ($penyedia as $abstrak => $objek) {
            $this->app->instance($abstrak, $objek);
        }
        $this->app->tag(array_keys($penyedia), KatalogPenyediaLayanan::TAG);
    }

    public function test_menyimpan_kredensial_terenkripsi_tanpa_membocorkannya(): void
    {
        $this->sebagaiAdmin()
            ->put('/admin-platform/penyedia-layanan/Pembayaran/UjiBayarA', [
                'Aktif' => true,
                'ModeUji' => true,
                'Kredensial' => ['IdPedagang' => 'M-001', 'KunciRahasia' => self::RAHASIA],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $mentah = (string) DB::table('PenyediaLayananPlatform')->where('Kode', 'UjiBayarA')->value('KredensialTerenkripsi');
        $this->assertStringNotContainsString(self::RAHASIA, $mentah);
        $this->assertStringNotContainsString('M-001', $mentah);

        $baris = $this->baris('UjiBayarA');
        $this->assertSame(['IdPedagang' => 'M-001', 'KunciRahasia' => self::RAHASIA, 'Kanal' => 'VA'], $baris->nilaiKredensial());
        $this->assertSame(64, strlen((string) $baris->SidikKredensial));

        $audit = DB::table('CatatanAudit')->where('Aksi', 'PenyediaLayanan.Diubah')->where('EntitasId', $baris->Id)->first();
        $this->assertNotNull($audit);
        $this->assertStringNotContainsString(self::RAHASIA, (string) $audit->DataSesudah);

        $halaman = $this->sebagaiAdmin()->get('/admin-platform/penyedia-layanan');
        $halaman->assertOk()->assertDontSee(self::RAHASIA);
        $halaman->assertInertia(fn (AssertableInertia $inertia) => $inertia
            ->component('Platform/PenyediaLayanan/Index')
            ->where('kategori.0.Kode', 'Pembayaran')
            ->where('kategori.0.Penyedia.0.Kode', 'UjiBayarA')
            ->where('kategori.0.Penyedia.0.Aktif', true)
            ->where('kategori.0.Penyedia.0.Isian.0.Nilai', 'M-001')
            ->where('kategori.0.Penyedia.0.Isian.1.Nilai', null)
            ->where('kategori.0.Penyedia.0.Isian.1.Tersimpan', true)
            ->where('kategori.0.Penyedia.0.Isian.1.Akhiran', '9f3a'));
    }

    public function test_rahasia_kosong_mempertahankan_nilai_lama(): void
    {
        $this->simpan('Pembayaran', 'UjiBayarA', ['IdPedagang' => 'M-001', 'KunciRahasia' => self::RAHASIA]);
        $sidikLama = $this->baris('UjiBayarA')->SidikKredensial;

        $this->simpan('Pembayaran', 'UjiBayarA', ['IdPedagang' => 'M-002', 'KunciRahasia' => '']);

        $baris = $this->baris('UjiBayarA');
        $this->assertSame(self::RAHASIA, $baris->nilaiKredensial()['KunciRahasia']);
        $this->assertSame('M-002', $baris->nilaiKredensial()['IdPedagang']);
        $this->assertNotSame($sidikLama, $baris->SidikKredensial);
    }

    public function test_tidak_bisa_diaktifkan_sebelum_isian_wajib_lengkap(): void
    {
        $this->sebagaiAdmin()
            ->from('/admin-platform/penyedia-layanan')
            ->put('/admin-platform/penyedia-layanan/Pembayaran/UjiBayarA', [
                'Aktif' => true,
                'Kredensial' => ['IdPedagang' => 'M-001'],
            ])
            ->assertSessionHasErrors('Kredensial');

        $this->assertNull(PenyediaLayananPlatform::query()->where('Kode', 'UjiBayarA')->first());

        // Menyimpan tanpa mengaktifkan tetap boleh, supaya kredensial bisa dilengkapi bertahap.
        $this->simpan('Pembayaran', 'UjiBayarA', ['IdPedagang' => 'M-001'], aktif: false);
        $this->assertFalse($this->baris('UjiBayarA')->Aktif);
    }

    public function test_pilihan_di_luar_daftar_ditolak(): void
    {
        $this->sebagaiAdmin()
            ->put('/admin-platform/penyedia-layanan/Pembayaran/UjiBayarA', [
                'Aktif' => false,
                'Kredensial' => ['Kanal' => 'Kartu'],
            ])
            ->assertSessionHasErrors('Kredensial');
    }

    public function test_whatsapp_hanya_satu_yang_aktif(): void
    {
        $this->simpan('WhatsApp', 'UjiWaResmi', $this->lengkap());
        $this->simpan('WhatsApp', 'UjiWaTakResmi', $this->lengkap());

        $this->assertFalse($this->baris('UjiWaResmi', 'WhatsApp')->Aktif);
        $this->assertTrue($this->baris('UjiWaTakResmi', 'WhatsApp')->Aktif);

        $pembaca = app(PembacaKredensialPenyedia::class);
        $this->assertSame('UjiWaTakResmi', $pembaca->kodeUtama(KategoriPenyediaLayanan::WhatsApp));
        $this->assertNull($pembaca->untuk(KategoriPenyediaLayanan::WhatsApp, 'UjiWaResmi'));
    }

    public function test_pembayaran_boleh_banyak_aktif_dengan_satu_utama(): void
    {
        $this->simpan('Pembayaran', 'UjiBayarA', $this->lengkap());
        $this->assertTrue($this->baris('UjiBayarA')->Utama, 'Penyedia aktif pertama otomatis menjadi utama.');

        $this->simpan('Pembayaran', 'UjiBayarB', $this->lengkap());
        $this->assertTrue($this->baris('UjiBayarA')->Utama);
        $this->assertFalse($this->baris('UjiBayarB')->Utama);

        $this->simpan('Pembayaran', 'UjiBayarB', [], utama: true);

        $pembaca = app(PembacaKredensialPenyedia::class);
        $this->assertSame('UjiBayarB', $pembaca->kodeUtama(KategoriPenyediaLayanan::Pembayaran));
        $this->assertSame(['UjiBayarB', 'UjiBayarA'], $pembaca->kodeAktif(KategoriPenyediaLayanan::Pembayaran));

        // Mematikan yang utama menyerahkan gelarnya ke penyedia aktif yang tersisa.
        $this->simpan('Pembayaran', 'UjiBayarB', [], aktif: false);
        $this->assertSame('UjiBayarA', $pembaca->kodeUtama(KategoriPenyediaLayanan::Pembayaran));
        $this->assertFalse($this->baris('UjiBayarB')->Utama);
    }

    public function test_uji_koneksi_memakai_kredensial_tersimpan_walau_belum_aktif(): void
    {
        $this->simpan('Pembayaran', 'UjiBayarA', $this->lengkap(), aktif: false);

        $this->sebagaiAdmin()
            ->postJson('/admin-platform/penyedia-layanan/Pembayaran/UjiBayarA/uji')
            ->assertOk()
            ->assertJson(['Berhasil' => true, 'Pesan' => 'Terhubung sebagai M-001.']);

        $this->assertTrue($this->bayarA->kredensialDiuji?->modeUji);
    }

    public function test_galat_tak_terduga_saat_uji_tidak_membocorkan_rahasia(): void
    {
        $this->simpan('Pembayaran', 'UjiBayarA', $this->lengkap());
        $this->bayarA->gagalTakTerduga = true;

        $respons = $this->sebagaiAdmin()->postJson('/admin-platform/penyedia-layanan/Pembayaran/UjiBayarA/uji');

        $respons->assertUnprocessable()->assertJson(['Berhasil' => false]);
        $this->assertStringNotContainsString(self::RAHASIA, (string) $respons->getContent());
    }

    public function test_penyedia_atau_kategori_tak_dikenal_ditolak(): void
    {
        $this->sebagaiAdmin()
            ->putJson('/admin-platform/penyedia-layanan/Pembayaran/TidakAda', ['Aktif' => false])
            ->assertNotFound();
        $this->sebagaiAdmin()
            ->putJson('/admin-platform/penyedia-layanan/Sms/UjiBayarA', ['Aktif' => false])
            ->assertNotFound();
    }

    public function test_hanya_admin_berizin_yang_boleh_masuk(): void
    {
        $this->get('/admin-platform/penyedia-layanan')->assertRedirect();

        $tanpaIzin = $this->buatAdmin(superAdmin: false);
        $this->actingAs($tanpaIzin, 'platform')->get('/admin-platform/penyedia-layanan')->assertForbidden();
        $this->actingAs($tanpaIzin, 'platform')
            ->put('/admin-platform/penyedia-layanan/Pembayaran/UjiBayarA', ['Aktif' => false])
            ->assertForbidden();

        $berizin = $this->buatAdmin(superAdmin: false, izin: [KatalogPenyediaLayanan::IZIN_KELOLA]);
        $this->actingAs($berizin, 'platform')->get('/admin-platform/penyedia-layanan')->assertOk();
    }

    /** @return array<string, string> */
    private function lengkap(): array
    {
        return ['IdPedagang' => 'M-001', 'KunciRahasia' => self::RAHASIA];
    }

    /** @param  array<string, string>  $kredensial */
    private function simpan(string $kategori, string $kode, array $kredensial, bool $aktif = true, ?bool $utama = null): void
    {
        $this->sebagaiAdmin()
            ->put("/admin-platform/penyedia-layanan/{$kategori}/{$kode}", array_filter([
                'Aktif' => $aktif,
                'Utama' => $utama,
                'Kredensial' => $kredensial,
            ], fn (mixed $nilai): bool => $nilai !== null))
            ->assertSessionHasNoErrors();
    }

    private function baris(string $kode, string $kategori = 'Pembayaran'): PenyediaLayananPlatform
    {
        return PenyediaLayananPlatform::query()->where('Kategori', $kategori)->where('Kode', $kode)->firstOrFail();
    }

    private function sebagaiAdmin(): self
    {
        return $this->actingAs($this->buatAdmin(), 'platform');
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
