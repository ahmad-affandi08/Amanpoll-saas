<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Services;

use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\ValueObjects\InstruksiPembayaran;
use App\Domain\Langganan\Domain\ValueObjects\PeristiwaPembayaran;
use App\Domain\Langganan\Domain\ValueObjects\PesananPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DOKU Checkout (PRD 8.23). Permintaan dan notifikasi memakai skema tanda tangan
 * yang sama: `Signature: HMACSHA256=base64(HMAC-SHA256(komponen, SecretKey))` dengan
 * komponen Client-Id, Request-Id, Request-Timestamp, Request-Target, dan Digest
 * (base64 SHA-256 badan mentah), dipisah baris baru.
 *
 * DOKU tidak menyediakan panggilan baca yang murah untuk menguji kredensial,
 * jadi adapter ini tidak mengimplementasikan uji koneksi.
 */
final class PenyediaPembayaranDoku extends PenyediaGerbangPembayaran
{
    public const KODE = 'Doku';

    private const TARGET_CHECKOUT = '/checkout/v1/payment';

    public function kode(): string
    {
        return self::KODE;
    }

    public function nama(): string
    {
        return 'DOKU';
    }

    public function keterangan(): string
    {
        return 'DOKU Checkout: virtual account, kartu, e-wallet, QRIS, dan gerai ritel.';
    }

    /** @return list<IsianKredensial> */
    public function isian(): array
    {
        return [
            new IsianKredensial('ClientId', 'Client ID', petunjuk: 'DOKU Back Office → Integration → API Keys.'),
            new IsianKredensial('SecretKey', 'Secret Key', rahasia: true),
        ];
    }

    public function urlApi(bool $modeUji): string
    {
        return $modeUji ? 'https://api-sandbox.doku.com' : 'https://api.doku.com';
    }

    public function tandaTangan(
        string $idKlien,
        string $idPermintaan,
        string $cap,
        string $target,
        string $badan,
        string $kunciRahasia,
    ): string {
        $komponen = implode("\n", [
            'Client-Id:'.$idKlien,
            'Request-Id:'.$idPermintaan,
            'Request-Timestamp:'.$cap,
            'Request-Target:'.$target,
            'Digest:'.base64_encode(hash('sha256', $badan, true)),
        ]);

        return 'HMACSHA256='.base64_encode(hash_hmac('sha256', $komponen, $kunciRahasia, true));
    }

    public function mulaiPembayaran(TagihanLangganan $tagihan, PesananPembayaran $pesanan): InstruksiPembayaran
    {
        $kredensial = $this->kredensial();
        $idKlien = $kredensial->ambil('ClientId');
        $kunciRahasia = $kredensial->ambil('SecretKey');
        $idPermintaan = (string) Str::uuid();
        $cap = now()->utc()->format('Y-m-d\TH:i:s\Z');

        $badan = (string) json_encode([
            'order' => [
                'amount' => $pesanan->jumlah,
                'invoice_number' => $pesanan->idPesanan,
                'currency' => 'IDR',
                'callback_url' => $pesanan->urlKembali,
                'line_items' => [[
                    'name' => Str::limit($pesanan->deskripsi, 255, ''),
                    'price' => $pesanan->jumlah,
                    'quantity' => 1,
                ]],
            ],
            'payment' => ['payment_due_date' => $pesanan->menitBerlaku],
            'customer' => [
                'name' => $pesanan->namaPelanggan,
                'email' => $pesanan->emailPelanggan,
            ],
            'additional_info' => ['override_notification_url' => $pesanan->urlNotifikasi],
        ], JSON_UNESCAPED_SLASHES);

        $respons = $this->panggil(fn (PendingRequest $http): Response => $http
            ->withHeaders([
                'Client-Id' => $idKlien,
                'Request-Id' => $idPermintaan,
                'Request-Timestamp' => $cap,
                'Signature' => $this->tandaTangan($idKlien, $idPermintaan, $cap, self::TARGET_CHECKOUT, $badan, $kunciRahasia),
            ])
            ->withBody($badan, 'application/json')
            ->post($this->urlApi($kredensial->modeUji).self::TARGET_CHECKOUT));

        $url = self::teks($respons->json('response.payment.url'));
        if ($url === '') {
            throw new AturanBisnisDilanggar('DOKU tidak mengembalikan halaman pembayaran.');
        }

        return InstruksiPembayaran::pengalihan($url, $pesanan->kedaluwarsaPada, self::teks($respons->json('response.payment.token_id')) ?: null);
    }

    public function webhookSah(Request $permintaan): bool
    {
        $kredensial = $this->kredensialAtauKosong();
        $idKlien = $kredensial?->ambilAtau('ClientId') ?? '';
        $kunciRahasia = $kredensial?->ambilAtau('SecretKey') ?? '';

        $dikirimKlien = self::teks($permintaan->header('Client-Id'));
        $idPermintaan = self::teks($permintaan->header('Request-Id'));
        $cap = self::teks($permintaan->header('Request-Timestamp'));
        $tandaTangan = self::teks($permintaan->header('Signature'));

        if ($idKlien === '' || $kunciRahasia === '' || $dikirimKlien !== $idKlien || $idPermintaan === '' || $cap === '' || $tandaTangan === '') {
            return false;
        }

        // Request-Target notifikasi adalah jalur endpoint penerima, mis. /webhook/pembayaran/Doku.
        $target = '/'.ltrim($permintaan->path(), '/');

        return hash_equals(
            $this->tandaTangan($idKlien, $idPermintaan, $cap, $target, $permintaan->getContent(), $kunciRahasia),
            $tandaTangan,
        );
    }

    public function terjemahkanWebhook(Request $permintaan): PeristiwaPembayaran
    {
        $muatan = self::muatanJson($permintaan->getContent());
        $idPesanan = self::teks(self::ambil($muatan, 'order.invoice_number'));
        $statusDoku = strtoupper(self::teks(self::ambil($muatan, 'transaction.status')));

        $status = match ($statusDoku) {
            'SUCCESS' => StatusPembayaranLangganan::Berhasil,
            'FAILED', 'EXPIRED' => StatusPembayaranLangganan::Gagal,
            default => StatusPembayaranLangganan::Menunggu,
        };

        $metode = self::teks(self::ambil($muatan, 'channel.id')) ?: self::teks(self::ambil($muatan, 'service.id'));

        return new PeristiwaPembayaran(
            idPeristiwa: "{$idPesanan}:{$status->value}",
            nomorTagihan: '',
            jumlah: self::angka(self::ambil($muatan, 'order.amount')),
            status: $status,
            referensiEksternal: self::teks(self::ambil($muatan, 'transaction.original_request_id')) ?: null,
            metode: $metode ?: 'DOKU',
            muatanMentah: $muatan,
            idPesananPenyedia: $idPesanan,
            kedaluwarsa: $statusDoku === 'EXPIRED',
        );
    }
}
