<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Services;

use Closure;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;

/**
 * Transport Symfony yang menyerahkan email ke HTTP API penyedia (PRD 8.23).
 *
 * Panggilan API-nya milik adapter penyedia (lewat facade `Http` Laravel), sehingga
 * satu kelas ini cukup untuk Brevo, SendGrid, Mailgun, Postmark, dan Resend.
 */
final class TransportEmailHttp extends AbstractTransport
{
    /**
     * @param  Closure(Email, Envelope): (string|null)  $pengirim  mengembalikan ID pesan dari penyedia bila ada
     */
    public function __construct(
        private readonly string $nama,
        private readonly Closure $pengirim,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $asli = $message->getOriginalMessage();

        if (! $asli instanceof Message) {
            throw new TransportException("Penyedia email {$this->nama} hanya dapat mengirim pesan MIME yang utuh.");
        }

        $idPesan = ($this->pengirim)(MessageConverter::toEmail($asli), $message->getEnvelope());

        if ($idPesan !== null && $idPesan !== '') {
            $message->setMessageId($idPesan);
        }
    }

    public function __toString(): string
    {
        return mb_strtolower($this->nama).'+api';
    }
}
