<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Services;

use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\PeristiwaWebhookWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanMasukWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\StatusKirimanWhatsApp;
use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** WAHA (WhatsApp HTTP API) yang dipasang sendiri: satu sesi WhatsApp biasa hasil scan QR; tidak resmi. */
final class PenyediaWhatsAppWaha extends PenyediaWhatsAppTidakResmi
{
    private const SESI_BAWAAN = 'default';

    public function kode(): string
    {
        return 'Waha';
    }

    public function nama(): string
    {
        return 'WAHA (self-hosted)';
    }

    protected function ringkasan(): string
    {
        return 'Server WAHA milik sendiri yang mengirim pesan teks dari sesi WhatsApp hasil pindai kode QR.';
    }

    /** @return list<IsianKredensial> */
    protected function isianPenyedia(): array
    {
        return [
            new IsianKredensial('UrlDasar', 'URL dasar', petunjuk: 'Alamat server WAHA, mis. https://waha.perusahaan.id.'),
            new IsianKredensial('KunciApi', 'API key', rahasia: true, wajib: false, petunjuk: 'Nilai WAHA_API_KEY di server; kosongkan bila server tidak memakainya.'),
            new IsianKredensial('NamaSesi', 'Nama sesi', wajib: false, petunjuk: 'Sesi WAHA yang sudah dipindai.', bawaan: self::SESI_BAWAAN),
        ];
    }

    protected function kirimTeks(KredensialPenyedia $kredensial, string $nomor, string $teks): string
    {
        $jawaban = $this->panggil($kredensial, 'pengiriman pesan', fn (): Response => $this->http($kredensial)->post('/api/sendText', [
            'session' => $this->sesi($kredensial),
            'chatId' => $nomor.'@c.us',
            'text' => $teks,
        ]));

        // Bentuk id berbeda antar-engine WAHA: teks langsung, objek bersarang, atau di dalam `key`.
        $id = $this->teksDari($jawaban, 'id._serialized')
            ?: $this->teksDari($jawaban, 'id')
            ?: $this->teksDari($jawaban, 'key.id');

        return $id !== '' ? $id : throw $this->galat($kredensial, 'pengiriman pesan', $jawaban, 'jawaban tidak memuat id pesan.');
    }

    protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $sesi = $this->sesi($kredensial);
        $jawaban = $this->panggil($kredensial, 'pemeriksaan sesi', fn (): Response => $this->http($kredensial)
            ->get('/api/sessions/'.rawurlencode($sesi)));

        $status = $this->teksDari($jawaban, 'status');

        if ($status !== 'WORKING') {
            return new HasilUjiKoneksi(false, "Server WAHA terjangkau, tetapi sesi {$sesi} berstatus {$status}. Pindai kode QR sampai statusnya WORKING.");
        }

        return new HasilUjiKoneksi(true, "Sesi {$sesi} aktif ({$this->teksDari($jawaban, 'me.pushName')}).");
    }

    protected function pesanGalat(Response $jawaban): string
    {
        return $this->teksDari($jawaban, 'message') ?: $this->teksDari($jawaban, 'error');
    }

    /** @param array<mixed> $muatan */
    public function terjemahkanWebhook(array $muatan): PeristiwaWebhookWhatsApp
    {
        $sesi = $this->kredensialAtauKosong();

        // Satu server WAHA dapat menjalankan banyak sesi; hanya sesi yang dipakai pemasaran yang dibaca.
        if ($sesi !== null && $this->teksDalam($muatan, 'session') !== $this->sesi($sesi)) {
            return new PeristiwaWebhookWhatsApp;
        }

        return match ($this->teksDalam($muatan, 'event')) {
            'message' => $this->pesanMasuk($muatan),
            'message.ack' => $this->laporanStatus($muatan),
            default => new PeristiwaWebhookWhatsApp,
        };
    }

    /** @param array<mixed> $muatan */
    private function pesanMasuk(array $muatan): PeristiwaWebhookWhatsApp
    {
        $dari = $this->teksDalam($muatan, 'payload.from');
        $isi = $this->teksDalam($muatan, 'payload.body');
        $dariKita = filter_var(data_get($muatan, 'payload.fromMe'), FILTER_VALIDATE_BOOLEAN);

        if ($dariKita || $isi === '' || ! str_ends_with($dari, '@c.us')) {
            return new PeristiwaWebhookWhatsApp;
        }

        return new PeristiwaWebhookWhatsApp(pesanMasuk: [
            new PesanMasukWhatsApp(substr($dari, 0, -5), $isi, $this->teksDalam($muatan, 'payload.id') ?: null),
        ]);
    }

    /** @param array<mixed> $muatan */
    private function laporanStatus(array $muatan): PeristiwaWebhookWhatsApp
    {
        $id = $this->teksDalam($muatan, 'payload.id');
        $ack = data_get($muatan, 'payload.ack');

        $status = match (is_numeric($ack) ? (int) $ack : null) {
            -1 => StatusPengirimanWhatsApp::Gagal,
            1 => StatusPengirimanWhatsApp::Dikirim,
            2 => StatusPengirimanWhatsApp::Terkirim,
            3, 4 => StatusPengirimanWhatsApp::Dibaca,
            default => null,
        };

        if ($id === '' || $status === null) {
            return new PeristiwaWebhookWhatsApp;
        }

        return new PeristiwaWebhookWhatsApp(status: [new StatusKirimanWhatsApp($id, $status)]);
    }

    private function sesi(KredensialPenyedia $kredensial): string
    {
        return $kredensial->ambilAtau('NamaSesi', self::SESI_BAWAAN);
    }

    private function http(KredensialPenyedia $kredensial): PendingRequest
    {
        $permintaan = Http::baseUrl($this->urlDasar($kredensial))
            ->acceptJson()
            ->timeout(self::BATAS_WAKTU_DETIK);

        $kunci = $kredensial->ambilAtau('KunciApi');

        return $kunci === '' ? $permintaan : $permintaan->withHeaders(['X-Api-Key' => $kunci]);
    }

    private function urlDasar(KredensialPenyedia $kredensial): string
    {
        $url = rtrim($kredensial->ambil('UrlDasar'), '/');
        $skema = parse_url($url, PHP_URL_SCHEME);

        if (! in_array($skema, ['http', 'https'], true) || ! is_string(parse_url($url, PHP_URL_HOST))) {
            throw new AturanBisnisDilanggar('URL dasar WAHA harus berupa alamat http atau https lengkap, mis. https://waha.perusahaan.id.');
        }

        return $url;
    }
}
