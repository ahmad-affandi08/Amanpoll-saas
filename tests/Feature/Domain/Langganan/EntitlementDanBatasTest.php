<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Langganan;

use App\Domain\Aset\Application\Actions\BuatAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Langganan\Application\Services\PenjagaBatasLangganan;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Platform\Application\Actions\BuatPengguna;
use App\Domain\Platform\Application\DTO\PenggunaData;
use App\Shared\Domain\Exceptions\LanggananTidakMengizinkan;

/** Entitlement, batas kuota, dan prop untuk UI (22.05). */
final class EntitlementDanBatasTest extends KasusLangganan
{
    public function test_organisasi_baru_tanpa_paket_berada_dalam_uji_coba_awal(): void
    {
        $entitlement = app(PemeriksaEntitlement::class)->untukOrganisasi((string) $this->organisasi->Id);

        $this->assertSame(StatusLangganan::UjiCoba, $entitlement->status);
        $this->assertTrue($entitlement->memberiAksesPenuh());
        foreach ([KatalogFitur::MODUL_KALIBRASI, KatalogFitur::MODUL_KEPATUHAN] as $kode) {
            $this->assertTrue($entitlement->bolehFitur($kode));
        }
        // Uji coba dibatasi waktu, bukan jumlah.
        $this->assertNull($entitlement->batas(KatalogFitur::BATAS_ASET));
    }

    public function test_uji_coba_awal_yang_sudah_lewat_menghentikan_akses_tulis(): void
    {
        $this->mundurkanPembuatanOrganisasi(365);

        $entitlement = app(PemeriksaEntitlement::class)->untukOrganisasi((string) $this->organisasi->Id);

        $this->assertSame(StatusLangganan::Kedaluwarsa, $entitlement->status);
        $this->assertFalse($entitlement->memberiAksesPenuh());
    }

    public function test_fitur_yang_tidak_disebut_paket_jatuh_ke_nilai_bawaan_katalog(): void
    {
        // Paket hanya menyebut kalibrasi; sisanya harus memakai bawaan katalog, bukan menjadi "tidak diketahui".
        $paket = $this->buatPaket('Paket Sebagian', [
            KatalogFitur::MODUL_KALIBRASI => ['Diizinkan' => true],
        ]);
        $this->buatLangganan($paket);

        $entitlement = app(PemeriksaEntitlement::class)->untukOrganisasi((string) $this->organisasi->Id);

        $this->assertTrue($entitlement->bolehFitur(KatalogFitur::MODUL_KALIBRASI));
        $this->assertFalse($entitlement->bolehFitur(KatalogFitur::MODUL_INTEGRASI));
        // Batas yang tidak pernah ditetapkan berarti tanpa batas, bukan nol.
        $this->assertNull($entitlement->batas(KatalogFitur::BATAS_ASET));
    }

    public function test_batas_nol_berbeda_dari_tanpa_batas(): void
    {
        $paket = $this->buatPaket('Paket Nol Aset', [
            KatalogFitur::BATAS_ASET => ['Diizinkan' => true, 'BatasNilai' => 0.0],
        ]);
        $this->buatLangganan($paket);

        $this->assertSame(
            0.0,
            app(PemeriksaEntitlement::class)->untukOrganisasi((string) $this->organisasi->Id)
                ->batas(KatalogFitur::BATAS_ASET),
        );

        $this->expectException(LanggananTidakMengizinkan::class);
        app(PenjagaBatasLangganan::class)->pastikanMasihMuat(KatalogFitur::BATAS_ASET);
    }

    public function test_penjaga_batas_mengizinkan_selama_masih_muat(): void
    {
        $paket = $this->buatPaket('Paket Dua Aset', [
            KatalogFitur::BATAS_ASET => ['Diizinkan' => true, 'BatasNilai' => 2.0],
        ]);
        $this->buatLangganan($paket);
        $this->buatAset();

        // Masih ada satu slot; tidak boleh menolak terlalu dini.
        app(PenjagaBatasLangganan::class)->pastikanMasihMuat(KatalogFitur::BATAS_ASET);

        $this->addToAssertionCount(1);
    }

    public function test_aset_yang_sudah_dihapus_tidak_memakan_kuota(): void
    {
        $paket = $this->buatPaket('Paket Satu Aset', [
            KatalogFitur::BATAS_ASET => ['Diizinkan' => true, 'BatasNilai' => 1.0],
        ]);
        $this->buatLangganan($paket);

        $aset = $this->buatAset();
        $aset->delete();

        // Pelanggan sudah melepasnya, jadi slotnya kembali.
        app(PenjagaBatasLangganan::class)->pastikanMasihMuat(KatalogFitur::BATAS_ASET);

        $this->addToAssertionCount(1);
    }

