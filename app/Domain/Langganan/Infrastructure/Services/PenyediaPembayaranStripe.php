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
 * Stripe Checkout Session lewat REST form-encoded (PRD 8.23). Webhook diverifikasi
 * dari header `Stripe-Signature: t=…,v1=…` = HMAC-SHA256("{t}.{badan mentah}",
 * webhook secret) dengan toleransi waktu lima menit untuk menolak kiriman ulang lama.
 *
 * Stripe memperlakukan IDR sebagai mata uang dua desimal, jadi nominal dikirim
 * dalam satuan sen (rupiah × 100). Mode uji ditentukan kunci (`sk_test_` vs `sk_live_`).
 */
final class PenyediaPembayaranStripe extends PenyediaGerbangPembayaran implements DapatDiujiKoneksi
{
    public const KODE = 'Stripe';

    private const URL_API = 'https://api.stripe.com/v1';

    private const TOLERANSI_DETIK = 300;

    public function kode(): string
    {
        return self::KODE;
    }

    public function nama(): string
    {
        return 'Stripe';
    }

    public function keterangan(): string
    {
        return 'Stripe Checkout: kartu internasional dan dompet digital global.';
    }

    /** @return list<IsianKredensial> */
    public function isian(): array
    {
        return [
            new IsianKredensial('SecretKey', 'Secret Key', rahasia: true, petunjuk: 'sk_test_… untuk mode uji, sk_live_… untuk produksi.'),
            new IsianKredensial('RahasiaWebhook', 'Webhook Signing Secret', rahasia: true, petunjuk: 'whsec_… dari endpoint webhook di dasbor Stripe (peristiwa checkout.session.*).'),
        ];
    }

    public function mulaiPembayaran(TagihanLangganan $tagihan, PesananPembayaran $pesanan): InstruksiPembayaran
    {
        $kredensial = $this->kredensial();
        $this->pastikanKunciSesuaiMode($kredensial, 'SecretKey', '_test_', '_live_');
        $kunci = $kredensial->ambil('SecretKey');

        $respons = $this->panggil(fn (PendingRequest $http): Response => $http
            ->withToken($kunci)
            // Klik ganda yang lolos tidak membuka dua sesi untuk order id yang sama.
            ->withHeaders(['Idempotency-Key' => $pesanan->idPesanan])
            ->asForm()
            ->post(self::URL_API.'/checkout/sessions', [
                'mode' => 'payment',
                'client_reference_id' => $pesanan->idPesanan,
                'customer_email' => $pesanan->emailPelanggan,
                'success_url' => $pesanan->urlKembali,
                'cancel_url' => $pesanan->urlKembali,
                'expires_at' => $pesanan->kedaluwarsaPada->getTimestamp(),
                'metadata' => [
                    'IdPesanan' => $pesanan->idPesanan,
                    'NomorTagihan' => $pesanan->nomorTagihan,
                ],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'idr',
                        'unit_amount' => $pesanan->jumlah * 100,
                        'product_data' => ['name' => Str::limit($pesanan->deskripsi, 250, '')],
                    ],
                ]],
            ]));

        $url = self::teks($respons->json('url'));
        if ($url === '') {
            throw new AturanBisnisDilanggar('Stripe tidak mengembalikan halaman pembayaran.');
        }

        return InstruksiPembayaran::pengalihan($url, $pesanan->kedaluwarsaPada, self::teks($respons->json('id')) ?: null);
    }

    public function webhookSah(Request $permintaan): bool
    {
        $rahasia = $this->kredensialAtauKosong()?->ambilAtau('RahasiaWebhook') ?? '';
        $kepala = self::teks($permintaan->header('Stripe-Signature'));

        if ($rahasia === '' || $kepala === '') {
            return false;
        }

        $cap = null;
        $tandaTangan = [];
        foreach (explode(',', $kepala) as $bagian) {
            [$kunci, $nilai] = array_pad(explode('=', trim($bagian), 2), 2, '');
            if ($kunci === 't' && ctype_digit($nilai)) {
                $cap = (int) $nilai;
            } elseif ($kunci === 'v1' && $nilai !== '') {
                $tandaTangan[] = $nilai;
            }
        }

        if ($cap === null || $tandaTangan === [] || abs(now()->getTimestamp() - $cap) > self::TOLERANSI_DETIK) {
            return false;
        }

        $diharapkan = hash_hmac('sha256', $cap.'.'.$permintaan->getContent(), $rahasia);

        foreach ($tandaTangan as $satu) {
            if (hash_equals($diharapkan, $satu)) {
                return true;
            }
        }

        return false;
    }

    public function terjemahkanWebhook(Request $permintaan): PeristiwaPembayaran
    {
        $muatan = self::muatanJson($permintaan->getContent());
        $jenis = self::teks($muatan['type'] ?? null);
        $objek = self::ambil($muatan, 'data.object');
        $objek = is_array($objek) ? $objek : [];

        $idPesanan = self::teks($objek['client_reference_id'] ?? null) ?: self::teks(data_get($objek, 'metadata.IdPesanan'));
        $statusBayar = self::teks($objek['payment_status'] ?? null);

        $status = match ($jenis) {
            'checkout.session.completed' => in_array($statusBayar, ['paid', 'no_payment_required'], true)
                ? StatusPembayaranLangganan::Berhasil
                // Metode tunda (mis. transfer) menyelesaikan sesi sebelum dananya masuk.
                : StatusPembayaranLangganan::Menunggu,
            'checkout.session.async_payment_succeeded' => StatusPembayaranLangganan::Berhasil,
            'checkout.session.async_payment_failed', 'checkout.session.expired' => StatusPembayaranLangganan::Gagal,
            default => StatusPembayaranLangganan::Menunggu,
        };

        return new PeristiwaPembayaran(
            idPeristiwa: "{$idPesanan}:{$status->value}",
            nomorTagihan: '',
            jumlah: self::angka($objek['amount_total'] ?? null) / 100,
            status: $status,
            referensiEksternal: self::teks($objek['id'] ?? null) ?: null,
            metode: 'Stripe Checkout',
            muatanMentah: $muatan,
            idPesananPenyedia: $idPesanan,
            kedaluwarsa: $jenis === 'checkout.session.expired',
        );
    }

    public function ujiKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $kunci = $kredensial->ambil('SecretKey');

        return $this->uji(fn (PendingRequest $http): Response => $http
            ->withToken($kunci)
            ->get(self::URL_API.'/balance'));
    }
}
