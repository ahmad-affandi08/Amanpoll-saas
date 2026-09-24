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
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;

/**
 * iPaymu redirect payment v2 (PRD 8.23). Setiap permintaan ditandatangani
 * HMAC-SHA256("POST:{VA}:{sha256(badan JSON)}:{ApiKey}", ApiKey).
 *
 * Notifikasi iPaymu (form-urlencoded) tidak membawa tanda tangan, jadi keasliannya
 * dipastikan dengan menanyakan balik transaksinya ke API iPaymu memakai kredensial
 * sendiri; status yang dicatat adalah status dari jawaban itu, bukan dari kiriman.
 */
final class PenyediaPembayaranIpaymu extends PenyediaGerbangPembayaran implements DapatDiujiKoneksi
{
    public const KODE = 'Ipaymu';

    /** @var array<string, array<string, mixed>> Hasil cek transaksi yang sudah diverifikasi, per trx_id. */
    private array $transaksiTerverifikasi = [];

    public function kode(): string
    {
        return self::KODE;
    }

    public function nama(): string
    {
        return 'iPaymu';
    }

    public function keterangan(): string
    {
        return 'Halaman bayar iPaymu: virtual account, QRIS, e-wallet, gerai ritel, dan kartu.';
    }

    /** @return list<IsianKredensial> */
    public function isian(): array
    {
        return [
            new IsianKredensial('NomorVa', 'Nomor VA iPaymu', petunjuk: 'Dasbor iPaymu → Integrasi → VA.'),
            new IsianKredensial('ApiKey', 'API Key', rahasia: true),
        ];
    }

    public function urlApi(bool $modeUji): string
    {
        return $modeUji ? 'https://sandbox.ipaymu.com' : 'https://my.ipaymu.com';
    }

    /** Tanda tangan permintaan iPaymu v2 untuk badan JSON yang dikirim apa adanya. */
    public function tandaTangan(string $badan, string $nomorVa, string $kunciApi): string
    {
        $teks = 'POST:'.$nomorVa.':'.strtolower(hash('sha256', $badan)).':'.$kunciApi;

        return hash_hmac('sha256', $teks, $kunciApi);
    }

    public function mulaiPembayaran(TagihanLangganan $tagihan, PesananPembayaran $pesanan): InstruksiPembayaran
    {
        $kredensial = $this->kredensial();

        $respons = $this->panggil(fn (PendingRequest $http): Response => $this->kirimBertanda($http, $kredensial, '/api/v2/payment', [
            'product' => [$pesanan->deskripsi],
            'qty' => [1],
            'price' => [$pesanan->jumlah],
            'description' => [$pesanan->nomorTagihan],
            'returnUrl' => $pesanan->urlKembali,
            'cancelUrl' => $pesanan->urlKembali,
            'notifyUrl' => $pesanan->urlNotifikasi,
            'referenceId' => $pesanan->idPesanan,
            'buyerName' => $pesanan->namaPelanggan,
            'buyerEmail' => $pesanan->emailPelanggan,
            'buyerPhone' => $pesanan->teleponPelanggan ?? '',
            'expired' => max(1, intdiv($pesanan->menitBerlaku, 60)),
            'expiredType' => 'hours',
        ]));

        $url = self::teks($respons->json('Data.Url'));
        if ($url === '') {
            throw new AturanBisnisDilanggar('iPaymu tidak mengembalikan halaman pembayaran.');
        }

        return InstruksiPembayaran::pengalihan($url, $pesanan->kedaluwarsaPada, self::teks($respons->json('Data.SessionID')) ?: null);
    }

    public function webhookSah(Request $permintaan): bool
    {
        $kredensial = $this->kredensialAtauKosong();
        $idTransaksi = self::teks($permintaan->input('trx_id'));
        $idPesanan = self::teks($permintaan->input('reference_id'));

        if ($kredensial === null || $idTransaksi === '' || $idPesanan === '') {
            return false;
        }

        $data = $this->cekTransaksi($kredensial, $idTransaksi);

        // Kiriman palsu yang menyebut trx_id orang lain tertolak di sini: order id-nya tidak cocok.
        return $data !== null && self::teks($data['ReferenceId'] ?? null) === $idPesanan;
    }

    public function terjemahkanWebhook(Request $permintaan): PeristiwaPembayaran
    {
        $muatan = self::muatanForm($permintaan);
        $idTransaksi = self::teks($muatan['trx_id'] ?? null);
        $idPesanan = self::teks($muatan['reference_id'] ?? null);
        $data = $this->transaksiTerverifikasi[$idTransaksi]
            ?? throw new AturanBisnisDilanggar('Transaksi iPaymu belum diverifikasi.');

        $kodeStatus = self::teks($data['Status'] ?? null);
        $status = match ($kodeStatus) {
            '1', '6' => StatusPembayaranLangganan::Berhasil,
            '0' => StatusPembayaranLangganan::Menunggu,
            '3' => StatusPembayaranLangganan::Dikembalikan,
            default => StatusPembayaranLangganan::Gagal,
        };

        return new PeristiwaPembayaran(
            idPeristiwa: "{$idPesanan}:{$status->value}",
            nomorTagihan: '',
            jumlah: self::angka($data['Amount'] ?? null) ?: self::angka($muatan['amount'] ?? $muatan['total'] ?? null),
            status: $status,
            referensiEksternal: $idTransaksi,
            metode: self::teks($muatan['via'] ?? null) ?: 'iPaymu',
            muatanMentah: $muatan,
            idPesananPenyedia: $idPesanan,
            kedaluwarsa: $kodeStatus === '-2',
        );
    }

    public function ujiKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        return $this->uji(
            fn (PendingRequest $http): Response => $this->kirimBertanda($http, $kredensial, '/api/v2/balance', [
                'account' => $kredensial->ambil('NomorVa'),
            ]),
            fn (Response $respons): bool => $respons->successful() && self::teks($respons->json('Status')) === '200',
        );
    }

    /** @return array<string, mixed>|null */
    private function cekTransaksi(KredensialPenyedia $kredensial, string $idTransaksi): ?array
    {
        try {
            $respons = $this->kirimBertanda($this->http(), $kredensial, '/api/v2/transaction', [
                'transactionId' => $idTransaksi,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        $data = $respons->json('Data');
        if (! $respons->successful() || ! is_array($data)) {
            return null;
        }

        $hasil = [];
        foreach ($data as $kunci => $nilai) {
            $hasil[(string) $kunci] = $nilai;
        }

        return $this->transaksiTerverifikasi[$idTransaksi] = $hasil;
    }

    /** @param  array<string, mixed>  $badan */
    private function kirimBertanda(PendingRequest $http, KredensialPenyedia $kredensial, string $jalur, array $badan): Response
    {
        $nomorVa = $kredensial->ambil('NomorVa');
        $kunciApi = $kredensial->ambil('ApiKey');
        $json = (string) json_encode($badan, JSON_UNESCAPED_SLASHES);

        return $http
            ->withHeaders([
                'va' => $nomorVa,
                'signature' => $this->tandaTangan($json, $nomorVa, $kunciApi),
                'timestamp' => now()->setTimezone('Asia/Jakarta')->format('YmdHis'),
            ])
            ->withBody($json, 'application/json')
            ->post($this->urlApi($kredensial->modeUji).$jalur);
    }
}
