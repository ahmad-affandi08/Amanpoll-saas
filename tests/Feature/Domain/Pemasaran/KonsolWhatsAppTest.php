<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananTemplateWhatsApp;
use App\Domain\Pemasaran\Application\Services\PemeriksaAlertPemasaran;
use App\Domain\Pemasaran\Application\Services\RegistriTindakanOtomasi;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\KatalogAlertPemasaran;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AlertPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\MenuWhatsAppPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanWhatsAppPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateWhatsAppPemasaran;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Konsol template dan menu, aksi otomasi, dan alert kegagalan (MARKETING.md 16, 17). */
final class KonsolWhatsAppTest extends KasusWhatsApp
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->nyalakanFitur(KatalogFiturPlatform::WHATSAPP);
    }

    public function test_konsol_tertutup_saat_flag_whatsapp_mati(): void
    {
        $this->matikanFitur(KatalogFiturPlatform::WHATSAPP);

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::WHATSAPP_LIHAT]), 'platform')
            ->get(route('pemasaran.whatsapp.index'))
            ->assertNotFound();
    }

    public function test_template_baru_selalu_lahir_sebagai_draf(): void
    {
        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.whatsapp.template.store'), $this->muatan())
            ->assertRedirect();

        $this->assertSame(
            StatusPersetujuanTemplateWa::Draf,
            TemplateWhatsAppPemasaran::query()->firstOrFail()->StatusPersetujuan,
        );
    }

    public function test_variabel_salah_ketik_kembali_sebagai_galat_field(): void
    {
        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.whatsapp.template.store'), [
                ...$this->muatan(),
                'IsiTeks' => 'Halo {{NamaSalahKetik}}',
            ])
            ->assertSessionHasErrors('IsiTeks');
    }

    /** Persetujuan Meta melekat pada naskah tertentu, bukan pada nama templatenya. */
    public function test_mengubah_naskah_membatalkan_persetujuannya(): void
    {
        $template = $this->buatTemplate();

        $this->actingAs($this->pengelola(), 'platform')
            ->put(route('pemasaran.whatsapp.template.update', $template), [
                ...$this->muatan(),
                'Kode' => $template->Kode,
                'IsiTeks' => 'Naskah yang sama sekali berbeda.',
            ])
            ->assertRedirect();

        $this->assertSame(
            StatusPersetujuanTemplateWa::Draf,
            $template->fresh()?->StatusPersetujuan,
        );
    }

    public function test_mengubah_selain_naskah_tidak_membatalkan_persetujuannya(): void
    {
        $template = $this->buatTemplate();

        $this->actingAs($this->pengelola(), 'platform')
            ->put(route('pemasaran.whatsapp.template.update', $template), [
                ...$this->muatan(),
                'Kode' => $template->Kode,
                'Nama' => 'Nama Baru',
                'IsiTeks' => $template->IsiTeks,
            ])
            ->assertRedirect();

        $this->assertSame(
            StatusPersetujuanTemplateWa::Disetujui,
            $template->fresh()?->StatusPersetujuan,
        );
    }

    /** Status persetujuan hanya boleh bergerak menurut petanya, termasuk lewat jalur manual. */
    public function test_keputusan_manual_di_luar_peta_ditolak(): void
    {
        $template = $this->buatTemplate(StatusPersetujuanTemplateWa::Draf);

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananTemplateWhatsApp::class)->catatKeputusanManual(
            $template,
            StatusPersetujuanTemplateWa::Disetujui,
        );
    }

    public function test_pengajuan_menyalin_keputusan_penyedia(): void
    {
        $template = $this->buatTemplate(StatusPersetujuanTemplateWa::Draf);
        $this->penyedia->keputusan = StatusPersetujuanTemplateWa::Diajukan;

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.whatsapp.template.ajukan', $template))
            ->assertRedirect();

        $segar = $template->fresh();

        $this->assertSame(StatusPersetujuanTemplateWa::Diajukan, $segar?->StatusPersetujuan);
        $this->assertSame('wa-sapaan', $segar?->IdTemplatePenyedia);
    }

    /** Penolakan penyedia tercatat beserta alasannya, bukan hanya statusnya. */
    public function test_penolakan_penyedia_tercatat_beserta_alasannya(): void
    {
        $template = $this->buatTemplate(StatusPersetujuanTemplateWa::Diajukan);
        $this->penyedia->keputusan = StatusPersetujuanTemplateWa::Ditolak;
        $this->penyedia->alasan = 'Naskah terlalu promosional.';

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.whatsapp.template.periksa', $template))
            ->assertRedirect();

        $segar = $template->fresh();

        $this->assertSame(StatusPersetujuanTemplateWa::Ditolak, $segar?->StatusPersetujuan);
        $this->assertSame('Naskah terlalu promosional.', $segar?->AlasanPenolakan);
        $this->assertFalse($segar?->siapKirim());
    }

    /** Menu dikirim utuh: butir yang hilang dari kiriman berarti dicabut. */
    public function test_butir_menu_yang_tidak_dikirim_ulang_tercabut(): void
    {
        $this->buatMenu();
        $admin = $this->pengelola();

        $this->assertSame(3, MenuWhatsAppPemasaran::query()->count());

        $this->actingAs($admin, 'platform')
            ->put(route('pemasaran.whatsapp.menu.simpan'), [
                'Menu' => [
                    ['Kunci' => '1', 'Label' => 'Lihat Demo', 'Balasan' => 'Tautan demo.', 'Aktif' => true],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(1, MenuWhatsAppPemasaran::query()->count());
        $this->assertSame('Tautan demo.', MenuWhatsAppPemasaran::query()->value('Balasan'));
    }

    public function test_izin_lihat_tidak_cukup_untuk_mengubah_template(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::WHATSAPP_LIHAT]), 'platform')
            ->post(route('pemasaran.whatsapp.template.store'), $this->muatan())
            ->assertForbidden();
    }

    public function test_konsol_menampilkan_pratinjau_menu_dan_ringkasan_kiriman(): void
    {
        $this->buatMenu();

        $props = $this->actingAs($this->pengelola(), 'platform')
            ->get(route('pemasaran.whatsapp.index'))
            ->viewData('page')['props'];

        $this->assertStringContainsString('1. Lihat Demo', $props['pratinjauMenu']);
        $this->assertContains('STOP', $props['kataBerhenti']);
        $this->assertSame(0, $props['ringkasanKiriman'][StatusPengirimanWhatsApp::Dikirim->value]);
    }

    /** Aksi KirimWhatsApp harus benar-benar terdaftar, bukan sekadar ada kelasnya. */
    public function test_aksi_kirim_whatsapp_terdaftar_di_registri(): void
    {
        $registri = app(RegistriTindakanOtomasi::class);

        $this->assertTrue($registri->ada('KirimWhatsApp'));

        $prospek = $this->buatProspek();
        $this->buatTemplate();

        $pesan = $registri->ambil('KirimWhatsApp')->jalankan(
            new KonteksOtomasi($prospek, null, null, kunciLangkah: 'otomasi:uji:1'),
            ['TemplateKode' => 'sapaan'],
        );

        $this->assertStringContainsString('6281234567890', $pesan);
        $this->assertSame(1, PengirimanWhatsAppPemasaran::query()->count());
    }

    /** Alert whatsapp_gagal_kirim tidak lagi menyebut dirinya belum tersedia. */
    public function test_alert_whatsapp_kini_tersedia(): void
    {
        $this->assertTrue(KatalogAlertPemasaran::tersedia(KatalogAlertPemasaran::WHATSAPP_GAGAL_KIRIM));
    }

    /** Kegagalan di atas ambang membangunkan tim; di bawah ambang tidak. */
    public function test_alert_berbunyi_saat_kegagalan_melewati_ambang(): void
    {
        $this->semaiKiriman(gagal: 8, berhasil: 17);

        $alert = app(PemeriksaAlertPemasaran::class)->periksa();
        $kode = array_map(fn (AlertPemasaran $satu): string => $satu->Kode, $alert);

        $this->assertContains(KatalogAlertPemasaran::WHATSAPP_GAGAL_KIRIM, $kode);
    }

    public function test_alert_diam_saat_kegagalan_masih_wajar(): void
    {
        $this->semaiKiriman(gagal: 1, berhasil: 30);

        $alert = app(PemeriksaAlertPemasaran::class)->periksa();
        $kode = array_map(fn (AlertPemasaran $satu): string => $satu->Kode, $alert);

        $this->assertNotContains(KatalogAlertPemasaran::WHATSAPP_GAGAL_KIRIM, $kode);
    }

    private function semaiKiriman(int $gagal, int $berhasil): void
    {
        $prospek = $this->buatProspek();
        $template = $this->buatTemplate();
        $sekarang = CarbonImmutable::now()->subHours(2);

        foreach (range(1, $gagal + $berhasil) as $ke) {
            PengirimanWhatsAppPemasaran::create([
                'ProspekId' => $prospek->Id,
                'Nomor' => '6281234567890',
                'TemplateWhatsAppPemasaranId' => $template->Id,
                'KunciIdempotensi' => "semai:{$ke}",
                'Status' => $ke <= $gagal
                    ? StatusPengirimanWhatsApp::Gagal
                    : StatusPengirimanWhatsApp::Terkirim,
                'IsiTeks' => 'Halo.',
                'Percobaan' => 1,
                'JadwalPada' => $sekarang,
                'DikirimPada' => $ke <= $gagal ? null : $sekarang,
                'DiperbaruiStatusPada' => $sekarang,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function muatan(): array
    {
        return [
            'Kode' => 'sapaan-baru',
            'Nama' => 'Sapaan Baru',
            'Bahasa' => 'id',
            'Kategori' => 'Marketing',
            'IsiTeks' => 'Halo {{Nama}}, ada yang bisa kami bantu?',
            'Aktif' => true,
        ];
    }

    private function pengelola(): AdminPlatform
    {
        return $this->buatAdmin([
            KatalogIzinPemasaran::WHATSAPP_LIHAT,
            KatalogIzinPemasaran::WHATSAPP_KELOLA,
        ]);
    }
}
