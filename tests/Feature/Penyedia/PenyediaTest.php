<?php

declare(strict_types=1);

namespace Tests\Feature\Penyedia;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KontakPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\PenilaianPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenyediaTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(Organisasi $organisasi, ?string $kodeIzin = null): Pengguna
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
            $izin = Izin::firstOrCreate(['Kode' => $kodeIzin], ['Nama' => $kodeIzin, 'Modul' => 'Uji']);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            $konteks->bersihkan();
        }

        return $pengguna;
    }

    private function buatPenyedia(Organisasi $organisasi): Penyedia
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $penyedia = Penyedia::create([
            'Kode' => 'PYD-'.uniqid(),
            'Nama' => 'Penyedia Uji',
            'Status' => Penyedia::STATUS_AKTIF,
        ]);
        $konteks->bersihkan();

        return $penyedia;
    }

    public function test_pengguna_tanpa_izin_tidak_bisa_melihat_penyedia(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);

        $this->actingAs($pengguna)->get('/penyedia')->assertForbidden();
    }

    public function test_pengguna_dengan_izin_bisa_crud_penyedia(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');

        $this->actingAs($pengguna)->get('/penyedia')->assertOk();

        $this->actingAs($pengguna)->post('/penyedia', [
            'Kode' => 'PYD-001', 'Nama' => 'PT Sumber Makmur', 'Status' => 'Aktif',
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $penyedia = Penyedia::query()->where('Kode', 'PYD-001')->firstOrFail();
        $konteks->bersihkan();

        $this->actingAs($pengguna)->put("/penyedia/{$penyedia->Id}", [
            'Kode' => 'PYD-001', 'Nama' => 'PT Sumber Makmur Jaya', 'Status' => 'Nonaktif',
        ])->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseHas('Penyedia', ['Id' => $penyedia->Id, 'Nama' => 'PT Sumber Makmur Jaya', 'Status' => 'Nonaktif']);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->delete("/penyedia/{$penyedia->Id}")->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertSoftDeleted('Penyedia', ['Id' => $penyedia->Id]);
        $konteks->bersihkan();
    }

    public function test_kode_penyedia_unik_per_organisasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');
        $this->buatPenyedia($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $existing = Penyedia::query()->firstOrFail();
        $konteks->bersihkan();

        $this->actingAs($pengguna)->post('/penyedia', [
            'Kode' => $existing->Kode, 'Nama' => 'Penyedia Lain', 'Status' => 'Aktif',
        ])->assertSessionHasErrors('Kode');
    }

    public function test_kategori_penyedia_tidak_bisa_dihapus_bila_masih_dipakai(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');
        $penyedia = $this->buatPenyedia($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $kategori = KategoriPenyedia::create(['Kode' => 'KAT-01', 'Nama' => 'Distributor']);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->post("/penyedia/{$penyedia->Id}/kategori", [
            'KategoriPenyediaId' => $kategori->Id,
        ])->assertSessionDoesntHaveErrors();

        $this->actingAs($pengguna)->delete("/penyedia/kategori/{$kategori->Id}")
            ->assertStatus(422);

        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseHas('KategoriPenyedia', ['Id' => $kategori->Id]);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->delete("/penyedia/{$penyedia->Id}/kategori/{$kategori->Id}")
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($pengguna)->delete("/penyedia/kategori/{$kategori->Id}")
            ->assertSessionDoesntHaveErrors();
    }

    public function test_hanya_satu_kontak_utama_per_penyedia(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');
        $penyedia = $this->buatPenyedia($organisasi);

        $this->actingAs($pengguna)->post("/penyedia/{$penyedia->Id}/kontak", [
            'Nama' => 'Budi', 'Utama' => true,
        ])->assertSessionDoesntHaveErrors();

        $this->actingAs($pengguna)->post("/penyedia/{$penyedia->Id}/kontak", [
            'Nama' => 'Siti', 'Utama' => true,
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $kontakUtama = KontakPenyedia::query()->where('PenyediaId', $penyedia->Id)->where('Utama', true)->get();
        $this->assertCount(1, $kontakUtama);
        $this->assertSame('Siti', $kontakUtama->first()->Nama);
        $konteks->bersihkan();
    }

    public function test_penilaian_penyedia_menghitung_skor_total_otomatis_dan_rekap(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, 'Penyedia.Kelola');
        $penyedia = $this->buatPenyedia($organisasi);

        $this->actingAs($pengguna)->post("/penyedia/{$penyedia->Id}/penilaian", [
            'PeriodeMulai' => '2026-01-01', 'PeriodeSelesai' => '2026-03-31',
            'SkorKualitas' => 80, 'SkorKetepatanWaktu' => 90, 'SkorHarga' => 70, 'SkorLayanan' => 100,
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $penilaian = PenilaianPenyedia::query()->firstOrFail();
        $this->assertEquals(85.0, (float) $penilaian->SkorTotal);
        $this->assertNotNull($penilaian->DinilaiOleh);
        $konteks->bersihkan();

        $response = $this->actingAs($pengguna)->get("/penyedia/{$penyedia->Id}/penilaian");
        $response->assertOk();
        $this->assertSame(1, $response->json('rekap.JumlahPenilaian'));
        $this->assertEquals(85.0, (float) $response->json('rekap.SkorTotalRataRata'));
    }

    public function test_penyedia_lintas_organisasi_tidak_bisa_diakses(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $penggunaB = $this->buatPengguna($organisasiB, 'Penyedia.Kelola');
        $penyediaA = $this->buatPenyedia($organisasiA);

        $this->actingAs($penggunaB)->put("/penyedia/{$penyediaA->Id}", [
            'Kode' => 'HACK', 'Nama' => 'Hack', 'Status' => 'Aktif',
        ])->assertNotFound();
    }
}
