<?php

declare(strict_types=1);

namespace Tests\Feature\IntegrasiAudit;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatatanAuditControllerTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(Organisasi $organisasi, ?string $kodeIzin): Pengguna
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
            $izin = Izin::firstOrCreate(['Kode' => $kodeIzin], ['Nama' => $kodeIzin, 'Modul' => 'Audit']);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            $konteks->bersihkan();
        }

        return $pengguna;
    }

    public function test_pengguna_tanpa_izin_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, null);

        $response = $this->actingAs($pengguna)->get('/integrasi-audit/audit');

        $response->assertForbidden();
    }

    public function test_pengguna_dengan_izin_melihat_catatan_organisasinya_saja(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $admin = $this->buatPengguna($organisasiA, 'Audit.Lihat');

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiA->Id);
        CatatanAudit::create(['Aksi' => 'Lokasi.Dibuat', 'JenisEntitas' => 'Lokasi', 'EntitasId' => '01JXXX']);
        $konteks->bersihkan();

        $konteks->tetapkan($organisasiB->Id);
        CatatanAudit::create(['Aksi' => 'Lokasi.Dibuat', 'JenisEntitas' => 'Lokasi', 'EntitasId' => '01JYYY']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->get('/integrasi-audit/audit');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Audit/Index')
            ->has('catatan.data', 1)
            ->where('catatan.data.0.EntitasId', '01JXXX'));
    }

    public function test_filter_jenis_entitas_dan_aksi_bekerja(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi, 'Audit.Lihat');

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        CatatanAudit::create(['Aksi' => 'Lokasi.Dibuat', 'JenisEntitas' => 'Lokasi', 'EntitasId' => '01JAAA']);
        CatatanAudit::create(['Aksi' => 'UnitOrganisasi.Dibuat', 'JenisEntitas' => 'UnitOrganisasi', 'EntitasId' => '01JBBB']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->get('/integrasi-audit/audit?jenisEntitas=Lokasi');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('catatan.data', 1)
            ->where('catatan.data.0.JenisEntitas', 'Lokasi'));
    }

    public function test_data_rahasia_diredaksi_saat_kunci_api_dibuat_dan_dicabut(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi, 'Audit.Lihat');
        $izinIntegrasi = Izin::firstOrCreate(['Kode' => 'Integrasi.Kelola'], ['Nama' => 'Kelola Integrasi', 'Modul' => 'Integrasi']);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $peran = Peran::create(['Kode' => 'ADMIN-'.uniqid(), 'Nama' => 'Admin']);
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izinIntegrasi->Id]);
        PenggunaPeran::create(['PenggunaId' => $admin->Id, 'PeranId' => $peran->Id]);
        $konteks->bersihkan();

        $this->actingAs($admin)->post('/platform/kunci-api', ['Nama' => 'Integrasi ERP'])->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $catatan = CatatanAudit::where('Aksi', 'KunciApi.Dibuat')->first();
        $konteks->bersihkan();

        $this->assertNotNull($catatan);
        $this->assertArrayNotHasKey('HashKunci', $catatan->DataSesudah);
    }

    /**
     * Rentang tanggal jejak audit adalah tanggal di rumah sakitnya.
     *
     * Peristiwa pukul 01:00 WIT tanggal 22 tersimpan 16:00 UTC tanggal 21.
     * `whereDate` membandingkannya dengan tanggal UTC, jadi penyaring "22"
     * melewatkannya dan penyaring "21" menampilkannya.
     */
    public function test_penyaring_tanggal_memakai_kalender_zona_organisasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-WIT', 'Nama' => 'RS Jayapura', 'ZonaWaktu' => 'Asia/Jayapura']);
        $admin = $this->buatPengguna($organisasi, 'Audit.Lihat');

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        (new CatatanAudit)->forceFill([
            'Aksi' => 'Lokasi.Dibuat',
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => '01JDINI',
            'DibuatPada' => CarbonImmutable::parse('2026-09-21 16:00:00', 'UTC'),
        ])->save();
        $konteks->bersihkan();

        $this->actingAs($admin)
            ->get('/integrasi-audit/audit?dariTanggal=2026-09-22&sampaiTanggal=2026-09-22')
            ->assertInertia(fn ($page) => $page->has('catatan.data', 1)->where('catatan.data.0.EntitasId', '01JDINI'));

        $this->actingAs($admin)
            ->get('/integrasi-audit/audit?dariTanggal=2026-09-21&sampaiTanggal=2026-09-21')
            ->assertInertia(fn ($page) => $page->has('catatan.data', 0));
    }
}
