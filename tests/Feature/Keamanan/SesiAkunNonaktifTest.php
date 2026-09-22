<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAkses;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/** Status akun diperiksa saat masuk, tetapi sesi berumur panjang. */
final class SesiAkunNonaktifTest extends KasusKeamanan
{
    public function test_pengguna_yang_dinonaktifkan_langsung_kehilangan_sesinya(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-NONAKTIF');
        $pengguna = $this->buatPengguna($organisasi, ['Aset.Lihat']);

        $this->actingAs($pengguna)->get('/aset')->assertOk();

        $pengguna->forceFill(['Status' => 'Nonaktif'])->save();

        $this->actingAs($pengguna)->get('/aset')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_pengguna_organisasi_nonaktif_kehilangan_sesinya(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-TUTUP');
        $pengguna = $this->buatPengguna($organisasi, ['Aset.Lihat']);

        $this->actingAs($pengguna)->get('/aset')->assertOk();

        $organisasi->forceFill(['Status' => 'Nonaktif'])->save();

        $this->actingAs($pengguna)->get('/aset')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_permintaan_json_dari_akun_nonaktif_dijawab_401(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-JSON');
        $pengguna = $this->buatPengguna($organisasi, ['Aset.Lihat']);
        $pengguna->forceFill(['Status' => 'Nonaktif'])->save();

        $this->actingAs($pengguna)
            ->getJson('/aset')
            ->assertUnauthorized();
    }

    public function test_pengakhiran_sesi_tercatat_pada_catatan_akses(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-CATAT');
        $pengguna = $this->buatPengguna($organisasi, ['Aset.Lihat']);
        $pengguna->forceFill(['Status' => 'Nonaktif'])->save();

        $this->actingAs($pengguna)->get('/aset');

        $catatan = $this->dalamOrganisasi(
            $organisasi,
            fn (): ?CatatanAkses => CatatanAkses::query()->where('Jenis', 'SesiDiakhiri')->first(),
        );

        $this->assertNotNull($catatan);
        $this->assertFalse((bool) $catatan->Berhasil);
        $this->assertSame($pengguna->Id, $catatan->PenggunaId);
    }

    public function test_pengguna_aktif_tidak_terganggu(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-AKTIF');
        $pengguna = $this->buatPengguna($organisasi, ['Aset.Lihat']);

        $this->actingAs($pengguna)->get('/aset')->assertOk();
        $this->actingAs($pengguna)->get('/aset')->assertOk();

        $this->assertInstanceOf(Pengguna::class, $pengguna->fresh());
    }
}
