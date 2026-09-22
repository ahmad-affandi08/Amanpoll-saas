<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\KirimFormulirPemasaran;
use App\Domain\Pemasaran\Application\Services\PerangkapSpam;
use App\Domain\Pemasaran\Domain\Enums\JenisBlokHalaman;
use App\Domain\Pemasaran\Domain\Enums\JenisFieldFormulir;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogTahapPipeline;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FieldFormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanFormulir;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;
use App\Http\Middleware\TetapkanSesiPengunjung;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

/** Formulir publik menghasilkan lead lengkap dengan UTM (Gate 32, MARKETING.md 10, 36). */
final class FormulirPemasaranTest extends KasusHalaman
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->semaiTahap();
    }

    public function test_pengiriman_menghasilkan_prospek(): void
    {
        $this->pasangFormulirDiHalaman();

        $this->kirim(['Nama' => 'Budi', 'Email' => 'budi@pabrik.test', 'Setuju' => true])
            ->assertRedirect();

        $prospek = Prospek::query()->where('Email', 'budi@pabrik.test')->first();

        $this->assertNotNull($prospek);
        $this->assertSame('Budi', $prospek->Nama);
        $this->assertSame(SumberProspek::LeadMagnet->value, $prospek->Sumber);
    }

    public function test_pengiriman_membawa_utm_dari_kunjungan(): void
    {
        $this->pasangFormulirDiHalaman();

        $kunjungan = $this->get($this->urlPublik('/unduh?utm_source=google&utm_medium=cpc&utm_campaign=q1'));
        $pengenal = $kunjungan->getCookie(TetapkanSesiPengunjung::NAMA_COOKIE)?->getValue();
        $this->assertNotNull($pengenal);

        $this->withCookie(TetapkanSesiPengunjung::NAMA_COOKIE, (string) $pengenal)
            ->post($this->urlPublik('/formulir/unduh-template'), [
                'Nama' => 'Budi',
                'Email' => 'budi@pabrik.test',
                'Setuju' => true,
            ])
            ->assertRedirect();

        $pengiriman = PengirimanFormulir::query()->firstOrFail();

        $this->assertSame('google', $pengiriman->Data['utm_source'] ?? null);
        $this->assertSame('cpc', $pengiriman->Data['utm_medium'] ?? null);
        $this->assertSame('q1', $pengiriman->Data['utm_campaign'] ?? null);
    }

    public function test_utm_dari_masukan_tidak_dipercaya(): void
    {
        $this->pasangFormulirDiHalaman();

        $this->kirim([
            'Nama' => 'Budi',
            'Email' => 'budi@pabrik.test',
            'Setuju' => true,
            'utm_source' => 'dipalsukan',
        ])->assertRedirect();

        $pengiriman = PengirimanFormulir::query()->firstOrFail();

        $this->assertNull($pengiriman->Data['utm_source'] ?? null);
    }

    public function test_honeypot_terisi_tidak_menghasilkan_apa_pun(): void
    {
        $this->pasangFormulirDiHalaman();

        $this->kirim([
            'Nama' => 'Bot',
            'Email' => 'bot@spam.test',
            'Setuju' => true,
            PerangkapSpam::FIELD => 'http://spam.test',
        ])->assertRedirect();

        $this->assertSame(0, Prospek::query()->count());
        $this->assertSame(0, PengirimanFormulir::query()->count());
    }

    public function test_field_wajib_yang_kosong_ditolak(): void
    {
        $this->pasangFormulirDiHalaman();

        $this->kirim(['Email' => 'budi@pabrik.test', 'Setuju' => true])
            ->assertSessionHasErrors('Nama');

        $this->assertSame(0, Prospek::query()->count());
    }

    public function test_persetujuan_yang_tidak_dicentang_ditolak(): void
    {
        $this->pasangFormulirDiHalaman();

        $this->kirim(['Nama' => 'Budi', 'Email' => 'budi@pabrik.test'])
            ->assertSessionHasErrors('Setuju');

        $this->assertSame(0, Prospek::query()->count());
    }

    /** Penjaga terakhir persetujuan. */
    public function test_formulir_wajib_persetujuan_tanpa_field_persetujuan_ditolak(): void
    {
        $formulir = $this->buatFormulir();
        FieldFormulirPemasaran::query()
            ->where('FormulirPemasaranId', $formulir->Id)
            ->where('Kode', 'Setuju')
            ->delete();

        $this->expectException(AturanBisnisDilanggar::class);

        app(KirimFormulirPemasaran::class)->jalankan($formulir->fresh(), [
            'Nama' => 'Budi',
            'Email' => 'budi@pabrik.test',
        ]);
    }

    public function test_persetujuan_tersimpan_pada_pengiriman(): void
    {
        $this->pasangFormulirDiHalaman();

        $this->kirim(['Nama' => 'Budi', 'Email' => 'budi@pabrik.test', 'Setuju' => true]);

        $this->assertTrue(PengirimanFormulir::query()->firstOrFail()->Persetujuan);
    }

    public function test_formulir_nonaktif_menolak_pengiriman(): void
    {
        $formulir = $this->buatFormulir();
        $formulir->update(['Aktif' => false]);

        $this->expectException(AturanBisnisDilanggar::class);

        app(KirimFormulirPemasaran::class)->jalankan($formulir->fresh(), [
            'Nama' => 'Budi',
            'Setuju' => true,
        ]);
    }

    /** Yang diperiksa adalah pemasangan middlewarenya, bukan penolakannya. */
    public function test_rute_formulir_berada_di_balik_pemeriksaan_csrf(): void
    {
        $rute = Route::getRoutes()->getByName('publik.formulir');
        $this->assertNotNull($rute);
        $this->assertContains('web', $rute->gatherMiddleware());

        /** @var Kernel $kernel */
        $kernel = app(Kernel::class);
        $this->assertContains(PreventRequestForgery::class, $kernel->getMiddlewareGroups()['web']);
    }

    public function test_pengiriman_dibatasi_lajunya(): void
    {
        $this->pasangFormulirDiHalaman();

        for ($ke = 0; $ke < 5; $ke++) {
            $this->kirim(['Nama' => "Budi {$ke}", 'Email' => "budi{$ke}@pabrik.test", 'Setuju' => true]);
        }

        $this->kirim(['Nama' => 'Budi 6', 'Email' => 'budi6@pabrik.test', 'Setuju' => true])
            ->assertStatus(429);
    }

    public function test_pengiriman_bersifat_hanya_tambah(): void
    {
        $this->pasangFormulirDiHalaman();
        $this->kirim(['Nama' => 'Budi', 'Email' => 'budi@pabrik.test', 'Setuju' => true]);

        $pengiriman = PengirimanFormulir::query()->firstOrFail();

        $this->expectException(AturanBisnisDilanggar::class);

        $pengiriman->Persetujuan = false;
        $pengiriman->save();
    }

    public function test_captcha_yang_menyala_tanpa_kunci_menolak_pengiriman(): void
    {
        config(['amanpoll.pemasaran.captcha.rahasia' => null]);

        $formulir = $this->buatFormulir();
        $formulir->update(['CaptchaAktif' => true]);

        $this->expectException(AturanBisnisDilanggar::class);

        app(KirimFormulirPemasaran::class)->jalankan($formulir->fresh(), [
            'Nama' => 'Budi',
            'Setuju' => true,
        ]);
    }

    public function test_captcha_tanpa_token_ditolak(): void
    {
        config(['amanpoll.pemasaran.captcha.rahasia' => 'rahasia-uji']);
        Http::fake();

        $formulir = $this->buatFormulir();
        $formulir->update(['CaptchaAktif' => true]);

        $this->expectException(AturanBisnisDilanggar::class);

        app(KirimFormulirPemasaran::class)->jalankan($formulir->fresh(), [
            'Nama' => 'Budi',
            'Setuju' => true,
        ]);
    }

    public function test_captcha_yang_ditolak_penyedia_menggagalkan_pengiriman(): void
    {
        config(['amanpoll.pemasaran.captcha.rahasia' => 'rahasia-uji']);
        Http::fake(['*' => Http::response(['success' => false])]);

        $formulir = $this->buatFormulir();
        $formulir->update(['CaptchaAktif' => true]);

        try {
            app(KirimFormulirPemasaran::class)->jalankan($formulir->fresh(), [
                'Nama' => 'Budi',
                'Setuju' => true,
                'cf-turnstile-response' => 'token-palsu',
            ]);
            $this->fail('Pengiriman dengan CAPTCHA yang ditolak seharusnya gagal.');
        } catch (AturanBisnisDilanggar) {
            $this->assertSame(0, Prospek::query()->count());
        }
    }

    public function test_captcha_yang_diterima_penyedia_meloloskan_pengiriman(): void
    {
        config(['amanpoll.pemasaran.captcha.rahasia' => 'rahasia-uji']);
        Http::fake(['*' => Http::response(['success' => true])]);

        $formulir = $this->buatFormulir();
        $formulir->update(['CaptchaAktif' => true]);

        app(KirimFormulirPemasaran::class)->jalankan($formulir->fresh(), [
            'Nama' => 'Budi',
            'Email' => 'budi@pabrik.test',
            'Setuju' => true,
            'cf-turnstile-response' => 'token-sah',
        ]);

        $this->assertSame(1, Prospek::query()->count());
    }

    public function test_penyedia_captcha_yang_tidak_dapat_dihubungi_menolak_pengiriman(): void
    {
        config(['amanpoll.pemasaran.captcha.rahasia' => 'rahasia-uji']);
        Http::fake(fn () => throw new ConnectionException('jaringan mati'));

        $formulir = $this->buatFormulir();
        $formulir->update(['CaptchaAktif' => true]);

        $this->expectException(AturanBisnisDilanggar::class);

        app(KirimFormulirPemasaran::class)->jalankan($formulir->fresh(), [
            'Nama' => 'Budi',
            'Setuju' => true,
            'cf-turnstile-response' => 'token-sah',
        ]);
    }

    public function test_konfigurasi_captcha_tidak_dikirim_saat_captcha_mati(): void
    {
        $this->pasangFormulirDiHalaman();

        $blok = $this->get($this->urlPublik('/unduh'))->viewData('page')['props']['halaman']['Blok'];

        $this->assertNull($blok[0]['Formulir']['Captcha']);
    }

    public function test_rahasia_captcha_tidak_pernah_dikirim_ke_browser(): void
    {
        config([
            'amanpoll.pemasaran.captcha.rahasia' => 'rahasia-uji',
            'amanpoll.pemasaran.captcha.kunci_situs' => 'kunci-situs-uji',
        ]);

        $this->buatFormulir()->update(['CaptchaAktif' => true]);
        $this->buatTerbit('/unduh', 'Unduh Template', [[
            'Jenis' => JenisBlokHalaman::Formulir->value,
            'Isi' => [],
            'FormulirKode' => 'unduh-template',
        ]]);

        $respons = $this->get($this->urlPublik('/unduh'));
        $captcha = $respons->viewData('page')['props']['halaman']['Blok'][0]['Formulir']['Captcha'];

        $this->assertSame('kunci-situs-uji', $captcha['KunciSitus']);
        $this->assertStringNotContainsString('rahasia-uji', $respons->getContent() ?: '');
    }

    public function test_formulir_ikut_terkirim_pada_halaman_terbit(): void
    {
        $this->pasangFormulirDiHalaman();

        $blok = $this->get($this->urlPublik('/unduh'))->viewData('page')['props']['halaman']['Blok'];

        $this->assertSame('unduh-template', $blok[0]['Formulir']['Kode']);
        $this->assertCount(3, $blok[0]['Formulir']['Field']);
    }

    public function test_field_utm_tersembunyi_tidak_dikirim_ke_browser(): void
    {
        $this->pasangFormulirDiHalaman();

        $blok = $this->get($this->urlPublik('/unduh'))->viewData('page')['props']['halaman']['Blok'];
        $kode = array_column($blok[0]['Formulir']['Field'], 'Kode');

        $this->assertNotContains('utm_source', $kode);
    }

    /** @param array<string, mixed> $data */
    private function kirim(array $data): TestResponse
    {
        return $this->post($this->urlPublik('/formulir/unduh-template'), $data);
    }

    private function pasangFormulirDiHalaman(): void
    {
        $this->buatFormulir();

        $this->buatTerbit('/unduh', 'Unduh Template', [[
            'Jenis' => JenisBlokHalaman::Formulir->value,
            'Isi' => ['judul' => 'Ambil templatnya'],
            'FormulirKode' => 'unduh-template',
        ]]);
    }

    private function buatFormulir(): FormulirPemasaran
    {
        $formulir = FormulirPemasaran::create([
            'Kode' => 'unduh-template',
            'Nama' => 'Unduh Template Preventive',
            'PesanSukses' => 'Templatnya sudah dikirim ke email Anda.',
            'Sumber' => SumberProspek::LeadMagnet->value,
            'Tag' => ['lead-magnet'],
            'WajibPersetujuan' => true,
            'Aktif' => true,
        ]);

        $field = [
            ['Kode' => 'Nama', 'Label' => 'Nama', 'Jenis' => JenisFieldFormulir::Teks, 'Wajib' => true],
            ['Kode' => 'Email', 'Label' => 'Email', 'Jenis' => JenisFieldFormulir::Email, 'Wajib' => true],
            [
                'Kode' => 'Setuju',
                'Label' => 'Saya setuju dihubungi',
                'Jenis' => JenisFieldFormulir::Persetujuan,
                'Wajib' => true,
            ],
            [
                'Kode' => 'utm_source',
                'Label' => 'Sumber',
                'Jenis' => JenisFieldFormulir::UtmTersembunyi,
                'Wajib' => false,
            ],
            [
                'Kode' => 'utm_medium',
                'Label' => 'Medium',
                'Jenis' => JenisFieldFormulir::UtmTersembunyi,
                'Wajib' => false,
            ],
            [
                'Kode' => 'utm_campaign',
                'Label' => 'Kampanye',
                'Jenis' => JenisFieldFormulir::UtmTersembunyi,
                'Wajib' => false,
            ],
        ];

        foreach ($field as $urutan => $satu) {
            FieldFormulirPemasaran::create([
                'FormulirPemasaranId' => $formulir->Id,
                'Urutan' => $urutan,
                ...$satu,
            ]);
        }

        return $formulir->fresh() ?? $formulir;
    }

    private function semaiTahap(): void
    {
        foreach (KatalogTahapPipeline::bawaan() as $tahap) {
            TahapPipeline::query()->firstOrCreate(['Kode' => $tahap['Kode']], [
                'Nama' => $tahap['Nama'],
                'Urutan' => $tahap['Urutan'],
                'TahapAkhir' => $tahap['TahapAkhir'],
                'DianggapMenang' => $tahap['DianggapMenang'],
            ]);
        }
    }
}
