<?php

declare(strict_types=1);

namespace Tests\Feature\Notifikasi;

use App\Domain\Notifikasi\Domain\Contracts\PenyediaEmail;
use App\Domain\Notifikasi\Infrastructure\Services\PenyediaEmailAmazonSes;
use App\Domain\Notifikasi\Infrastructure\Services\PenyediaEmailBrevo;
use App\Domain\Notifikasi\Infrastructure\Services\PenyediaEmailDasar;
use App\Domain\Notifikasi\Infrastructure\Services\PenyediaEmailMailgun;
use App\Domain\Notifikasi\Infrastructure\Services\PenyediaEmailPostmark;
use App\Domain\Notifikasi\Infrastructure\Services\PenyediaEmailResend;
use App\Domain\Notifikasi\Infrastructure\Services\PenyediaEmailSendGrid;
use App\Domain\Notifikasi\Infrastructure\Services\PenyediaEmailSmtp;
use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/** Adapter penyedia email yang dipilih di konsol platform (PRD 8.23, FASE 44). */
final class PenyediaEmailTest extends TestCase
{
    private const KUNCI = 'kunci-rahasia-sangat-panjang-7c1e';

    public function test_ketujuh_penyedia_terdaftar_dan_meminta_pengirim(): void
    {
        $penyedia = app(KatalogPenyediaLayanan::class)->menurutKategori(KategoriPenyediaLayanan::Email);

        $this->assertEqualsCanonicalizing(
            ['Smtp', 'AmazonSes', 'Brevo', 'SendGrid', 'Mailgun', 'Postmark', 'Resend'],
            array_map(fn ($satu): string => $satu->kode(), $penyedia),
        );

        foreach ($penyedia as $satu) {
            $this->assertInstanceOf(PenyediaEmail::class, $satu);
            $wajib = array_map(
                fn (IsianKredensial $isian): string => $isian->kunci,
                array_filter($satu->isian(), fn (IsianKredensial $isian): bool => $isian->wajib),
            );
            $this->assertContains('AlamatPengirim', $wajib, $satu->kode());
            $this->assertContains('NamaPengirim', $wajib, $satu->kode());
        }
    }

    public function test_brevo_mengirim_lengkap_dengan_lampiran(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<brevo-1@relay>'], 201)]);

        $terkirim = app(PenyediaEmailBrevo::class)
            ->buatTransport($this->kredensial('Brevo', ['KunciApi' => self::KUNCI]))
            ->send($this->surat());

        $this->assertSame('<brevo-1@relay>', $terkirim?->getMessageId());
        Http::assertSent(fn (Request $permintaan): bool => $permintaan->url() === 'https://api.brevo.com/v3/smtp/email'
            && $permintaan->header('api-key') === [self::KUNCI]
            && $permintaan->data() === [
                'sender' => ['email' => 'no-reply@klien.test', 'name' => 'Klien'],
                'to' => [['email' => 'budi@tujuan.test', 'name' => 'Budi']],
                'cc' => [['email' => 'sari@tujuan.test']],
                'bcc' => [['email' => 'arsip@klien.test']],
                'replyTo' => ['email' => 'cs@klien.test'],
                'subject' => 'Laporan bulanan',
                'htmlContent' => '<p>Isi laporan</p>',
                'textContent' => 'Isi laporan',
                'attachment' => [['name' => 'laporan.pdf', 'content' => base64_encode('ISI-PDF')]],
            ]);
    }

