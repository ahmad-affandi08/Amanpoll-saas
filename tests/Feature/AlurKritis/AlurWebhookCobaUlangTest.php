<?php

declare(strict_types=1);

namespace Tests\Feature\AlurKritis;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\IntegrasiAudit\Application\Services\LayananPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Domain\Enums\StatusKotakKeluarPeristiwa;
use App\Domain\IntegrasiAudit\Domain\Enums\StatusPengirimanPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KotakKeluarPeristiwa;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PengirimanPanggilanBalikWeb;
use App\Domain\Langganan\Domain\Enums\SiklusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\FiturPaket;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PembayaranLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Langganan\Infrastructure\Services\PenyediaPembayaranTransferManual;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Carbon\CarbonImmutable;
use Database\Seeders\FiturPaketSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as PermintaanHttp;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * FASE 26.03 — alur kritis coba ulang webhook, keluar maupun masuk.
 *
 * Keluar: peristiwa bisnis → kotak keluar → pengiriman gagal → dijadwalkan
 * ulang → dikirim ulang tepat waktu → berhasil, tanpa pengiriman kedua.
 * Masuk: penyedia pembayaran mengirim ulang peristiwa yang sama berkali-kali;
 * tagihan lunas dan langganan diperpanjang tepat satu kali.
 */
final class AlurWebhookCobaUlangTest extends TestCase
{
    use RefreshDatabase;

    private const KATA_SANDI = 'rahasia-panjang-01';

    private const URL_PENERIMA = 'https://penerima.test/amanpoll';

    private const RAHASIA_PENERIMA = 'rahasia-penerima-webhook-uji';

