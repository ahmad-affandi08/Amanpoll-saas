<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Langganan;

use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\StatusSesiPembayaran;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Domain\ValueObjects\PesananPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PembayaranLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Langganan\Infrastructure\Services\PenyediaPembayaranDoku;
use App\Domain\Langganan\Infrastructure\Services\PenyediaPembayaranDuitku;
use App\Domain\Langganan\Infrastructure\Services\PenyediaPembayaranIpaymu;
use App\Domain\Langganan\Infrastructure\Services\PenyediaPembayaranMidtrans;
use App\Domain\Langganan\Infrastructure\Services\PenyediaPembayaranStripe;
use App\Domain\Langganan\Infrastructure\Services\PenyediaPembayaranTripay;
use App\Domain\Langganan\Infrastructure\Services\PenyediaPembayaranXendit;
use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request as PermintaanHttp;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

/** Adapter payment gateway: bentuk permintaan buat-transaksi dan verifikasi webhook (PRD 8.23). */
final class GerbangPembayaranLanggananTest extends KasusGerbangPembayaran
{
    private function pesanan(): PesananPembayaran
    {
        return new PesananPembayaran(
            idPesanan: 'INV202607001-ABCDEFGHIJ',
            nomorTagihan: 'INV/2026/07/001',
            jumlah: 555_000,
            deskripsi: 'Tagihan langganan INV/2026/07/001',
            namaPelanggan: 'Budi Santoso',
            emailPelanggan: 'budi@contoh.test',
            teleponPelanggan: '081234567890',
            urlKembali: 'https://dasbor.contoh.test/langganan/tagihan/1/kembali',
            urlNotifikasi: 'https://dasbor.contoh.test/webhook/pembayaran/X',
            kedaluwarsaPada: CarbonImmutable::now()->addMinutes(720),
            menitBerlaku: 720,
        );
    }

    /**
     * Membuat transaksi di mode uji lalu produksi dan mengembalikan kedua permintaan yang terkirim.
     *
     * @param  array<string, mixed>  $jawaban
     * @return array{0: PermintaanHttp, 1: PermintaanHttp}
     */
    private function buatDiDuaMode(string $kode, string $kelas, array $jawaban): array
    {
        Http::fake(['*' => Http::response($jawaban, 200)]);

        $this->aktifkan($kode, modeUji: true);
        $uji = app($kelas)->mulaiPembayaran(new TagihanLangganan, $this->pesanan());
        $this->aktifkan($kode, modeUji: false, kredensial: $this->kredensialProduksi($kode));
        app($kelas)->mulaiPembayaran(new TagihanLangganan, $this->pesanan());

        $this->assertTrue($uji->mengalihkan());
        $this->assertSame('https://bayar.contoh.test/sesi', $uji->urlPembayaran);

        $terkirim = Http::recorded()->map(fn (array $pasangan): PermintaanHttp => $pasangan[0])->values()->all();
        $this->assertCount(2, $terkirim);

        return [$terkirim[0], $terkirim[1]];
    }

    /** @return array<string, string> */
    private function kredensialProduksi(string $kode): array
    {
        return match ($kode) {
            'Xendit' => ['SecretKey' => 'xnd_production_RAHASIAxendit'] + self::KREDENSIAL['Xendit'],
            'Stripe' => ['SecretKey' => 'sk_live_RAHASIAstripe'] + self::KREDENSIAL['Stripe'],
            default => self::KREDENSIAL[$kode],
        };
    }

    public function test_midtrans_membuat_transaksi_snap_dengan_basic_auth_server_key(): void
    {
        [$uji, $produksi] = $this->buatDiDuaMode('Midtrans', PenyediaPembayaranMidtrans::class, [
            'token' => 'snap-token', 'redirect_url' => 'https://bayar.contoh.test/sesi',
        ]);

        $this->assertSame('https://app.sandbox.midtrans.com/snap/v1/transactions', $uji->url());
        $this->assertSame('https://app.midtrans.com/snap/v1/transactions', $produksi->url());
        $this->assertSame('Basic '.base64_encode(self::KREDENSIAL['Midtrans']['ServerKey'].':'), $uji->header('Authorization')[0]);
        $this->assertSame('https://dasbor.contoh.test/webhook/pembayaran/X', $uji->header('X-Override-Notification')[0]);
        $this->assertSame('INV202607001-ABCDEFGHIJ', $uji['transaction_details']['order_id']);
        $this->assertSame(555_000, $uji['transaction_details']['gross_amount']);
        $this->assertSame(720, $uji['expiry']['duration']);
    }