    public function test_sendgrid_mengirim_dengan_bearer_dan_teks_lebih_dulu(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.sendgrid.com/v3/mail/send' => Http::response('', 202, ['X-Message-Id' => 'sg-1'])]);

        $terkirim = app(PenyediaEmailSendGrid::class)
            ->buatTransport($this->kredensial('SendGrid', ['KunciApi' => self::KUNCI]))
            ->send($this->surat());

        $this->assertSame('sg-1', $terkirim?->getMessageId());
        Http::assertSent(fn (Request $permintaan): bool => $permintaan->hasHeader('Authorization', 'Bearer '.self::KUNCI)
            && $permintaan['personalizations'] === [[
                'to' => [['email' => 'budi@tujuan.test', 'name' => 'Budi']],
                'cc' => [['email' => 'sari@tujuan.test']],
                'bcc' => [['email' => 'arsip@klien.test']],
            ]]
            && $permintaan['from'] === ['email' => 'no-reply@klien.test', 'name' => 'Klien']
            && $permintaan['reply_to'] === ['email' => 'cs@klien.test']
            && $permintaan['content'] === [
                ['type' => 'text/plain', 'value' => 'Isi laporan'],
                ['type' => 'text/html', 'value' => '<p>Isi laporan</p>'],
            ]
            && $permintaan['attachments'] === [[
                'content' => base64_encode('ISI-PDF'),
                'filename' => 'laporan.pdf',
                'type' => 'application/pdf',
                'disposition' => 'attachment',
            ]]);
    }

    public function test_mailgun_region_eu_multipart_dengan_basic_auth(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.eu.mailgun.net/v3/mg.klien.test/messages' => Http::response(['id' => '<mg-1@klien>', 'message' => 'Queued.'])]);

        $terkirim = app(PenyediaEmailMailgun::class)
            ->buatTransport($this->kredensial('Mailgun', ['Domain' => 'mg.klien.test', 'Region' => 'EU', 'KunciApi' => self::KUNCI]))
            ->send($this->surat());

        $this->assertSame('mg-1@klien', $terkirim?->getMessageId());
        Http::assertSent(function (Request $permintaan): bool {
            $bagian = collect($permintaan->data())->mapToGroups(fn (array $isi): array => [$isi['name'] => $isi]);

            return $permintaan->url() === 'https://api.eu.mailgun.net/v3/mg.klien.test/messages'
                && $permintaan->isMultipart()
                && $permintaan->hasHeader('Authorization', 'Basic '.base64_encode('api:'.self::KUNCI))
                && $bagian['from'][0]['contents'] === '"Klien" <no-reply@klien.test>'
                && $bagian['to'][0]['contents'] === '"Budi" <budi@tujuan.test>'
                && $bagian['cc'][0]['contents'] === 'sari@tujuan.test'
                && $bagian['bcc'][0]['contents'] === 'arsip@klien.test'
                && $bagian['h:Reply-To'][0]['contents'] === 'cs@klien.test'
                && $bagian['subject'][0]['contents'] === 'Laporan bulanan'
                && $bagian['text'][0]['contents'] === 'Isi laporan'
                && $bagian['html'][0]['contents'] === '<p>Isi laporan</p>'
                && $bagian['attachment'][0]['contents'] === 'ISI-PDF'
                && $bagian['attachment'][0]['filename'] === 'laporan.pdf';
        });
    }

    public function test_postmark_mengirim_dengan_token_server(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.postmarkapp.com/email' => Http::response(['ErrorCode' => 0, 'MessageID' => 'pm-1'])]);

        app(PenyediaEmailPostmark::class)
            ->buatTransport($this->kredensial('Postmark', ['TokenServer' => self::KUNCI]))
            ->send($this->surat());

        Http::assertSent(fn (Request $permintaan): bool => $permintaan->url() === 'https://api.postmarkapp.com/email'
            && $permintaan->header('X-Postmark-Server-Token') === [self::KUNCI]
            && $permintaan->data() === [
                'From' => '"Klien" <no-reply@klien.test>',
                'To' => '"Budi" <budi@tujuan.test>',
                'Cc' => 'sari@tujuan.test',
                'Bcc' => 'arsip@klien.test',
                'ReplyTo' => 'cs@klien.test',
                'Subject' => 'Laporan bulanan',
                'HtmlBody' => '<p>Isi laporan</p>',
                'TextBody' => 'Isi laporan',
                'MessageStream' => 'outbound',
                'Attachments' => [['Name' => 'laporan.pdf', 'Content' => base64_encode('ISI-PDF'), 'ContentType' => 'application/pdf']],
            ]);
    }

