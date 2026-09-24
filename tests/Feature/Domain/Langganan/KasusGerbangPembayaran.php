<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Langganan;

use App\Domain\Langganan\Application\Actions\TerbitkanTagihanLangganan;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusSesiPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\SesiPembayaranLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Langganan\Infrastructure\Services\PenyediaPembayaranDoku;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananPlatform;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

/**
 * Dasar bersama test payment gateway (PRD 8.23): kredensial konsol, sesi bayar,
 * dan pengirim webhook bertanda tangan untuk tiap penyedia.
 */
abstract class KasusGerbangPembayaran extends KasusLangganan
{
    /** @var array<string, array<string, string>> */
    protected const KREDENSIAL = [
        'Midtrans' => ['ServerKey' => 'SB-Mid-server-RAHASIAmidtrans', 'ClientKey' => 'SB-Mid-client-publik'],
        'Xendit' => ['SecretKey' => 'xnd_development_RAHASIAxendit', 'TokenCallback' => 'TOKENcallbackRAHASIA'],
        'Duitku' => ['KodeMerchant' => 'DS1234', 'ApiKey' => 'RAHASIAduitkuApiKey'],
        'Tripay' => ['KodeMerchant' => 'T0001', 'ApiKey' => 'DEV-RAHASIAtripayApi', 'PrivateKey' => 'RAHASIAtripayPrivat', 'KodeMetode' => 'BRIVA'],
        'Ipaymu' => ['NomorVa' => '0000001234567890', 'ApiKey' => 'SANDBOX-RAHASIAipaymu'],
        'Doku' => ['ClientId' => 'BRN-0001-000000001', 'SecretKey' => 'SK-RAHASIAdoku'],
        'Stripe' => ['SecretKey' => 'sk_test_RAHASIAstripe', 'RahasiaWebhook' => 'whsec_RAHASIAstripe'],
        'TransferManual' => ['Bank' => 'Bank Konsol', 'NomorRekening' => '123-456-789', 'AtasNama' => 'PT Konsol'],
    ];

    /** @var array<string, mixed> */
    private array $jawabanCekIpaymu = [];

    /** @return array<string, array{string}> */
    public static function penyediaGateway(): array
    {
        return [
            'Midtrans' => ['Midtrans'],
            'Xendit' => ['Xendit'],
            'Duitku' => ['Duitku'],
            'Tripay' => ['Tripay'],
            'iPaymu' => ['Ipaymu'],
            'DOKU' => ['Doku'],
            'Stripe' => ['Stripe'],
        ];
    }

    /** @param  array<string, string>|null  $kredensial */
    protected function aktifkan(string $kode, bool $modeUji = true, bool $utama = false, ?array $kredensial = null): PenyediaLayananPlatform
    {
        return PenyediaLayananPlatform::query()->updateOrCreate(
            ['Kategori' => KategoriPenyediaLayanan::Pembayaran->value, 'Kode' => $kode],
            [
                'Aktif' => true,
                'Utama' => $utama,
                'ModeUji' => $modeUji,
                'KredensialTerenkripsi' => $kredensial ?? self::KREDENSIAL[$kode],
            ],
        );
    }

    protected function terbitkanTagihan(): TagihanLangganan
    {
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, StatusLangganan::Aktif, berakhirPada: '2026-07-15');

