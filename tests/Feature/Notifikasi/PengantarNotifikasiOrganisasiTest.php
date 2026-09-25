<?php

declare(strict_types=1);

namespace Tests\Feature\Notifikasi;

use App\Domain\Langganan\Application\Services\PenjagaBatasLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Notifikasi\Application\Services\PemberitahuLayananPengirim;
use App\Domain\Notifikasi\Application\Services\PenghitungKuotaWhatsApp;
use App\Domain\Notifikasi\Domain\Enums\StatusNotifikasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananPlatform;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Domain\Langganan\KasusLangganan;

/**
 * Notifikasi berangkat lewat email dan nomor WhatsApp milik organisasi, dengan kuota
 * WhatsApp bawaan untuk yang memakai nomor Amanpoll (PRD 8.23).
 */
final class PengantarNotifikasiOrganisasiTest extends KasusLangganan
{
    private const URL_BREVO = 'https://api.brevo.com/v3/smtp/email';

    private const URL_FONNTE = 'https://api.fonnte.com/send';

    private const KUNCI_BREVO_PLATFORM = 'xkeysib-platform-1111';

    private const KUNCI_BREVO_ORGANISASI = 'xkeysib-organisasi-2222';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default' => 'amanpoll',
            'mail.from.address' => 'hello@amanpoll.test',
            'mail.from.name' => 'Amanpoll',
            'amanpoll.email.mailer_cadangan' => 'array',
        ]);
        Http::preventStrayRequests();
    }

    public function test_email_notifikasi_berangkat_lewat_email_milik_organisasi(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());
        $this->brevoPlatform();
        $this->brevoOrganisasi();
        Http::fake([self::URL_BREVO => Http::response(['messageId' => '<id@brevo>'], 201)]);

        $this->kirimEmail($this->buatPenerima());

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $permintaan): bool => $permintaan->header('api-key') === [self::KUNCI_BREVO_ORGANISASI]
            && $permintaan['sender'] === ['email' => 'ipsrs@rs-sehat.test', 'name' => 'IPSRS RS Sehat']
            && ! str_contains($permintaan->body(), 'X-Amanpoll-Organisasi'));
        $this->assertNotNull(PenyediaLayananOrganisasi::query()->value('TerakhirBerhasilPada'));
    }

    public function test_email_organisasi_yang_gagal_dialihkan_ke_platform_dan_pengelola_diberi_tahu_sekali(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());
        $this->brevoPlatform();
        $this->brevoOrganisasi();
        $pengelola = $this->buatPengguna(['Integrasi.Kelola']);
        Http::fake(fn (Request $permintaan) => $permintaan->header('api-key') === [self::KUNCI_BREVO_ORGANISASI]
            ? Http::response(['message' => 'Key not found: '.self::KUNCI_BREVO_ORGANISASI], 401)
            : Http::response(['messageId' => '<id@brevo>'], 201));

        $this->kirimEmail($this->buatPenerima());
        $this->kirimEmail($this->buatPenerima());

        $keyDipakai = Http::recorded()->map(fn (array $pasangan): string => $pasangan[0]->header('api-key')[0] ?? '')->all();
        $this->assertSame(
            [self::KUNCI_BREVO_ORGANISASI, self::KUNCI_BREVO_PLATFORM, self::KUNCI_BREVO_ORGANISASI, self::KUNCI_BREVO_PLATFORM],
            array_values($keyDipakai),
        );

        $baris = PenyediaLayananOrganisasi::query()->firstOrFail();
        $this->assertTrue($baris->sedangBermasalah());
        $this->assertStringNotContainsString(self::KUNCI_BREVO_ORGANISASI, (string) $baris->GalatTerakhir);
        $this->assertSame(1, $this->kabarUntuk($pengelola, PemberitahuLayananPengirim::PERISTIWA_PENYEDIA_BERMASALAH));
    }

    public function test_lewat_email_platform_nama_pengirim_menyebut_organisasi_dan_balasan_ke_email_organisasi(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());
        $this->organisasi->forceFill(['Nama' => 'RS Sehat', 'Email' => 'humas@rs-sehat.test'])->save();
        $this->brevoPlatform();
        Http::fake([self::URL_BREVO => Http::response(['messageId' => '<id@brevo>'], 201)]);

        $this->kirimEmail($this->buatPenerima());

        Http::assertSent(fn (Request $permintaan): bool => $permintaan['sender'] === ['email' => 'no-reply@amanpoll.test', 'name' => 'RS Sehat via Amanpoll']
            && $permintaan['replyTo'] === ['email' => 'humas@rs-sehat.test', 'name' => 'RS Sehat']);
    }

    public function test_email_tanpa_penanda_organisasi_selalu_lewat_platform(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());
        $this->brevoPlatform();
        $this->brevoOrganisasi();
        Http::fake([self::URL_BREVO => Http::response(['messageId' => '<id@brevo>'], 201)]);

        Mail::raw('Tautan reset kata sandi.', fn ($surat) => $surat->to('budi@tujuan.test')->subject('Reset kata sandi'));

        Http::assertSent(fn (Request $permintaan): bool => $permintaan->header('api-key') === [self::KUNCI_BREVO_PLATFORM]
            && $permintaan['sender'] === ['email' => 'no-reply@amanpoll.test', 'name' => 'Amanpoll']);
    }

    public function test_paket_tanpa_fitur_membuat_penyedia_organisasi_diabaikan(): void
    {
        $this->buatLangganan($this->buatPaket('Dasar'));
        $this->brevoPlatform();
        $this->brevoOrganisasi();
        $this->fonnteOrganisasi();
        Http::fake([self::URL_BREVO => Http::response(['messageId' => '<id@brevo>'], 201)]);

        $this->kirimEmail($this->buatPenerima());

        Http::assertSent(fn (Request $permintaan): bool => $permintaan->header('api-key') === [self::KUNCI_BREVO_PLATFORM]);
        Http::assertNotSent(fn (Request $permintaan): bool => $permintaan->header('api-key') === [self::KUNCI_BREVO_ORGANISASI]);
    }

    public function test_whatsapp_lewat_nomor_organisasi_tidak_memakan_kuota_bawaan(): void
    {
        $this->buatLangganan($this->buatPaket('Berkuota', [
            KatalogFitur::LAYANAN_PENYEDIA_SENDIRI => ['Diizinkan' => true],
            KatalogFitur::BATAS_WHATSAPP_BULANAN => ['Diizinkan' => true, 'BatasNilai' => 1],
        ]));
        $this->fonntePlatform();
        $this->fonnteOrganisasi();
        Http::fake([self::URL_FONNTE => Http::response(['status' => true, 'id' => ['1']])]);

        $this->kirimWhatsApp($this->buatPenerima());
        $this->kirimWhatsApp($this->buatPenerima());

        Http::assertSentCount(2);
        Http::assertNotSent(fn (Request $permintaan): bool => $permintaan->header('Authorization') === ['token-platform']);
        $this->assertSame(['Organisasi', 'Organisasi'], $this->sumberWhatsApp());
        $this->assertSame(0, app(PenghitungKuotaWhatsApp::class)->terpakai($this->organisasi->Id));
    }

    public function test_kuota_bawaan_menahan_whatsapp_berikutnya_dan_pengelola_diberi_tahu_sekali(): void
    {
        $this->buatLangganan($this->buatPaket('Berkuota', [
            KatalogFitur::BATAS_WHATSAPP_BULANAN => ['Diizinkan' => true, 'BatasNilai' => 2],
        ]));
        $this->fonntePlatform();
        $pengelola = $this->buatPengguna(['Integrasi.Kelola']);
        Http::fake([self::URL_FONNTE => Http::response(['status' => true, 'id' => ['1']])]);

        foreach (range(1, 4) as $ke) {
            $this->kirimWhatsApp($this->buatPenerima());
        }

        Http::assertSentCount(2);
        $this->assertSame(['Platform', 'Platform'], $this->sumberWhatsApp());
        $this->assertSame(1, $this->kabarUntuk($pengelola, PemberitahuLayananPengirim::PERISTIWA_KUOTA_WHATSAPP_HABIS));
        $this->assertSame(2, app(PenjagaBatasLangganan::class)->pemakaian()[KatalogFitur::BATAS_WHATSAPP_BULANAN]);
    }

    public function test_kiriman_gagal_dan_bulan_lalu_tidak_memakan_kuota_dengan_batas_bulan_di_zona_organisasi(): void
    {
        $this->organisasi->forceFill(['ZonaWaktu' => 'Asia/Jakarta'])->save();
        $penerima = $this->buatPenerima();

        // 15 Juni 2026 09.00 UTC; bulan Juni WIB dimulai 31 Mei pukul 17.00 UTC.
        $this->barisWhatsApp($penerima, '2026-05-31 16:59:00', StatusNotifikasi::Terkirim);
        $this->barisWhatsApp($penerima, '2026-05-31 17:00:00', StatusNotifikasi::Terkirim);
        $this->barisWhatsApp($penerima, '2026-06-10 08:00:00', StatusNotifikasi::Gagal);
        $this->barisWhatsApp($penerima, '2026-06-14 08:00:00', StatusNotifikasi::Antri);
        $this->barisWhatsApp($penerima, '2026-06-14 09:00:00', StatusNotifikasi::Terkirim, 'Organisasi');

        $this->assertSame(2, app(PenghitungKuotaWhatsApp::class)->terpakai($this->organisasi->Id));
    }

    public function test_nomor_organisasi_yang_gagal_tidak_dialihkan_ke_nomor_amanpoll(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());
        $this->fonntePlatform();
        $this->fonnteOrganisasi();
        $pengelola = $this->buatPengguna(['Integrasi.Kelola']);
        $penerima = $this->buatPenerima();
        Http::fake([self::URL_FONNTE => Http::response(['status' => false, 'reason' => 'device disconnected'])]);

        try {
            $this->kirimWhatsApp($penerima);
            $this->fail('Kegagalan nomor organisasi seharusnya diteruskan ke antrian untuk dicoba ulang.');
        } catch (AturanBisnisDilanggar) {
            // Antrian sinkron meneruskan galat pekerjaan; di produksi pekerjaan dicoba ulang lalu ditandai gagal.
        }

        Http::assertNotSent(fn (Request $permintaan): bool => $permintaan->header('Authorization') === ['token-platform']);
        $this->assertTrue(PenyediaLayananOrganisasi::query()->firstOrFail()->sedangBermasalah());
        $this->assertSame(1, $this->kabarUntuk($pengelola, PemberitahuLayananPengirim::PERISTIWA_PENYEDIA_BERMASALAH));
        $this->assertSame('Organisasi', Notifikasi::query()->where('Kanal', 'WhatsApp')->value('SumberPenyedia'));
    }

    public function test_paket_tanpa_whatsapp_bawaan_tidak_membuat_baris_whatsapp(): void
    {
        $this->buatLangganan($this->buatPaket('Tanpa WhatsApp', [
            KatalogFitur::BATAS_WHATSAPP_BULANAN => ['Diizinkan' => false],
        ]));
        $this->fonntePlatform();

        $this->kirimWhatsApp($this->buatPenerima());

        $this->assertSame([], $this->sumberWhatsApp());
        Http::assertNothingSent();
    }

    private function brevoPlatform(): void
    {
        PenyediaLayananPlatform::create([
            'Kategori' => KategoriPenyediaLayanan::Email,
            'Kode' => 'Brevo',
            'Aktif' => true,
            'Utama' => true,
            'ModeUji' => false,
            'KredensialTerenkripsi' => ['KunciApi' => self::KUNCI_BREVO_PLATFORM, 'AlamatPengirim' => 'no-reply@amanpoll.test', 'NamaPengirim' => 'Amanpoll'],
        ]);
    }

    private function brevoOrganisasi(): void
    {
        PenyediaLayananOrganisasi::create([
            'Kategori' => KategoriPenyediaLayanan::Email,
            'Kode' => 'Brevo',
            'Aktif' => true,
            'KredensialTerenkripsi' => ['KunciApi' => self::KUNCI_BREVO_ORGANISASI, 'AlamatPengirim' => 'ipsrs@rs-sehat.test', 'NamaPengirim' => 'IPSRS RS Sehat'],
        ]);
    }

    private function fonntePlatform(): void
    {
        PenyediaLayananPlatform::create([
            'Kategori' => KategoriPenyediaLayanan::WhatsApp,
            'Kode' => 'Fonnte',
            'Aktif' => true,
            'Utama' => true,
            'ModeUji' => false,
            'KredensialTerenkripsi' => ['Token' => 'token-platform'],
        ]);
    }

    private function fonnteOrganisasi(): void
    {
        PenyediaLayananOrganisasi::create([
            'Kategori' => KategoriPenyediaLayanan::WhatsApp,
            'Kode' => 'Fonnte',
            'Aktif' => true,
            'KredensialTerenkripsi' => ['Token' => 'token-organisasi'],
        ]);
    }

    private function buatPenerima(): Pengguna
    {
        $pengguna = $this->buatPengguna();
        $pengguna->forceFill(['Telepon' => '08123456'.random_int(1000, 9999)])->save();

        return $pengguna;
    }

    private function kirimEmail(Pengguna $penerima): void
    {
        app(LayananNotifikasi::class)->kirim($penerima->Id, 'Keluhan.Baru', 'Pompa ruang IGD bocor.', 'Keluhan KLH-001', kanal: ['Email']);
    }

    private function kirimWhatsApp(Pengguna $penerima): void
    {
        app(LayananNotifikasi::class)->kirim($penerima->Id, 'Keluhan.Baru', 'Pompa ruang IGD bocor.', 'Keluhan KLH-001', kanal: ['WhatsApp']);
    }

    private function barisWhatsApp(Pengguna $penerima, string $jadwal, StatusNotifikasi $status, string $sumber = 'Platform'): void
    {
        Notifikasi::create([
            'PenggunaId' => $penerima->Id,
            'Kanal' => 'WhatsApp',
            'SumberPenyedia' => $sumber,
            'JenisPeristiwa' => 'Keluhan.Baru',
            'Isi' => 'Isi.',
            'Status' => $status->value,
            'JadwalKirimPada' => $jadwal,
        ]);
    }

    /** @return list<string> */
    private function sumberWhatsApp(): array
    {
        return Notifikasi::query()->where('Kanal', 'WhatsApp')->orderBy('JadwalKirimPada')->pluck('SumberPenyedia')
            ->map(fn (mixed $sumber): string => (string) $sumber)->values()->all();
    }

    private function kabarUntuk(Pengguna $pengelola, string $peristiwa): int
    {
        return Notifikasi::query()->where('PenggunaId', $pengelola->Id)->where('JenisPeristiwa', $peristiwa)->count();
    }
}