    public function test_resend_mengirim_dengan_bearer(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.resend.com/emails' => Http::response(['id' => 're-1'])]);

        $terkirim = app(PenyediaEmailResend::class)
            ->buatTransport($this->kredensial('Resend', ['KunciApi' => self::KUNCI]))
            ->send($this->surat());

        $this->assertSame('re-1', $terkirim?->getMessageId());
        Http::assertSent(fn (Request $permintaan): bool => $permintaan->url() === 'https://api.resend.com/emails'
            && $permintaan->hasHeader('Authorization', 'Bearer '.self::KUNCI)
            && $permintaan->data() === [
                'from' => '"Klien" <no-reply@klien.test>',
                'to' => ['"Budi" <budi@tujuan.test>'],
                'cc' => ['sari@tujuan.test'],
                'bcc' => ['arsip@klien.test'],
                'reply_to' => ['cs@klien.test'],
                'subject' => 'Laporan bulanan',
                'html' => '<p>Isi laporan</p>',
                'text' => 'Isi laporan',
                'attachments' => [['filename' => 'laporan.pdf', 'content' => base64_encode('ISI-PDF'), 'content_type' => 'application/pdf']],
            ]);
    }

    /**
     * @param  class-string<PenyediaEmailDasar>  $kelas
     * @param  array<string, string>  $nilai
     * @param  array<string, mixed>  $jawaban
     */
    #[DataProvider('galatPenyedia')]
    public function test_galat_penyedia_berpesan_indonesia_tanpa_kunci(string $kelas, string $kode, array $nilai, string $url, array $jawaban): void
    {
        Http::preventStrayRequests();
        Http::fake([$url => Http::response($jawaban, 401)]);

        try {
            app($kelas)->buatTransport($this->kredensial($kode, $nilai))->send($this->surat());
            $this->fail('Seharusnya ditolak.');
        } catch (TransportException $galat) {
            $this->assertStringContainsString('menolak pengiriman email (HTTP 401)', $galat->getMessage());
            $this->assertStringNotContainsString(self::KUNCI, $galat->getMessage());
        }
    }

    /** @return iterable<string, array{0: class-string<PenyediaEmailDasar>, 1: string, 2: array<string, string>, 3: string, 4: array<string, mixed>}> */
    public static function galatPenyedia(): iterable
    {
        $bocor = 'Kunci tidak dikenal: '.self::KUNCI;

        yield 'Brevo' => [PenyediaEmailBrevo::class, 'Brevo', ['KunciApi' => self::KUNCI], 'https://api.brevo.com/v3/smtp/email', ['message' => $bocor]];
        yield 'SendGrid' => [PenyediaEmailSendGrid::class, 'SendGrid', ['KunciApi' => self::KUNCI], 'https://api.sendgrid.com/v3/mail/send', ['errors' => [['message' => $bocor]]]];
        yield 'Mailgun' => [PenyediaEmailMailgun::class, 'Mailgun', ['Domain' => 'mg.klien.test', 'KunciApi' => self::KUNCI], 'https://api.mailgun.net/v3/mg.klien.test/messages', ['message' => $bocor]];
        yield 'Postmark' => [PenyediaEmailPostmark::class, 'Postmark', ['TokenServer' => self::KUNCI], 'https://api.postmarkapp.com/email', ['Message' => $bocor]];
        yield 'Resend' => [PenyediaEmailResend::class, 'Resend', ['KunciApi' => self::KUNCI], 'https://api.resend.com/emails', ['message' => $bocor]];
    }

    public function test_uji_koneksi_sendgrid_menolak_kunci_tanpa_izin_kirim(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.sendgrid.com/v3/scopes' => Http::response(['scopes' => ['stats.read']])]);

        $hasil = app(PenyediaEmailSendGrid::class)->ujiKoneksi($this->kredensial('SendGrid', ['KunciApi' => self::KUNCI]));

        $this->assertFalse($hasil->berhasil);
        $this->assertSame('Kunci API SendGrid benar, tetapi tidak punya izin Mail Send.', $hasil->pesan);
    }

