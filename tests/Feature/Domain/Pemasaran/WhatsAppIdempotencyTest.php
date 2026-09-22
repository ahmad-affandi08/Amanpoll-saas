<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PengirimWhatsAppPemasaran;
use App\Domain\Pemasaran\Application\Services\PenjadwalWhatsAppPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanWhatsAppPemasaran;
use App\Domain\Pemasaran\Jobs\KirimWhatsAppPemasaran;
use Carbon\CarbonImmutable;

/** Satu pesan hanya berangkat sekali, berapa kali pun jalurnya diulang (MARKETING.md 16, 29). */
final class WhatsAppIdempotencyTest extends KasusWhatsApp
{
    /** Penjadwalan ulang dengan kunci yang sama mengembalikan baris yang sama, bukan baris kedua. */
    public function test_penjadwalan_ulang_tidak_menggandakan_barisnya(): void
    {
        $prospek = $this->buatProspek();
        $template = $this->buatTemplate();
        $penjadwal = app(PenjadwalWhatsAppPemasaran::class);

        $pertama = $penjadwal->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:sama');
        $kedua = $penjadwal->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:sama');

        $this->assertNotNull($pertama);
        $this->assertSame($pertama->Id, $kedua?->Id);
        $this->assertSame(1, PengirimanWhatsAppPemasaran::query()->count());
    }

    /** Kunci yang berbeda memang harus menghasilkan dua pesan; tanpa ini idempotensi jadi kebetulan. */
    public function test_kunci_berbeda_menghasilkan_kiriman_terpisah(): void
    {
        $prospek = $this->buatProspek();
        $template = $this->buatTemplate();
        $penjadwal = app(PenjadwalWhatsAppPemasaran::class);

        $penjadwal->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:satu');
        $penjadwal->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:dua');

        $this->assertSame(2, PengirimanWhatsAppPemasaran::query()->count());
    }

    /** Job yang dijalankan dua kali tidak mengirim dua kali; statusnya sendiri yang jadi penjaga. */
    public function test_job_yang_diulang_tidak_mengirim_dua_kali(): void
    {
        $pengiriman = $this->jadwalkan('uji:job');

        $job = new KirimWhatsAppPemasaran($pengiriman->Id);
        $job->handle(app(PengirimWhatsAppPemasaran::class));
        $job->handle(app(PengirimWhatsAppPemasaran::class));

        $this->assertCount(1, $this->penyedia->terkirim);
        $this->assertSame(1, $pengiriman->fresh()?->Percobaan);
    }

    /** Memanggil pengirim langsung pun tetap sekali; konsol punya tombol coba lagi yang melewati job-nya. */
    public function test_pengirim_yang_dipanggil_ulang_tidak_mengirim_dua_kali(): void
    {
        $pengiriman = $this->jadwalkan('uji:langsung');
        $pengirim = app(PengirimWhatsAppPemasaran::class);

        $this->assertSame(StatusPengirimanWhatsApp::Dikirim, $pengirim->kirim($pengiriman));
        $this->assertSame(StatusPengirimanWhatsApp::Dikirim, $pengirim->kirim($pengiriman));

        $this->assertCount(1, $this->penyedia->terkirim);
    }

    /** Kegagalan penyedia menyimpan galatnya dan membiarkan barisnya tetap Terjadwal untuk dicoba lagi. */
    public function test_kegagalan_penyedia_tidak_menandai_terkirim(): void
    {
        $pengiriman = $this->jadwalkan('uji:gagal');
        $this->penyedia->gagalkan = true;

        try {
            app(PengirimWhatsAppPemasaran::class)->kirim($pengiriman);
            $this->fail('Kegagalan penyedia seharusnya dilemparkan.');
        } catch (\Throwable) {
            $segar = $pengiriman->fresh();

            $this->assertSame(StatusPengirimanWhatsApp::Terjadwal, $segar?->Status);
            $this->assertNotNull($segar?->Galat);
            $this->assertNull($segar?->DikirimPada);
        }
    }

    /** Job yang menyerah menandai barisnya Gagal, bukan meninggalkannya menggantung selamanya. */
    public function test_job_yang_menyerah_menandai_barisnya_gagal(): void
    {
        $pengiriman = $this->jadwalkan('uji:menyerah');

        (new KirimWhatsAppPemasaran($pengiriman->Id))->failed(new \RuntimeException('Penyedia tidak menjawab.'));

        $this->assertSame(StatusPengirimanWhatsApp::Gagal, $pengiriman->fresh()?->Status);
    }

    /** Kabar yang datang terlambat tidak boleh memundurkan status yang sudah lebih jauh. */
    public function test_status_tidak_dapat_mundur(): void
    {
        $pengiriman = $this->jadwalkan('uji:mundur');
        $pengirim = app(PengirimWhatsAppPemasaran::class);

        $pengirim->kirim($pengiriman);
        $pengirim->perbaruiStatus($pengiriman, StatusPengirimanWhatsApp::Dibaca);
        $pengirim->perbaruiStatus($pengiriman, StatusPengirimanWhatsApp::Terkirim);

        $this->assertSame(StatusPengirimanWhatsApp::Dibaca, $pengiriman->fresh()?->Status);
    }

    private function jadwalkan(string $kunci): PengirimanWhatsAppPemasaran
    {
        $pengiriman = app(PenjadwalWhatsAppPemasaran::class)->jadwalkan(
            $this->buatProspek(),
            $this->buatTemplate(),
            CarbonImmutable::now(),
            $kunci,
        );

        $this->assertNotNull($pengiriman);

        return $pengiriman;
    }
}
