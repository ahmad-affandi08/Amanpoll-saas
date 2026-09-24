<?php

declare(strict_types=1);

namespace App\Domain\Platform\Notifications;

use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ResetKataSandiNotification extends Notification
{
    public function __construct(private readonly string $tokenMentah) {}

    public function tokenMentah(): string
    {
        return $this->tokenMentah;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var Pengguna $notifiable */
        $url = url('/reset-kata-sandi/'.$notifiable->Id.'/'.$this->tokenMentah);
        $namaOrganisasi = (string) $notifiable->organisasi?->Nama;

        return (new MailMessage)
            ->subject("Permintaan Reset Kata Sandi Amanpoll — {$namaOrganisasi}")
            ->line("Kami menerima permintaan reset kata sandi untuk akun Anda di organisasi {$namaOrganisasi}.")
            ->line('Bila email Anda terdaftar di beberapa organisasi, tiap organisasi mengirim tautannya sendiri.')
            ->action('Reset Kata Sandi', $url)
            ->line('Tautan ini berlaku selama 60 menit. Abaikan email ini bila Anda tidak meminta reset kata sandi.');
    }
}
