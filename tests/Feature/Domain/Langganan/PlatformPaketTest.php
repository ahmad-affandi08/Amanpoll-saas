<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Langganan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;

/**
 * CRUD paket oleh admin platform beserta validasi entitlement-nya
 * (22.02/22.03), dan pemisahan identitasnya dari tenant.
 */
final class PlatformPaketTest extends KasusLangganan
{
    public function test_tenant_biasa_tidak_dapat_membuka_konsol_platform(): void
    {
        $pengguna = $this->buatPengguna(['Pengaturan.Kelola']);

        // Guard berbeda: sesi tenant tidak pernah menjadi sesi platform, berapa
        // pun izin yang dimilikinya di dalam organisasinya.
        $this->actingAs($pengguna)
            ->get(route('adminPlatform.paket.index'))
            ->assertRedirect(route('adminPlatform.login'));
    }

    public function test_tamu_diarahkan_ke_halaman_masuk_platform(): void
    {
        $this->get(route('adminPlatform.paket.index'))->assertRedirect(route('adminPlatform.login'));
    }

    public function test_admin_platform_dapat_masuk_dan_membuka_katalog_paket(): void
    {
        $admin = $this->buatAdminPlatform();
        app(KonteksOrganisasi::class)->bersihkan();

        $this->post(route('adminPlatform.login.store'), [
            'Email' => $admin->Email,
            'KataSandi' => 'rahasia',
        ])->assertRedirect(route('adminPlatform.paket.index'));

        $this->sebagaiAdminPlatform($admin)
            ->get(route('adminPlatform.paket.index'))
            ->assertOk();
    }

    public function test_admin_platform_nonaktif_tidak_dapat_masuk(): void
    {
        $admin = $this->buatAdminPlatform();
        $admin->update(['Status' => 'Nonaktif']);
        app(KonteksOrganisasi::class)->bersihkan();

        $this->post(route('adminPlatform.login.store'), [
            'Email' => $admin->Email,
            'KataSandi' => 'rahasia',
        ])->assertSessionHasErrors('Email');
    }

    public function test_membuat_paket_menyimpan_entitlement_dan_batasnya(): void
    {
        $this->sebagaiAdminPlatform()
            ->post(route('adminPlatform.paket.store'), $this->muatanPaket([
                ['Kode' => KatalogFitur::MODUL_KALIBRASI, 'Diizinkan' => true],
                ['Kode' => KatalogFitur::BATAS_ASET, 'Diizinkan' => true, 'BatasNilai' => 250],
            ]))
            ->assertRedirect();

        $paket = PaketLangganan::query()->where('Nama', 'Paket Uji')->firstOrFail();
        $this->buatLangganan($paket);

        $entitlement = app(PemeriksaEntitlement::class)->untukOrganisasi((string) $this->organisasi->Id);

        $this->assertTrue($entitlement->bolehFitur(KatalogFitur::MODUL_KALIBRASI));
        $this->assertSame(250.0, $entitlement->batas(KatalogFitur::BATAS_ASET));
        // Modul yang tidak disebut tetap tertutup.
        $this->assertFalse($entitlement->bolehFitur(KatalogFitur::MODUL_KEPATUHAN));
    }

    public function test_batas_pada_fitur_boolean_ditolak(): void
    {
        $this->sebagaiAdminPlatform()
            ->post(route('adminPlatform.paket.store'), $this->muatanPaket([
                ['Kode' => KatalogFitur::MODUL_KALIBRASI, 'Diizinkan' => true, 'BatasNilai' => 10],
            ]))
            ->assertStatus(422);

        $this->assertSame(0, PaketLangganan::query()->where('Nama', 'Paket Uji')->count());
    }

    public function test_fitur_yang_disebut_dua_kali_ditolak(): void
    {
        $this->sebagaiAdminPlatform()
            ->post(route('adminPlatform.paket.store'), $this->muatanPaket([
                ['Kode' => KatalogFitur::MODUL_KALIBRASI, 'Diizinkan' => true],
                ['Kode' => KatalogFitur::MODUL_KALIBRASI, 'Diizinkan' => false],
            ]))
            ->assertStatus(422);
    }

    public function test_fitur_di_luar_katalog_gagal_validasi(): void
    {
        $this->sebagaiAdminPlatform()
            ->post(route('adminPlatform.paket.store'), $this->muatanPaket([
                ['Kode' => 'modul.karangan', 'Diizinkan' => true],
            ]))
            ->assertSessionHasErrors('Fitur.0.Kode');
    }

    public function test_kode_paket_harus_unik(): void
    {
        $admin = $this->buatAdminPlatform();
        $muatan = $this->muatanPaket([['Kode' => KatalogFitur::MODUL_KALIBRASI, 'Diizinkan' => true]]);

        $this->sebagaiAdminPlatform($admin)->post(route('adminPlatform.paket.store'), $muatan)->assertRedirect();
        $this->sebagaiAdminPlatform($admin)->post(route('adminPlatform.paket.store'), $muatan)
            ->assertSessionHasErrors('Kode');
    }

    public function test_mengubah_paket_langsung_mengubah_entitlement_pelanggannya(): void
    {
        $paket = $this->buatPaket('Paket Berubah', [
            KatalogFitur::MODUL_KALIBRASI => ['Diizinkan' => true],
        ]);
        $this->buatLangganan($paket);

        $pemeriksa = app(PemeriksaEntitlement::class);
        $this->assertTrue($pemeriksa->bolehFitur(KatalogFitur::MODUL_KALIBRASI, (string) $this->organisasi->Id));

        $this->sebagaiAdminPlatform()
            ->put(route('adminPlatform.paket.update', $paket), [
                'Kode' => $paket->Kode,
                'Nama' => $paket->Nama,
                'HargaBulanan' => 500_000,
                'HargaTahunan' => 5_000_000,
                'Aktif' => true,
                'Fitur' => [['Kode' => KatalogFitur::MODUL_KALIBRASI, 'Diizinkan' => false]],
            ])
            ->assertRedirect();

        // Cache tidak boleh menunda pencabutan modul yang sudah tidak dibayar.
        $this->assertFalse($pemeriksa->bolehFitur(KatalogFitur::MODUL_KALIBRASI, (string) $this->organisasi->Id));
    }

    public function test_paket_yang_masih_dipakai_tidak_dapat_dihapus(): void
    {
        $paket = $this->buatPaketLengkap();
        $this->buatLangganan($paket);

        $this->sebagaiAdminPlatform()
            ->delete(route('adminPlatform.paket.destroy', $paket))
            ->assertStatus(422);

        $this->assertNotNull(PaketLangganan::query()->find($paket->Id));
    }

    public function test_paket_tanpa_pelanggan_dapat_dihapus(): void
    {
        $paket = $this->buatPaketLengkap();

        $this->sebagaiAdminPlatform()
            ->delete(route('adminPlatform.paket.destroy', $paket))
            ->assertRedirect();

        $this->assertNull(PaketLangganan::query()->find($paket->Id));
    }

    /**
     * @param  list<array<string, mixed>>  $fitur
     * @return array<string, mixed>
     */
    private function muatanPaket(array $fitur): array
    {
        return [
            'Kode' => 'PKT-UJI',
            'Nama' => 'Paket Uji',
            'Deskripsi' => 'Paket untuk pengujian.',
            'HargaBulanan' => 750_000,
            'HargaTahunan' => 7_500_000,
            'MataUang' => 'IDR',
            'Aktif' => true,
            'Fitur' => $fitur,
        ];
    }
}
