<?php

declare(strict_types=1);

namespace Tests\Feature\IntegrasiAudit;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAkses;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatatanAksesTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(): Pengguna
    {
        $organisasi = Organisasi::create(['Kode' => 'AMANPOLL', 'Nama' => 'Amanpoll Demo']);

        return Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Admin Demo',
            'Email' => 'admin@amanpoll.test',
            'KataSandi' => 'kata-sandi-benar',
            'Status' => 'Aktif',
        ]);
    }

    public function test_login_berhasil_tercatat_di_catatan_akses(): void
    {
        $pengguna = $this->buatPengguna();

        $this->post('/login', [
            'KodeOrganisasi' => 'AMANPOLL',
            'Email' => 'admin@amanpoll.test',
            'KataSandi' => 'kata-sandi-benar',
        ])->assertRedirect(route('dashboard'));

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($pengguna->OrganisasiId);
        $catatan = CatatanAkses::where('Jenis', 'Login')->where('Berhasil', true)->first();
        $konteks->bersihkan();

        $this->assertNotNull($catatan);
        $this->assertSame($pengguna->Id, $catatan->PenggunaId);
    }

    public function test_login_gagal_tercatat_dengan_alasan(): void
    {
        $this->buatPengguna();

        $this->post('/login', [
            'KodeOrganisasi' => 'AMANPOLL',
            'Email' => 'admin@amanpoll.test',
            'KataSandi' => 'salah',
        ]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan(Organisasi::first()->Id);
        $catatan = CatatanAkses::where('Jenis', 'Login')->where('Berhasil', false)->first();
        $konteks->bersihkan();

        $this->assertNotNull($catatan);
        $this->assertNull($catatan->PenggunaId);
        $this->assertNotNull($catatan->AlasanGagal);
    }

    public function test_login_dengan_kode_organisasi_tidak_ada_tetap_tercatat_tanpa_organisasi(): void
    {
        $this->buatPengguna();

        $this->post('/login', [
            'KodeOrganisasi' => 'TIDAK-ADA',
            'Email' => 'admin@amanpoll.test',
            'KataSandi' => 'kata-sandi-benar',
        ]);

        $catatan = CatatanAkses::withoutGlobalScope(ScopeOrganisasi::class)->where('Jenis', 'Login')->first();

        $this->assertNotNull($catatan);
        $this->assertNull($catatan->OrganisasiId);
        $this->assertFalse($catatan->Berhasil);
    }

    public function test_perintah_retensi_menghapus_catatan_lebih_tua_dari_batas(): void
    {
        $pengguna = $this->buatPengguna();
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($pengguna->OrganisasiId);

        $lama = CatatanAkses::create(['Jenis' => 'Login', 'Berhasil' => true]);
        $lama->forceFill(['DibuatPada' => now()->subDays(200)])->saveQuietly();

        $baru = CatatanAkses::create(['Jenis' => 'Login', 'Berhasil' => true]);
        $konteks->bersihkan();

        config(['amanpoll.retensi_catatan_akses_hari' => 90]);
        $this->artisan('catatan-akses:bersihkan')->assertSuccessful();

        $konteks->tetapkan($pengguna->OrganisasiId);
        $this->assertNull(CatatanAkses::find($lama->Id));
        $this->assertNotNull(CatatanAkses::find($baru->Id));
        $konteks->bersihkan();
    }
}
