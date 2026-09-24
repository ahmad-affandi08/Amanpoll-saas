<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Services;

use App\Domain\Notifikasi\Domain\Contracts\PenyediaEmail;
use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Application\Services\PembacaKredensialPenyedia;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Header\MailboxListHeader;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

/**
 * Transport mailer `amanpoll`: meneruskan setiap email ke penyedia email aktif di konsol platform (PRD 8.23).
 *
 * Penyedia dan kredensialnya dibaca ulang pada setiap kiriman, karena transport ini
 * disimpan MailManager selama proses hidup (termasuk `queue:work`). Tanpa penyedia
 * aktif, email diteruskan ke mailer cadangan dari konfigurasi.
 *
 * Alamat pengirim bawaan aplikasi (`mail.from.address`) diganti dengan alamat pengirim
 * milik penyedia, karena penyedia hanya mau mengirim dari domain yang sudah diverifikasi.
 * Pengirim yang disetel sendiri oleh pemanggil dibiarkan.
 */
final class TransportEmailAmanpoll implements TransportInterface
{
    public function __construct(
        private readonly PembacaKredensialPenyedia $pembaca,
        private readonly KatalogPenyediaLayanan $katalog,
        private readonly MailManager $pengelolaSurat,
        private readonly string $mailerCadangan,
    ) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $kode = $this->pembaca->kodeUtama(KategoriPenyediaLayanan::Email);

        if ($kode === null) {
            return $this->cadangan()->send($message, $envelope);
        }

        $penyedia = $this->katalog->untuk(KategoriPenyediaLayanan::Email, $kode);
        $kredensial = $this->pembaca->untuk(KategoriPenyediaLayanan::Email, $kode);

        if (! $penyedia instanceof PenyediaEmail || $kredensial === null) {
            Log::warning('Penyedia email aktif tidak dikenal; email diteruskan ke mailer cadangan.', ['Kode' => $kode]);

            return $this->cadangan()->send($message, $envelope);
        }

        [$message, $envelope] = $this->denganPengirimPenyedia($message, $envelope, $kredensial);

        return $penyedia->buatTransport($kredensial)->send($message, $envelope);
    }

    public function __toString(): string
    {
        return 'amanpoll';
    }

    private function cadangan(): TransportInterface
    {
        // Menunjuk dirinya sendiri akan berputar tanpa akhir; log setidaknya menyimpan isinya.
        $nama = $this->mailerCadangan === '' || $this->mailerCadangan === 'amanpoll' ? 'log' : $this->mailerCadangan;

        return $this->pengelolaSurat->mailer($nama)->getSymfonyTransport();
    }

    /**
     * Mengganti From bawaan aplikasi dengan pengirim penyedia, lalu membuat ulang envelope.
     *
     * Envelope bawaan Laravel membaca pengirimnya dari objek pesan yang lama, jadi
     * tanpa dibuat ulang penyedia tetap menerima alamat bawaan sebagai pengirim.
     *
     * @return array{0: RawMessage, 1: Envelope|null}
     */
    private function denganPengirimPenyedia(RawMessage $pesan, ?Envelope $amplop, KredensialPenyedia $kredensial): array
    {
        if (! $pesan instanceof Message || ! $this->memakaiPengirimBawaan($pesan)) {
            return [$pesan, $amplop];
        }

        $pesan = clone $pesan;
        $pesan->getHeaders()->remove('From');
        $pesan->getHeaders()->addMailboxListHeader('From', [new Address(
            $kredensial->ambil(PenyediaEmail::ISIAN_ALAMAT_PENGIRIM),
            $kredensial->ambilAtau(PenyediaEmail::ISIAN_NAMA_PENGIRIM),
        )]);

        if ($amplop === null) {
            return [$pesan, null];
        }

        return [$pesan, new Envelope(Envelope::create($pesan)->getSender(), $amplop->getRecipients())];
    }

    private function memakaiPengirimBawaan(Message $pesan): bool
    {
        $bawaan = mb_strtolower(trim((string) config('mail.from.address')));
        $header = $pesan->getHeaders()->get('From');

        if ($bawaan === '' || ! $header instanceof MailboxListHeader) {
            return false;
        }

        $dari = $header->getAddresses();

        return count($dari) === 1 && mb_strtolower($dari[0]->getAddress()) === $bawaan;
    }
}