        return app(TerbitkanTagihanLangganan::class)->jalankan($langganan);
    }

    protected function bukaSesi(TagihanLangganan $tagihan, string $kode, string $idPesanan): SesiPembayaranLangganan
    {
        return SesiPembayaranLangganan::create([
            'OrganisasiId' => $tagihan->OrganisasiId,
            'TagihanLanggananId' => $tagihan->Id,
            'Penyedia' => $kode,
            'IdPesananPenyedia' => $idPesanan,
            'UrlPembayaran' => 'https://bayar.contoh.test/'.$idPesanan,
            'Jumlah' => (float) $tagihan->Total,
            'KedaluwarsaPada' => CarbonImmutable::now()->addHours(12),
            'Status' => StatusSesiPembayaran::Menunggu->value,
        ]);
    }

    /**
     * Mengirim webhook dengan status baku ('lunas', 'gagal', 'kedaluwarsa', 'menunggu')
     * dalam bentuk asli penyedianya.
     */
    protected function kirimWebhook(string $kode, string $idPesanan, string $status, int $jumlah = 500_000, bool $tandaTanganSah = true): TestResponse
    {
        $uri = route('langganan.webhook.pembayaran', ['penyedia' => $kode]);

        return match ($kode) {
            'Midtrans' => $this->webhookMidtrans($uri, $idPesanan, $status, $jumlah, $tandaTanganSah),
            'Xendit' => $this->webhookXendit($uri, $idPesanan, $status, $jumlah, $tandaTanganSah),
            'Duitku' => $this->webhookDuitku($uri, $idPesanan, $status, $jumlah, $tandaTanganSah),
            'Tripay' => $this->webhookTripay($uri, $idPesanan, $status, $jumlah, $tandaTanganSah),
            'Ipaymu' => $this->webhookIpaymu($uri, $idPesanan, $status, $jumlah, $tandaTanganSah),
            'Doku' => $this->webhookDoku($uri, $idPesanan, $status, $jumlah, $tandaTanganSah),
            'Stripe' => $this->webhookStripe($uri, $idPesanan, $status, $jumlah, $tandaTanganSah),
        };
    }

    /** @param  array<string, string>  $header */
    protected function kirimMentah(string $uri, string $badan, array $header): TestResponse
    {
        return $this->call(
            'POST',
            $uri,
            [],
            [],
            [],
            $this->transformHeadersToServerVars($header + ['Content-Type' => 'application/json', 'Accept' => 'application/json']),
            $badan,
        );
    }

    private function webhookMidtrans(string $uri, string $idPesanan, string $status, int $jumlah, bool $sah): TestResponse
    {
        [$transaksi, $kodeStatus] = match ($status) {
            'lunas' => ['settlement', '200'],
            'gagal' => ['deny', '202'],
            'kedaluwarsa' => ['expire', '202'],
            default => ['pending', '201'],
        };
        $bruto = $jumlah.'.00';
        $kunci = $sah ? self::KREDENSIAL['Midtrans']['ServerKey'] : 'kunci-karangan';

        return $this->kirimMentah($uri, (string) json_encode([
            'transaction_time' => '2026-06-15 16:00:00',
            'transaction_status' => $transaksi,
            'transaction_id' => 'trx-'.$idPesanan,
            'status_code' => $kodeStatus,
            'signature_key' => hash('sha512', $idPesanan.$kodeStatus.$bruto.$kunci),
            'payment_type' => 'bank_transfer',
            'order_id' => $idPesanan,
            'gross_amount' => $bruto,
            'fraud_status' => 'accept',
        ]), []);
    }

    private function webhookXendit(string $uri, string $idPesanan, string $status, int $jumlah, bool $sah): TestResponse
    {
        return $this->kirimMentah($uri, (string) json_encode([
            'id' => 'inv-'.$idPesanan,
            'external_id' => $idPesanan,
            'status' => match ($status) {
                'lunas' => 'PAID',
                'kedaluwarsa', 'gagal' => 'EXPIRED',
                default => 'PENDING',
            },
            'amount' => $jumlah,
            'paid_amount' => $status === 'lunas' ? $jumlah : null,
            'payment_method' => 'BANK_TRANSFER',
            'payment_channel' => 'BCA',
        ]), ['x-callback-token' => $sah ? self::KREDENSIAL['Xendit']['TokenCallback'] : 'token-karangan']);
    }

    private function webhookDuitku(string $uri, string $idPesanan, string $status, int $jumlah, bool $sah): TestResponse
    {
        $kredensial = self::KREDENSIAL['Duitku'];
        $kunci = $sah ? $kredensial['ApiKey'] : 'kunci-karangan';

        return $this->post($uri, [
            'merchantCode' => $kredensial['KodeMerchant'],
            'amount' => (string) $jumlah,
            'merchantOrderId' => $idPesanan,
            'productDetail' => 'Tagihan langganan',
            'paymentCode' => 'VA',
            // Duitku tidak punya callback "menunggu"; kode tak dikenal dipakai untuk mewakilinya.
            'resultCode' => match ($status) {
                'lunas' => '00',
                'menunggu' => '99',
                default => '01',
            },
            'reference' => 'DS-REF-'.$idPesanan,
            'signature' => md5($kredensial['KodeMerchant'].$jumlah.$idPesanan.$kunci),
        ], ['Accept' => 'application/json']);
    }

    private function webhookTripay(string $uri, string $idPesanan, string $status, int $jumlah, bool $sah): TestResponse
    {
        $badan = (string) json_encode([
            'reference' => 'T0001-'.$idPesanan,
            'merchant_ref' => $idPesanan,
            'payment_method' => 'BRI Virtual Account',
            'payment_method_code' => 'BRIVA',
            'total_amount' => $jumlah + 4250,
            'fee_merchant' => 0,
            'fee_customer' => 4250,
            'total_fee' => 4250,
            'amount_received' => $jumlah,
            'is_closed_payment' => 1,
            'status' => match ($status) {
                'lunas' => 'PAID',
                'gagal' => 'FAILED',
                'kedaluwarsa' => 'EXPIRED',
                default => 'UNPAID',
            },
            'paid_at' => $status === 'lunas' ? 1781510400 : null,
        ]);
        $kunci = $sah ? self::KREDENSIAL['Tripay']['PrivateKey'] : 'kunci-karangan';

        return $this->kirimMentah($uri, $badan, [
            'X-Callback-Event' => 'payment_status',
            'X-Callback-Signature' => hash_hmac('sha256', $badan, $kunci),
        ]);
    }

    /**
     * iPaymu tidak menandatangani notifikasi; keasliannya dipastikan lewat cek
     * transaksi ke API. Tanda tangan "tidak sah" di sini berarti API menjawab
     * bahwa trx_id itu milik order lain.
     */
    private function webhookIpaymu(string $uri, string $idPesanan, string $status, int $jumlah, bool $sah): TestResponse
    {
        $kodeStatus = match ($status) {
            'lunas' => 1,
            'gagal' => 5,
            'kedaluwarsa' => -2,
            default => 0,
        };

        $this->jawabanCekIpaymu = [
            'Status' => 200,
            'Success' => true,
            'Data' => [
                'TransactionId' => 9001,
                'ReferenceId' => $sah ? $idPesanan : 'ORDER-LAIN',
                'Status' => $kodeStatus,
                'Amount' => $jumlah,
            ],
        ];
        // Stub pertama yang cocok selalu menang, jadi jawabannya dibaca dari properti saat dipanggil.
        Http::fake([
            'sandbox.ipaymu.com/api/v2/transaction' => fn () => Http::response($this->jawabanCekIpaymu),
        ]);

        return $this->post($uri, [
            'trx_id' => '9001',
            'sid' => 'sesi-ipaymu',
            'reference_id' => $idPesanan,
            'status' => $status === 'lunas' ? 'berhasil' : 'pending',
            'status_code' => (string) $kodeStatus,
            'via' => 'va',
            'channel' => 'bca',
            'total' => (string) $jumlah,
        ], ['Accept' => 'application/json']);
    }

    private function webhookDoku(string $uri, string $idPesanan, string $status, int $jumlah, bool $sah): TestResponse
    {
        $kredensial = self::KREDENSIAL['Doku'];
        $badan = (string) json_encode([
            'order' => ['invoice_number' => $idPesanan, 'amount' => $jumlah],
            'transaction' => [
                'status' => match ($status) {
                    'lunas' => 'SUCCESS',
                    'gagal' => 'FAILED',
                    'kedaluwarsa' => 'EXPIRED',
                    default => 'PENDING',
                },
                'date' => '2026-06-15T09:00:00Z',
                'original_request_id' => 'req-'.$idPesanan,
            ],
            'service' => ['id' => 'VIRTUAL_ACCOUNT'],
            'channel' => ['id' => 'VIRTUAL_ACCOUNT_BCA'],
        ]);
        $idPermintaan = 'notif-'.$idPesanan;
        $cap = '2026-06-15T09:00:00Z';
        $target = (string) parse_url($uri, PHP_URL_PATH);
        $tandaTangan = app(PenyediaPembayaranDoku::class)->tandaTangan(
            $kredensial['ClientId'],
            $idPermintaan,
            $cap,
            $target,
            $badan,
            $sah ? $kredensial['SecretKey'] : 'kunci-karangan',
        );

        return $this->kirimMentah($uri, $badan, [
            'Client-Id' => $kredensial['ClientId'],
            'Request-Id' => $idPermintaan,
            'Request-Timestamp' => $cap,
            'Signature' => $tandaTangan,
        ]);
    }

    private function webhookStripe(string $uri, string $idPesanan, string $status, int $jumlah, bool $sah, ?int $cap = null): TestResponse
    {
        [$jenis, $statusBayar] = match ($status) {
            'lunas' => ['checkout.session.completed', 'paid'],
            'gagal' => ['checkout.session.async_payment_failed', 'unpaid'],
            'kedaluwarsa' => ['checkout.session.expired', 'unpaid'],
            default => ['checkout.session.completed', 'unpaid'],
        };
        $badan = (string) json_encode([
            'id' => 'evt_'.$idPesanan,
            'object' => 'event',
            'type' => $jenis,
            'data' => ['object' => [
                'id' => 'cs_test_'.$idPesanan,
                'object' => 'checkout.session',
                'client_reference_id' => $idPesanan,
                'amount_total' => $jumlah * 100,
                'currency' => 'idr',
                'payment_status' => $statusBayar,
                'metadata' => ['IdPesanan' => $idPesanan],
            ]],
        ]);

        return $this->kirimMentah($uri, $badan, [
            'Stripe-Signature' => $this->tandaTanganStripe($badan, $sah ? self::KREDENSIAL['Stripe']['RahasiaWebhook'] : 'whsec_karangan', $cap),
        ]);
    }

    protected function tandaTanganStripe(string $badan, string $rahasia, ?int $cap = null): string
    {
        $cap ??= now()->getTimestamp();

        return 't='.$cap.',v1='.hash_hmac('sha256', $cap.'.'.$badan, $rahasia);
    }

    /**
     * Callback Duitku hanya membawa berhasil atau gagal; sesi yang habis tidak dikabarkan.
     *
     * @return array<string, array{string}>
     */
    public static function penyediaYangMengabarkanKedaluwarsa(): array
    {
        return array_diff_key(self::penyediaGateway(), ['Duitku' => true]);
    }
}
