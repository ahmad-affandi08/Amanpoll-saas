<?php

declare(strict_types=1);

namespace Tests\Feature\Notifikasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\PreferensiNotifikasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifikasiTest extends TestCase
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

    public function test_admin_dapat_membuat_templat_notifikasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi, 'Pengaturan.Kelola');

        $this->actingAs($admin)->post('/notifikasi/templat', [
            'Kode' => 'Persetujuan.PerluTindakan',
            'Kanal' => 'Email',
            'IsiTemplat' => 'Halo {Nama}, ada persetujuan menunggu.',
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseHas('TemplatNotifikasi', ['Kode' => 'Persetujuan.PerluTindakan', 'Kanal' => 'Email']);
        $konteks->bersihkan();
    }

    public function test_pengguna_tanpa_izin_tidak_bisa_membuat_templat(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);

        $this->actingAs($pengguna)->post('/notifikasi/templat', [
            'Kode' => 'X', 'Kanal' => 'Email', 'IsiTemplat' => 'Isi',
        ])->assertForbidden();
    }

    public function test_kirim_notifikasi_membuat_baris_dan_langsung_terkirim_untuk_in_app(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        app(LayananNotifikasi::class)->kirim(
            penggunaId: $pengguna->Id,
            jenisPeristiwa: 'Persetujuan.PerluTindakan',
            isi: 'Ada persetujuan menunggu.',
        );

        $notifikasi = Notifikasi::where('PenggunaId', $pengguna->Id)->first();
        $konteks->bersihkan();

        $this->assertNotNull($notifikasi);
        $this->assertSame(Notifikasi::STATUS_TERKIRIM, $notifikasi->Status);
        $this->assertNotNull($notifikasi->DikirimPada);
    }

    public function test_preferensi_nonaktif_mencegah_pengiriman(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        PreferensiNotifikasi::create([
            'PenggunaId' => $pengguna->Id, 'JenisPeristiwa' => 'Persetujuan.PerluTindakan', 'Kanal' => 'InApp', 'Aktif' => false,
        ]);

        app(LayananNotifikasi::class)->kirim(
            penggunaId: $pengguna->Id,
            jenisPeristiwa: 'Persetujuan.PerluTindakan',
            isi: 'Ada persetujuan menunggu.',
        );

        $jumlah = Notifikasi::where('PenggunaId', $pengguna->Id)->count();
        $konteks->bersihkan();

        $this->assertSame(0, $jumlah);
    }

    public function test_pengguna_dapat_melihat_dan_menandai_baca_notifikasi_miliknya(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        app(LayananNotifikasi::class)->kirim($pengguna->Id, 'Persetujuan.Disetujui', 'Disetujui.');
        $notifikasi = Notifikasi::where('PenggunaId', $pengguna->Id)->first();
        $konteks->bersihkan();

        $ringkasan = $this->actingAs($pengguna)->get('/notifikasi/ringkasan');
        $ringkasan->assertOk();
        $this->assertSame(1, $ringkasan->json('jumlahBelumDibaca'));

        $this->actingAs($pengguna)->post("/notifikasi/{$notifikasi->Id}/baca")->assertRedirect();

        $ringkasanSetelah = $this->actingAs($pengguna)->get('/notifikasi/ringkasan');
        $this->assertSame(0, $ringkasanSetelah->json('jumlahBelumDibaca'));
    }

    public function test_pengguna_tidak_bisa_menandai_baca_notifikasi_orang_lain(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pemilik = $this->buatPengguna($organisasi);
        $lain = $this->buatPengguna($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        app(LayananNotifikasi::class)->kirim($pemilik->Id, 'Persetujuan.Disetujui', 'Disetujui.');
        $notifikasi = Notifikasi::where('PenggunaId', $pemilik->Id)->first();
        $konteks->bersihkan();

        $this->actingAs($lain)->post("/notifikasi/{$notifikasi->Id}/baca")->assertForbidden();
    }

    public function test_pengguna_dapat_menyimpan_preferensi_notifikasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);

        $awal = $this->actingAs($pengguna)->get('/notifikasi/preferensi/data');
        $awal->assertOk();
        $this->assertTrue(collect($awal->json('data'))->every(fn ($p) => $p['Aktif'] === true));

        $this->actingAs($pengguna)->post('/notifikasi/preferensi', [
            'JenisPeristiwa' => 'Persetujuan.PerluTindakan', 'Kanal' => 'Email', 'Aktif' => false,
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseHas('PreferensiNotifikasi', [
            'PenggunaId' => $pengguna->Id, 'JenisPeristiwa' => 'Persetujuan.PerluTindakan', 'Kanal' => 'Email', 'Aktif' => false,
        ]);
        $konteks->bersihkan();
    }
}
