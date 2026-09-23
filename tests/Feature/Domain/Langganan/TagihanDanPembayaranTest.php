<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Langganan;

use App\Domain\Langganan\Application\Actions\TerbitkanTagihanLangganan;
use App\Domain\Langganan\Application\Services\LayananKebijakanTenggang;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PembayaranLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Langganan\Infrastructure\Services\PenyediaPembayaranTransferManual;
use Carbon\CarbonImmutable;
use Illuminate\Testing\TestResponse;

/** Tagihan, pembayaran, webhook idempoten, dan rekonsiliasi (22.06). */
final class TagihanDanPembayaranTest extends KasusLangganan
{
    private const RAHASIA = 'rahasia-webhook-uji';

    protected function setUp(): void
    {
        parent::setUp();
        config(['amanpoll.langganan.rahasia_webhook' => self::RAHASIA]);
    }

    public function test_tagihan_diterbitkan_untuk_periode_setelah_periode_berjalan(): void
    {
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, berakhirPada: '2026-07-15');

        $tagihan = app(TerbitkanTagihanLangganan::class)->jalankan($langganan);

        $this->assertSame('2026-07-16', $tagihan->PeriodeMulai->toDateString());
        $this->assertSame('2026-08-15', $tagihan->PeriodeSelesai->toDateString());
        $this->assertSame(500_000.0, (float) $tagihan->Total);
        $this->assertSame(StatusTagihanLangganan::BelumDibayar->value, $tagihan->Status);
    }

    public function test_menjalankan_penagihan_dua_kali_tidak_menerbitkan_tagihan_ganda(): void
    {
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, berakhirPada: '2026-07-15');

        $pertama = app(TerbitkanTagihanLangganan::class)->jalankan($langganan);
        $kedua = app(TerbitkanTagihanLangganan::class)->jalankan($langganan);

        $this->assertSame($pertama->Id, $kedua->Id);
        $this->assertSame(1, TagihanLangganan::query()->withoutGlobalScopes()
            ->where('LanggananId', $langganan->Id)->count());
    }

    public function test_pajak_dihitung_dari_konfigurasi(): void
    {
        config(['amanpoll.langganan.pajak_persen' => 11]);
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, berakhirPada: '2026-07-15');

        $tagihan = app(TerbitkanTagihanLangganan::class)->jalankan($langganan);

        $this->assertSame(55_000.0, (float) $tagihan->Pajak);
        $this->assertSame(555_000.0, (float) $tagihan->Total);
    }

    public function test_webhook_melunasi_tagihan_dan_memperpanjang_langganan(): void
    {
        $tagihan = $this->terbitkanTagihan();
        $langganan = $tagihan->langganan;

        $this->kirimWebhook([
            'IdPeristiwa' => 'evt-001',
            'NomorTagihan' => (string) $tagihan->Nomor,
            'Jumlah' => 500_000,
        ])->assertOk();

        $this->assertSame(
            StatusTagihanLangganan::Lunas->value,
            (string) $tagihan->refresh()->Status,
        );
        // Pelunasan adalah satu-satunya peristiwa yang memperpanjang langganan.
        $this->assertSame('2026-08-15', $langganan->refresh()->BerakhirPada->toDateString());
    }

    public function test_webhook_yang_sama_dikirim_ulang_tidak_menggandakan_pembayaran(): void
    {
        $tagihan = $this->terbitkanTagihan();

        $muatan = [
            'IdPeristiwa' => 'evt-ulang',
            'NomorTagihan' => (string) $tagihan->Nomor,
            'Jumlah' => 500_000,
        ];

        $this->kirimWebhook($muatan)->assertOk();
        $this->kirimWebhook($muatan)->assertOk();
        $this->kirimWebhook($muatan)->assertOk();

        $this->assertSame(
            1,
            PembayaranLangganan::query()->withoutGlobalScopes()
                ->where('IdPeristiwaPenyedia', 'evt-ulang')->count(),
            'Satu peristiwa penyedia hanya boleh menjadi satu pembayaran.',
        );
        // Langganan pun tidak boleh ikut diperpanjang berkali-kali.
        $this->assertSame(
            '2026-08-15',
            $tagihan->refresh()->langganan->BerakhirPada->toDateString(),
        );
    }

    public function test_webhook_tanpa_tanda_tangan_yang_sah_ditolak(): void
    {
        $tagihan = $this->terbitkanTagihan();

        $this->postJson(
            route('langganan.webhook.pembayaran', ['penyedia' => PenyediaPembayaranTransferManual::KODE]),
            ['IdPeristiwa' => 'evt-palsu', 'NomorTagihan' => (string) $tagihan->Nomor, 'Jumlah' => 500_000],
            ['X-Amanpoll-Tanda-Tangan' => 'tanda-tangan-karangan'],
        )->assertForbidden();

        $this->assertSame(0, PembayaranLangganan::query()->withoutGlobalScopes()->count());
    }

    public function test_webhook_ditolak_bila_rahasia_belum_dikonfigurasi(): void
    {
        // Gagal-tertutup: endpoint ini dapat melunasi tagihan siapa pun.
        config(['amanpoll.langganan.rahasia_webhook' => '']);
        $tagihan = $this->terbitkanTagihan();

        $this->kirimWebhook([
            'IdPeristiwa' => 'evt-tanpa-rahasia',
            'NomorTagihan' => (string) $tagihan->Nomor,
            'Jumlah' => 500_000,
        ])->assertForbidden();
    }

    public function test_pembayaran_sebagian_tidak_melunasi_dan_tidak_memperpanjang(): void
    {
        $tagihan = $this->terbitkanTagihan();
        $akhirSebelum = $tagihan->langganan->BerakhirPada->toDateString();

        $this->kirimWebhook([
            'IdPeristiwa' => 'evt-separuh',
            'NomorTagihan' => (string) $tagihan->Nomor,
            'Jumlah' => 200_000,
        ])->assertOk();

        $this->assertSame(
            StatusTagihanLangganan::SebagianDibayar->value,
            (string) $tagihan->refresh()->Status,
        );
        $this->assertSame($akhirSebelum, $tagihan->langganan->refresh()->BerakhirPada->toDateString());
    }

    public function test_pembayaran_gagal_tidak_mengurangi_tagihan(): void
    {
        $tagihan = $this->terbitkanTagihan();

        $this->kirimWebhook([
            'IdPeristiwa' => 'evt-gagal',
            'NomorTagihan' => (string) $tagihan->Nomor,
            'Jumlah' => 500_000,
            'Status' => StatusPembayaranLangganan::Gagal->value,
        ])->assertOk();

        $this->assertSame(
            StatusTagihanLangganan::BelumDibayar->value,
            (string) $tagihan->refresh()->Status,
        );
    }

    public function test_rekonsiliasi_memperbaiki_status_tagihan_yang_tertinggal(): void
    {
        $tagihan = $this->terbitkanTagihan();

        // Pembayaran tercatat di luar alur webhook (mis.
        PembayaranLangganan::create([
            'OrganisasiId' => $this->organisasi->Id,
            'TagihanLanggananId' => $tagihan->Id,
            'PenyediaPembayaran' => PenyediaPembayaranTransferManual::KODE,
            'IdPeristiwaPenyedia' => 'evt-manual',
            'Jumlah' => 500_000,
            'Status' => StatusPembayaranLangganan::Berhasil->value,
            'DibayarPada' => now(),
        ]);

        $this->artisan('langganan:rekonsiliasi')->assertSuccessful();

        $this->assertSame(StatusTagihanLangganan::Lunas->value, (string) $tagihan->refresh()->Status);
    }

    public function test_rekonsiliasi_melaporkan_pembayaran_berlebih_sebagai_kegagalan(): void
    {
        $tagihan = $this->terbitkanTagihan();

        PembayaranLangganan::create([
            'OrganisasiId' => $this->organisasi->Id,
            'TagihanLanggananId' => $tagihan->Id,
            'PenyediaPembayaran' => PenyediaPembayaranTransferManual::KODE,
            'IdPeristiwaPenyedia' => 'evt-berlebih',
            'Jumlah' => 900_000,
            'Status' => StatusPembayaranLangganan::Berhasil->value,
            'DibayarPada' => now(),
        ]);

        // Selisih menuntut keputusan manusia, jadi dilaporkan sebagai gagal.
        $this->artisan('langganan:rekonsiliasi')->assertFailed();
    }

    public function test_tenant_mendapat_instruksi_pembayaran_dari_penyedia_terpasang(): void
    {
        $tagihan = $this->terbitkanTagihan();
        $pengguna = $this->buatPengguna(['Pengaturan.Kelola']);

        $this->actingAs($pengguna)
            ->post(route('langganan.tagihan.bayar', $tagihan))
            ->assertRedirect()
            ->assertSessionHas('instruksiPembayaran');
    }

    private function terbitkanTagihan(): TagihanLangganan
    {
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, StatusLangganan::Aktif, berakhirPada: '2026-07-15');

        return app(TerbitkanTagihanLangganan::class)->jalankan($langganan);
    }

    /** @param array<string, mixed> $muatan */
    private function kirimWebhook(array $muatan): TestResponse
    {
        $penyedia = app(PenyediaPembayaranTransferManual::class);
        $tandaTangan = hash_hmac('sha256', $penyedia->muatanKanonik($muatan), self::RAHASIA);

        return $this->postJson(
            route('langganan.webhook.pembayaran', ['penyedia' => PenyediaPembayaranTransferManual::KODE]),
            $muatan,
            ['X-Amanpoll-Tanda-Tangan' => $tandaTangan],
        );
    }

    /**
     * Periode dan jatuh tempo tagihan adalah tanggal di kalender tenant.
     *
     * Tagihan yang diterbitkan 01:30 WIB tanggal 22 untuk langganan tanpa
     * tanggal akhir dimulai tanggal 22, bukan tanggal 21 menurut UTC.
     */
    public function test_periode_tagihan_dimulai_pada_hari_ini_tenant(): void
    {
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket);
        $langganan->update(['BerakhirPada' => null]);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-21 18:30:00', 'UTC'));
        $tagihan = app(TerbitkanTagihanLangganan::class)->jalankan($langganan->refresh());

        $this->assertSame('2026-09-22', $tagihan->PeriodeMulai->toDateString());
        $this->assertSame(
            CarbonImmutable::parse('2026-09-22')->addDays(app(LayananKebijakanTenggang::class)->hariJatuhTempo())->toDateString(),
            $tagihan->JatuhTempo->toDateString(),
        );
    }
}
