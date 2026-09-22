<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;

/** Otorisasi konsol trial (MARKETING.md 26). */
final class KonsolTrialTest extends KasusTrial
{
    public function test_admin_tanpa_izin_tidak_dapat_melihat_trial(): void
    {
        $this->actingAs($this->buatAdmin(), 'platform')
            ->get(route('pemasaran.trial.index'))
            ->assertForbidden();
    }

    public function test_konsol_tertutup_saat_fitur_crm_mati(): void
    {
        $this->matikanFitur(KatalogFiturPlatform::CRM);

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_LIHAT]), 'platform')
            ->get(route('pemasaran.trial.index'))
            ->assertNotFound();
    }

    public function test_setelan_yang_tampil_dibaca_dari_domain_langganan(): void
    {
        config(['amanpoll.langganan.hari_uji_coba' => 30, 'amanpoll.langganan.hari_tenggang' => 5]);

        $props = $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_LIHAT]), 'platform')
            ->get(route('pemasaran.trial.index'))
            ->viewData('page')['props'];

        $this->assertSame(30, $props['konfigurasi']['DurasiHari']);
        $this->assertSame(5, $props['konfigurasi']['HariTenggang']);
    }

    public function test_izin_lihat_tidak_cukup_untuk_memperpanjang(): void
    {
        $trial = $this->mulaiTrial();

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_LIHAT]), 'platform')
            ->post(route('pemasaran.trial.perpanjang', $trial->Id), ['Hari' => 3])
            ->assertForbidden();

        $this->assertSame(0, $trial->fresh()?->HariPerpanjangan);
    }

    public function test_izin_kelola_dapat_memperpanjang(): void
    {
        $trial = $this->mulaiTrial();
        $this->buatLangganan();

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_KELOLA]), 'platform')
            ->post(route('pemasaran.trial.perpanjang', $trial->Id), ['Hari' => 3])
            ->assertRedirect();

        $this->assertSame(3, $trial->fresh()?->HariPerpanjangan);
    }

    public function test_perpanjangan_lewat_konsol_tetap_tunduk_pada_kebijakan(): void
    {
        $trial = $this->mulaiTrial();

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_KELOLA]), 'platform')
            ->post(route('pemasaran.trial.perpanjang', $trial->Id), ['Hari' => 999])
            ->assertSessionHasErrors('Hari');
    }

    public function test_status_diperpanjang_tidak_dapat_disetel_langsung(): void
    {
        $trial = $this->mulaiTrial();

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_KELOLA]), 'platform')
            ->post(route('pemasaran.trial.status', $trial->Id), [
                'Status' => StatusTrial::Diperpanjang->value,
            ])
            ->assertSessionHasErrors('Status');
    }

    public function test_pembatalan_lewat_konsol_berhasil(): void
    {
        $trial = $this->mulaiTrial();

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_KELOLA]), 'platform')
            ->post(route('pemasaran.trial.status', $trial->Id), [
                'Status' => StatusTrial::Dibatalkan->value,
                'Alasan' => 'Diminta pelanggan.',
            ])
            ->assertRedirect();

        $this->assertSame(StatusTrial::Dibatalkan, $trial->fresh()?->Status);
    }
}
