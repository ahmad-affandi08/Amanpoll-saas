<?php

declare(strict_types=1);

namespace Tests\Feature\Kolaborasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\KomentarEntitas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KomentarEntitasTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(Organisasi $organisasi, ?string $kodeIzin = 'Pengaturan.Kelola'): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== null) {
            $konteks = app(KonteksOrganisasi::class);
            $konteks->tetapkan($organisasi->Id);
            $izin = Izin::firstOrCreate(['Kode' => $kodeIzin], ['Nama' => $kodeIzin, 'Modul' => 'Pengaturan']);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            $konteks->bersihkan();
        }

        return $pengguna;
    }

    private function buatLokasi(Organisasi $organisasi): Lokasi
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $lokasi = Lokasi::create(['Kode' => 'LOK-'.uniqid(), 'Nama' => 'Lokasi Uji']);
        $konteks->bersihkan();

        return $lokasi;
    }

    public function test_tambah_komentar_tercatat_dan_diaudit(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $this->actingAs($pengguna)->post('/kolaborasi/komentar', [
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasi->Id,
            'Isi' => 'Butuh perbaikan atap.',
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $komentar = KomentarEntitas::where('EntitasId', $lokasi->Id)->first();
        $audit = CatatanAudit::where('Aksi', 'Komentar.Ditambahkan')->first();
        $konteks->bersihkan();

        $this->assertNotNull($komentar);
        $this->assertSame('Butuh perbaikan atap.', $komentar->Isi);
        $this->assertSame($pengguna->Id, $komentar->DibuatOleh);
        $this->assertNotNull($audit);
    }

    public function test_penulis_dapat_mengubah_komentarnya_sendiri(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penulis = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $komentar = KomentarEntitas::create(['JenisEntitas' => 'Lokasi', 'EntitasId' => $lokasi->Id, 'Isi' => 'Awal', 'DibuatOleh' => $penulis->Id]);
        $konteks->bersihkan();

        $this->actingAs($penulis)->put("/kolaborasi/komentar/{$komentar->Id}", ['Isi' => 'Diperbarui'])
            ->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertSame('Diperbarui', $komentar->fresh()->Isi);
        $konteks->bersihkan();
    }

    public function test_pengguna_lain_tidak_bisa_mengubah_komentar_orang_lain(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penulis = $this->buatPengguna($organisasi);
        $lain = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $komentar = KomentarEntitas::create(['JenisEntitas' => 'Lokasi', 'EntitasId' => $lokasi->Id, 'Isi' => 'Awal', 'DibuatOleh' => $penulis->Id]);
        $konteks->bersihkan();

        $this->actingAs($lain)->put("/kolaborasi/komentar/{$komentar->Id}", ['Isi' => 'Diretas'])
            ->assertForbidden();
    }

    public function test_penulis_dapat_menghapus_komentarnya_sendiri(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penulis = $this->buatPengguna($organisasi, null);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $komentar = KomentarEntitas::create(['JenisEntitas' => 'Lokasi', 'EntitasId' => $lokasi->Id, 'Isi' => 'Awal', 'DibuatOleh' => $penulis->Id]);
        $konteks->bersihkan();

        $this->actingAs($penulis)->delete("/kolaborasi/komentar/{$komentar->Id}")
            ->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertSoftDeleted('KomentarEntitas', ['Id' => $komentar->Id]);
        $konteks->bersihkan();
    }

    public function test_pengelola_entitas_dapat_menghapus_komentar_orang_lain(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penulis = $this->buatPengguna($organisasi, null);
        $pengelola = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $komentar = KomentarEntitas::create(['JenisEntitas' => 'Lokasi', 'EntitasId' => $lokasi->Id, 'Isi' => 'Awal', 'DibuatOleh' => $penulis->Id]);
        $konteks->bersihkan();

        $this->actingAs($pengelola)->delete("/kolaborasi/komentar/{$komentar->Id}")
            ->assertSessionDoesntHaveErrors();
    }

    public function test_pengguna_biasa_tanpa_izin_tidak_bisa_menghapus_komentar_orang_lain(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penulis = $this->buatPengguna($organisasi, null);
        $lain = $this->buatPengguna($organisasi, null);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $komentar = KomentarEntitas::create(['JenisEntitas' => 'Lokasi', 'EntitasId' => $lokasi->Id, 'Isi' => 'Awal', 'DibuatOleh' => $penulis->Id]);
        $konteks->bersihkan();

        $this->actingAs($lain)->delete("/kolaborasi/komentar/{$komentar->Id}")
            ->assertForbidden();
    }
}