    public function test_uji_koneksi_resend_menerima_kunci_khusus_pengiriman(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.resend.com/domains' => Http::response(['name' => 'restricted_api_key', 'message' => 'This API key is restricted to only send emails'], 401)]);

        $hasil = app(PenyediaEmailResend::class)->ujiKoneksi($this->kredensial('Resend', ['KunciApi' => self::KUNCI]));

        $this->assertTrue($hasil->berhasil);
    }

    public function test_uji_koneksi_gagal_tidak_membocorkan_kunci(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.mailgun.net/v3/domains/mg.klien.test' => Http::response(['message' => 'Invalid private key '.self::KUNCI], 401)]);

        $hasil = app(PenyediaEmailMailgun::class)->ujiKoneksi($this->kredensial('Mailgun', ['Domain' => 'mg.klien.test', 'KunciApi' => self::KUNCI]));

        $this->assertFalse($hasil->berhasil);
        $this->assertSame('Mailgun menolak pemeriksaan domain (HTTP 401): Invalid private key ***', $hasil->pesan);
    }

    #[TestWith(['ssl', '465', true, false, true])]
    #[TestWith(['tls', '587', false, true, true])]
    #[TestWith(['tanpa', '25', false, false, false])]
    public function test_smtp_membangun_transport_sesuai_enkripsi(string $enkripsi, string $port, bool $tlsLangsung, bool $wajibStartTls, bool $startTlsOtomatis): void
    {
        $transport = app(PenyediaEmailSmtp::class)->buatTransport($this->kredensial('Smtp', [
            'Host' => 'smtp.klien.test',
            'Port' => $port,
            'Enkripsi' => $enkripsi,
            'NamaPengguna' => 'no-reply@klien.test',
            'KataSandi' => self::KUNCI,
        ]));

        $aliran = $transport->getStream();
        $this->assertInstanceOf(SocketStream::class, $aliran);
        $this->assertSame('smtp.klien.test', $aliran->getHost());
        $this->assertSame((int) $port, $aliran->getPort());
        $this->assertSame($tlsLangsung, $aliran->isTLS());
        $this->assertSame($wajibStartTls, $transport->isTlsRequired());
        $this->assertSame($startTlsOtomatis, $transport->isAutoTls());
        $this->assertSame('no-reply@klien.test', $transport->getUsername());
        $this->assertSame(self::KUNCI, $transport->getPassword());
    }

    public function test_amazon_ses_membangun_transport_dengan_region_dan_kredensial(): void
    {
        $transport = app(PenyediaEmailAmazonSes::class)->buatTransport($this->kredensial('AmazonSes', [
            'Region' => 'ap-southeast-3',
            'AccessKeyId' => 'AKIAKLIENUJI',
            'SecretAccessKey' => self::KUNCI,
        ]));

        $kredensialAws = $transport->ses()->getCredentials()->wait();
        $this->assertSame('ap-southeast-3', $transport->ses()->getRegion());
        $this->assertSame('AKIAKLIENUJI', $kredensialAws->getAccessKeyId());
        $this->assertSame(self::KUNCI, $kredensialAws->getSecretKey());
    }

    /** @param  array<string, string>  $nilai */
    private function kredensial(string $kode, array $nilai): KredensialPenyedia
    {
        return new KredensialPenyedia(KategoriPenyediaLayanan::Email, $kode, false, [
            ...$nilai,
            'AlamatPengirim' => 'no-reply@klien.test',
            'NamaPengirim' => 'Klien',
        ]);
    }

    private function surat(): Email
    {
        return (new Email)
            ->from(new Address('no-reply@klien.test', 'Klien'))
            ->to(new Address('budi@tujuan.test', 'Budi'))
            ->cc('sari@tujuan.test')
            ->bcc('arsip@klien.test')
            ->replyTo('cs@klien.test')
            ->subject('Laporan bulanan')
            ->text('Isi laporan')
            ->html('<p>Isi laporan</p>')
            ->attach('ISI-PDF', 'laporan.pdf', 'application/pdf');
    }
}
