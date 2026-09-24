<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Tindakan\TindakanWebhook;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Keamanan\PenjagaUrlKeluar;
use App\Shared\Infrastructure\Keamanan\UrlKeluarDitolak;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/** Langkah otomasi "Panggil webhook" memakai penjaga URL keluar yang sama dengan webhook tenant. */
final class TindakanWebhookPenjagaTest extends TestCase
{
    public function test_aturan_langkah_menolak_url_jaringan_internal(): void
    {
        $pemeriksa = Validator::make(['Url' => 'http://169.254.169.254/latest/meta-data/'], app(TindakanWebhook::class)->aturan());

        $this->assertTrue($pemeriksa->fails());
        $this->assertSame(UrlKeluarDitolak::PESAN_JARINGAN_INTERNAL, $pemeriksa->errors()->first('Url'));
    }

    public function test_langkah_yang_tersimpan_dengan_url_internal_tidak_dikirim(): void
    {
        config(['amanpoll.pemasaran.webhook_rahasia' => 'rahasia-webhook-pemasaran']);
        $this->dnsPalsu()->petakan('hook.contoh.co.id', ['192.168.0.9']);
        Http::preventStrayRequests();
        Http::fake();

        try {
            app(TindakanWebhook::class)->jalankan(new KonteksOtomasi(null, null, null), ['Url' => 'https://hook.contoh.co.id/masuk']);
            $this->fail('Seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringStartsWith(PenjagaUrlKeluar::PESAN_DITOLAK_SAAT_KIRIM, $galat->getMessage());
        }

        Http::assertNothingSent();
    }
}
