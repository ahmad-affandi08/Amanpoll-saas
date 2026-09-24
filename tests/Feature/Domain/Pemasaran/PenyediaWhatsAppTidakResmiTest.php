<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananTemplateWhatsApp;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Services\PenyediaWhatsAppFonnte;
use App\Domain\Pemasaran\Infrastructure\Services\PenyediaWhatsAppWablas;
use App\Domain\Pemasaran\Infrastructure\Services\PenyediaWhatsAppWaha;
use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/** Gateway WhatsApp tidak resmi: teks biasa tanpa peninjauan Meta, dan risikonya terpampang di konsol. */
final class PenyediaWhatsAppTidakResmiTest extends KasusWhatsApp
{
    use MengaturPenyediaWhatsApp;

    public function test_konsol_menawarkan_satu_penyedia_resmi_dan_tiga_tidak_resmi_beserta_risikonya(): void
    {
        $penyedia = app(KatalogPenyediaLayanan::class)->menurutKategori(KategoriPenyediaLayanan::WhatsApp);
        $resmi = [];

        foreach ($penyedia as $satu) {
            $resmi[$satu->kode()] = $satu->resmi();
        }

        $this->assertSame(['MetaCloud' => true, 'Fonnte' => false, 'Wablas' => false, 'Waha' => false], $resmi);

        foreach (array_filter($penyedia, fn (DeskripsiPenyediaLayanan $satu): bool => ! $satu->resmi()) as $satu) {
            $this->assertStringContainsString('diblokir WhatsApp', $satu->keterangan(), $satu->kode());
            $this->assertStringContainsString('melanggar ketentuan layanan WhatsApp', $satu->keterangan(), $satu->kode());
        }
    }

    public function test_fonnte_mengirim_teks_terender_dengan_token_di_header(): void
    {
        $this->aturPenyedia('Fonnte', ['Token' => 'token-fonnte']);
        Http::preventStrayRequests();
        Http::fake(['https://api.fonnte.com/send' => Http::response([
            'status' => true,
            'id' => ['80367170'],
            'process' => 'pending',
        ])]);

        $id = app(PenyediaWhatsAppFonnte::class)->kirim($this->pesan());

        $this->assertSame('80367170', $id);
        Http::assertSent(fn (Request $permintaan): bool => $permintaan->method() === 'POST'
            && $permintaan->hasHeader('Authorization', 'token-fonnte')
            && $permintaan->isForm()
            && $permintaan->data() === ['target' => '6281234567890', 'message' => 'Halo Budi, terima kasih.']);
    }

