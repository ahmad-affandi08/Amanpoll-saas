<?php

declare(strict_types=1);

namespace Tests\Feature\Notifikasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Notifikasi\Domain\Enums\StatusNotifikasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\PreferensiNotifikasi;
use App\Domain\Notifikasi\Jobs\KirimNotifikasi;
use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\KanalPesan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananPlatform;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\ValueObjects\NomorWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** WhatsApp sebagai kanal ketiga notifikasi operasional (PRD 8.15). */
final class NotifikasiWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private const URL_FONNTE = 'https://api.fonnte.com/send';

    private const URL_META = 'https://graph.facebook.com/v21.0/1098765/messages';

    private Organisasi $organisasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
    }

    public function test_peristiwa_penting_dikirim_lewat_penyedia_whatsapp_aktif_sebagai_teks_berjudul(): void
    {
        $this->aturFonnte();
        $pengguna = $this->buatPengguna('0812-3456-7890');
        Http::preventStrayRequests();
        Http::fake([self::URL_FONNTE => Http::response(['status' => true, 'id' => ['111']])]);

        $this->kirim($pengguna, 'Keluhan.Baru');

        $this->assertSame(['InApp', 'WhatsApp'], $this->kanalTercatat($pengguna));
        $this->assertSame(StatusNotifikasi::Terkirim->value, $this->barisWhatsApp($pengguna)?->Status);
        Http::assertSent(fn (Request $permintaan): bool => $permintaan['target'] === '6281234567890'
            && $permintaan['message'] === "*Keluhan KLH-001*\nPompa ruang IGD bocor.");
    }

    public function test_tanpa_penyedia_whatsapp_aktif_baris_whatsapp_tidak_dibuat(): void
    {
        $this->aturFonnte(aktif: false);
        $pengguna = $this->buatPengguna('081234567890');

        $this->kirim($pengguna, 'Keluhan.Baru');

        $this->assertSame(['InApp'], $this->kanalTercatat($pengguna));
    }

    public function test_tanpa_nomor_yang_valid_baris_whatsapp_tidak_dibuat(): void
    {
        $this->aturFonnte();
        $tanpaNomor = $this->buatPengguna(null);
        $nomorAsal = $this->buatPengguna('belum ada');

        $this->kirim($tanpaNomor, 'Keluhan.Baru');
        $this->kirim($nomorAsal, 'Keluhan.Baru');

        $this->assertSame(['InApp'], $this->kanalTercatat($tanpaNomor));
        $this->assertSame(['InApp'], $this->kanalTercatat($nomorAsal));
    }

    public function test_preferensi_whatsapp_yang_dimatikan_dihormati(): void
    {
        $this->aturFonnte();
        $pengguna = $this->buatPengguna('081234567890');
        $this->aturPreferensi($pengguna, 'Keluhan.Baru', false);

        $this->kirim($pengguna, 'Keluhan.Baru');

        $this->assertSame(['InApp'], $this->kanalTercatat($pengguna));
    }

    /** Peristiwa ramai tidak masuk WhatsApp tanpa diminta, tetapi pengguna boleh menyalakannya sendiri. */
    public function test_peristiwa_ramai_hanya_lewat_whatsapp_bila_pengguna_menyalakannya(): void
    {
        $this->aturFonnte();
        $bawaan = $this->buatPengguna('081234567890');
        $memilih = $this->buatPengguna('081298765432');
        $this->aturPreferensi($memilih, 'Stok.MinimumTercapai', true);
        Http::preventStrayRequests();
        Http::fake([self::URL_FONNTE => Http::response(['status' => true, 'id' => ['222']])]);

        $this->kirim($bawaan, 'Stok.MinimumTercapai');
        $this->kirim($memilih, 'Stok.MinimumTercapai');

        $this->assertSame(['InApp'], $this->kanalTercatat($bawaan));
        $this->assertSame(['InApp', 'WhatsApp'], $this->kanalTercatat($memilih));
    }

    /** Daftar kanal eksplisit (mis. aturan eskalasi SLA) menang: WhatsApp hanya bila disebut. */
    public function test_daftar_kanal_eksplisit_tanpa_whatsapp_tidak_menambah_whatsapp(): void
    {
        $this->aturFonnte();
        $pengguna = $this->buatPengguna('081234567890');

        $this->kirim($pengguna, 'Keluhan.Sla.Terlewati', ['InApp']);

        $this->assertSame(['InApp'], $this->kanalTercatat($pengguna));
    }

    /** Pesan operasional ke staf bukan pemasaran: supresi WhatsApp pemasaran tidak menghalanginya. */
    public function test_supresi_pemasaran_tidak_menghalangi_notifikasi_operasional(): void
    {
        $this->aturFonnte();
        $pengguna = $this->buatPengguna('081234567890');
        app(LayananKonsen::class)->supresi('081234567890', AlasanSupresi::Unsubscribe, kanal: KanalPesan::WhatsApp);
        Http::preventStrayRequests();
        Http::fake([self::URL_FONNTE => Http::response(['status' => true, 'id' => ['333']])]);

        $this->kirim($pengguna, 'Keluhan.Baru');

        $this->assertSame(StatusNotifikasi::Terkirim->value, $this->barisWhatsApp($pengguna)?->Status);
        Http::assertSentCount(1);
    }

    public function test_meta_tanpa_template_notifikasi_gagal_dengan_alasan_jelas(): void
    {
        $this->aturMeta();
        $pengguna = $this->buatPengguna('081234567890');
        Http::preventStrayRequests();
        Http::fake();
        Queue::fake([KirimNotifikasi::class]);
        $this->kirim($pengguna, 'Keluhan.Baru');
        $baris = $this->barisWhatsApp($pengguna);
        $this->assertNotNull($baris);

        try {
            (new KirimNotifikasi($baris->Id))->handle();
            $this->fail('Seharusnya gagal.');
        } catch (AturanBisnisDilanggar) {
            // diharapkan
        }

        $this->assertStringContainsString('Template notifikasi staf', (string) $baris->refresh()->KesalahanTerakhir);
        $this->assertSame(StatusNotifikasi::Antri->value, $baris->Status);
        Http::assertNothingSent();
    }

    public function test_meta_mengirim_template_notifikasi_dengan_dua_parameter_yang_dirapikan(): void
    {
        $this->aturMeta(['TemplateNotifikasi' => 'notifikasi_staf', 'BahasaTemplateNotifikasi' => 'en_US']);
        $pengguna = $this->buatPengguna('+62 812 3456 7890');
        Http::preventStrayRequests();
        Http::fake([self::URL_META => Http::response(['messages' => [['id' => 'wamid.N1']]])]);

        $this->kirim($pengguna, 'Keluhan.Baru', isi: "Baris satu\n\nBaris dua ".str_repeat('x', 800));

        Http::assertSent(function (Request $permintaan): bool {
            $parameter = $permintaan['template']['components'][0]['parameters'];

            return $permintaan['to'] === '6281234567890'
                && $permintaan['template']['name'] === 'notifikasi_staf'
                && $permintaan['template']['language'] === ['code' => 'en_US']
                && $parameter[0]['text'] === 'Keluhan KLH-001'
                && str_starts_with($parameter[1]['text'], 'Baris satu Baris dua xxx')
                && mb_strlen($parameter[1]['text']) === 700
                && str_ends_with($parameter[1]['text'], '…');
        });
    }

    public function test_galat_penyedia_tidak_membocorkan_nomor_maupun_token_ke_kesalahan_terakhir(): void
    {
        $this->aturFonnte();
        $pengguna = $this->buatPengguna('081234567890');
        Http::preventStrayRequests();
        Http::fake([self::URL_FONNTE => Http::response([
            'status' => false,
            'reason' => 'target 6281234567890 invalid for token token-fonnte',
        ])]);
        Queue::fake([KirimNotifikasi::class]);
        $this->kirim($pengguna, 'Keluhan.Baru');
        $baris = $this->barisWhatsApp($pengguna);
        $this->assertNotNull($baris);

        try {
            (new KirimNotifikasi($baris->Id))->handle();
            $this->fail('Seharusnya gagal.');
        } catch (AturanBisnisDilanggar) {
            // diharapkan
        }

        $kesalahan = (string) $baris->refresh()->KesalahanTerakhir;
        $this->assertStringContainsString('Fonnte menolak pengiriman pesan', $kesalahan);
        $this->assertStringContainsString('*******7890', $kesalahan);
        $this->assertStringNotContainsString('6281234567890', $kesalahan);
        $this->assertStringNotContainsString('token-fonnte', $kesalahan);
    }

    public function test_halaman_preferensi_menampilkan_bawaan_whatsapp_dan_kesiapannya(): void
    {
        $pengguna = $this->buatPengguna(null);

        $respons = $this->actingAs($pengguna)->get('/notifikasi/preferensi/data')->assertOk();

        $whatsApp = collect($respons->json('data'))->where('Kanal', 'WhatsApp')->pluck('Aktif', 'JenisPeristiwa');
        $this->assertTrue($whatsApp['PerintahKerja.Ditugaskan']);
        $this->assertTrue($whatsApp['Keluhan.Sla.Terlewati']);
        $this->assertFalse($whatsApp['Stok.MinimumTercapai']);
        $respons->assertJson(['whatsapp' => ['PenyediaAktif' => false, 'NomorValid' => false]]);

        $this->aturFonnte();
        $pengguna->update(['Telepon' => '081234567890']);

        $this->actingAs($pengguna)->get('/notifikasi/preferensi/data')
            ->assertJson(['whatsapp' => ['PenyediaAktif' => true, 'NomorValid' => true]]);
    }

    #[DataProvider('bentukNomor')]
    public function test_nomor_telepon_dinormalkan_ke_format_internasional(?string $masukan, ?string $harapan): void
    {
        $this->assertSame($harapan, NomorWhatsApp::internasional($masukan));
    }

    /** @return array<string, array{0: string|null, 1: string|null}> */
    public static function bentukNomor(): array
    {
        return [
            'awalan nol' => ['0812-3456-7890', '6281234567890'],
            'awalan plus' => ['+62 812 3456 7890', '6281234567890'],
            'sudah baku' => ['6281234567890', '6281234567890'],
            'tanpa nol' => ['81234567890', '6281234567890'],
            'bertanda kurung' => ['(0812) 3456.7890', '6281234567890'],
            'kosong' => [null, null],
            'terlalu pendek' => ['0812', null],
            'terlalu panjang' => ['62812345678901234', null],
            'berhuruf' => ['hubungi admin', null],
        ];
    }

    private function aturFonnte(bool $aktif = true): void
    {
        $this->aturPenyedia('Fonnte', ['Token' => 'token-fonnte'], $aktif);
    }

    /** @param array<string, string> $tambahan */
    private function aturMeta(array $tambahan = []): void
    {
        $this->aturPenyedia('MetaCloud', [
            'PhoneNumberId' => '1098765',
            'WabaId' => '2024001',
            'AccessToken' => 'token-meta',
            ...$tambahan,
        ]);
    }

    /** @param array<string, string> $kredensial */
    private function aturPenyedia(string $kode, array $kredensial, bool $aktif = true): void
    {
        PenyediaLayananPlatform::create([
            'Kategori' => KategoriPenyediaLayanan::WhatsApp,
            'Kode' => $kode,
            'Aktif' => $aktif,
            'Utama' => true,
            'ModeUji' => false,
            'KredensialTerenkripsi' => $kredensial,
        ]);
    }

    private function buatPengguna(?string $telepon): Pengguna
    {
        return Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Teknisi '.uniqid(),
            'Email' => 'teknisi+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
            'Telepon' => $telepon,
        ]);
    }

    private function aturPreferensi(Pengguna $pengguna, string $jenisPeristiwa, bool $aktif): void
    {
        PreferensiNotifikasi::create([
            'PenggunaId' => $pengguna->Id,
            'JenisPeristiwa' => $jenisPeristiwa,
            'Kanal' => 'WhatsApp',
            'Aktif' => $aktif,
        ]);
    }

    /** @param list<string>|null $kanal */
    private function kirim(Pengguna $pengguna, string $jenisPeristiwa, ?array $kanal = null, string $isi = 'Pompa ruang IGD bocor.'): void
    {
        app(LayananNotifikasi::class)->kirim(
            penggunaId: $pengguna->Id,
            jenisPeristiwa: $jenisPeristiwa,
            isi: $isi,
            judul: 'Keluhan KLH-001',
            kanal: $kanal,
        );
    }

    /** @return list<string> */
    private function kanalTercatat(Pengguna $pengguna): array
    {
        return Notifikasi::query()
            ->where('PenggunaId', $pengguna->Id)
            ->orderBy('Kanal')
            ->pluck('Kanal')
            ->map(fn (mixed $kanal): string => (string) $kanal)
            ->all();
    }

    private function barisWhatsApp(Pengguna $pengguna): ?Notifikasi
    {
        return Notifikasi::query()->where('PenggunaId', $pengguna->Id)->where('Kanal', 'WhatsApp')->first();
    }
}
