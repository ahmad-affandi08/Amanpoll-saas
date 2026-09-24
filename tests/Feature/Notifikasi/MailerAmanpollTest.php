<?php

declare(strict_types=1);

namespace Tests\Feature\Notifikasi;

use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananPlatform;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Mail\Message;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

/** Mailer `amanpoll` memakai penyedia email aktif di konsol platform (PRD 8.23, FASE 44). */
final class MailerAmanpollTest extends TestCase
{
    use DatabaseTransactions;

    private const URL_BREVO = 'https://api.brevo.com/v3/smtp/email';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.from.address' => 'hello@amanpoll.test',
            'mail.from.name' => 'Amanpoll',
            'amanpoll.email.mailer_cadangan' => 'array',
        ]);
    }

    public function test_tanpa_penyedia_aktif_email_jatuh_ke_mailer_cadangan(): void
    {
        // Permintaan HTTP apa pun ke penyedia akan menggagalkan test.
        Http::preventStrayRequests();
        // Tersimpan tetapi tidak aktif tidak boleh dipakai.
        $this->simpanBrevo(aktif: false);

        $this->kirim();

        $pesan = $this->kotakCadangan();
        $this->assertCount(1, $pesan);
        $this->assertSame('hello@amanpoll.test', $pesan[0]->getEnvelope()->getSender()->getAddress());
    }

    public function test_penyedia_aktif_mengirim_dan_from_bawaan_diganti_pengirim_penyedia(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::URL_BREVO => Http::response(['messageId' => '<id-1@brevo>'], 201)]);
        $this->simpanBrevo();

        $this->kirim();

        Http::assertSent(fn (Request $permintaan): bool => $permintaan->url() === self::URL_BREVO
            && $permintaan->header('api-key') === ['xkeysib-kunci-rahasia-1234']
            && $permintaan['sender'] === ['email' => 'no-reply@klien.test', 'name' => 'Klien Amanpoll']
            && $permintaan['to'] === [['email' => 'budi@tujuan.test']]);
        $this->assertSame([], $this->kotakCadangan());
    }

    public function test_from_yang_disetel_pemanggil_dibiarkan(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::URL_BREVO => Http::response(['messageId' => '<id-2@brevo>'], 201)]);
        $this->simpanBrevo();

        $this->kirim(fn (Message $surat) => $surat->from('tim@klien.test', 'Tim Klien'));

        Http::assertSent(fn (Request $permintaan): bool => $permintaan['sender'] === ['email' => 'tim@klien.test', 'name' => 'Tim Klien']);
    }

    public function test_penyedia_dibaca_ulang_pada_setiap_kiriman(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::URL_BREVO => Http::response(['messageId' => '<id-3@brevo>'], 201)]);
        $baris = $this->simpanBrevo();

        $this->kirim();
        $baris->update(['Aktif' => false, 'Utama' => false]);
        $this->kirim();

        Http::assertSentCount(1);
        $this->assertCount(1, $this->kotakCadangan());
    }

    private function simpanBrevo(bool $aktif = true): PenyediaLayananPlatform
    {
        return PenyediaLayananPlatform::create([
            'Kategori' => KategoriPenyediaLayanan::Email,
            'Kode' => 'Brevo',
            'Aktif' => $aktif,
            'Utama' => $aktif,
            'ModeUji' => false,
            'KredensialTerenkripsi' => [
                'KunciApi' => 'xkeysib-kunci-rahasia-1234',
                'AlamatPengirim' => 'no-reply@klien.test',
                'NamaPengirim' => 'Klien Amanpoll',
            ],
        ]);
    }

    /** @param  (callable(Message): mixed)|null  $atur */
    private function kirim(?callable $atur = null): void
    {
        Mail::mailer('amanpoll')->raw('Halo dari Amanpoll.', function (Message $surat) use ($atur): void {
            $surat->to('budi@tujuan.test')->subject('Uji mailer');

            if ($atur !== null) {
                $atur($surat);
            }
        });
    }

    /** @return list<SentMessage> */
    private function kotakCadangan(): array
    {
        $transport = Mail::mailer('array')->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);

        return array_values($transport->messages()->all());
    }
}
