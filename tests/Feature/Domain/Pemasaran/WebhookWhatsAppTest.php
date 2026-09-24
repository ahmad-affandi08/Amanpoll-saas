<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Domain\Enums\KanalPesan;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanWhatsAppPemasaran;
use App\Domain\Pemasaran\Infrastructure\Services\PenyediaWhatsAppFonnte;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

/** Webhook WhatsApp: hanya kiriman asli penyedia yang boleh mengubah status atau mencabut consent. */
final class WebhookWhatsAppTest extends KasusWhatsApp
{
    use MengaturPenyediaWhatsApp;

    private const URL_PESAN_META = 'https://graph.facebook.com/v21.0/1098765/messages';

    public function test_verifikasi_meta_mengembalikan_tantangan_hanya_untuk_token_yang_benar(): void
    {
        $this->aturMetaCloud();
        $url = route('pemasaran.webhook.whatsapp.verifikasi', ['penyedia' => 'MetaCloud']);

        $this->get($url.'?hub.mode=subscribe&hub.verify_token=verifikasi-rahasia&hub.challenge=12345')
            ->assertOk()
            ->assertContent('12345');

        $this->get($url.'?hub.mode=subscribe&hub.verify_token=tebakan&hub.challenge=12345')
            ->assertForbidden();
    }

    public function test_webhook_meta_bertanda_tangan_salah_ditolak_dan_tidak_mengubah_apa_pun(): void
    {
        $this->aturMetaCloud();
        $pengiriman = $this->buatPengiriman('wamid.1');

        $this->kirimMeta($this->laporanMeta('wamid.1', 'delivered'), 'rahasia-tebakan')->assertForbidden();

        $this->assertSame(StatusPengirimanWhatsApp::Dikirim, $pengiriman->refresh()->Status);
    }

    public function test_webhook_meta_bertanda_tangan_benar_memajukan_status_kiriman(): void
    {
        $this->aturMetaCloud();
        $pengiriman = $this->buatPengiriman('wamid.1');

        $this->kirimMeta($this->laporanMeta('wamid.1', 'read'))
            ->assertOk()
            ->assertJson(['Diterima' => true, 'Status' => 1]);

        $this->assertSame(StatusPengirimanWhatsApp::Dibaca, $pengiriman->refresh()->Status);
    }

    public function test_balasan_stop_lewat_meta_mencabut_consent_dan_dikonfirmasi(): void
    {
        $this->aturMetaCloud();
        $this->buatProspek();
        Http::preventStrayRequests();
        Http::fake([self::URL_PESAN_META => Http::response(['messages' => [['id' => 'wamid.balas']]])]);

        $this->kirimMeta($this->pesanMasukMeta('6281234567890', 'STOP'))
            ->assertOk()
            ->assertJson(['PesanMasuk' => 1]);

        $konsen = app(LayananKonsen::class);
        $this->assertTrue($konsen->disupresi('081234567890', KanalPesan::WhatsApp));
        $this->assertFalse($konsen->disetujui('081234567890', KanalPesan::WhatsApp));
        Http::assertSent(fn (Request $permintaan): bool => $permintaan['type'] === 'text'
            && $permintaan['to'] === '6281234567890');
    }

    /** Balasan yang gagal terkirim tidak boleh membuat Meta mengulang, atau membatalkan STOP yang sudah tercatat. */
    public function test_balasan_yang_gagal_tidak_menggagalkan_webhook(): void
    {
        $this->aturMetaCloud();
        Http::preventStrayRequests();
        Http::fake([self::URL_PESAN_META => Http::response(['error' => ['message' => 'Re-engagement window closed']], 400)]);

        $this->kirimMeta($this->pesanMasukMeta('6281234567890', 'STOP'))->assertOk();

        $this->assertTrue(app(LayananKonsen::class)->disupresi('6281234567890', KanalPesan::WhatsApp));
    }

    /** Kode 131050: penerima menghentikan pesan pemasaran dari aplikasi WhatsApp; itu sama dengan STOP. */
    public function test_penolakan_pemasaran_dari_meta_disupresi(): void
    {
        $this->aturMetaCloud();
        $pengiriman = $this->buatPengiriman('wamid.2');

        $this->kirimMeta($this->laporanMeta('wamid.2', 'failed', [['code' => 131050, 'title' => 'User opted out']]))
            ->assertOk();

        $this->assertSame(StatusPengirimanWhatsApp::Unsubscribe, $pengiriman->refresh()->Status);
        $this->assertTrue(app(LayananKonsen::class)->disupresi('081234567890', KanalPesan::WhatsApp));
    }

    public function test_penyedia_tak_dikenal_ditolak(): void
    {
        $this->postJson(route('pemasaran.webhook.whatsapp', ['penyedia' => 'Log']), [])->assertForbidden();
    }

