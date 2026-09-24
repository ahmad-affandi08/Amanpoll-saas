<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Infrastructure\Keamanan\PenjagaUrlKeluar;
use App\Shared\Infrastructure\Keamanan\UrlKeluarDitolak;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Dukungan\PenyelesaiDnsPalsu;
use Tests\TestCase;

/** Temuan audit #1: URL isian pengguna tidak boleh membawa server ke jaringan internal (SSRF). */
final class PenjagaUrlKeluarTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function urlTerlarang(): array
    {
        $internal = UrlKeluarDitolak::PESAN_JARINGAN_INTERNAL;

        return [
            'loopback' => ['http://127.0.0.1/hook', $internal],
            'localhost' => ['https://localhost/hook', $internal],
            'subdomain localhost' => ['https://api.localhost/hook', $internal],
            'privat 10/8' => ['https://10.20.30.40/', $internal],
            'privat 172.16/12' => ['https://172.16.5.4/', $internal],
            'privat 192.168/16' => ['https://192.168.1.1/', $internal],
            'metadata awan' => ['http://169.254.169.254/latest/meta-data/', $internal],
            'CGNAT' => ['https://100.64.0.1/', $internal],
            '0.0.0.0' => ['https://0.0.0.0/', $internal],
            'IPv6 loopback' => ['https://[::1]/', $internal],
            'IPv4-mapped IPv6 bertitik' => ['https://[::ffff:127.0.0.1]/', $internal],
            'IPv4-mapped IPv6 heksa' => ['https://[::ffff:7f00:1]/', $internal],
            'NAT64 ke privat' => ['https://[64:ff9b::a00:1]/', $internal],
            '6to4 ke privat' => ['https://[2002:c0a8:101::1]/', $internal],
            'IPv6 ULA metadata AWS' => ['https://[fd00:ec2::254]/', $internal],
            'IPv6 link-local' => ['https://[fe80::1]/', $internal],
            'IPv4-compatible usang' => ['https://[::10.0.0.1]/', $internal],
            // Dua berikut lolos filter_var bahkan dengan FILTER_FLAG_GLOBAL_RANGE; hanya daftar CIDR eksplisit yang menutupnya.
            'NAT64 lokal' => ['https://[64:ff9b:1::a00:1]/', $internal],
            'IPv6 site-local usang' => ['https://[fec0::1]/', $internal],
            'heksa tunggal' => ['https://0x7f000001/', $internal],
            'desimal tunggal' => ['https://2130706433/', $internal],
            'oktal' => ['https://0177.0.0.1/', $internal],
            'bentuk pendek' => ['https://127.1/', $internal],
            'akhiran internal' => ['https://metadata.google.internal/', $internal],
            'nama satu label' => ['https://redis/', 'Nama host harus berupa domain lengkap, mis. contoh.co.id.'],
            'userinfo' => ['https://pengguna:sandi@contoh.co.id/', 'Alamat tidak boleh memuat nama pengguna atau kata sandi.'],
            'userinfo membelokkan host' => ['https://contoh.co.id@127.0.0.1/', 'Alamat tidak boleh memuat nama pengguna atau kata sandi.'],
            'garis miring terbalik' => ['https://contoh.co.id\\@127.0.0.1/', 'Alamat tidak valid. Tulis alamat lengkap tanpa spasi, mis. https://contoh.co.id/webhook.'],
            'port tak lazim' => ['https://contoh.co.id:6379/', 'Port 6379 tidak diizinkan. Pakai port bawaan (443 untuk https).'],
            'skema lain' => ['gopher://contoh.co.id/', 'Alamat harus diawali http:// atau https://.'],
            'IP publik tidak baku' => ['https://134744072/', 'Alamat IP harus ditulis dalam bentuk baku: empat bilangan desimal bertitik.'],
            'tanpa host' => ['https:///hook', 'Alamat tidak valid. Tulis alamat lengkap, mis. https://contoh.co.id/webhook.'],
        ];
    }

    #[DataProvider('urlTerlarang')]
    public function test_url_terlarang_ditolak_dengan_pesan_yang_tepat(string $url, string $pesan): void
    {
        try {
            app(PenjagaUrlKeluar::class)->periksa($url);
            $this->fail("Seharusnya ditolak: {$url}");
        } catch (UrlKeluarDitolak $galat) {
            $this->assertSame($pesan, $galat->getMessage());
            $this->assertFalse($galat->sementara);
        }
    }

    public function test_domain_publik_yang_meresolusi_ke_ip_privat_ditolak(): void
    {
        $this->dnsPalsu()->petakan('rebind.contoh.co.id', ['10.0.0.8']);

        $this->assertDitolak('https://rebind.contoh.co.id/hook', UrlKeluarDitolak::PESAN_JARINGAN_INTERNAL);
    }

    /** Satu alamat internal di antara alamat publik sudah cukup untuk menolak: curl bisa memilih yang mana saja. */
    public function test_satu_alamat_internal_di_antara_alamat_publik_menolak_seluruhnya(): void
    {
        $this->dnsPalsu()->petakan('campur.contoh.co.id', [PenyelesaiDnsPalsu::ALAMAT_PUBLIK, '::1']);

        $this->assertDitolak('https://campur.contoh.co.id/', UrlKeluarDitolak::PESAN_JARINGAN_INTERNAL);
    }

    public function test_host_yang_tidak_ditemukan_ditolak_sebagai_kegagalan_sementara(): void
    {
        $this->dnsPalsu()->petakan('hilang.contoh.co.id', []);

        try {
            app(PenjagaUrlKeluar::class)->periksa('https://hilang.contoh.co.id/');
            $this->fail('Seharusnya ditolak.');
        } catch (UrlKeluarDitolak $galat) {
            $this->assertTrue($galat->sementara);
        }
    }

    public function test_http_ditolak_bila_konfigurasi_produksi_tidak_mengizinkannya(): void
    {
        config(['amanpoll.http_keluar.izinkan_http' => false]);

        $this->assertDitolak('http://contoh.co.id/hook', 'Alamat harus memakai https://.');
        $this->assertSame('https://contoh.co.id/hook', app(PenjagaUrlKeluar::class)->periksa('https://contoh.co.id/hook')->url);
    }

    public function test_port_tambahan_dari_konfigurasi_diterima(): void
    {
        config(['amanpoll.http_keluar.port_tambahan' => [3000]]);

        $sah = app(PenjagaUrlKeluar::class)->periksa('https://waha.contoh.co.id:3000/');

        $this->assertSame(3000, $sah->port);
        $this->assertSame('waha.contoh.co.id:3000:'.PenyelesaiDnsPalsu::ALAMAT_PUBLIK, $sah->entriResolve());
    }

    public function test_domain_publik_https_diterima_dan_disusun_ulang_dalam_bentuk_baku(): void
    {
        $this->dnsPalsu()->petakan('hooks.contoh.co.id', ['2606:4700::6810:84e5', '104.16.132.229']);

        $sah = app(PenjagaUrlKeluar::class)->periksa('HTTPS://Hooks.Contoh.co.id/masuk/erp?sumber=amanpoll#bagian');

        $this->assertSame('https://hooks.contoh.co.id/masuk/erp?sumber=amanpoll', $sah->url);
        $this->assertSame(443, $sah->port);
        // IPv4 didahulukan untuk disematkan karena shared hosting sering tanpa rute IPv6.
        $this->assertSame('hooks.contoh.co.id:443:104.16.132.229', $sah->entriResolve());
    }

    /**
     * Bukti CURLOPT_RESOLVE benar-benar terpasang: Http::fake meneruskan opsi
     * Guzzle yang akan diterima curl ke callback-nya.
     */
    public function test_klien_menyematkan_ip_yang_diperiksa_dan_mematikan_redirect(): void
    {
        $opsiTerkirim = null;
        Http::preventStrayRequests();
        Http::fake(function (Request $permintaan, array $opsi) use (&$opsiTerkirim) {
            $opsiTerkirim = $opsi;

            return Http::response('ok');
        });
        $penjaga = app(PenjagaUrlKeluar::class);
        $sah = $penjaga->periksa('https://hooks.contoh.co.id/masuk');

        $penjaga->klien($sah)->post($sah->url, ['a' => 1]);

        $this->assertIsArray($opsiTerkirim);
        $this->assertSame(['hooks.contoh.co.id:443:'.PenyelesaiDnsPalsu::ALAMAT_PUBLIK], $opsiTerkirim['curl'][CURLOPT_RESOLVE] ?? null);
        $this->assertFalse($opsiTerkirim['allow_redirects']);
    }

    public function test_redirect_ke_ip_privat_tidak_diikuti(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'hooks.contoh.co.id/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/rahasia']),
            '127.0.0.1/*' => Http::response('isi internal', 200),
        ]);
        $penjaga = app(PenjagaUrlKeluar::class);
        $sah = $penjaga->periksa('https://hooks.contoh.co.id/masuk');

        $respons = $penjaga->klien($sah)->get($sah->url);

        $this->assertSame(302, $respons->status());
        Http::assertSentCount(1);
        Http::assertNotSent(fn (Request $permintaan): bool => str_contains($permintaan->url(), '127.0.0.1'));
    }

    public function test_klien_menolak_dipakai_untuk_host_yang_tidak_diperiksa(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $penjaga = app(PenjagaUrlKeluar::class);
        $sah = $penjaga->periksa('https://hooks.contoh.co.id/masuk');

        try {
            $penjaga->klien($sah)->get('http://169.254.169.254/latest/meta-data/');
            $this->fail('Seharusnya ditolak.');
        } catch (UrlKeluarDitolak) {
            // diharapkan
        }

        Http::assertNothingSent();
    }

    private function assertDitolak(string $url, string $pesan): void
    {
        try {
            app(PenjagaUrlKeluar::class)->periksa($url);
            $this->fail("Seharusnya ditolak: {$url}");
        } catch (UrlKeluarDitolak $galat) {
            $this->assertSame($pesan, $galat->getMessage());
        }
    }
}
