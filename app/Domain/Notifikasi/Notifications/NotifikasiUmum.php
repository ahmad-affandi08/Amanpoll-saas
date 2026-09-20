<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NotifikasiUmum extends Notification
{
    public function __construct(private readonly ?string $judul, private readonly string $isi) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->judul ?? 'Notifikasi Amanpoll')
            ->line($this->isi);
    }
}
