<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\MulaiEksekusiOtomasi;
use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Application\Services\PenjalanOtomasi;
use App\Domain\Pemasaran\Domain\Enums\HasilLangkahOtomasi;
use App\Domain\Pemasaran\Domain\Enums\JenisAktivitasProspek;
use App\Domain\Pemasaran\Domain\Enums\StatusEksekusiOtomasi;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AktivitasProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksekusiOtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LogEksekusiOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use App\Domain\Pemasaran\Jobs\ProsesOtomasiPemasaran;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/** Idempotensi eksekusi dan DLQ-nya (Gate 35, MARKETING.md 17). */
final class AutomationIdempotencyTest extends KasusOtomasi
{
    public function test_peristiwa_yang_sama_tidak_melahirkan_eksekusi_kedua(): void
    {
        $otomasi = $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('TambahTag', ['Tag' => ['sekali']]),
        ]);

        $this->buatProspek();
        $event = EventPemasaran::query()->where('Jenis', 'ProspekDibuat')->firstOrFail();

        $pertama = app(MulaiEksekusiOtomasi::class)->untuk($otomasi, $event);
        $kedua = app(MulaiEksekusiOtomasi::class)->untuk($otomasi, $event);

        $this->assertSame($pertama?->Id, $kedua?->Id);
        $this->assertSame(1, EksekusiOtomasiPemasaran::query()->count());
    }

    /** Inti Gate 35: pekerjaan yang dijalankan dua kali tidak menghasilkan aksi ganda. */
    public function test_menjalankan_pekerjaan_dua_kali_tidak_menggandakan_aksinya(): void
    {
        $template = $this->buatTemplate('otomasi-sapaan');
        $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('KirimEmail', ['TemplateKode' => $template->Kode]),
        ]);

        $this->buatProspek();
        $eksekusi = EksekusiOtomasiPemasaran::query()->firstOrFail();

        (new ProsesOtomasiPemasaran($eksekusi->Id))->handle(app(PenjalanOtomasi::class));
        (new ProsesOtomasiPemasaran($eksekusi->Id))->handle(app(PenjalanOtomasi::class));

        $this->assertSame(1, PengirimanEmailPemasaran::query()->count());
        $this->assertSame(StatusEksekusiOtomasi::Selesai, $eksekusi->fresh()?->Status);
    }

    /** Langkah yang sudah sukses dilewati saat dicoba ulang, bukan diulang dari awal. */
    public function test_percobaan_ulang_melewati_langkah_yang_sudah_sukses(): void
    {
        $template = $this->buatTemplate('otomasi-langkah-satu');
        $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('KirimEmail', ['TemplateKode' => $template->Kode]),
            $this->langkahAksi('Webhook', ['Url' => 'https://contoh.test/hook']),
        ]);

        config(['amanpoll.pemasaran.webhook_rahasia' => 'rahasia-uji']);
        // Gagal sekali lalu berhasil; Http::fake kedua tidak menimpa yang pertama, jadi dipakai urutan.
        Http::fake(['contoh.test/*' => Http::sequence()->push('', 500)->push('ok', 200)]);

        $this->buatProspek();
        $eksekusi = EksekusiOtomasiPemasaran::query()->firstOrFail();

        app(PenjalanOtomasi::class)->jalankan($eksekusi);
        $this->assertSame(1, PengirimanEmailPemasaran::query()->count());

        app(PenjalanOtomasi::class)->jalankan($eksekusi->refresh());

        $this->assertSame(1, PengirimanEmailPemasaran::query()->count());
        $this->assertSame(StatusEksekusiOtomasi::Selesai, $eksekusi->fresh()?->Status);
    }

    /** Pekerja mati setelah aksinya jalan tetapi sebelum kemajuannya tersimpan; hanya log langkah yang menahan pengulangan. */
    public function test_aksi_tidak_terulang_walau_penanda_kemajuannya_hilang(): void
    {
        $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('NotifikasiInternal', ['Judul' => 'Prospek baru masuk']),
        ]);

        $prospek = $this->buatProspek();
        $eksekusi = EksekusiOtomasiPemasaran::query()->firstOrFail();

        app(PenjalanOtomasi::class)->jalankan($eksekusi);
        $this->assertSame(1, $this->jumlahNotifikasi($prospek->Id));

        $eksekusi->forceFill([
            'LangkahBerikutnya' => 0,
            'Status' => StatusEksekusiOtomasi::Berjalan->value,
            'SelesaiPada' => null,
        ])->save();

        app(PenjalanOtomasi::class)->jalankan($eksekusi->refresh());

        $this->assertSame(1, $this->jumlahNotifikasi($prospek->Id));
        $this->assertSame(StatusEksekusiOtomasi::Selesai, $eksekusi->fresh()?->Status);
    }

    private function jumlahNotifikasi(string $prospekId): int
    {
        return AktivitasProspek::query()
            ->where('ProspekId', $prospekId)
            ->where('Jenis', JenisAktivitasProspek::Otomasi->value)
            ->count();
    }

    /** Paruh kedua Gate 35: kegagalan berhenti di DLQ, tidak mengulang tanpa batas. */
    public function test_kegagalan_berulang_berhenti_di_dlq(): void
    {
        app(LayananKonfigurasiPemasaran::class)
            ->simpan(KatalogKonfigurasiPemasaran::OTOMASI_CAP_PERCOBAAN, 3);

        $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('Webhook', ['Url' => 'https://contoh.test/hook']),
        ]);

        config(['amanpoll.pemasaran.webhook_rahasia' => 'rahasia-uji']);
        Http::fake(['contoh.test/*' => Http::response('', 500)]);

        $this->buatProspek();
        $eksekusi = EksekusiOtomasiPemasaran::query()->firstOrFail();

        for ($percobaan = 1; $percobaan <= 3; $percobaan++) {
            app(PenjalanOtomasi::class)->jalankan($eksekusi->refresh());
        }

        $akhir = $eksekusi->fresh();
        $this->assertSame(StatusEksekusiOtomasi::GagalPermanen, $akhir?->Status);
        $this->assertSame(3, $akhir?->Percobaan);
        $this->assertNull($akhir?->LanjutPada);

        // Sudah final: dijalankan lagi tidak menambah percobaan dan tidak memanggil webhook lagi.
        app(PenjalanOtomasi::class)->jalankan($eksekusi->refresh());
        $this->assertSame(3, $eksekusi->fresh()?->Percobaan);
        Http::assertSentCount(3);
    }

    public function test_percobaan_mundur_semakin_lama_sebelum_menyerah(): void
    {
        app(LayananKonfigurasiPemasaran::class)
            ->simpan(KatalogKonfigurasiPemasaran::OTOMASI_CAP_PERCOBAAN, 3);

        $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('Webhook', ['Url' => 'https://contoh.test/hook']),
        ]);

        config(['amanpoll.pemasaran.webhook_rahasia' => 'rahasia-uji']);
        Http::fake(['contoh.test/*' => Http::response('', 500)]);

        $this->buatProspek();
        $eksekusi = EksekusiOtomasiPemasaran::query()->firstOrFail();

        app(PenjalanOtomasi::class)->jalankan($eksekusi);
        $pertama = $eksekusi->fresh()?->LanjutPada;

        app(PenjalanOtomasi::class)->jalankan($eksekusi->refresh());
        $kedua = $eksekusi->fresh()?->LanjutPada;

        $this->assertNotNull($pertama);
        $this->assertNotNull($kedua);
        $this->assertTrue($kedua->greaterThan($pertama));
    }

    public function test_jeda_menunda_eksekusi_dan_tidak_terulang_saat_dilanjutkan(): void
    {
        $template = $this->buatTemplate('otomasi-setelah-jeda');
        $this->buatOtomasi('ProspekDibuat', [
            $this->langkahJeda(60),
            $this->langkahAksi('KirimEmail', ['TemplateKode' => $template->Kode]),
        ]);

        $this->buatProspek();
        $eksekusi = EksekusiOtomasiPemasaran::query()->firstOrFail();

        app(PenjalanOtomasi::class)->jalankan($eksekusi);

        $this->assertSame(StatusEksekusiOtomasi::Tertunda, $eksekusi->fresh()?->Status);
        $this->assertSame(0, PengirimanEmailPemasaran::query()->count());

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addHours(2));
        app(PenjalanOtomasi::class)->jalankan($eksekusi->refresh());

        $this->assertSame(StatusEksekusiOtomasi::Selesai, $eksekusi->fresh()?->Status);
        $this->assertSame(1, PengirimanEmailPemasaran::query()->count());
        $this->assertSame(1, LogEksekusiOtomasi::query()
            ->where('Urutan', 0)
            ->where('Hasil', HasilLangkahOtomasi::Sukses->value)
            ->count());
    }

    public function test_penjadwal_hanya_mengantrekan_yang_jedanya_sudah_lewat(): void
    {
        $this->buatOtomasi('ProspekDibuat', [
            $this->langkahJeda(120),
            $this->langkahAksi('TambahTag', ['Tag' => ['nanti']]),
        ]);

        $this->buatProspek();
        app(PenjalanOtomasi::class)->jalankan(EksekusiOtomasiPemasaran::query()->firstOrFail());

        $this->artisan('pemasaran:proses-antrian-otomasi')->assertSuccessful();

        $this->assertSame(StatusEksekusiOtomasi::Tertunda, EksekusiOtomasiPemasaran::query()
            ->firstOrFail()->Status);
    }

    public function test_cap_eksekusi_membatasi_yang_diantrekan_sekali_jalan(): void
    {
        app(LayananKonfigurasiPemasaran::class)
            ->simpan(KatalogKonfigurasiPemasaran::OTOMASI_CAP_EKSEKUSI, 1);

        $this->buatOtomasi('ProspekDibuat', [$this->langkahAksi('TambahTag', ['Tag' => ['a']])]);
        $this->buatOtomasi('ProspekDibuat', [$this->langkahAksi('TambahTag', ['Tag' => ['b']])]);

        // Dibuat sebelum antrean dipalsukan agar observernya tidak ikut terhitung.
        $this->buatProspek();
        EksekusiOtomasiPemasaran::query()->update(['Status' => StatusEksekusiOtomasi::Gagal->value]);

        Queue::fake();
        $this->artisan('pemasaran:proses-antrian-otomasi')->assertSuccessful();

        Queue::assertPushed(ProsesOtomasiPemasaran::class, 1);
    }
}