    public function test_xendit_membuat_invoice_dan_menolak_kunci_produksi_di_mode_uji(): void
    {
        [$uji] = $this->buatDiDuaMode('Xendit', PenyediaPembayaranXendit::class, [
            'id' => 'inv-1', 'invoice_url' => 'https://bayar.contoh.test/sesi',
        ]);

        $this->assertSame('https://api.xendit.co/v2/invoices', $uji->url());
        $this->assertSame('Basic '.base64_encode(self::KREDENSIAL['Xendit']['SecretKey'].':'), $uji->header('Authorization')[0]);
        $this->assertSame('INV202607001-ABCDEFGHIJ', $uji['external_id']);
        $this->assertSame(555_000, $uji['amount']);
        $this->assertSame(720 * 60, $uji['invoice_duration']);

        $this->aktifkan('Xendit', modeUji: true, kredensial: $this->kredensialProduksi('Xendit'));
        $this->expectException(AturanBisnisDilanggar::class);
        app(PenyediaPembayaranXendit::class)->mulaiPembayaran(new TagihanLangganan, $this->pesanan());
    }

    public function test_duitku_membuat_invoice_pop_dengan_header_tanda_tangan(): void
    {
        [$uji, $produksi] = $this->buatDiDuaMode('Duitku', PenyediaPembayaranDuitku::class, [
            'merchantCode' => 'DS1234', 'reference' => 'DS-REF', 'paymentUrl' => 'https://bayar.contoh.test/sesi', 'statusCode' => '00',
        ]);

        $this->assertSame('https://api-sandbox.duitku.com/api/merchant/createInvoice', $uji->url());
        $this->assertSame('https://api-prod.duitku.com/api/merchant/createInvoice', $produksi->url());
        $cap = $uji->header('x-duitku-timestamp')[0];
        $this->assertSame(
            hash('sha256', 'DS1234'.$cap.self::KREDENSIAL['Duitku']['ApiKey']),
            $uji->header('x-duitku-signature')[0],
        );
        $this->assertSame('DS1234', $uji->header('x-duitku-merchantcode')[0]);
        $this->assertSame(555_000, $uji['paymentAmount']);
        $this->assertSame('INV202607001-ABCDEFGHIJ', $uji['merchantOrderId']);
        $this->assertSame('https://dasbor.contoh.test/webhook/pembayaran/X', $uji['callbackUrl']);
    }

    public function test_tripay_membuat_transaksi_closed_payment_dengan_metode_dan_tanda_tangan(): void
    {
        [$uji, $produksi] = $this->buatDiDuaMode('Tripay', PenyediaPembayaranTripay::class, [
            'success' => true, 'data' => ['reference' => 'T0001-REF', 'checkout_url' => 'https://bayar.contoh.test/sesi'],
        ]);

        $this->assertSame('https://tripay.co.id/api-sandbox/transaction/create', $uji->url());
        $this->assertSame('https://tripay.co.id/api/transaction/create', $produksi->url());
        $this->assertSame('Bearer '.self::KREDENSIAL['Tripay']['ApiKey'], $uji->header('Authorization')[0]);
        $this->assertSame('BRIVA', $uji['method']);
        $this->assertSame(555_000, $uji['amount']);
        $this->assertSame(
            hash_hmac('sha256', 'T0001INV202607001-ABCDEFGHIJ555000', self::KREDENSIAL['Tripay']['PrivateKey']),
            $uji['signature'],
        );
    }