    public function test_pesan_penolakan_menyebut_batas_dan_pemakaian(): void
    {
        $paket = $this->buatPaket('Paket Satu Aset', [
            KatalogFitur::BATAS_ASET => ['Diizinkan' => true, 'BatasNilai' => 1.0],
        ]);
        $this->buatLangganan($paket);
        $this->buatAset();

        try {
            app(PenjagaBatasLangganan::class)->pastikanMasihMuat(KatalogFitur::BATAS_ASET);
            $this->fail('Seharusnya ditolak karena kuota habis.');
        } catch (LanggananTidakMengizinkan $e) {
            // Pesan harus cukup untuk bertindak, bukan sekadar "ditolak".
            $this->assertStringContainsString('1', $e->getMessage());
            $this->assertStringContainsString('aset', $e->getMessage());
            $this->assertStringContainsString('Naikkan paket', $e->getMessage());
        }
    }

    public function test_batas_pengguna_ditegakkan_saat_menambah_pengguna(): void
    {
        $paket = $this->buatPaket('Paket Satu Pengguna', [
            KatalogFitur::BATAS_PENGGUNA => ['Diizinkan' => true, 'BatasNilai' => 1.0],
        ]);
        $this->buatLangganan($paket);
        $this->buatPengguna();

        $this->expectException(LanggananTidakMengizinkan::class);

        app(BuatPengguna::class)->jalankan(new PenggunaData(
            Nama: 'Pengguna Kedua',
            Email: 'kedua-'.uniqid().'@amanpoll.test',
            KataSandi: 'rahasia',
        ));
    }

    public function test_pemakaian_dilaporkan_untuk_tiap_fitur_berbatas(): void
    {
        $paket = $this->buatPaketLengkap();
        $this->buatLangganan($paket);
        $pembuat = $this->buatPengguna();
        $this->buatAset($pembuat->Id);
        $this->buatAset($pembuat->Id);

        $pemakaian = app(PenjagaBatasLangganan::class)->pemakaian();

        $this->assertSame(2, $pemakaian[KatalogFitur::BATAS_ASET]);
        $this->assertSame(1, $pemakaian[KatalogFitur::BATAS_PENGGUNA]);
        $this->assertArrayNotHasKey(KatalogFitur::MODUL_KALIBRASI, $pemakaian);
    }

    public function test_prop_entitlement_dibagikan_ke_ui_dari_sumber_yang_sama(): void
    {
        $paket = $this->buatPaket('Paket UI', [
            KatalogFitur::MODUL_KALIBRASI => ['Diizinkan' => true],
            KatalogFitur::BATAS_ASET => ['Diizinkan' => true, 'BatasNilai' => 100],
        ]);
        $this->buatLangganan($paket);

        $respons = $this->actingAs($this->buatPengguna(['Pengaturan.Kelola']))->get(route('langganan.index'));
        $respons->assertOk();

        $props = $respons->viewData('page')['props'];

        // Prop UI dan penegakan backend membaca pemeriksa yang sama.
        $this->assertTrue($props['entitlement']['Fitur'][KatalogFitur::MODUL_KALIBRASI]);
        $this->assertSame(100.0, $props['entitlement']['Batas'][KatalogFitur::BATAS_ASET]);
        $this->assertTrue($props['entitlement']['AksesPenuh']);
        $this->assertSame('Paket UI', $props['entitlement']['NamaPaket']);
    }

    public function test_prop_entitlement_menandai_akses_tulis_yang_dicabut(): void
    {
        $paket = $this->buatPaketLengkap();
        $this->buatLangganan($paket, StatusLangganan::Aktif, berakhirPada: '2026-01-01');

        $props = $this->actingAs($this->buatPengguna(['Pengaturan.Kelola']))
            ->get(route('langganan.index'))
            ->viewData('page')['props'];

        $this->assertFalse($props['entitlement']['AksesPenuh']);
        $this->assertSame(StatusLangganan::Kedaluwarsa->value, $props['entitlement']['Status']);
    }

    private function buatAset(?string $dibuatOleh = null): Aset
    {
        $kategori = KategoriAset::firstOrCreate(
            ['OrganisasiId' => $this->organisasi->Id, 'Kode' => 'KAT-ENT'],
            ['Nama' => 'Kategori Entitlement'],
        );

        return app(BuatAset::class)->jalankan([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => 'Aset uji',
            'Status' => StatusAset::Aktif->value,
        ], $dibuatOleh ?? (string) $this->buatPengguna()->Id);
    }
}
