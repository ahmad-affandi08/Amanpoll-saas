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

/** Brevo (dulu Sendinblue) lewat API transaksional v3. */
final class PenyediaEmailBrevo extends PenyediaEmailHttp
{
    private const URL_DASAR = 'https://api.brevo.com/v3';

    public function kode(): string
    {
        return 'Brevo';
    }

    public function nama(): string
    {
        return 'Brevo';
    }

    public function keterangan(): string
    {
        return 'Email transaksional Brevo lewat HTTP API; tetap jalan di hosting yang memblokir port SMTP.';
    }

    protected function isianPenyedia(): array
    {
        return [
            new IsianKredensial('KunciApi', 'Kunci API', rahasia: true, petunjuk: 'Menu SMTP & API > API Keys di dashboard Brevo (diawali xkeysib-).'),
        ];
    }

    protected function kirim(KredensialPenyedia $kredensial, Email $surat, Envelope $amplop): ?string
    {
        $penerima = $this->penerima($surat, $amplop);
        $balasKe = $surat->getReplyTo()[0] ?? null;

        $badan = array_filter([
            'sender' => $this->alamat($amplop->getSender()),
            'to' => array_map($this->alamat(...), $penerima['kepada']),
            'cc' => array_map($this->alamat(...), $penerima['tembusan']),
            'bcc' => array_map($this->alamat(...), $penerima['tersembunyi']),
            'replyTo' => $balasKe === null ? null : $this->alamat($balasKe),
            'subject' => (string) $surat->getSubject(),
            'htmlContent' => $this->html($surat),
            'textContent' => $this->teks($surat),
            'attachment' => array_map(
                fn (array $lampiran): array => ['name' => $lampiran['nama'], 'content' => base64_encode($lampiran['isi'])],
                $this->lampiran($surat),
            ),
        ], fn (mixed $nilai): bool => $nilai !== null && $nilai !== []);

        $jawaban = $this->panggil($kredensial, 'pengiriman email', fn (): Response => $this->http($kredensial)->post('/smtp/email', $badan));

        return $this->teksDari($jawaban, 'messageId') ?: null;
    }

    protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $jawaban = $this->panggil($kredensial, 'pemeriksaan akun', fn (): Response => $this->http($kredensial)->get('/account'));
        $akun = $this->teksDari($jawaban, 'email') ?: $this->teksDari($jawaban, 'companyName');

        return new HasilUjiKoneksi(true, $akun === '' ? 'Terhubung ke akun Brevo.' : "Terhubung ke akun Brevo {$akun}.");
    }

    protected function pesanGalat(Response $jawaban): string
    {
        return $this->teksDari($jawaban, 'message') ?: $this->teksDari($jawaban, 'code');
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
            ->withHeaders(['api-key' => $kredensial->ambil('KunciApi')])
            ->acceptJson()
            ->asJson()
            ->timeout(self::BATAS_WAKTU_DETIK);
    }
}