    public function test_ipaymu_membuat_redirect_payment_dengan_tanda_tangan_badan(): void
    {
        [$uji, $produksi] = $this->buatDiDuaMode('Ipaymu', PenyediaPembayaranIpaymu::class, [
            'Status' => 200, 'Success' => true, 'Data' => ['SessionID' => 'sid-1', 'Url' => 'https://bayar.contoh.test/sesi'],
        ]);

        $this->assertSame('https://sandbox.ipaymu.com/api/v2/payment', $uji->url());
        $this->assertSame('https://my.ipaymu.com/api/v2/payment', $produksi->url());
        $kredensial = self::KREDENSIAL['Ipaymu'];
        $this->assertSame($kredensial['NomorVa'], $uji->header('va')[0]);
        $this->assertSame(
            hash_hmac('sha256', 'POST:'.$kredensial['NomorVa'].':'.hash('sha256', $uji->body()).':'.$kredensial['ApiKey'], $kredensial['ApiKey']),
            $uji->header('signature')[0],
        );
        $this->assertSame([555_000], $uji['price']);
        $this->assertSame('INV202607001-ABCDEFGHIJ', $uji['referenceId']);
    }

    public function test_doku_membuat_checkout_dengan_tanda_tangan_hmac(): void
    {
        [$uji, $produksi] = $this->buatDiDuaMode('Doku', PenyediaPembayaranDoku::class, [
            'response' => ['payment' => ['url' => 'https://bayar.contoh.test/sesi', 'token_id' => 'tok']],
        ]);

        $this->assertSame('https://api-sandbox.doku.com/checkout/v1/payment', $uji->url());
        $this->assertSame('https://api.doku.com/checkout/v1/payment', $produksi->url());
        $kredensial = self::KREDENSIAL['Doku'];
        $this->assertSame(
            app(PenyediaPembayaranDoku::class)->tandaTangan(
                $kredensial['ClientId'],
                $uji->header('Request-Id')[0],
                $uji->header('Request-Timestamp')[0],
                '/checkout/v1/payment',
                $uji->body(),
                $kredensial['SecretKey'],
            ),
            $uji->header('Signature')[0],
        );
        $this->assertSame(555_000, $uji['order']['amount']);
        $this->assertSame('INV202607001-ABCDEFGHIJ', $uji['order']['invoice_number']);
    }

    public function test_stripe_membuat_checkout_session_form_encoded_dalam_sen(): void
    {
        [$uji] = $this->buatDiDuaMode('Stripe', PenyediaPembayaranStripe::class, [
            'id' => 'cs_test_1', 'url' => 'https://bayar.contoh.test/sesi',
        ]);

        $this->assertSame('https://api.stripe.com/v1/checkout/sessions', $uji->url());
        $this->assertSame('Bearer '.self::KREDENSIAL['Stripe']['SecretKey'], $uji->header('Authorization')[0]);
        $this->assertTrue($uji->isForm());
        $this->assertSame('INV202607001-ABCDEFGHIJ', $uji['client_reference_id']);
        $this->assertSame('idr', $uji['line_items'][0]['price_data']['currency']);
        $this->assertSame('55500000', (string) $uji['line_items'][0]['price_data']['unit_amount']);
    }

