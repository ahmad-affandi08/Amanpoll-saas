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

/** Twilio SendGrid lewat Mail Send API v3. */
final class PenyediaEmailSendGrid extends PenyediaEmailHttp
{
    private const URL_DASAR = 'https://api.sendgrid.com/v3';

    public function kode(): string
    {
        return 'SendGrid';
    }

    public function nama(): string
    {
        return 'SendGrid';
    }

    public function keterangan(): string
    {
        return 'Twilio SendGrid lewat HTTP API; kunci API perlu izin Mail Send.';
    }

    protected function isianPenyedia(): array
    {
        return [
            new IsianKredensial('KunciApi', 'Kunci API', rahasia: true, petunjuk: 'Settings > API Keys di dashboard SendGrid (diawali SG.), minimal izin Mail Send.'),
        ];
    }

    protected function kirim(KredensialPenyedia $kredensial, Email $surat, Envelope $amplop): ?string
    {
        $penerima = $this->penerima($surat, $amplop);
        $balasKe = $surat->getReplyTo()[0] ?? null;

        // SendGrid mensyaratkan text/plain mendahului text/html.
        $isi = array_values(array_filter([
            ['type' => 'text/plain', 'value' => $this->teks($surat)],
            ['type' => 'text/html', 'value' => $this->html($surat)],
        ], fn (array $bagian): bool => $bagian['value'] !== null));

        $badan = array_filter([
            'personalizations' => [array_filter([
                'to' => array_map($this->alamat(...), $penerima['kepada']),
                'cc' => array_map($this->alamat(...), $penerima['tembusan']),
                'bcc' => array_map($this->alamat(...), $penerima['tersembunyi']),
            ])],
            'from' => $this->alamat($amplop->getSender()),
            'reply_to' => $balasKe === null ? null : $this->alamat($balasKe),
            'subject' => (string) $surat->getSubject(),
            'content' => $isi,
            'attachments' => array_map(
                fn (array $lampiran): array => array_filter([
                    'content' => base64_encode($lampiran['isi']),
                    'filename' => $lampiran['nama'],
                    'type' => $lampiran['jenis'],
                    'disposition' => $lampiran['inline'] ? 'inline' : 'attachment',
                    'content_id' => $lampiran['idKonten'],
                ], fn (mixed $nilai): bool => $nilai !== null),
                $this->lampiran($surat),
            ),
        ], fn (mixed $nilai): bool => $nilai !== null && $nilai !== []);

        $jawaban = $this->panggil($kredensial, 'pengiriman email', fn (): Response => $this->http($kredensial)->post('/mail/send', $badan));

        return $jawaban->header('X-Message-Id') ?: null;
    }

    protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $jawaban = $this->panggil($kredensial, 'pemeriksaan kunci API', fn (): Response => $this->http($kredensial)->get('/scopes'));
        $cakupan = $jawaban->json('scopes');

        if (! is_array($cakupan) || ! in_array('mail.send', $cakupan, true)) {
            return new HasilUjiKoneksi(false, 'Kunci API SendGrid benar, tetapi tidak punya izin Mail Send.');
        }

        return new HasilUjiKoneksi(true, 'Kunci API SendGrid valid dan boleh mengirim email.');
    }

    protected function pesanGalat(Response $jawaban): string
    {
        return $this->teksDari($jawaban, 'errors.0.message');
    }

    /** @return array{email: string, name?: string} */
    private function alamat(Address $alamat): array
    {
        return $alamat->getName() === ''
            ? ['email' => $alamat->getAddress()]
            : ['email' => $alamat->getAddress(), 'name' => $alamat->getName()];
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