    /** Fonnte menjawab 200 juga saat menolak; tanpa memeriksa `status`, kiriman gagal tercatat berhasil. */
    public function test_penolakan_fonnte_berstatus_200_tetap_menjadi_galat_tanpa_token(): void
    {
        $this->aturPenyedia('Fonnte', ['Token' => 'token-fonnte']);
        Http::preventStrayRequests();
        Http::fake(['https://api.fonnte.com/send' => Http::response([
            'status' => false,
            'reason' => 'invalid token token-fonnte',
        ])]);

        try {
            app(PenyediaWhatsAppFonnte::class)->kirim($this->pesan());
            $this->fail('Seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringContainsString('Fonnte menolak pengiriman pesan (HTTP 200): invalid token ***', $galat->getMessage());
            $this->assertStringNotContainsString('token-fonnte', $galat->getMessage());
        }
    }

    public function test_uji_koneksi_fonnte_membedakan_token_salah_dan_perangkat_terputus(): void
    {
        $kredensial = $this->aturPenyedia('Fonnte', ['Token' => 'token-fonnte'])->keKredensial();
        Http::preventStrayRequests();
        Http::fake(['https://api.fonnte.com/device' => Http::sequence()
            ->push(['status' => true, 'device_status' => 'connect', 'name' => 'Pemasaran', 'device' => '62811'])
            ->push(['status' => true, 'device_status' => 'disconnect', 'name' => 'Pemasaran', 'device' => '62811'])
            ->push(['status' => false, 'reason' => 'token invalid']),
        ]);

        $tersambung = app(PenyediaWhatsAppFonnte::class)->ujiKoneksi($kredensial);
        $terputus = app(PenyediaWhatsAppFonnte::class)->ujiKoneksi($kredensial);
        $tokenSalah = app(PenyediaWhatsAppFonnte::class)->ujiKoneksi($kredensial);

        $this->assertTrue($tersambung->berhasil);
        $this->assertFalse($terputus->berhasil);
        $this->assertStringContainsString('Pindai ulang', $terputus->pesan);
        $this->assertFalse($tokenSalah->berhasil);
        $this->assertStringContainsString('token invalid', $tokenSalah->pesan);
    }

    public function test_wablas_memakai_domain_akun_serta_token_dan_secret_key(): void
    {
        $this->aturPenyedia('Wablas', ['Domain' => 'tegal.wablas.com/', 'Token' => 'tok', 'SecretKey' => 'sek']);
        Http::preventStrayRequests();
        Http::fake(['https://tegal.wablas.com/api/send-message' => Http::response([
            'status' => true,
            'data' => ['messages' => [['id' => 'wbl-1', 'status' => 'pending']]],
        ])]);

        $id = app(PenyediaWhatsAppWablas::class)->kirim($this->pesan());

        $this->assertSame('wbl-1', $id);
        Http::assertSent(fn (Request $permintaan): bool => $permintaan->hasHeader('Authorization', 'tok.sek')
            && $permintaan->data() === ['phone' => '6281234567890', 'message' => 'Halo Budi, terima kasih.']);
    }

    /** Token ikut di header, jadi domain tanpa enkripsi ditolak sebelum permintaan berangkat. */
    public function test_wablas_menolak_domain_http_biasa(): void
    {
        $this->aturPenyedia('Wablas', ['Domain' => 'http://tegal.wablas.com', 'Token' => 'tok', 'SecretKey' => 'sek']);
        Http::preventStrayRequests();
        Http::fake();

        try {
            app(PenyediaWhatsAppWablas::class)->kirim($this->pesan());
            $this->fail('Seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringContainsString('https', $galat->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_waha_mengirim_ke_sesi_dengan_kunci_api(): void
    {
        $this->aturPenyedia('Waha', ['UrlDasar' => 'https://waha.test/', 'KunciApi' => 'kunci-waha', 'NamaSesi' => 'pemasaran']);
        Http::preventStrayRequests();
        Http::fake(['https://waha.test/api/sendText' => Http::response([
            'id' => ['fromMe' => true, '_serialized' => 'true_6281234567890@c.us_3EB0'],
        ], 201)]);

        $id = app(PenyediaWhatsAppWaha::class)->kirim($this->pesan());

        $this->assertSame('true_6281234567890@c.us_3EB0', $id);
        Http::assertSent(fn (Request $permintaan): bool => $permintaan->hasHeader('X-Api-Key', 'kunci-waha')
            && $permintaan->isJson()
            && $permintaan->data() === [
                'session' => 'pemasaran',
                'chatId' => '6281234567890@c.us',
                'text' => 'Halo Budi, terima kasih.',
            ]);
    }

    public function test_uji_koneksi_waha_melaporkan_sesi_yang_belum_dipindai(): void
    {
        $kredensial = $this->aturPenyedia('Waha', ['UrlDasar' => 'https://waha.test'])->keKredensial();
        Http::preventStrayRequests();
        Http::fake(['https://waha.test/api/sessions/default' => Http::response(['name' => 'default', 'status' => 'SCAN_QR_CODE'])]);

        $hasil = app(PenyediaWhatsAppWaha::class)->ujiKoneksi($kredensial);

        $this->assertFalse($hasil->berhasil);
        $this->assertStringContainsString('SCAN_QR_CODE', $hasil->pesan);
    }

    /** Tidak ada Meta yang meninjau, jadi template langsung disetujui dengan alasan yang terus terang. */
    public function test_template_pada_penyedia_tidak_resmi_langsung_disetujui_tanpa_panggilan_api(): void
    {
        $this->aturPenyedia('Fonnte', ['Token' => 'token-fonnte']);
        $this->app->instance(PenyediaWhatsApp::class, app(PenyediaWhatsAppFonnte::class));
        Http::preventStrayRequests();
        Http::fake();
        $template = $this->buatTemplate(StatusPersetujuanTemplateWa::Draf);

        $status = app(LayananTemplateWhatsApp::class)->ajukan($template);

        $this->assertSame(StatusPersetujuanTemplateWa::Disetujui, $status);
        $this->assertTrue($template->refresh()->siapKirim());
        $this->assertStringContainsString('tanpa peninjauan template oleh Meta', (string) $template->AlasanPenolakan);
        Http::assertNothingSent();
    }

    private function pesan(): PesanWhatsApp
    {
        return new PesanWhatsApp('0812-3456-7890', 'sapaan', 'id', 'Halo Budi, terima kasih.', naskahTemplate: 'Halo {{Nama}}, terima kasih.');
    }
}
