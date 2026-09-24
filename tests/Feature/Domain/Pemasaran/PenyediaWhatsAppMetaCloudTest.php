<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PengirimWhatsAppPemasaran;
use App\Domain\Pemasaran\Application\Services\PenjadwalWhatsAppPemasaran;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Services\PenyediaWhatsAppMetaCloud;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/** Adapter WhatsApp Cloud API resmi: bentuk permintaan ke Graph API dan terjemahan galatnya. */
final class PenyediaWhatsAppMetaCloudTest extends KasusWhatsApp
{
    use MengaturPenyediaWhatsApp;

    private const URL_PESAN = 'https://graph.facebook.com/v21.0/1098765/messages';

    private const URL_TEMPLATE = 'https://graph.facebook.com/v21.0/2024001/message_templates*';

    public function test_kirim_template_menyusun_parameter_dari_naskah_dan_teks_terender(): void
    {
        $this->aturMetaCloud();
        Http::preventStrayRequests();
        Http::fake([self::URL_PESAN => Http::response(['messages' => [['id' => 'wamid.ABC']]])]);

        $id = $this->adapter()->kirim(new PesanWhatsApp(
            kepada: '0812-3456-7890',
            kodeTemplate: 'Sapaan-Awal',
            bahasa: 'id',
            isiTeks: 'Halo Budi, PT Maju, Jaya menunggu. Salam Budi',
            naskahTemplate: 'Halo {{Nama}}, {{Perusahaan}} menunggu. Salam {{Nama}}',
        ));

        $this->assertSame('wamid.ABC', $id);
        Http::assertSent(fn (Request $permintaan): bool => $permintaan->url() === self::URL_PESAN
            && $permintaan->method() === 'POST'
            && $permintaan->hasHeader('Authorization', 'Bearer token-meta-rahasia')
            && $permintaan->data() === [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => '6281234567890',
                'type' => 'template',
                'template' => [
                    'name' => 'sapaan_awal',
                    'language' => ['code' => 'id'],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => 'Budi'],
                            ['type' => 'text', 'text' => 'PT Maju, Jaya'],
                            ['type' => 'text', 'text' => 'Budi'],
                        ],
                    ]],
                ],
            ]);
    }

    /** Jalur pengirim sungguhan harus meneruskan naskah template; tanpanya parameter tidak dapat disusun. */
    public function test_pengirim_pemasaran_meneruskan_naskah_template_ke_meta(): void
    {
        $this->aturMetaCloud();
        $this->app->instance(PenyediaWhatsApp::class, $this->adapter());
        Http::preventStrayRequests();
        Http::fake([self::URL_PESAN => Http::response(['messages' => [['id' => 'wamid.XYZ']]])]);

        $pengiriman = app(PenjadwalWhatsAppPemasaran::class)
            ->jadwalkan($this->buatProspek(), $this->buatTemplate(), CarbonImmutable::now(), 'uji:meta');
        $this->assertNotNull($pengiriman);

        $status = app(PengirimWhatsAppPemasaran::class)->kirim($pengiriman);

        $this->assertSame(StatusPengirimanWhatsApp::Dikirim, $status);
        $this->assertSame('wamid.XYZ', $pengiriman->refresh()->IdPesanPenyedia);
        Http::assertSent(fn (Request $permintaan): bool => $permintaan['template']['components'][0]['parameters']
            === [['type' => 'text', 'text' => 'Budi Pabrik']]);
    }

    public function test_teks_yang_tidak_cocok_dengan_naskah_ditolak_sebelum_menghubungi_meta(): void
    {
        $this->aturMetaCloud();
        Http::preventStrayRequests();
        Http::fake();

        try {
            $this->adapter()->kirim(new PesanWhatsApp('081234567890', 'sapaan', 'id', 'Teks lain sama sekali', naskahTemplate: 'Halo {{Nama}}.'));
            $this->fail('Seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringContainsString('tidak lagi cocok', $galat->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_ajukan_template_memakai_nomor_urut_dan_contoh_variabel(): void
    {
        $this->aturPenyedia('MetaCloud', [
            'PhoneNumberId' => '1098765',
            'WabaId' => '2024001',
            'AccessToken' => 'token-meta-rahasia',
            'VersiGraph' => 'v22.0',
        ]);
        Http::preventStrayRequests();
        Http::fake(['https://graph.facebook.com/v22.0/2024001/message_templates' => Http::response([
            'id' => '5550001',
            'status' => 'PENDING',
            'category' => 'MARKETING',
        ])]);

        $keputusan = $this->adapter()->ajukanTemplate('TWA-0001', 'id', 'Marketing', 'Halo {{Nama}}, sisa trial {{HariTrial}} hari.');

        $this->assertSame(StatusPersetujuanTemplateWa::Diajukan, $keputusan->status);
        $this->assertSame('5550001', $keputusan->idTemplatePenyedia);
        Http::assertSent(fn (Request $permintaan): bool => $permintaan->method() === 'POST'
            && $permintaan->data() === [
                'name' => 'twa_0001',
                'language' => 'id',
                'category' => 'MARKETING',
                'components' => [[
                    'type' => 'BODY',
                    'text' => 'Halo {{1}}, sisa trial {{2}} hari.',
                    'example' => ['body_text' => [['Nama', 'HariTrial']]],
                ]],
            ]);
    }

    public function test_periksa_template_menerjemahkan_keputusan_meta(): void
    {
        $this->aturMetaCloud();
        Http::preventStrayRequests();
        Http::fake([self::URL_TEMPLATE => Http::sequence()
            ->push(['data' => [['id' => '1', 'name' => 'sapaan', 'status' => 'APPROVED', 'rejected_reason' => 'NONE']]])
            ->push(['data' => [['id' => '1', 'name' => 'sapaan', 'status' => 'REJECTED', 'rejected_reason' => 'INVALID_FORMAT']]])
            ->push(['data' => [['id' => '9', 'name' => 'sapaan_lama', 'status' => 'APPROVED']]]),
        ]);

        $disetujui = $this->adapter()->periksaTemplate('sapaan');
        $ditolak = $this->adapter()->periksaTemplate('sapaan');
        $hilang = $this->adapter()->periksaTemplate('sapaan');

        $this->assertSame(StatusPersetujuanTemplateWa::Disetujui, $disetujui->status);
        $this->assertNull($disetujui->alasan);
        $this->assertSame(StatusPersetujuanTemplateWa::Ditolak, $ditolak->status);
        $this->assertSame('INVALID_FORMAT', $ditolak->alasan);
        // Nama lain yang kebetulan mirip bukan template kita.
        $this->assertSame(StatusPersetujuanTemplateWa::Ditolak, $hilang->status);
        Http::assertSent(fn (Request $permintaan): bool => $permintaan->method() === 'GET'
            && $permintaan['name'] === 'sapaan');
    }

    public function test_galat_meta_menjadi_pesan_indonesia_tanpa_membocorkan_token(): void
    {
        $this->aturMetaCloud();
        Http::preventStrayRequests();
        Http::fake([self::URL_PESAN => Http::response([
            'error' => ['message' => 'Invalid OAuth access token token-meta-rahasia.', 'code' => 190],
        ], 401)]);

        try {
            $this->adapter()->kirim(new PesanWhatsApp('081234567890', 'sapaan', 'id', 'Halo'));
            $this->fail('Seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringContainsString('menolak pengiriman pesan (HTTP 401)', $galat->getMessage());
            $this->assertStringNotContainsString('token-meta-rahasia', $galat->getMessage());
        }
    }

    public function test_jaringan_putus_menjadi_pesan_indonesia(): void
    {
        $this->aturMetaCloud();
        Http::preventStrayRequests();
        Http::fake(fn () => throw new ConnectionException('cURL error 28 untuk https://graph.facebook.com/?access_token=token-meta-rahasia'));

        try {
            $this->adapter()->kirim(new PesanWhatsApp('081234567890', 'sapaan', 'id', 'Halo'));
            $this->fail('Seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringContainsString('Tidak dapat menghubungi', $galat->getMessage());
            $this->assertStringNotContainsString('token-meta-rahasia', $galat->getMessage());
        }
    }

    public function test_penyedia_yang_belum_diaktifkan_menolak_dengan_pesan_jelas(): void
    {
        $this->aturPenyedia('MetaCloud', ['AccessToken' => 'x'], aktif: false);
        Http::preventStrayRequests();
        Http::fake();

        $this->expectException(AturanBisnisDilanggar::class);
        $this->expectExceptionMessage('belum diatur atau belum diaktifkan di konsol platform');

        $this->adapter()->kirim(new PesanWhatsApp('081234567890', 'sapaan', 'id', 'Halo'));
    }

    public function test_uji_koneksi_membaca_nomor_dan_melaporkan_kegagalan(): void
    {
        $kredensial = $this->aturMetaCloud()->keKredensial();
        Http::preventStrayRequests();
        Http::fake(['https://graph.facebook.com/v21.0/1098765*' => Http::sequence()
            ->push(['display_phone_number' => '+62 811-000', 'verified_name' => 'Amanpoll'])
            ->push(['error' => ['message' => 'Object does not exist']], 400),
        ]);

        $berhasil = $this->adapter()->ujiKoneksi($kredensial);
        $gagal = $this->adapter()->ujiKoneksi($kredensial);

        $this->assertTrue($berhasil->berhasil);
        $this->assertStringContainsString('+62 811-000', $berhasil->pesan);
        $this->assertFalse($gagal->berhasil);
        $this->assertStringContainsString('Object does not exist', $gagal->pesan);
    }

    private function adapter(): PenyediaWhatsAppMetaCloud
    {
        return app(PenyediaWhatsAppMetaCloud::class);
    }
}
