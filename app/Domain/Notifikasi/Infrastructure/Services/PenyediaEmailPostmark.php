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

/** Postmark lewat Email API dengan server token. */
final class PenyediaEmailPostmark extends PenyediaEmailHttp
{
    private const URL_DASAR = 'https://api.postmarkapp.com';

    public function kode(): string
    {
        return 'Postmark';
    }

    public function nama(): string
    {
        return 'Postmark';
    }

    public function keterangan(): string
    {
        return 'Postmark lewat HTTP API; dikenal cepat sampai untuk email transaksional.';
    }

    protected function isianPenyedia(): array
    {
        return [
            new IsianKredensial('TokenServer', 'Server API token', rahasia: true, petunjuk: 'Servers > (server Anda) > API Tokens di dashboard Postmark.'),
            new IsianKredensial('AliranPesan', 'Message stream', wajib: false, bawaan: 'outbound', petunjuk: 'Biarkan "outbound" kecuali Anda membuat stream transaksional lain.'),
        ];
    }

    protected function kirim(KredensialPenyedia $kredensial, Email $surat, Envelope $amplop): ?string
    {
        $penerima = $this->penerima($surat, $amplop);

        $badan = array_filter([
            'From' => $amplop->getSender()->toString(),
            'To' => $this->daftar($penerima['kepada']),
            'Cc' => $this->daftar($penerima['tembusan']),
            'Bcc' => $this->daftar($penerima['tersembunyi']),
            'ReplyTo' => $this->daftar(array_values($surat->getReplyTo())),
            'Subject' => (string) $surat->getSubject(),
            'HtmlBody' => $this->html($surat),
            'TextBody' => $this->teks($surat),
            'MessageStream' => $kredensial->ambilAtau('AliranPesan', 'outbound'),
            'Attachments' => array_map(
                fn (array $lampiran): array => array_filter([
                    'Name' => $lampiran['nama'],
                    'Content' => base64_encode($lampiran['isi']),
                    'ContentType' => $lampiran['jenis'],
                    'ContentID' => $lampiran['idKonten'] === null ? null : 'cid:'.$lampiran['idKonten'],
                ], fn (mixed $nilai): bool => $nilai !== null),
                $this->lampiran($surat),
            ),
        ], fn (mixed $nilai): bool => $nilai !== null && $nilai !== '' && $nilai !== []);

        $jawaban = $this->panggil($kredensial, 'pengiriman email', fn (): Response => $this->http($kredensial)->post('/email', $badan));

        return $this->teksDari($jawaban, 'MessageID') ?: null;
    }

    protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $jawaban = $this->panggil($kredensial, 'pemeriksaan server', fn (): Response => $this->http($kredensial)->get('/server'));
        $server = $this->teksDari($jawaban, 'Name');

        return new HasilUjiKoneksi(true, $server === '' ? 'Terhubung ke server Postmark.' : "Terhubung ke server Postmark {$server}.");
    }

    protected function pesanGalat(Response $jawaban): string
    {
        return $this->teksDari($jawaban, 'Message');
    }

    /** @param  list<Address>  $alamat */
    private function daftar(array $alamat): string
    {
        return implode(', ', array_map(fn (Address $satu): string => $satu->toString(), $alamat));
    }

    private function http(KredensialPenyedia $kredensial): PendingRequest
    {
        return Http::baseUrl(self::URL_DASAR)
            ->withHeaders(['X-Postmark-Server-Token' => $kredensial->ambil('TokenServer')])
            ->acceptJson()
            ->asJson()
            ->timeout(self::BATAS_WAKTU_DETIK);
    }
}
