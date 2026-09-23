<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Actions\PasangPeranAwal;
use App\Domain\Platform\Domain\ValueObjects\KatalogPeranAwal;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Database\Seeders\IzinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeranAwalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IzinSeeder::class);
    }

    /**
     * Katalog menyebut izin lewat kodenya, dan kode yang salah ketik hanya
     * berakibat peran itu lahir tanpa izinnya -- diam, tanpa galat.
     */
    public function test_seluruh_kode_izin_katalog_benar_benar_ada(): void
    {
        $dikenal = Izin::query()->pluck('Kode')->all();
        $asing = [];

        foreach (KatalogPeranAwal::semua() as $contoh) {
            foreach ($contoh['Izin'] as $kode) {
                if (! in_array($kode, $dikenal, true)) {
                    $asing[] = "{$contoh['Kode']}: {$kode}";
                }
            }
        }

        $this->assertSame([], $asing, 'Kode izin berikut tidak ada di IzinSeeder.');
    }

    /** Izin penembus kontrol tidak boleh ikut menempel pada jabatan. */
    public function test_katalog_tidak_memuat_izin_berisiko_tinggi(): void
    {
        foreach (KatalogPeranAwal::semua() as $contoh) {
            foreach (KatalogPeranAwal::IZIN_DIKECUALIKAN as $terlarang) {
                $this->assertNotContains(
                    $terlarang,
                    $contoh['Izin'],
                    "Peran {$contoh['Kode']} tidak boleh membawa {$terlarang}.",
                );
            }
        }
    }

    public function test_kode_peran_katalog_tidak_kembar(): void
    {
        $kode = array_column(KatalogPeranAwal::semua(), 'Kode');

        $this->assertSame(array_values(array_unique($kode)), $kode);
    }

    public function test_memasang_seluruh_peran_beserta_izinnya(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-PASANG');

        $baru = app(PasangPeranAwal::class)->jalankan($organisasi->Id);

        $this->assertCount(count(KatalogPeranAwal::semua()), $baru);

        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        foreach (KatalogPeranAwal::semua() as $contoh) {
            $peran = Peran::query()->where('Kode', $contoh['Kode'])->first();

            $this->assertNotNull($peran, "Peran {$contoh['Kode']} tidak terpasang.");
            $this->assertSame($contoh['Nama'], $peran->Nama);
            $this->assertCount(count($contoh['Izin']), $peran->peranIzin);
        }
    }

    /** Titik mulai, bukan pagar: tenant harus tetap dapat menghapusnya. */
    public function test_peran_bawaan_tidak_ditandai_bawaan_sistem(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-HAPUS');

        app(PasangPeranAwal::class)->jalankan($organisasi->Id);

        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $this->assertSame(0, Peran::query()->where('BawaanSistem', true)->count());
    }

    public function test_pemasangan_kedua_tidak_menduplikasi(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-ULANG');
        $aksi = app(PasangPeranAwal::class);

        $aksi->jalankan($organisasi->Id);
        $kedua = $aksi->jalankan($organisasi->Id);

        $this->assertSame([], $kedua);

        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $this->assertSame(count(KatalogPeranAwal::semua()), Peran::query()->count());
    }

    /**
     * Tenant yang sudah menyempitkan "Teknisi" miliknya tidak boleh kehilangan
     * penyesuaian itu hanya karena tombol pasang ditekan lagi.
     */
    public function test_pemasangan_ulang_tidak_mengembalikan_izin_yang_disesuaikan(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-SESUAI');
        $aksi = app(PasangPeranAwal::class);
        $aksi->jalankan($organisasi->Id);

        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $teknisi = Peran::query()->where('Kode', 'TEKNISI')->firstOrFail();
        PeranIzin::query()->where('PeranId', $teknisi->Id)->delete();
        $teknisi->update(['Nama' => 'Teknisi Lapangan']);

        $aksi->jalankan($organisasi->Id);

        $this->assertSame(0, PeranIzin::query()->where('PeranId', $teknisi->Id)->count());
        $this->assertSame('Teknisi Lapangan', $teknisi->fresh()?->Nama);
    }

    public function test_peran_hanya_masuk_ke_organisasi_yang_ditunjuk(): void
    {
        $satu = $this->buatOrganisasi('ORG-SATU');
        $dua = $this->buatOrganisasi('ORG-DUA');

        app(PasangPeranAwal::class)->jalankan($satu->Id);

        app(KonteksOrganisasi::class)->tetapkan($dua->Id);
        $this->assertSame(0, Peran::query()->count());
    }

    /**
     * Sesi yang sedang berjalan di satu tenant tidak boleh menulis ke tenant lain.
     *
     * Pesannya ikut diperiksa karena MilikOrganisasi juga menolak penulisan ini
     * di tingkat model. Tanpa itu, mencabut penjaga di aksinya tetap terlihat
     * hijau -- yang tertangkap hanya jaring lapis bawahnya.
     */
    public function test_menolak_memasang_ke_organisasi_lain_saat_konteks_aktif(): void
    {
        $satu = $this->buatOrganisasi('ORG-AKTIF');
        $dua = $this->buatOrganisasi('ORG-LAIN');

        app(KonteksOrganisasi::class)->tetapkan($satu->Id);

        $this->expectException(AturanBisnisDilanggar::class);
        $this->expectExceptionMessage('Peran bawaan tidak boleh dipasang ke organisasi lain.');

        app(PasangPeranAwal::class)->jalankan($dua->Id);
    }

    public function test_halaman_peran_mengirim_jumlah_bawaan_yang_belum_terpasang(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-HALAMAN');
        $admin = $this->buatAdmin($organisasi);

        $this->actingAs($admin)->get('/platform/peran')
            ->assertOk()
            ->assertInertia(fn ($props) => $props
                ->where('bawaanBelumTerpasang', count(KatalogPeranAwal::semua()))
                ->etc());
    }

    public function test_admin_dapat_memasang_peran_bawaan_lewat_rute(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-RUTE');
        $admin = $this->buatAdmin($organisasi);

        $this->actingAs($admin)->post('/platform/peran/bawaan')
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($admin)->get('/platform/peran')
            ->assertInertia(fn ($props) => $props->where('bawaanBelumTerpasang', 0)->etc());

        $this->assertDatabaseHas('Peran', ['Kode' => 'TEKNISI', 'OrganisasiId' => $organisasi->Id]);
    }

    public function test_pengguna_tanpa_izin_ditolak_memasang_peran_bawaan(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-TOLAK');

        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $this->actingAs($pengguna)->post('/platform/peran/bawaan')->assertForbidden();

        $this->assertDatabaseMissing('Peran', ['Kode' => 'TEKNISI', 'OrganisasiId' => $organisasi->Id]);
    }

    public function test_perintah_artisan_memasang_ke_seluruh_organisasi(): void
    {
        $satu = $this->buatOrganisasi('ORG-CLI-1');
        $dua = $this->buatOrganisasi('ORG-CLI-2');

        $this->artisan('platform:pasang-peran-awal')->assertSuccessful();

        foreach ([$satu, $dua] as $organisasi) {
            $this->assertDatabaseHas('Peran', [
                'Kode' => 'AUDITOR',
                'OrganisasiId' => $organisasi->Id,
            ]);
        }
    }

    public function test_perintah_artisan_dapat_dibatasi_ke_satu_organisasi(): void
    {
        $satu = $this->buatOrganisasi('ORG-CLI-A');
        $dua = $this->buatOrganisasi('ORG-CLI-B');

        $this->artisan('platform:pasang-peran-awal', ['--organisasi' => $satu->Id])->assertSuccessful();

        $this->assertDatabaseHas('Peran', ['Kode' => 'AUDITOR', 'OrganisasiId' => $satu->Id]);
        $this->assertDatabaseMissing('Peran', ['Kode' => 'AUDITOR', 'OrganisasiId' => $dua->Id]);
    }

    private function buatOrganisasi(string $kode): Organisasi
    {
        app(KonteksOrganisasi::class)->bersihkan();

        return Organisasi::create(['Kode' => $kode, 'Nama' => $kode, 'Status' => 'Aktif']);
    }

    private function buatAdmin(Organisasi $organisasi): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Admin',
            'Email' => 'admin+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);

        $peran = Peran::create(['Kode' => 'ADMIN', 'Nama' => 'Administrator']);
        $izin = Izin::query()->where('Kode', 'Pengguna.Kelola')->firstOrFail();
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        $konteks->bersihkan();

        return $pengguna;
    }
}
