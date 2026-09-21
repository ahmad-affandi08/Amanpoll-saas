<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Langganan;

use App\Domain\Langganan\Application\Actions\TerbitkanTagihanLangganan;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;

/**
 * Halaman langganan memuat tagihan dan tombol bayar, jadi ia bukan halaman
 * untuk semua orang yang kebetulan dapat masuk (24).
 */
final class OtorisasiLanggananTest extends KasusLangganan
{
    public function test_pengguna_tanpa_izin_pengaturan_tidak_dapat_membuka_halaman_langganan(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());

        $this->actingAs($this->buatPengguna(['Aset.Lihat']))
            ->get(route('langganan.index'))
            ->assertForbidden();
    }

    public function test_pengguna_tanpa_izin_pengaturan_tidak_dapat_memulai_pembayaran(): void
    {
        $langganan = $this->buatLangganan(
            $this->buatPaketLengkap(),
            StatusLangganan::Aktif,
            berakhirPada: '2026-07-15',
        );
        $tagihan = app(TerbitkanTagihanLangganan::class)->jalankan($langganan);
        $this->assertInstanceOf(TagihanLangganan::class, $tagihan);

        $this->actingAs($this->buatPengguna(['Aset.Lihat']))
            ->post(route('langganan.tagihan.bayar', $tagihan))
            ->assertForbidden();
    }

    public function test_pengelola_pengaturan_tetap_dapat_membuka_halaman_langganan(): void
    {
        $this->buatLangganan($this->buatPaketLengkap());

        $this->actingAs($this->buatPengguna(['Pengaturan.Kelola']))
            ->get(route('langganan.index'))
            ->assertOk();
    }
}