    public function test_gateway_yang_belum_diatur_di_konsol_ditolak_dengan_pesan_jelas(): void
    {
        Http::fake();

        try {
            app(PenyediaPembayaranMidtrans::class)->mulaiPembayaran(new TagihanLangganan, $this->pesanan());
            $this->fail('Seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringContainsString('belum diatur di konsol platform', $galat->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_penolakan_gateway_tidak_membocorkan_kredensial_ke_pesan_maupun_log(): void
    {
        $kunci = self::KREDENSIAL['Midtrans']['ServerKey'];
        $this->aktifkan('Midtrans');
        // Jawaban galat yang menggemakan kunci tidak boleh diteruskan ke mana pun.
        Http::fake(['*' => Http::response(['error_messages' => ['Access denied for '.$kunci]], 401)]);
        $log = [];
        app('log')->listen(function ($peristiwa) use (&$log): void {
            $log[] = $peristiwa->message.json_encode($peristiwa->context);
        });

        try {
            app(PenyediaPembayaranMidtrans::class)->mulaiPembayaran(new TagihanLangganan, $this->pesanan());
            $this->fail('Seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringNotContainsString($kunci, $galat->getMessage());
            $this->assertStringContainsString('HTTP 401', $galat->getMessage());
        }

        $this->assertNotEmpty($log);
        $this->assertStringNotContainsString($kunci, implode("\n", $log));
    }

    #[DataProvider('penyediaGateway')]
    public function test_webhook_bertanda_tangan_sah_melunasi_tagihan(string $kode): void
    {
        $this->aktifkan($kode);
        $tagihan = $this->terbitkanTagihan();
        $sesi = $this->bukaSesi($tagihan, $kode, 'ORD-'.$kode.'-001');

        $this->kirimWebhook($kode, $sesi->IdPesananPenyedia, 'lunas')
            ->assertOk()
            ->assertJson(['success' => true, 'Status' => StatusPembayaranLangganan::Berhasil->value]);

        $this->assertSame(StatusTagihanLangganan::Lunas->value, (string) $tagihan->refresh()->Status);
        $this->assertSame(StatusSesiPembayaran::Dibayar->value, (string) $sesi->refresh()->Status);
        $pembayaran = PembayaranLangganan::query()->where('TagihanLanggananId', $tagihan->Id)->sole();
        $this->assertSame($kode, $pembayaran->PenyediaPembayaran);
        $this->assertSame(500_000.0, (float) $pembayaran->Jumlah);
    }

    #[DataProvider('penyediaGateway')]
    public function test_webhook_dengan_tanda_tangan_salah_ditolak(string $kode): void
    {
        $this->aktifkan($kode);
        $tagihan = $this->terbitkanTagihan();
        $sesi = $this->bukaSesi($tagihan, $kode, 'ORD-'.$kode.'-002');

        $this->kirimWebhook($kode, $sesi->IdPesananPenyedia, 'lunas', tandaTanganSah: false)->assertForbidden();

        $this->assertSame(0, PembayaranLangganan::query()->where('TagihanLanggananId', $tagihan->Id)->count());
        $this->assertSame(StatusTagihanLangganan::BelumDibayar->value, (string) $tagihan->refresh()->Status);
    }

    #[DataProvider('penyediaGateway')]
    public function test_webhook_ditolak_bila_penyedia_tidak_aktif_di_konsol(string $kode): void
    {
        $tagihan = $this->terbitkanTagihan();
        $sesi = $this->bukaSesi($tagihan, $kode, 'ORD-'.$kode.'-003');

        // Tanda tangannya dibuat dengan kredensial yang benar, tetapi penyedianya belum dinyalakan.
        $this->kirimWebhook($kode, $sesi->IdPesananPenyedia, 'lunas')->assertForbidden();

        $this->assertSame(0, PembayaranLangganan::query()->where('TagihanLanggananId', $tagihan->Id)->count());
    }

    #[DataProvider('penyediaGateway')]
    public function test_webhook_gagal_tidak_mengurangi_tagihan_dan_kabar_menunggu_tidak_dicatat(string $kode): void
    {
        $this->aktifkan($kode);
        $tagihan = $this->terbitkanTagihan();
        $sesi = $this->bukaSesi($tagihan, $kode, 'ORD-'.$kode.'-004');

        $this->kirimWebhook($kode, $sesi->IdPesananPenyedia, 'menunggu')->assertOk();
        $this->assertSame(0, PembayaranLangganan::query()->where('TagihanLanggananId', $tagihan->Id)->count());
        $this->assertSame(StatusSesiPembayaran::Menunggu->value, (string) $sesi->refresh()->Status);

        $this->kirimWebhook($kode, $sesi->IdPesananPenyedia, 'gagal')->assertOk();

        $pembayaran = PembayaranLangganan::query()->where('TagihanLanggananId', $tagihan->Id)->sole();
        $this->assertSame(StatusPembayaranLangganan::Gagal->value, $pembayaran->Status);
        $this->assertSame(StatusTagihanLangganan::BelumDibayar->value, (string) $tagihan->refresh()->Status);
        $this->assertNotSame(StatusSesiPembayaran::Menunggu->value, (string) $sesi->refresh()->Status);
    }

    #[DataProvider('penyediaYangMengabarkanKedaluwarsa')]
    public function test_webhook_kedaluwarsa_menandai_sesi_kedaluwarsa(string $kode): void
    {
        $this->aktifkan($kode);
        $tagihan = $this->terbitkanTagihan();
        $sesi = $this->bukaSesi($tagihan, $kode, 'ORD-'.$kode.'-005');

        $this->kirimWebhook($kode, $sesi->IdPesananPenyedia, 'kedaluwarsa')->assertOk();

        $this->assertSame(StatusSesiPembayaran::Kedaluwarsa->value, (string) $sesi->refresh()->Status);
        $this->assertSame(StatusTagihanLangganan::BelumDibayar->value, (string) $tagihan->refresh()->Status);
    }

    #[DataProvider('penyediaGateway')]
    public function test_webhook_ganda_tidak_mencatat_pembayaran_dua_kali(string $kode): void
    {
        $this->aktifkan($kode);
        $tagihan = $this->terbitkanTagihan();
        $sesi = $this->bukaSesi($tagihan, $kode, 'ORD-'.$kode.'-006');
        $akhirSebelum = $tagihan->langganan->BerakhirPada->toDateString();

        $this->kirimWebhook($kode, $sesi->IdPesananPenyedia, 'lunas')->assertOk();
        $this->kirimWebhook($kode, $sesi->IdPesananPenyedia, 'lunas')->assertOk();
        $this->kirimWebhook($kode, $sesi->IdPesananPenyedia, 'lunas')->assertOk();

        $this->assertSame(1, PembayaranLangganan::query()->where('TagihanLanggananId', $tagihan->Id)->count());
        $this->assertSame(
            CarbonImmutable::parse($akhirSebelum)->addMonth()->toDateString(),
            $tagihan->refresh()->langganan->BerakhirPada->toDateString(),
            'Langganan hanya boleh diperpanjang sekali.',
        );
    }

    public function test_midtrans_capture_lalu_settlement_tercatat_sekali(): void
    {
        $this->aktifkan('Midtrans');
        $tagihan = $this->terbitkanTagihan();
        $sesi = $this->bukaSesi($tagihan, 'Midtrans', 'ORD-MT-CAPTURE');
        $kunci = self::KREDENSIAL['Midtrans']['ServerKey'];
        $uri = route('langganan.webhook.pembayaran', ['penyedia' => 'Midtrans']);

        foreach (['capture', 'settlement'] as $status) {
            $this->kirimMentah($uri, (string) json_encode([
                'order_id' => $sesi->IdPesananPenyedia,
                'status_code' => '200',
                'gross_amount' => '500000.00',
                'transaction_status' => $status,
                'fraud_status' => 'accept',
                'signature_key' => hash('sha512', $sesi->IdPesananPenyedia.'200500000.00'.$kunci),
            ]), [])->assertOk();
        }

        $this->assertSame(1, PembayaranLangganan::query()->where('TagihanLanggananId', $tagihan->Id)->count());
    }

    public function test_webhook_untuk_order_id_yang_tidak_dikenal_tidak_melunasi_apa_pun(): void
    {
        $this->aktifkan('Xendit');
        $tagihan = $this->terbitkanTagihan();

        $this->kirimWebhook('Xendit', 'ORDER-KARANGAN', 'lunas')->assertNotFound();

        $this->assertSame(0, PembayaranLangganan::query()->where('TagihanLanggananId', $tagihan->Id)->count());
    }

    public function test_stripe_menolak_tanda_tangan_yang_kedaluwarsa(): void
    {
        $this->aktifkan('Stripe');
        $tagihan = $this->terbitkanTagihan();
        $sesi = $this->bukaSesi($tagihan, 'Stripe', 'ORD-STRIPE-LAMA');
        $badan = (string) json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['client_reference_id' => $sesi->IdPesananPenyedia, 'payment_status' => 'paid', 'amount_total' => 50_000_000]],
        ]);
        $capLama = now()->getTimestamp() - 3600;

        $this->kirimMentah(
            route('langganan.webhook.pembayaran', ['penyedia' => 'Stripe']),
            $badan,
            ['Stripe-Signature' => $this->tandaTanganStripe($badan, self::KREDENSIAL['Stripe']['RahasiaWebhook'], $capLama)],
        )->assertForbidden();

        $this->assertSame(0, PembayaranLangganan::query()->where('TagihanLanggananId', $tagihan->Id)->count());
    }

    public function test_semua_adapter_pembayaran_muncul_di_katalog_konsol(): void
    {
        $kode = array_map(
            fn ($penyedia): string => $penyedia->kode(),
            app(KatalogPenyediaLayanan::class)->menurutKategori(KategoriPenyediaLayanan::Pembayaran),
        );

        sort($kode);
        $this->assertSame(['Doku', 'Duitku', 'Ipaymu', 'Midtrans', 'Stripe', 'TransferManual', 'Tripay', 'Xendit'], $kode);
    }
}
