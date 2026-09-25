<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Notifications;

use App\Domain\Notifikasi\Infrastructure\Services\TransportEmailAmanpoll;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;

/**
 * Surat notifikasi umum. Surat milik sebuah organisasi membawa penandanya, supaya
 * transport `amanpoll` dapat mengirimnya lewat email milik organisasi itu (PRD 8.23).
 */
final class NotifikasiUmum extends Notification
{
    public function __construct(
        private readonly ?string $judul,
        private readonly string $isi,
        private readonly ?string $organisasiId = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $surat = (new MailMessage)
            ->subject($this->judul ?? 'Notifikasi Amanpoll')
            ->line($this->isi);

        if ($this->organisasiId !== null) {
            $organisasiId = $this->organisasiId;
            $surat->withSymfonyMessage(
                fn (Email $email) => $email->getHeaders()->addTextHeader(TransportEmailAmanpoll::HEADER_ORGANISASI, $organisasiId),
            );
        }

        return $surat;
    }
}
