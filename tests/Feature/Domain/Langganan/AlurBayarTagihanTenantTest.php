<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Langganan;

use App\Domain\Langganan\Application\Services\RegistriPenyediaPembayaran;
use App\Domain\Langganan\Domain\Enums\StatusSesiPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\SesiPembayaranLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

/** Tenant memilih metode bayar, dialihkan ke gateway, dan kembali menunggu konfirmasi (PRD 8.23). */
final class AlurBayarTagihanTenantTest extends KasusGerbangPembayaran
{
    private const URL_SNAP = 'https://app.sandbox.midtrans.com/snap/v4/redirection/snap-token-1';

    private Pengguna $pengguna;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pengguna = $this->buatPengguna(['Pengaturan.Kelola']);
    }

    private function palsukanMidtrans(): void
    {
        Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['token' => 'snap-token-1', 'redirect_url' => self::URL_SNAP], 201)]);
    }

    private function jumlahSesi(TagihanLangganan $tagihan): int
    {
        return SesiPembayaranLangganan::query()->where('TagihanLanggananId', $tagihan->Id)->count();
    }

    public function test_bayar_lewat_gateway_mengalihkan_ke_halaman_bayar_dan_membuka_sesi(): void
    {
        $this->aktifkan('Midtrans', utama: true);
        $this->palsukanMidtrans();
        $tagihan = $this->terbitkanTagihan();

        $this->actingAs($this->pengguna)
            ->post(route('langganan.tagihan.bayar', $tagihan), ['Penyedia' => 'Midtrans'])
            ->assertRedirect(self::URL_SNAP);

        $sesi = SesiPembayaranLangganan::query()->where('TagihanLanggananId', $tagihan->Id)->sole();
        $this->assertSame('Midtrans', $sesi->Penyedia);
        $this->assertSame(StatusSesiPembayaran::Menunggu->value, $sesi->Status);
        $this->assertSame(self::URL_SNAP, $sesi->UrlPembayaran);
        Http::assertSent(fn ($permintaan): bool => $permintaan['transaction_details']['order_id'] === $sesi->IdPesananPenyedia
            && $permintaan['transaction_details']['gross_amount'] === 500_000
            && $permintaan['callbacks']['finish'] === route('langganan.tagihan.kembali', $tagihan));
    }

    public function test_permintaan_inertia_dialihkan_lewat_lokasi_eksternal(): void
    {
        $this->aktifkan('Midtrans', utama: true);
        $this->palsukanMidtrans();
        $tagihan = $this->terbitkanTagihan();

        $this->actingAs($this->pengguna)
            ->withHeaders(['X-Inertia' => 'true'])
            ->post(route('langganan.tagihan.bayar', $tagihan), ['Penyedia' => 'Midtrans'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', self::URL_SNAP);
    }

    public function test_klik_bayar_berulang_memakai_sesi_yang_masih_berlaku(): void
    {
        $this->aktifkan('Midtrans', utama: true);
        $this->palsukanMidtrans();
        $tagihan = $this->terbitkanTagihan();

        $this->actingAs($this->pengguna)->post(route('langganan.tagihan.bayar', $tagihan), ['Penyedia' => 'Midtrans'])
            ->assertRedirect(self::URL_SNAP);
        $this->actingAs($this->pengguna)->post(route('langganan.tagihan.bayar', $tagihan), ['Penyedia' => 'Midtrans'])
            ->assertRedirect(self::URL_SNAP);

        $this->assertSame(1, $this->jumlahSesi($tagihan));
        Http::assertSentCount(1);
    }

    public function test_sesi_yang_kedaluwarsa_diganti_sesi_baru_dengan_order_id_baru(): void
    {
        $this->aktifkan('Midtrans', utama: true);
        $this->palsukanMidtrans();
        $tagihan = $this->terbitkanTagihan();
        $lama = $this->bukaSesi($tagihan, 'Midtrans', 'ORD-LAMA');
        $lama->update(['KedaluwarsaPada' => CarbonImmutable::now()->subMinute()]);

        $this->actingAs($this->pengguna)->post(route('langganan.tagihan.bayar', $tagihan), ['Penyedia' => 'Midtrans'])
            ->assertRedirect(self::URL_SNAP);

        $this->assertSame(2, $this->jumlahSesi($tagihan));
        Http::assertSent(fn ($permintaan): bool => $permintaan['transaction_details']['order_id'] !== 'ORD-LAMA');
    }

    public function test_tenant_tidak_bisa_memilih_penyedia_yang_tidak_aktif(): void
    {
        $this->aktifkan('Midtrans', utama: true);
        // Stripe terpasang dan berkredensial, tetapi dimatikan di konsol.
        $this->aktifkan('Stripe')->update(['Aktif' => false]);
        Http::fake();
        $tagihan = $this->terbitkanTagihan();

        foreach (['Stripe', 'PenyediaKarangan'] as $kode) {
            $this->actingAs($this->pengguna)
                ->post(route('langganan.tagihan.bayar', $tagihan), ['Penyedia' => $kode])
                ->assertSessionHasErrors('Penyedia');
        }

        Http::assertNothingSent();
        $this->assertSame(0, $this->jumlahSesi($tagihan));
    }

    public function test_tanpa_pilihan_dipakai_penyedia_utama(): void
    {
        $this->aktifkan('Xendit');
        $this->aktifkan('Midtrans', utama: true);
        $this->palsukanMidtrans();
        $tagihan = $this->terbitkanTagihan();

        $this->actingAs($this->pengguna)->post(route('langganan.tagihan.bayar', $tagihan))->assertRedirect(self::URL_SNAP);

        $this->assertSame('Midtrans', app(RegistriPenyediaPembayaran::class)->bawaan()->kode());
    }

    public function test_halaman_menampilkan_pilihan_metode_aktif_tanpa_kredensial(): void
    {
        $this->aktifkan('Midtrans', utama: true);
        $this->aktifkan('Xendit');
        $this->aktifkan('Stripe')->update(['Aktif' => false]);
        $this->terbitkanTagihan();

        $respons = $this->actingAs($this->pengguna)->get(route('langganan.index'));

        $respons->assertOk()->assertInertia(fn (AssertableInertia $halaman) => $halaman
            ->component('Langganan/Index')
            ->where('metodePembayaran', [
                ['Kode' => 'Midtrans', 'Nama' => 'Midtrans'],
                ['Kode' => 'Xendit', 'Nama' => 'Xendit'],
            ])
            ->where('pembayaranKembali', null));

        foreach ([self::KREDENSIAL['Midtrans']['ServerKey'], self::KREDENSIAL['Xendit']['SecretKey'], self::KREDENSIAL['Xendit']['TokenCallback']] as $rahasia) {
            $this->assertStringNotContainsString($rahasia, (string) $respons->getContent());
        }
    }

    public function test_tanpa_penyedia_aktif_transfer_manual_menjadi_cadangan(): void
    {
        $this->terbitkanTagihan();

        $this->actingAs($this->pengguna)->get(route('langganan.index'))
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('metodePembayaran', [['Kode' => 'TransferManual', 'Nama' => 'Transfer Bank (konfirmasi manual)']]));
    }

    public function test_transfer_manual_memakai_rekening_dari_konsol_dan_tidak_membuka_sesi(): void
    {
        $this->aktifkan('TransferManual', utama: true);
        $tagihan = $this->terbitkanTagihan();

        $this->actingAs($this->pengguna)
            ->post(route('langganan.tagihan.bayar', $tagihan), ['Penyedia' => 'TransferManual'])
            ->assertRedirect()
            ->assertSessionHas('instruksiPembayaran', fn (array $instruksi): bool => $instruksi['Instruksi']['Bank'] === 'Bank Konsol'
                && $instruksi['Instruksi']['NomorRekening'] === '123-456-789'
                && $instruksi['Instruksi']['BeritaTransfer'] === (string) $tagihan->Nomor);

        $this->assertSame(0, $this->jumlahSesi($tagihan));
    }

    public function test_halaman_kembali_menampilkan_status_dari_tagihan_bukan_dari_url(): void
    {
        $this->aktifkan('Midtrans', utama: true);
        $tagihan = $this->terbitkanTagihan();
        $sesi = $this->bukaSesi($tagihan, 'Midtrans', 'ORD-KEMBALI');

        // Gateway menempelkan status di URL; nilainya bisa diketik siapa saja, jadi diabaikan.
        $this->actingAs($this->pengguna)
            ->get(route('langganan.tagihan.kembali', $tagihan).'?order_id=ORD-KEMBALI&transaction_status=settlement')
            ->assertRedirect(route('langganan.index'));

        $this->actingAs($this->pengguna)->get(route('langganan.index'))
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('pembayaranKembali.Nomor', (string) $tagihan->Nomor)
                ->where('pembayaranKembali.Lunas', false));

        $this->kirimWebhook('Midtrans', $sesi->IdPesananPenyedia, 'lunas')->assertOk();

        $this->actingAs($this->pengguna)->get(route('langganan.tagihan.kembali', $tagihan));
        $this->actingAs($this->pengguna)->get(route('langganan.index'))
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('pembayaranKembali.Lunas', true));
    }

    public function test_tagihan_lunas_tidak_bisa_dibayar_lagi(): void
    {
        $this->aktifkan('Midtrans', utama: true);
        Http::fake();
        $tagihan = $this->terbitkanTagihan();
        $tagihan->update(['Status' => 'Lunas']);

        $this->actingAs($this->pengguna)
            ->post(route('langganan.tagihan.bayar', $tagihan), ['Penyedia' => 'Midtrans'])
            ->assertUnprocessable();

        Http::assertNothingSent();
    }
}
