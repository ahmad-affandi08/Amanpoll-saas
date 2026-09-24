<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Services;

use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/** Mailgun lewat Messages API v3, dengan pilihan region US atau EU. */
final class PenyediaEmailMailgun extends PenyediaEmailHttp
{
    private const URL_US = 'https://api.mailgun.net/v3';

    private const URL_EU = 'https://api.eu.mailgun.net/v3';

    public function kode(): string
    {
        return 'Mailgun';
    }

    public function nama(): string
    {
        return 'Mailgun';
    }

    public function keterangan(): string
    {
        return 'Mailgun lewat HTTP API untuk satu domain pengirim yang sudah diverifikasi.';
    }

    protected function isianPenyedia(): array
    {
        return [
            new IsianKredensial('Domain', 'Domain pengirim', petunjuk: 'Domain yang terdaftar di Mailgun, mis. mg.domainanda.id.'),
            new IsianKredensial('Region', 'Region', pilihan: ['US', 'EU'], bawaan: 'US', petunjuk: 'Sesuai region saat domain dibuat di Mailgun.'),
            new IsianKredensial('KunciApi', 'Kunci API', rahasia: true, petunjuk: 'API Security > API keys di dashboard Mailgun.'),
        ];
    }

    protected function kirim(KredensialPenyedia $kredensial, Email $surat, Envelope $amplop): ?string
    {
        $penerima = $this->penerima($surat, $amplop);
        $bagian = [
            ['name' => 'from', 'contents' => $amplop->getSender()->toString()],
            ['name' => 'subject', 'contents' => (string) $surat->getSubject()],
        ];

        foreach (['to' => 'kepada', 'cc' => 'tembusan', 'bcc' => 'tersembunyi'] as $kolom => $kelompok) {
            foreach ($penerima[$kelompok] as $alamat) {
                $bagian[] = ['name' => $kolom, 'contents' => $alamat->toString()];
            }
        }

        $balasKe = implode(', ', array_map(fn (Address $alamat): string => $alamat->toString(), $surat->getReplyTo()));
        $isian = ['text' => $this->teks($surat), 'html' => $this->html($surat), 'h:Reply-To' => $balasKe === '' ? null : $balasKe];

        foreach ($isian as $kolom => $isi) {
            if ($isi !== null) {
                $bagian[] = ['name' => $kolom, 'contents' => $isi];
            }
        }

        foreach ($this->lampiran($surat) as $lampiran) {
            $bagian[] = [
                'name' => $lampiran['inline'] ? 'inline' : 'attachment',
                'contents' => $lampiran['isi'],
                'filename' => $lampiran['inline'] && $lampiran['idKonten'] !== null ? $lampiran['idKonten'] : $lampiran['nama'],
                'headers' => ['Content-Type' => $lampiran['jenis']],
            ];
        }

        $domain = rawurlencode($kredensial->ambil('Domain'));
        $jawaban = $this->panggil($kredensial, 'pengiriman email', fn (): Response => $this->http($kredensial)
            ->asMultipart()
            ->post("/{$domain}/messages", $bagian));

        return trim($this->teksDari($jawaban, 'id'), '<>') ?: null;
    }

    protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $domain = $kredensial->ambil('Domain');
        $jawaban = $this->panggil($kredensial, 'pemeriksaan domain', fn (): Response => $this->http($kredensial)
            ->get('/domains/'.rawurlencode($domain)));
        $status = $this->teksDari($jawaban, 'domain.state');

        if ($status !== '' && $status !== 'active') {
            return new HasilUjiKoneksi(false, "Kunci API benar, tetapi domain {$domain} berstatus {$status} di Mailgun. Selesaikan verifikasi DNS-nya dulu.");
        }

        return new HasilUjiKoneksi(true, "Terhubung ke domain Mailgun {$domain}.");
    }

    protected function pesanGalat(Response $jawaban): string
    {
        return $this->teksDari($jawaban, 'message');
    }

    private function http(KredensialPenyedia $kredensial): PendingRequest
    {
        $urlDasar = $kredensial->ambilAtau('Region', 'US') === 'EU' ? self::URL_EU : self::URL_US;

        return Http::baseUrl($urlDasar)
            ->withBasicAuth('api', $kredensial->ambil('KunciApi'))
            ->acceptJson()
            ->timeout(self::BATAS_WAKTU_DETIK);
    }
}
