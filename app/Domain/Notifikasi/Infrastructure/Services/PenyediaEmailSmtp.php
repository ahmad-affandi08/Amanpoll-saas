<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Services;

use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

/**
 * Server SMTP apa pun: Gmail/Google Workspace, email hosting Niagahoster, Zoho,
 * atau relai SMTP Brevo/SendGrid/Mailgun.
 */
final class PenyediaEmailSmtp extends PenyediaEmailDasar
{
    public function kode(): string
    {
        return 'Smtp';
    }

    public function nama(): string
    {
        return 'SMTP';
    }

    public function keterangan(): string
    {
        return 'Server SMTP umum (Gmail/Google Workspace, email hosting, Zoho, relai Brevo/SendGrid/Mailgun); sebagian shared hosting memblokir port SMTP keluar.';
    }

    protected function isianPenyedia(): array
    {
        return [
            new IsianKredensial('Host', 'Host SMTP', petunjuk: 'Mis. smtp.gmail.com, smtp.zoho.com, atau mail.domainanda.id.'),
            new IsianKredensial('Port', 'Port', pilihan: ['587', '465', '25', '2525'], bawaan: '587', petunjuk: '587 untuk TLS (STARTTLS), 465 untuk SSL.'),
            new IsianKredensial('Enkripsi', 'Enkripsi', pilihan: ['tls', 'ssl', 'tanpa'], bawaan: 'tls', petunjuk: 'tls = STARTTLS (port 587), ssl = TLS langsung (port 465).'),
            new IsianKredensial('NamaPengguna', 'Nama pengguna', petunjuk: 'Biasanya alamat email lengkap akun pengirim.'),
            new IsianKredensial('KataSandi', 'Kata sandi', rahasia: true, petunjuk: 'Gmail/Google Workspace memakai App Password, bukan kata sandi akun.'),
        ];
    }

    public function buatTransport(KredensialPenyedia $kredensial): EsmtpTransport
    {
        $enkripsi = $kredensial->ambilAtau('Enkripsi', 'tls');
        $transport = new EsmtpTransport(
            $kredensial->ambil('Host'),
            (int) $kredensial->ambilAtau('Port', '587'),
            $enkripsi === 'ssl',
        );

        // "tls" berarti STARTTLS wajib: lebih baik gagal daripada diam-diam mengirim kata sandi tanpa enkripsi.
        $transport->setAutoTls($enkripsi !== 'tanpa');
        $transport->setRequireTls($enkripsi === 'tls');
        $transport->setUsername($kredensial->ambil('NamaPengguna'));
        $transport->setPassword($kredensial->ambil('KataSandi'));

        $domainLokal = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($domainLokal) && $domainLokal !== '') {
            $transport->setLocalDomain($domainLokal);
        }

        $aliran = $transport->getStream();
        if ($aliran instanceof SocketStream) {
            $aliran->setTimeout(self::BATAS_WAKTU_DETIK);
        }

        return $transport;
    }

    protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $transport = $this->buatTransport($kredensial);

        try {
            // start() membuka koneksi, menjalankan STARTTLS bila diminta, lalu login.
            $transport->start();
            $transport->stop();
        } catch (TransportExceptionInterface $galat) {
            return new HasilUjiKoneksi(false, 'Server SMTP tidak dapat dihubungi atau menolak login: '.$this->sensor($kredensial, $galat->getMessage()));
        }

        return new HasilUjiKoneksi(
            true,
            "Berhasil masuk ke server SMTP {$kredensial->ambil('Host')}:{$kredensial->ambilAtau('Port', '587')}.",
        );
    }
}
