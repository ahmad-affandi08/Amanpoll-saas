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

/** Resend lewat Emails API. */
final class PenyediaEmailResend extends PenyediaEmailHttp
{
    private const URL_DASAR = 'https://api.resend.com';

    public function kode(): string
    {
        return 'Resend';
    }

    public function nama(): string
    {
        return 'Resend';
    }

    public function keterangan(): string
    {
        return 'Resend lewat HTTP API dengan satu kunci API.';
    }

    protected function isianPenyedia(): array
    {
        return [
            new IsianKredensial('KunciApi', 'Kunci API', rahasia: true, petunjuk: 'API Keys di dashboard Resend (diawali re_).'),
        ];
    }

    protected function kirim(KredensialPenyedia $kredensial, Email $surat, Envelope $amplop): ?string
    {
        $penerima = $this->penerima($surat, $amplop);

        $badan = array_filter([
            'from' => $amplop->getSender()->toString(),
            'to' => $this->daftar($penerima['kepada']),
            'cc' => $this->daftar($penerima['tembusan']),
            'bcc' => $this->daftar($penerima['tersembunyi']),
            'reply_to' => $this->daftar(array_values($surat->getReplyTo())),
            'subject' => (string) $surat->getSubject(),
            'html' => $this->html($surat),
            'text' => $this->teks($surat),
            'attachments' => array_map(
                fn (array $lampiran): array => array_filter([
                    'filename' => $lampiran['nama'],
                    'content' => base64_encode($lampiran['isi']),
                    'content_type' => $lampiran['jenis'],
                    'content_id' => $lampiran['idKonten'],
                ], fn (mixed $nilai): bool => $nilai !== null),
                $this->lampiran($surat),
            ),
        ], fn (mixed $nilai): bool => $nilai !== null && $nilai !== []);

        $jawaban = $this->panggil($kredensial, 'pengiriman email', fn (): Response => $this->http($kredensial)->post('/emails', $badan));

        return $this->teksDari($jawaban, 'id') ?: null;
    }

    protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $tindakan = 'pemeriksaan kunci API';
        $jawaban = $this->hubungi($tindakan, fn (): Response => $this->http($kredensial)->get('/domains'));

        // Kunci "sending access" sengaja tidak boleh membaca domain, tetapi itu tandanya kunci valid.
        if ($jawaban->status() === 401 && $this->teksDari($jawaban, 'name') === 'restricted_api_key') {
            return new HasilUjiKoneksi(true, 'Kunci API Resend valid (khusus pengiriman).');
        }

        if ($jawaban->failed()) {
            throw $this->galat($kredensial, $tindakan, $jawaban);
        }

        $domain = $jawaban->json('data');

        return new HasilUjiKoneksi(true, is_array($domain) && $domain !== []
            ? 'Kunci API Resend valid; '.count($domain).' domain terdaftar.'
            : 'Kunci API Resend valid, tetapi belum ada domain terdaftar.');
    }

    protected function pesanGalat(Response $jawaban): string
    {
        return $this->teksDari($jawaban, 'message');
    }

    /**
     * @param  list<Address>  $alamat
     * @return list<string>
     */
    private function daftar(array $alamat): array
    {
        return array_map(fn (Address $satu): string => $satu->toString(), $alamat);
    }

    private function http(KredensialPenyedia $kredensial): PendingRequest
    {
        return Http::baseUrl(self::URL_DASAR)
            ->withToken($kredensial->ambil('KunciApi'))
            ->acceptJson()
            ->asJson()
            ->timeout(self::BATAS_WAKTU_DETIK);
    }
}