    public function test_webhook_tidak_resmi_hanya_diterima_dengan_token_yang_diatur(): void
    {
        $this->aturPenyedia('Fonnte', ['Token' => 'token-fonnte', 'TokenWebhook' => 'token-webhook']);
        Http::preventStrayRequests();
        Http::fake(['https://api.fonnte.com/send' => Http::response(['status' => true, 'id' => ['1']])]);
        $url = route('pemasaran.webhook.whatsapp', ['penyedia' => 'Fonnte']);
        $muatan = ['device' => '62811', 'sender' => '6281234567890', 'message' => 'stop'];

        $this->postJson($url, $muatan)->assertForbidden();
        $this->postJson($url.'?token=salah', $muatan)->assertForbidden();
        $this->assertFalse(app(LayananKonsen::class)->disupresi('6281234567890', KanalPesan::WhatsApp));

        $this->postJson($url.'?token=token-webhook', $muatan)->assertOk();
        $this->assertTrue(app(LayananKonsen::class)->disupresi('6281234567890', KanalPesan::WhatsApp));
        Http::assertSent(fn (Request $permintaan): bool => $permintaan['target'] === '6281234567890');
    }

    /** Tanpa token yang diatur, webhook tidak resmi tertutup sama sekali, bukan terbuka untuk siapa pun. */
    public function test_webhook_tidak_resmi_tanpa_token_terpasang_selalu_ditolak(): void
    {
        $this->aturPenyedia('Fonnte', ['Token' => 'token-fonnte']);

        $this->postJson(route('pemasaran.webhook.whatsapp', ['penyedia' => 'Fonnte']).'?token=', [
            'sender' => '6281234567890',
            'message' => 'stop',
        ])->assertForbidden();

        // Middleware mengubah token kosong menjadi null; adapternya sendiri pun harus menolak token kosong.
        $this->assertFalse(app(PenyediaWhatsAppFonnte::class)->webhookSah('{}', [], ['token' => '']));
        $this->assertFalse(app(LayananKonsen::class)->disupresi('6281234567890', KanalPesan::WhatsApp));
    }

    public function test_laporan_ack_waha_memajukan_status_kiriman(): void
    {
        $this->aturPenyedia('Waha', ['UrlDasar' => 'https://waha.test', 'TokenWebhook' => 'token-webhook']);
        $pengiriman = $this->buatPengiriman('true_6281234567890@c.us_3EB0');

        $this->postJson(route('pemasaran.webhook.whatsapp', ['penyedia' => 'Waha']).'?token=token-webhook', [
            'event' => 'message.ack',
            'session' => 'default',
            'payload' => ['id' => 'true_6281234567890@c.us_3EB0', 'ack' => 2, 'ackName' => 'DEVICE'],
        ])->assertOk()->assertJson(['Status' => 1]);

        $this->assertSame(StatusPengirimanWhatsApp::Terkirim, $pengiriman->refresh()->Status);
    }

    private function buatPengiriman(string $idPesan): PengirimanWhatsAppPemasaran
    {
        $prospek = $this->buatProspek();

        return PengirimanWhatsAppPemasaran::create([
            'ProspekId' => $prospek->Id,
            'Nomor' => '6281234567890',
            'TemplateWhatsAppPemasaranId' => $this->buatTemplate()->Id,
            'KunciIdempotensi' => 'uji:'.$idPesan,
            'Status' => StatusPengirimanWhatsApp::Dikirim,
            'IsiTeks' => 'Halo Budi Pabrik, terima kasih sudah mampir.',
            'IdPesanPenyedia' => $idPesan,
            'JadwalPada' => CarbonImmutable::now(),
            'DikirimPada' => CarbonImmutable::now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $muatan
     * @return TestResponse<Response>
     */
    private function kirimMeta(array $muatan, string $rahasia = 'app-secret-rahasia'): TestResponse
    {
        $badan = (string) json_encode($muatan);

        return $this->call(
            'POST',
            route('pemasaran.webhook.whatsapp', ['penyedia' => 'MetaCloud']),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $badan, $rahasia),
            ],
            content: $badan,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $galat
     * @return array<string, mixed>
     */
    private function laporanMeta(string $idPesan, string $status, array $galat = []): array
    {
        $laporan = ['id' => $idPesan, 'status' => $status, 'recipient_id' => '6281234567890'];

        if ($galat !== []) {
            $laporan['errors'] = $galat;
        }

        return $this->amplopMeta(['statuses' => [$laporan]]);
    }

    /** @return array<string, mixed> */
    private function pesanMasukMeta(string $dari, string $teks): array
    {
        return $this->amplopMeta(['messages' => [[
            'from' => $dari,
            'id' => 'wamid.masuk',
            'type' => 'text',
            'text' => ['body' => $teks],
        ]]]);
    }

    /**
     * @param  array<string, mixed>  $nilai
     * @return array<string, mixed>
     */
    private function amplopMeta(array $nilai): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '2024001',
                'changes' => [['field' => 'messages', 'value' => ['messaging_product' => 'whatsapp', ...$nilai]]],
            ]],
        ];
    }
}
