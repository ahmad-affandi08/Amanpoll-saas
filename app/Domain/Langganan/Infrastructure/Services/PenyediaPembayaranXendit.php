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

/**
 * Xendit Invoice API (PRD 8.23): invoice dibuat lalu pengguna dialihkan ke
 * `invoice_url`. Callback diverifikasi dengan header `x-callback-token` yang sama
 * persis dengan verification token di dasbor. URL callback diatur di dasbor Xendit
 * (Settings → Callbacks → Invoices paid), bukan per invoice.
 *
 * Xendit tidak punya host sandbox terpisah: mode uji ditentukan kunci
 * (`xnd_development_…` vs `xnd_production_…`), dan adapter ini menolak campuran keduanya.
 */
final class PenyediaPembayaranXendit extends PenyediaGerbangPembayaran implements DapatDiujiKoneksi
{
    public const KODE = 'Xendit';

    private const URL_API = 'https://api.xendit.co';

    public function kode(): string
    {
        return self::KODE;
    }

    public function nama(): string
    {
        return 'Xendit';
    }

    public function keterangan(): string
    {
        return 'Invoice Xendit: virtual account, e-wallet, QRIS, kartu, dan gerai ritel.';
    }

    /** @return list<IsianKredensial> */
    public function isian(): array
    {
        return [
            new IsianKredensial('SecretKey', 'Secret API Key', rahasia: true, petunjuk: 'Dasbor Xendit → Settings → API Keys (izin tulis Money-in).'),
            new IsianKredensial('TokenCallback', 'Callback Verification Token', rahasia: true, petunjuk: 'Dasbor Xendit → Settings → Callbacks. Arahkan callback Invoice ke URL webhook Amanpoll.'),
        ];
    }

    public function mulaiPembayaran(TagihanLangganan $tagihan, PesananPembayaran $pesanan): InstruksiPembayaran
    {
        $kredensial = $this->kredensial();
        $this->pastikanKunciSesuaiMode($kredensial, 'SecretKey', 'xnd_development_', 'xnd_production_');
        $kunci = $kredensial->ambil('SecretKey');

        $respons = $this->panggil(fn (PendingRequest $http): Response => $http
            ->withBasicAuth($kunci, '')
            ->post(self::URL_API.'/v2/invoices', [
                'external_id' => $pesanan->idPesanan,
                'amount' => $pesanan->jumlah,
                'currency' => 'IDR',
                'description' => $pesanan->deskripsi,
                'invoice_duration' => $pesanan->menitBerlaku * 60,
                'payer_email' => $pesanan->emailPelanggan,
                'customer' => array_filter([
                    'given_names' => $pesanan->namaPelanggan,
                    'email' => $pesanan->emailPelanggan,
                    'mobile_number' => $pesanan->teleponPelanggan,
                ]),
                'items' => [[
                    'name' => $pesanan->deskripsi,
                    'quantity' => 1,
                    'price' => $pesanan->jumlah,
                ]],
                'success_redirect_url' => $pesanan->urlKembali,
                'failure_redirect_url' => $pesanan->urlKembali,
            ]));

        $url = self::teks($respons->json('invoice_url'));
        if ($url === '') {
            throw new AturanBisnisDilanggar('Xendit tidak mengembalikan halaman pembayaran.');
        }

        return InstruksiPembayaran::pengalihan($url, $pesanan->kedaluwarsaPada, self::teks($respons->json('id')) ?: null);
    }

    public function webhookSah(Request $permintaan): bool
    {
        $token = $this->kredensialAtauKosong()?->ambilAtau('TokenCallback') ?? '';
        $dikirim = self::teks($permintaan->header('x-callback-token'));

        return $token !== '' && $dikirim !== '' && hash_equals($token, $dikirim);
    }

    public function terjemahkanWebhook(Request $permintaan): PeristiwaPembayaran
    {
        $muatan = self::muatanJson($permintaan->getContent());
        $idPesanan = self::teks($muatan['external_id'] ?? null);
        $statusInvoice = strtoupper(self::teks($muatan['status'] ?? null));

        $status = match ($statusInvoice) {
            'PAID', 'SETTLED' => StatusPembayaranLangganan::Berhasil,
            'EXPIRED' => StatusPembayaranLangganan::Gagal,
            default => StatusPembayaranLangganan::Menunggu,
        };

        $jumlah = self::angka($muatan['paid_amount'] ?? null) ?: self::angka($muatan['amount'] ?? null);

        return new PeristiwaPembayaran(
            idPeristiwa: "{$idPesanan}:{$status->value}",
            nomorTagihan: '',
            jumlah: $jumlah,
            status: $status,
            referensiEksternal: self::teks($muatan['id'] ?? null) ?: null,
            metode: self::teks($muatan['payment_channel'] ?? null) ?: (self::teks($muatan['payment_method'] ?? null) ?: 'Xendit'),
            muatanMentah: $muatan,
            idPesananPenyedia: $idPesanan,
            kedaluwarsa: $statusInvoice === 'EXPIRED',
        );
    }

    public function ujiKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $kunci = $kredensial->ambil('SecretKey');

        return $this->uji(fn (PendingRequest $http): Response => $http
            ->withBasicAuth($kunci, '')
            ->get(self::URL_API.'/balance'));
    }
}
