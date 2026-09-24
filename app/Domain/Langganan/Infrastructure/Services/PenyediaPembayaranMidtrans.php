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
 * Midtrans Snap (PRD 8.23): transaksi dibuat lewat Snap API lalu pengguna dialihkan
 * ke `redirect_url`. Notifikasi HTTP diverifikasi dengan `signature_key` =
 * SHA512(order_id + status_code + gross_amount + ServerKey).
 */
final class PenyediaPembayaranMidtrans extends PenyediaGerbangPembayaran implements DapatDiujiKoneksi
{
    public const KODE = 'Midtrans';

    public function kode(): string
    {
        return self::KODE;
    }

    public function nama(): string
    {
        return 'Midtrans';
    }

    public function keterangan(): string
    {
        return 'Halaman bayar Snap Midtrans: virtual account, kartu, e-wallet, dan QRIS.';
    }

    /** @return list<IsianKredensial> */
    public function isian(): array
    {
        return [
            new IsianKredensial('ServerKey', 'Server Key', rahasia: true, petunjuk: 'Dasbor Midtrans → Settings → Access Keys.'),
            new IsianKredensial('ClientKey', 'Client Key', wajib: false, petunjuk: 'Tidak dipakai halaman Snap redirect; disimpan untuk pemakaian mendatang.'),
        ];
    }

    public function urlSnap(bool $modeUji): string
    {
        return $modeUji
            ? 'https://app.sandbox.midtrans.com/snap/v1/transactions'
            : 'https://app.midtrans.com/snap/v1/transactions';
    }

    public function urlApi(bool $modeUji): string
    {
        return $modeUji ? 'https://api.sandbox.midtrans.com' : 'https://api.midtrans.com';
    }

    public function mulaiPembayaran(TagihanLangganan $tagihan, PesananPembayaran $pesanan): InstruksiPembayaran
    {
        $kredensial = $this->kredensial();
        $kunciServer = $kredensial->ambil('ServerKey');

        $respons = $this->panggil(fn (PendingRequest $http): Response => $http
            ->withBasicAuth($kunciServer, '')
            // Notifikasi diarahkan ke endpoint webhook ini tanpa bergantung pada setelan dasbor.
            ->withHeaders(['X-Override-Notification' => $pesanan->urlNotifikasi])
            ->post($this->urlSnap($kredensial->modeUji), [
                'transaction_details' => [
                    'order_id' => $pesanan->idPesanan,
                    'gross_amount' => $pesanan->jumlah,
                ],
                'item_details' => [[
                    'id' => Str::limit($pesanan->nomorTagihan, 50, ''),
                    'price' => $pesanan->jumlah,
                    'quantity' => 1,
                    'name' => Str::limit($pesanan->deskripsi, 50, ''),
                ]],
                'customer_details' => array_filter([
                    'first_name' => $pesanan->namaPelanggan,
                    'email' => $pesanan->emailPelanggan,
                    'phone' => $pesanan->teleponPelanggan,
                ]),
                'callbacks' => ['finish' => $pesanan->urlKembali],
                'expiry' => [
                    'start_time' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s O'),
                    'unit' => 'minute',
                    'duration' => $pesanan->menitBerlaku,
                ],
            ]));

        $url = self::teks($respons->json('redirect_url'));
        if ($url === '') {
            throw new AturanBisnisDilanggar('Midtrans tidak mengembalikan halaman pembayaran.');
        }

        return InstruksiPembayaran::pengalihan($url, $pesanan->kedaluwarsaPada, self::teks($respons->json('token')) ?: null);
    }

    public function webhookSah(Request $permintaan): bool
    {
        $kunciServer = $this->kredensialAtauKosong()?->ambilAtau('ServerKey') ?? '';
        if ($kunciServer === '') {
            return false;
        }

        $muatan = self::muatanJson($permintaan->getContent());
        $tandaTangan = self::teks($muatan['signature_key'] ?? null);
        $bagian = [
            self::teks($muatan['order_id'] ?? null),
            self::teks($muatan['status_code'] ?? null),
            self::teks($muatan['gross_amount'] ?? null),
        ];

        if ($tandaTangan === '' || in_array('', $bagian, true)) {
            return false;
        }

        return hash_equals(hash('sha512', implode('', $bagian).$kunciServer), $tandaTangan);
    }

    public function terjemahkanWebhook(Request $permintaan): PeristiwaPembayaran
    {
        $muatan = self::muatanJson($permintaan->getContent());
        $idPesanan = self::teks($muatan['order_id'] ?? null);
        $statusTransaksi = self::teks($muatan['transaction_status'] ?? null);
        $statusPenipuan = self::teks($muatan['fraud_status'] ?? null);

        $status = match ($statusTransaksi) {
            'capture' => match ($statusPenipuan) {
                'challenge' => StatusPembayaranLangganan::Menunggu,
                'deny' => StatusPembayaranLangganan::Gagal,
                default => StatusPembayaranLangganan::Berhasil,
            },
            'settlement' => StatusPembayaranLangganan::Berhasil,
            'deny', 'cancel', 'failure', 'expire' => StatusPembayaranLangganan::Gagal,
            'refund', 'partial_refund', 'chargeback', 'partial_chargeback' => StatusPembayaranLangganan::Dikembalikan,
            default => StatusPembayaranLangganan::Menunggu,
        };

        return new PeristiwaPembayaran(
            // capture lalu settlement sama-sama "berhasil"; kuncinya status baku supaya tidak tercatat dua kali.
            idPeristiwa: "{$idPesanan}:{$status->value}",
            nomorTagihan: '',
            jumlah: self::angka($muatan['gross_amount'] ?? null),
            status: $status,
            referensiEksternal: self::teks($muatan['transaction_id'] ?? null) ?: null,
            metode: self::teks($muatan['payment_type'] ?? null) ?: 'Midtrans',
            muatanMentah: $muatan,
            idPesananPenyedia: $idPesanan,
            kedaluwarsa: $statusTransaksi === 'expire',
        );
    }

    /** Order id acak yang pasti tidak ada: 404 berarti kunci diterima, 401 berarti ditolak. */
    public function ujiKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $kunciServer = $kredensial->ambil('ServerKey');

        return $this->uji(
            fn (PendingRequest $http): Response => $http
                ->withBasicAuth($kunciServer, '')
                ->get($this->urlApi($kredensial->modeUji).'/v2/uji-koneksi-'.Str::lower(Str::random(12)).'/status'),
            fn (Response $respons): bool => $respons->status() < 500
                && ! in_array(self::teks($respons->json('status_code')), ['401', '403'], true),
        );
    }
}
