<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Services;

use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\ValueObjects\InstruksiPembayaran;
use App\Domain\Langganan\Domain\ValueObjects\PeristiwaPembayaran;
use App\Domain\Langganan\Domain\ValueObjects\PesananPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Platform\Domain\Contracts\DapatDiujiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Tripay closed payment (PRD 8.23): transaksi dibuat dengan satu metode bayar (isian
 * KodeMetode) lalu pengguna dialihkan ke `checkout_url`. Tanda tangan permintaan =
 * HMAC-SHA256(merchantCode + merchantRef + amount, PrivateKey); callback diverifikasi
 * dengan header `X-Callback-Signature` = HMAC-SHA256(badan mentah, PrivateKey).
 */
final class PenyediaPembayaranTripay extends PenyediaGerbangPembayaran implements DapatDiujiKoneksi
{
    public const KODE = 'Tripay';

    /** @var list<string> */
    public const METODE = [
        'QRIS', 'QRISC', 'QRIS2', 'BRIVA', 'BNIVA', 'MANDIRIVA', 'BCAVA', 'PERMATAVA', 'CIMBVA', 'BSIVA',
        'MUAMALATVA', 'DANAMONVA', 'OCBCVA', 'ALFAMART', 'INDOMARET', 'ALFAMIDI', 'OVO', 'DANA', 'SHOPEEPAY',
    ];

    public function kode(): string
    {
        return self::KODE;
    }

    public function nama(): string
    {
        return 'Tripay';
    }

    public function keterangan(): string
    {
        return 'Tripay closed payment: satu metode bayar pilihan (QRIS, virtual account, e-wallet, gerai ritel).';
    }

    /** @return list<IsianKredensial> */
    public function isian(): array
    {
        return [
            new IsianKredensial('KodeMerchant', 'Kode Merchant', petunjuk: 'Dasbor Tripay → Merchant → Detail (mis. T0001).'),
            new IsianKredensial('ApiKey', 'API Key', rahasia: true),
            new IsianKredensial('PrivateKey', 'Private Key', rahasia: true),
            new IsianKredensial('KodeMetode', 'Metode pembayaran', petunjuk: 'Kanal yang ditawarkan kepada tenant.', pilihan: self::METODE, bawaan: 'QRIS'),
        ];
    }

    public function urlApi(bool $modeUji): string
    {
        return $modeUji ? 'https://tripay.co.id/api-sandbox' : 'https://tripay.co.id/api';
    }

    public function mulaiPembayaran(TagihanLangganan $tagihan, PesananPembayaran $pesanan): InstruksiPembayaran
    {
        $kredensial = $this->kredensial();
        $kodeMerchant = $kredensial->ambil('KodeMerchant');
        $kunciApi = $kredensial->ambil('ApiKey');
        $kunciPrivat = $kredensial->ambil('PrivateKey');

        $respons = $this->panggil(fn (PendingRequest $http): Response => $http
            ->withToken($kunciApi)
            ->post($this->urlApi($kredensial->modeUji).'/transaction/create', [
                'method' => $kredensial->ambilAtau('KodeMetode', 'QRIS'),
                'merchant_ref' => $pesanan->idPesanan,
                'amount' => $pesanan->jumlah,
                'customer_name' => $pesanan->namaPelanggan,
                'customer_email' => $pesanan->emailPelanggan,
                'customer_phone' => $pesanan->teleponPelanggan ?? '',
                'order_items' => [[
                    'sku' => Str::limit($pesanan->nomorTagihan, 50, ''),
                    'name' => $pesanan->deskripsi,
                    'price' => $pesanan->jumlah,
                    'quantity' => 1,
                ]],
                'callback_url' => $pesanan->urlNotifikasi,
                'return_url' => $pesanan->urlKembali,
                'expired_time' => $pesanan->kedaluwarsaPada->getTimestamp(),
                'signature' => hash_hmac('sha256', $kodeMerchant.$pesanan->idPesanan.$pesanan->jumlah, $kunciPrivat),
            ]));

        $url = self::teks($respons->json('data.checkout_url'));
        if ($respons->json('success') !== true || $url === '') {
            throw new AturanBisnisDilanggar('Tripay tidak mengembalikan halaman pembayaran.');
        }

        return InstruksiPembayaran::pengalihan($url, $pesanan->kedaluwarsaPada, self::teks($respons->json('data.reference')) ?: null);
    }

    public function webhookSah(Request $permintaan): bool
    {
        $kunciPrivat = $this->kredensialAtauKosong()?->ambilAtau('PrivateKey') ?? '';
        $tandaTangan = self::teks($permintaan->header('X-Callback-Signature'));

        if ($kunciPrivat === '' || $tandaTangan === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $permintaan->getContent(), $kunciPrivat), $tandaTangan);
    }

    public function terjemahkanWebhook(Request $permintaan): PeristiwaPembayaran
    {
        $muatan = self::muatanJson($permintaan->getContent());
        $idPesanan = self::teks($muatan['merchant_ref'] ?? null);
        $statusTripay = strtoupper(self::teks($muatan['status'] ?? null));
        $bukanStatusBayar = self::teks($permintaan->header('X-Callback-Event')) !== 'payment_status';

        $status = $bukanStatusBayar ? StatusPembayaranLangganan::Menunggu : match ($statusTripay) {
            'PAID' => StatusPembayaranLangganan::Berhasil,
            'EXPIRED', 'FAILED' => StatusPembayaranLangganan::Gagal,
            'REFUND' => StatusPembayaranLangganan::Dikembalikan,
            default => StatusPembayaranLangganan::Menunggu,
        };

        // total_amount memuat biaya yang dibebankan ke pelanggan; nilai tagihannya dikurangi biaya itu.
        $jumlah = self::angka($muatan['total_amount'] ?? null) - self::angka($muatan['fee_customer'] ?? null);

        return new PeristiwaPembayaran(
            idPeristiwa: "{$idPesanan}:{$status->value}",
            nomorTagihan: '',
            jumlah: $jumlah,
            status: $status,
            referensiEksternal: self::teks($muatan['reference'] ?? null) ?: null,
            metode: self::teks($muatan['payment_method'] ?? null) ?: 'Tripay',
            muatanMentah: $muatan,
            idPesananPenyedia: $idPesanan,
            kedaluwarsa: $statusTripay === 'EXPIRED',
        );
    }

    public function ujiKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $kunciApi = $kredensial->ambil('ApiKey');

        return $this->uji(
            fn (PendingRequest $http): Response => $http
                ->withToken($kunciApi)
                ->get($this->urlApi($kredensial->modeUji).'/merchant/payment-channel'),
            fn (Response $respons): bool => $respons->successful() && $respons->json('success') === true,
        );
    }
}
