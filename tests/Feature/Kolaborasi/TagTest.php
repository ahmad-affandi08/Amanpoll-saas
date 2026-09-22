<?php

declare(strict_types=1);

namespace Tests\Feature\Kolaborasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\EntitasTag;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Tag;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
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

    /**
     * Pemilih tag di layar lain menghabiskan daftar ini sekaligus.
     *
     * Halaman admin Tag dipaginasi, tapi cabang JSON-nya tidak boleh ikut:
     * kalau ikut, tag ke-26 dan seterusnya akan hilang dari pemilih tanpa
     * satu pun pesan galat.
     */
    public function test_cabang_json_tag_tidak_dipaginasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        for ($i = 1; $i <= 30; $i++) {
            Tag::create(['Nama' => sprintf('Tag %02d', $i), 'Warna' => '#64748b']);
        }
        $konteks->bersihkan();

        $json = $this->actingAs($admin)->getJson('/kolaborasi/tag');
        $json->assertOk();
        $this->assertCount(30, $json->json());

        $halaman = $this->actingAs($admin)->get('/kolaborasi/tag');
        $halaman->assertOk();
        $halaman->assertInertia(fn ($page) => $page->has('tag.data', 25)->where('tag.meta.total', 30)->etc());
    }

    public function test_admin_dapat_membuat_mengubah_dan_menghapus_tag(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi);

        $this->actingAs($admin)->post('/kolaborasi/tag', ['Nama' => 'Kritis', 'Warna' => '#ff0000'])
            ->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $tag = Tag::where('Nama', 'Kritis')->first();
        $konteks->bersihkan();
        $this->assertNotNull($tag);

        $this->actingAs($admin)->put("/kolaborasi/tag/{$tag->Id}", ['Nama' => 'Sangat Kritis'])
            ->assertSessionDoesntHaveErrors();
        $konteks->tetapkan($organisasi->Id);
        $this->assertSame('Sangat Kritis', $tag->fresh()->Nama);
        $konteks->bersihkan();

        $this->actingAs($admin)->delete("/kolaborasi/tag/{$tag->Id}")->assertSessionDoesntHaveErrors();
        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseMissing('Tag', ['Id' => $tag->Id]);
        $konteks->bersihkan();
    }

    public function test_nama_tag_harus_unik_per_organisasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        Tag::create(['Nama' => 'Kritis']);
        $konteks->bersihkan();

        $this->actingAs($admin)->post('/kolaborasi/tag', ['Nama' => 'Kritis'])
            ->assertSessionHasErrors('Nama');
    }

    public function test_pengguna_tanpa_izin_tidak_bisa_membuat_tag(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, null);

        $this->actingAs($pengguna)->post('/kolaborasi/tag', ['Nama' => 'Kritis'])->assertForbidden();
    }

    public function test_tambahkan_dan_lepaskan_tag_dari_lokasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $tag = Tag::create(['Nama' => 'Prioritas']);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->post('/kolaborasi/entitas-tag', [
            'TagId' => $tag->Id,
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasi->Id,
        ])->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $entitasTag = EntitasTag::where('TagId', $tag->Id)->where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();
        $this->assertNotNull($entitasTag);

        $this->actingAs($pengguna)->delete("/kolaborasi/entitas-tag/{$entitasTag->Id}")
            ->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseMissing('EntitasTag', ['Id' => $entitasTag->Id]);
        $konteks->bersihkan();
    }

    public function test_menambahkan_tag_yang_sama_dua_kali_idempoten(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $tag = Tag::create(['Nama' => 'Prioritas']);
        $konteks->bersihkan();

        $payload = ['TagId' => $tag->Id, 'JenisEntitas' => 'Lokasi', 'EntitasId' => $lokasi->Id];
        $this->actingAs($pengguna)->post('/kolaborasi/entitas-tag', $payload)->assertSessionDoesntHaveErrors();
        $this->actingAs($pengguna)->post('/kolaborasi/entitas-tag', $payload)->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertSame(1, EntitasTag::where('TagId', $tag->Id)->count());
        $konteks->bersihkan();
    }

    public function test_tambahkan_tag_ditolak_tanpa_izin_kelola_entitas(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $tanpaIzin = $this->buatPengguna($organisasi, null);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $tag = Tag::create(['Nama' => 'Prioritas']);
        $konteks->bersihkan();

        $this->actingAs($tanpaIzin)->post('/kolaborasi/entitas-tag', [
            'TagId' => $tag->Id,
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasi->Id,
        ])->assertForbidden();
    }

    public function test_hapus_tag_juga_melepaskan_semua_penandaan(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $tag = Tag::create(['Nama' => 'Prioritas']);
        EntitasTag::create(['TagId' => $tag->Id, 'JenisEntitas' => 'Lokasi', 'EntitasId' => $lokasi->Id]);
        $konteks->bersihkan();

        $this->actingAs($admin)->delete("/kolaborasi/tag/{$tag->Id}")->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseMissing('EntitasTag', ['TagId' => $tag->Id]);
        $konteks->bersihkan();
    }
}
