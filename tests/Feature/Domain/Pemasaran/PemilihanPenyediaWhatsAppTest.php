<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Services\PenyediaWhatsAppFonnte;
use App\Domain\Pemasaran\Infrastructure\Services\PenyediaWhatsAppLog;
use App\Domain\Pemasaran\Infrastructure\Services\PenyediaWhatsAppMetaCloud;
use App\Domain\Pemasaran\Infrastructure\Services\PenyediaWhatsAppWaha;

/** Penyedia WhatsApp yang dipakai: utama di konsol platform, lalu konfigurasi, lalu log. */
final class PemilihanPenyediaWhatsAppTest extends KasusPemasaran
{
    use MengaturPenyediaWhatsApp;

    public function test_tanpa_pengaturan_konsol_dipakai_penyedia_log(): void
    {
        $this->assertInstanceOf(PenyediaWhatsAppLog::class, app(PenyediaWhatsApp::class));
    }

    public function test_penyedia_utama_yang_aktif_di_konsol_yang_dipakai(): void
    {
        $this->aturPenyedia('Fonnte', ['Token' => 't'], utama: false);
        $this->aturMetaCloud();

        $this->assertInstanceOf(PenyediaWhatsAppMetaCloud::class, app(PenyediaWhatsApp::class));
    }

    /** Utama yang dinonaktifkan tidak boleh dipakai; mengirim lewat penyedia yang dimatikan operator berarti mengabaikannya. */
    public function test_penyedia_utama_yang_dinonaktifkan_tidak_dipakai(): void
    {
        $this->aturPenyedia('Waha', ['UrlDasar' => 'https://waha.test'], aktif: false);

        $this->assertInstanceOf(PenyediaWhatsAppLog::class, app(PenyediaWhatsApp::class));
    }

    public function test_tanpa_utama_di_konsol_konfigurasi_menentukan_penyedia(): void
    {
        config(['amanpoll.pemasaran.penyedia_whatsapp' => 'Fonnte']);

        $this->assertInstanceOf(PenyediaWhatsAppFonnte::class, app(PenyediaWhatsApp::class));

        $this->aturPenyedia('Waha', ['UrlDasar' => 'https://waha.test']);

        $this->assertInstanceOf(PenyediaWhatsAppWaha::class, app(PenyediaWhatsApp::class));
    }
}
