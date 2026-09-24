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
 * Duitku POP (PRD 8.23): `createInvoice` mengembalikan `paymentUrl`. Header
 * `x-duitku-signature` = SHA256(merchantCode + timestamp + apiKey). Callback dikirim
 * form-urlencoded dan diverifikasi dengan MD5(merchantCode + amount + merchantOrderId + apiKey).
 */
final class PenyediaPembayaranDuitku extends PenyediaGerbangPembayaran implements DapatDiujiKoneksi
{
    public const KODE = 'Duitku';

    public function kode(): string
    {
        return self::KODE;
    }

    public function nama(): string
    {
        return 'Duitku';
    }

    public function keterangan(): string
    {
        return 'Halaman bayar Duitku POP: virtual account, e-wallet, QRIS, dan gerai ritel.';
    }

    /** @return list<IsianKredensial> */
    public function isian(): array
    {
        return [
            new IsianKredensial('KodeMerchant', 'Merchant Code', petunjuk: 'Dasbor Duitku → Proyek → Merchant Code (mis. DXXXX).'),
            new IsianKredensial('ApiKey', 'API Key', rahasia: true, petunjuk: 'Dasbor Duitku → Proyek → API Key.'),
        ];
    }

    public function urlApi(bool $modeUji): string
    {
        return $modeUji ? 'https://api-sandbox.duitku.com' : 'https://api-prod.duitku.com';
    }

    public function urlPassport(bool $modeUji): string
    {
        return $modeUji ? 'https://sandbox.duitku.com' : 'https://passport.duitku.com';
    }

    public function mulaiPembayaran(TagihanLangganan $tagihan, PesananPembayaran $pesanan): InstruksiPembayaran
    {
        $kredensial = $this->kredensial();
        $kodeMerchant = $kredensial->ambil('KodeMerchant');
        $kunciApi = $kredensial->ambil('ApiKey');
        $cap = (string) now()->getTimestampMs();

        $respons = $this->panggil(fn (PendingRequest $http): Response => $http
            ->withHeaders([
                'x-duitku-signature' => hash('sha256', $kodeMerchant.$cap.$kunciApi),
                'x-duitku-timestamp' => $cap,
                'x-duitku-merchantcode' => $kodeMerchant,
            ])
            ->post($this->urlApi($kredensial->modeUji).'/api/merchant/createInvoice', [
                'paymentAmount' => $pesanan->jumlah,
                'merchantOrderId' => $pesanan->idPesanan,
                'productDetails' => Str::limit($pesanan->deskripsi, 255, ''),
                'email' => $pesanan->emailPelanggan,
                'phoneNumber' => $pesanan->teleponPelanggan ?? '',
                'customerVaName' => Str::limit($pesanan->namaPelanggan, 20, ''),
                'callbackUrl' => $pesanan->urlNotifikasi,
                'returnUrl' => $pesanan->urlKembali,
                'expiryPeriod' => $pesanan->menitBerlaku,
                'itemDetails' => [[
                    'name' => Str::limit($pesanan->deskripsi, 50, ''),
                    'price' => $pesanan->jumlah,
                    'quantity' => 1,
                ]],
                'customerDetail' => [
                    'firstName' => $pesanan->namaPelanggan,
                    'email' => $pesanan->emailPelanggan,
                ],
            ]));

        $url = self::teks($respons->json('paymentUrl'));
        if ($url === '') {
            throw new AturanBisnisDilanggar('Duitku tidak mengembalikan halaman pembayaran.');
        }

        return InstruksiPembayaran::pengalihan($url, $pesanan->kedaluwarsaPada, self::teks($respons->json('reference')) ?: null);
    }

    public function webhookSah(Request $permintaan): bool
    {
        $kredensial = $this->kredensialAtauKosong();
        $kodeMerchant = $kredensial?->ambilAtau('KodeMerchant') ?? '';
        $kunciApi = $kredensial?->ambilAtau('ApiKey') ?? '';
        if ($kodeMerchant === '' || $kunciApi === '') {
            return false;
        }

        $dikirim = self::teks($permintaan->input('merchantCode'));
        $jumlah = self::teks($permintaan->input('amount'));
        $idPesanan = self::teks($permintaan->input('merchantOrderId'));
        $tandaTangan = self::teks($permintaan->input('signature'));

        if ($dikirim !== $kodeMerchant || $jumlah === '' || $idPesanan === '' || $tandaTangan === '') {
            return false;
        }

        return hash_equals(md5($kodeMerchant.$jumlah.$idPesanan.$kunciApi), strtolower($tandaTangan));
    }

    public function terjemahkanWebhook(Request $permintaan): PeristiwaPembayaran
    {
        $muatan = self::muatanForm($permintaan);
        $idPesanan = self::teks($muatan['merchantOrderId'] ?? null);

        // Callback Duitku hanya membawa "00" (berhasil) atau "01" (gagal); sesi yang habis tidak dikabarkan.
        $status = match (self::teks($muatan['resultCode'] ?? null)) {
            '00' => StatusPembayaranLangganan::Berhasil,
            '01', '02' => StatusPembayaranLangganan::Gagal,
            default => StatusPembayaranLangganan::Menunggu,
        };

        return new PeristiwaPembayaran(
            idPeristiwa: "{$idPesanan}:{$status->value}",
            nomorTagihan: '',
            jumlah: self::angka($muatan['amount'] ?? null),
            status: $status,
            referensiEksternal: self::teks($muatan['reference'] ?? null) ?: null,
            metode: self::teks($muatan['paymentCode'] ?? null) ?: 'Duitku',
            muatanMentah: $muatan,
            idPesananPenyedia: $idPesanan,
        );
    }

    public function ujiKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $kodeMerchant = $kredensial->ambil('KodeMerchant');
        $kunciApi = $kredensial->ambil('ApiKey');
        $waktu = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        $jumlah = 10000;

        return $this->uji(
            fn (PendingRequest $http): Response => $http->post(
                $this->urlPassport($kredensial->modeUji).'/webapi/api/merchant/paymentmethod/getpaymentmethod',
                [
                    'merchantcode' => $kodeMerchant,
                    'amount' => $jumlah,
                    'datetime' => $waktu,
                    'signature' => hash('sha256', $kodeMerchant.$jumlah.$waktu.$kunciApi),
                ],
            ),
            fn (Response $respons): bool => $respons->successful() && self::teks($respons->json('responseCode')) === '00',
        );
    }
}
