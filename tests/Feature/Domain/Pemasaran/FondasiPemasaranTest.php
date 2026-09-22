<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Application\Services\PemeriksaFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;

/**
 * Fondasi konsol Growth & Marketing (FASE 29).
 */
final class FondasiPemasaranTest extends KasusPemasaran
{
    public function test_konsol_menolak_admin_tanpa_izin_pemasaran(): void
    {
        $this->actingAs($this->buatAdmin(), 'platform')
            ->get(route('pemasaran.ringkasan'))
            ->assertForbidden();
    }

    public function test_konsol_terbuka_bagi_admin_berizin(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PEMASARAN_LIHAT]), 'platform')
            ->get(route('pemasaran.ringkasan'))
            ->assertOk();
    }

    public function test_super_admin_tidak_dapat_terkunci_dari_konsolnya_sendiri(): void
    {
        $this->actingAs($this->buatAdmin([], superAdmin: true), 'platform')
            ->get(route('pemasaran.ringkasan'))
            ->assertOk();
    }

    public function test_pengguna_tenant_tidak_dapat_membuka_konsol_platform(): void
    {
        $this->get(route('pemasaran.ringkasan'))->assertRedirect(route('adminPlatform.login'));
    }

    public function test_izin_lihat_tidak_cukup_untuk_membuka_pengaturan(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PEMASARAN_LIHAT]), 'platform')
            ->get(route('pemasaran.pengaturan.index'))
            ->assertForbidden();
    }

    public function test_pengaturan_menampilkan_host_dari_konfigurasi(): void
    {
        $props = $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PEMASARAN_KELOLA]), 'platform')
            ->get(route('pemasaran.pengaturan.index'))
            ->viewData('page')['props'];

        $this->assertSame(config('amanpoll.domain.dashboard'), $props['domain']['dashboard']);
        $this->assertSame(config('amanpoll.domain.publik'), $props['domain']['publik']);
    }

    public function test_flag_baru_mati_secara_bawaan(): void
    {
        $fitur = app(PemeriksaFiturPlatform::class);

        foreach (KatalogFiturPlatform::kode() as $kode) {
            $this->assertFalse($fitur->aktif($kode), "Flag {$kode} seharusnya mati sebelum dinyalakan.");
        }
    }

    public function test_flag_yang_tidak_dikenal_selalu_mati(): void
    {
        $this->assertFalse(app(PemeriksaFiturPlatform::class)->aktif('marketing.salah-ketik'));
    }

    public function test_menyalakan_flag_tercatat_di_audit(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PEMASARAN_KELOLA]), 'platform')
            ->post(route('pemasaran.pengaturan.fitur'), [
                'Kode' => KatalogFiturPlatform::CRM,
                'Aktif' => true,
            ])
            ->assertRedirect();

        $this->assertTrue(app(PemeriksaFiturPlatform::class)->aktif(KatalogFiturPlatform::CRM));

        $catatan = CatatanAudit::query()
            ->withoutGlobalScopes()
            ->where('Aksi', 'FiturPlatform.Diubah')
            ->first();

        $this->assertNotNull($catatan);
        $this->assertSame(['Aktif' => false], $catatan->DataSebelum);
        $this->assertSame(['Aktif' => true], $catatan->DataSesudah);
    }

    public function test_konfigurasi_memakai_nilai_bawaan_sebelum_pernah_disimpan(): void
    {
        $konfigurasi = app(LayananKonfigurasiPemasaran::class);

        $this->assertSame(
            KatalogKonfigurasiPemasaran::bawaan(KatalogKonfigurasiPemasaran::TRIAL_HARI),
            $konfigurasi->ambil(KatalogKonfigurasiPemasaran::TRIAL_HARI),
        );
    }

    public function test_konfigurasi_yang_disimpan_menggantikan_bawaannya(): void
    {
        $konfigurasi = app(LayananKonfigurasiPemasaran::class);
        $konfigurasi->simpan(KatalogKonfigurasiPemasaran::TRIAL_HARI, 30);

        $this->assertSame(30, $konfigurasi->angka(KatalogKonfigurasiPemasaran::TRIAL_HARI));
    }

    public function test_konfigurasi_di_luar_katalog_ditolak(): void
    {
        $this->expectException(DataTidakDitemukan::class);

        app(LayananKonfigurasiPemasaran::class)->ambil('kunci.karangan');
    }

    public function test_bobot_skor_dapat_diubah_tanpa_menyentuh_kode(): void
    {
        $konfigurasi = app(LayananKonfigurasiPemasaran::class);
        $bawaan = $konfigurasi->daftar(KatalogKonfigurasiPemasaran::SKOR_ATURAN);

        $this->assertArrayHasKey('FormulirDikirim', $bawaan);

        $konfigurasi->simpan(KatalogKonfigurasiPemasaran::SKOR_ATURAN, ['FormulirDikirim' => 99]);

        $this->assertSame(
            ['FormulirDikirim' => 99],
            $konfigurasi->daftar(KatalogKonfigurasiPemasaran::SKOR_ATURAN),
        );
    }

    public function test_menyimpan_konfigurasi_lewat_konsol_tercatat_di_audit(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PEMASARAN_KELOLA]), 'platform')
            ->post(route('pemasaran.pengaturan.konfigurasi'), [
                'Kunci' => KatalogKonfigurasiPemasaran::TRIAL_HARI,
                'Nilai' => 21,
            ])
            ->assertRedirect();

        $this->assertSame(21, app(LayananKonfigurasiPemasaran::class)->angka(KatalogKonfigurasiPemasaran::TRIAL_HARI));

        $this->assertTrue(
            CatatanAudit::query()
                ->withoutGlobalScopes()
                ->where('Aksi', 'KonfigurasiPemasaran.Diubah')
                ->exists(),
        );
    }

    public function test_seluruh_kode_izin_pemasaran_unik_dan_berawalan_platform(): void
    {
        $kode = KatalogIzinPemasaran::semua();

        $this->assertSame($kode, array_values(array_unique($kode)));

        foreach ($kode as $satu) {
            $this->assertStringStartsWith('platform.', $satu);
        }
    }
}
