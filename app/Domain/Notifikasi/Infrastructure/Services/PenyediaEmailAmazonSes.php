<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Services;

use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use Aws\Exception\AwsException;
use Aws\SesV2\SesV2Client;
use Illuminate\Mail\Transport\SesV2Transport;

/** Amazon SES lewat API SES v2 (transport bawaan Laravel), dengan kunci IAM milik platform. */
final class PenyediaEmailAmazonSes extends PenyediaEmailDasar
{
    public function kode(): string
    {
        return 'AmazonSes';
    }

    public function nama(): string
    {
        return 'Amazon SES';
    }

    public function keterangan(): string
    {
        return 'Amazon Simple Email Service lewat HTTPS API; murah untuk volume besar, perlu akses produksi dari AWS.';
    }

    protected function isianPenyedia(): array
    {
        return [
            new IsianKredensial('Region', 'Region', bawaan: 'ap-southeast-1', petunjuk: 'Region tempat identitas pengirim diverifikasi, mis. ap-southeast-1 (Singapura) atau ap-southeast-3 (Jakarta).'),
            new IsianKredensial('AccessKeyId', 'Access key ID', petunjuk: 'Kunci pengguna IAM dengan izin ses:SendEmail dan ses:GetAccount.'),
            new IsianKredensial('SecretAccessKey', 'Secret access key', rahasia: true),
        ];
    }

    public function buatTransport(KredensialPenyedia $kredensial): SesV2Transport
    {
        return new SesV2Transport($this->klien($kredensial));
    }

    protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        try {
            $akun = $this->klien($kredensial)->getAccount();
        } catch (AwsException $galat) {
            $kode = (string) $galat->getAwsErrorCode();

            return new HasilUjiKoneksi(false, $kode === ''
                ? 'Amazon SES tidak dapat dihubungi. Periksa region dan jaringan server.'
                : "Amazon SES menolak kredensial ini ({$kode}).");
        }

        if ($akun->get('SendingEnabled') !== true) {
            return new HasilUjiKoneksi(false, 'Kredensial benar, tetapi pengiriman email dinonaktifkan untuk akun SES ini.');
        }

        return new HasilUjiKoneksi(true, $akun->get('ProductionAccessEnabled') === true
            ? 'Terhubung ke Amazon SES (akses produksi).'
            : 'Terhubung ke Amazon SES, tetapi akun masih sandbox: hanya bisa mengirim ke alamat yang sudah diverifikasi.');
    }

    private function klien(KredensialPenyedia $kredensial): SesV2Client
    {
        return new SesV2Client([
            'version' => 'latest',
            'region' => $kredensial->ambil('Region'),
            'credentials' => [
                'key' => $kredensial->ambil('AccessKeyId'),
                'secret' => $kredensial->ambil('SecretAccessKey'),
            ],
            'http' => ['timeout' => self::BATAS_WAKTU_DETIK, 'connect_timeout' => 5],
        ]);
    }
}