    private const RAHASIA_PEMBAYARAN = 'rahasia-webhook-pembayaran-uji';

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 03:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_webhook_keluar_yang_gagal_dikirim_ulang_sesuai_jadwal_tanpa_pengiriman_ganda(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'penerima.test/*' => Http::sequence()
                ->push('sedang pemeliharaan', 503)
                ->push('diterima', 200),
        ]);

        $konteks = $this->siapkanTenantIntegrasi();
        app(KonteksOrganisasi::class)->bersihkan();
        $this->masuk($konteks['organisasi']->Kode, $konteks['pengguna']->Email);

        // Langkah 1 — pengguna mendaftarkan endpoint penerima.
        $this->post(route('integrasi.panggilan-balik.store'), [
            'Nama' => 'Penerima ERP',
            'Url' => self::URL_PENERIMA,
            'Rahasia' => self::RAHASIA_PENERIMA,
            'Peristiwa' => ['Keluhan.*'],
            'Aktif' => true,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $webhook = PanggilanBalikWeb::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $konteks['organisasi']->Id)
            ->sole();

        // Langkah 2 — peristiwa bisnis: keluhan baru dilaporkan lewat formulir.
        $this->post(route('pemeliharaan.keluhan.store'), [
            'KategoriKeluhanId' => $konteks['kategori']->Id,
            'LokasiId' => $konteks['lokasi']->Id,
            'Judul' => 'AC ruang operasi mati',
            'Deskripsi' => 'Suhu ruangan naik sejak pagi.',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(1, Keluhan::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $konteks['organisasi']->Id)->count());
        $peristiwa = KotakKeluarPeristiwa::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $konteks['organisasi']->Id)
            ->where('NamaPeristiwa', 'Keluhan.Dibuat')
            ->sole();
        $this->assertSame(0, $this->jumlahPermintaanKePenerima(), 'Belum ada yang dikirim sebelum kotak keluar diproses.');

        // Langkah 3 — worker kotak keluar menerbitkan; endpoint sedang menolak.
        $this->artisan('outbox:proses')->assertSuccessful();

        $this->assertSame(StatusKotakKeluarPeristiwa::Selesai->value, $peristiwa->refresh()->Status);
        $pengiriman = PengirimanPanggilanBalikWeb::query()->withoutGlobalScopes()
            ->where('PanggilanBalikWebId', $webhook->Id)
            ->sole();
        $this->assertSame(StatusPengirimanPanggilanBalikWeb::Gagal->value, $pengiriman->Status);
        $this->assertSame(1, $pengiriman->Percobaan);
        $this->assertSame(503, $pengiriman->StatusHttp);
        $this->assertSame('2026-09-01 03:01:00', $pengiriman->JadwalCobaLagiPada?->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(1, $this->jumlahPermintaanKePenerima());

        // Worker kotak keluar yang jalan lagi tidak menerbitkan pengiriman kedua.
        $this->artisan('outbox:proses')->assertSuccessful();
        $this->assertSame(1, PengirimanPanggilanBalikWeb::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $konteks['organisasi']->Id)->count());
        $this->assertSame(1, $this->jumlahPermintaanKePenerima());

        // Langkah 4 — pengirim ulang yang jalan sebelum jadwalnya tidak menyentuh endpoint.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 03:00:30', 'UTC'));
        $this->artisan('panggilan-balik:kirim-ulang')->assertSuccessful();
        $this->assertSame(1, $this->jumlahPermintaanKePenerima());
        $this->assertSame(StatusPengirimanPanggilanBalikWeb::Gagal->value, $pengiriman->refresh()->Status);

        // Langkah 5 — setelah jadwalnya tiba, pengiriman yang sama diulang dan berhasil.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 03:01:05', 'UTC'));
        $this->artisan('panggilan-balik:kirim-ulang')->assertSuccessful();

        $pengiriman->refresh();
        $this->assertSame(StatusPengirimanPanggilanBalikWeb::Berhasil->value, $pengiriman->Status);
        $this->assertSame(2, $pengiriman->Percobaan);
        $this->assertSame(200, $pengiriman->StatusHttp);
        $this->assertNull($pengiriman->JadwalCobaLagiPada);
        $this->assertSame(2, $this->jumlahPermintaanKePenerima());

        // Kedua percobaan membawa identitas pengiriman dan peristiwa yang sama, ditandatangani sah,
        // sehingga penerima dapat membuang duplikatnya sendiri.
        $layanan = app(LayananPanggilanBalikWeb::class);
        foreach ($this->permintaanKePenerima() as $permintaan) {
            $this->assertTrue($permintaan->hasHeader(LayananPanggilanBalikWeb::HEADER_PENGIRIMAN, $pengiriman->Id));
            $this->assertTrue($permintaan->hasHeader(
                LayananPanggilanBalikWeb::HEADER_TANDA_TANGAN,
                $layanan->tandaTangan($permintaan->body(), self::RAHASIA_PENERIMA),
            ));
            $this->assertSame($peristiwa->Id, $permintaan->data()['IdPeristiwa'] ?? null);
        }

        // Langkah 6 — pengiriman yang sudah berhasil tidak pernah dikirim lagi.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 04:00:00', 'UTC'));
        $this->artisan('panggilan-balik:kirim-ulang')->assertSuccessful();
        $this->artisan('outbox:proses')->assertSuccessful();
        $this->assertSame(2, $this->jumlahPermintaanKePenerima());
        $this->assertSame(1, PengirimanPanggilanBalikWeb::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $konteks['organisasi']->Id)->count());

        // Riwayat pengiriman yang dilihat pengguna memuat satu pengiriman dengan dua percobaan.
        $this->get(route('integrasi.panggilan-balik.pengiriman', $webhook->Id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Integrasi/Pengiriman')
                ->has('pengiriman.data', 1)
                ->where('pengiriman.data.0.Status', StatusPengirimanPanggilanBalikWeb::Berhasil->value)
                ->where('pengiriman.data.0.Percobaan', 2)
                ->where('pengiriman.meta.last_page', 1)
                ->etc());
    }

    public function test_webhook_pembayaran_yang_dikirim_ulang_penyedia_hanya_melunasi_sekali(): void
    {
        config(['amanpoll.langganan.rahasia_webhook' => self::RAHASIA_PEMBAYARAN]);
        $langganan = $this->siapkanLanggananBerbayar();
        $organisasiId = (string) $langganan->OrganisasiId;
        $admin = AdminPlatform::create([
            'Nama' => 'Admin Penagihan',
            'Email' => 'penagihan@platform.test',
            'KataSandi' => self::KATA_SANDI,
            'Status' => 'Aktif',
        ]);
        app(KonteksOrganisasi::class)->bersihkan();

        // Langkah 1 — admin platform masuk lalu menerbitkan tagihan periode berikutnya.
        $this->post(route('adminPlatform.login.store'), [
            'Email' => $admin->Email,
            'KataSandi' => self::KATA_SANDI,
        ])->assertSessionHasNoErrors()->assertRedirect(route('adminPlatform.paket.index'));

        $this->post(route('adminPlatform.langganan.tagihan', $langganan->Id))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $tagihan = TagihanLangganan::query()->withoutGlobalScopes()
            ->where('LanggananId', $langganan->Id)
            ->sole();
        $this->assertSame(StatusTagihanLangganan::BelumDibayar->value, $tagihan->Status);
        $muatan = [
            'IdPeristiwa' => 'evt-transfer-0001',
            'NomorTagihan' => (string) $tagihan->Nomor,
            'Jumlah' => (float) $tagihan->Total,
            'ReferensiEksternal' => 'TRF-778899',
        ];

        // Langkah 2 — percobaan pertama penyedia ditandatangani dengan rahasia yang keliru: ditolak tanpa bekas.
        $this->kirimWebhookPembayaran($muatan, 'rahasia-yang-sudah-dirotasi')->assertForbidden();

        $this->assertSame(0, PembayaranLangganan::query()->withoutGlobalScopes()->count());
        $this->assertSame(StatusTagihanLangganan::BelumDibayar->value, $tagihan->refresh()->Status);
        $this->assertSame('2026-09-15', $this->berakhirPada($langganan));

        // Langkah 3 — penyedia mencoba lagi dengan tanda tangan sah: tagihan lunas, langganan diperpanjang.
        $diterima = $this->kirimWebhookPembayaran($muatan, self::RAHASIA_PEMBAYARAN)
            ->assertOk()
            ->assertJsonPath('Diterima', true);
        $pembayaranId = $diterima->json('PembayaranId');

        $this->assertSame(1, PembayaranLangganan::query()->withoutGlobalScopes()->count());
        $this->assertSame(StatusTagihanLangganan::Lunas->value, $tagihan->refresh()->Status);
        $this->assertSame('2026-10-15', $this->berakhirPada($langganan));
        $this->assertSame(1, $this->jumlahAuditPembayaran());
        $this->assertSame(1, $this->jumlahPeristiwaPembayaranBerhasil($organisasiId));

        // Langkah 4 — balasan dianggap hilang, penyedia mengirim ulang peristiwa yang sama dua kali.
        foreach ([1, 2] as $_) {
            $this->kirimWebhookPembayaran($muatan, self::RAHASIA_PEMBAYARAN)
                ->assertOk()
                ->assertJsonPath('PembayaranId', $pembayaranId);
        }

        $this->assertSame(1, PembayaranLangganan::query()->withoutGlobalScopes()->count(), 'Satu peristiwa penyedia hanya boleh menjadi satu pembayaran.');
        $this->assertSame(StatusTagihanLangganan::Lunas->value, $tagihan->refresh()->Status);
        $this->assertSame('2026-10-15', $this->berakhirPada($langganan), 'Langganan tidak boleh diperpanjang lagi oleh pengiriman ulang.');
        $this->assertSame(1, $this->jumlahAuditPembayaran());
        $this->assertSame(1, $this->jumlahPeristiwaPembayaranBerhasil($organisasiId));
    }

    private function masuk(string $kodeOrganisasi, string $email): void
    {
        $this->post(route('login.store'), [
            'KodeOrganisasi' => $kodeOrganisasi,
            'Email' => $email,
            'KataSandi' => self::KATA_SANDI,
        ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
    }

    /** @return list<PermintaanHttp> */
    private function permintaanKePenerima(): array
    {
        return Http::recorded(fn (PermintaanHttp $permintaan): bool => $permintaan->url() === self::URL_PENERIMA)
            ->map(fn (array $pasangan): PermintaanHttp => $pasangan[0])
            ->values()
            ->all();
    }

    private function jumlahPermintaanKePenerima(): int
    {
        return count($this->permintaanKePenerima());
    }

    /** @param array<string, mixed> $muatan */
    private function kirimWebhookPembayaran(array $muatan, string $rahasia): TestResponse
    {
        $penyedia = app(PenyediaPembayaranTransferManual::class);

        return $this->postJson(
            route('langganan.webhook.pembayaran', ['penyedia' => PenyediaPembayaranTransferManual::KODE]),
            $muatan,
            ['X-Amanpoll-Tanda-Tangan' => hash_hmac('sha256', $penyedia->muatanKanonik($muatan), $rahasia)],
        );
    }

    private function berakhirPada(Langganan $langganan): ?string
    {
        return Langganan::query()->withoutGlobalScopes()->findOrFail($langganan->Id)->BerakhirPada?->toDateString();
    }

    private function jumlahAuditPembayaran(): int
    {
        return CatatanAudit::query()->withoutGlobalScopes()
            ->where('Aksi', 'PembayaranLangganan.Dicatat')
            ->count();
    }

    private function jumlahPeristiwaPembayaranBerhasil(string $organisasiId): int
    {
        return EventPemasaran::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasiId)
            ->where('Jenis', KatalogPeristiwaPemasaran::PEMBAYARAN_BERHASIL)
            ->count();
    }

    /**
     * @return array{organisasi: Organisasi, pengguna: Pengguna, lokasi: Lokasi, kategori: KategoriKeluhan}
     */
    private function siapkanTenantIntegrasi(): array
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-WEBHOOK', 'Nama' => 'RS Webhook', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Petugas Integrasi',
            'Email' => 'integrasi@webhook.test',
            'KataSandi' => self::KATA_SANDI,
            'Status' => 'Aktif',
        ]);
        $peran = Peran::create(['OrganisasiId' => $organisasi->Id, 'Kode' => 'PERAN-INTEGRASI', 'Nama' => 'Integrasi']);
        foreach (['Integrasi.Kelola', 'Keluhan.Kelola'] as $kode) {
            $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Integrasi']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        }
        PenggunaPeran::create(['OrganisasiId' => $organisasi->Id, 'PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        NomorDokumen::create([
            'OrganisasiId' => $organisasi->Id,
            'JenisDokumen' => 'Keluhan',
            'Awalan' => 'KLH',
            'FormatNomor' => '{Awalan}-{Nomor:5}',
            'NomorTerakhir' => 0,
            'ResetPeriode' => 'TidakAda',
        ]);
        $lokasi = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Kode' => 'LOK-OK', 'Nama' => 'Kamar Operasi', 'Status' => 'Aktif']);
        $kategori = KategoriKeluhan::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'KAT-UMUM',
            'Nama' => 'Gangguan Umum',
            'PrioritasBawaan' => 'Normal',
            'Aktif' => true,
        ]);

        return ['organisasi' => $organisasi, 'pengguna' => $pengguna, 'lokasi' => $lokasi, 'kategori' => $kategori];
    }

    private function siapkanLanggananBerbayar(): Langganan
    {
        $this->seed(FiturPaketSeeder::class);

        $organisasi = Organisasi::create(['Kode' => 'ORG-BAYAR', 'Nama' => 'RS Pembayar', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $paket = PaketLangganan::create([
            'Kode' => 'PKT-ALUR-BAYAR',
            'Nama' => 'Paket Alur Bayar',
            'HargaBulanan' => 500_000,
            'HargaTahunan' => 5_000_000,
            'MataUang' => 'IDR',
            'Aktif' => true,
        ]);
        foreach (KatalogFitur::kode() as $kode) {
            PaketFitur::create([
                'PaketLanggananId' => $paket->Id,
                'FiturPaketId' => FiturPaket::query()->where('Kode', $kode)->firstOrFail()->Id,
                'Diizinkan' => true,
                'BatasNilai' => null,
            ]);
        }

        return Langganan::create([
            'OrganisasiId' => $organisasi->Id,
            'PaketLanggananId' => $paket->Id,
            'Siklus' => SiklusLangganan::Bulanan->value,
            'MulaiPada' => '2026-08-15',
            'BerakhirPada' => '2026-09-15',
            'Status' => StatusLangganan::Aktif->value,
        ]);
    }
}
