<?php

declare(strict_types=1);

namespace Tests\Feature\AlurKritis;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\JenisRiwayatLokasiAset;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\FiturPaket;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Carbon\CarbonImmutable;
use Database\Seeders\FiturPaketSeeder;
use Database\Seeders\IzinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * FASE 26.03 — alur kritis: tenant baru berdiri, menambah pengguna dan
 * perannya, membuat lokasi, lalu mendaftarkan aset pertamanya. Seluruh langkah
 * ditempuh lewat rute HTTP yang sama dengan yang dipakai pengguna, dan di
 * ujungnya organisasi lain yang sudah lebih dulu ada tidak boleh melihat apa
 * pun milik tenant baru itu.
 */
final class AlurSetupTenantTest extends TestCase
{
    use RefreshDatabase;

    private const KATA_SANDI = 'rahasia-panjang-01';

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 02:00:00', 'UTC'));
        $this->seed(IzinSeeder::class);
        $this->seed(FiturPaketSeeder::class);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_setup_tenant_pengguna_lokasi_aset_hanya_terlihat_di_tenant_itu(): void
    {
        $paket = $this->siapkanPaketTrial();
        $pembanding = $this->siapkanOrganisasiLain();
        app(KonteksOrganisasi::class)->bersihkan();

        // Langkah 1 — pendaftaran mandiri melahirkan organisasi dan pemiliknya.
        $this->post(route('daftar.store'), [
            'NamaOrganisasi' => 'RS Sehat Sentosa',
            'Nama' => 'Dewi Pemilik',
            'Email' => 'dewi@sehat.test',
            'Telepon' => '0811222333',
            'KataSandi' => self::KATA_SANDI,
            'KataSandi_confirmation' => self::KATA_SANDI,
            'Persetujuan' => true,
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $organisasi = Organisasi::query()->where('Nama', 'RS Sehat Sentosa')->sole();
        $this->assertNotSame($pembanding['organisasi']->Id, $organisasi->Id);
        $pemilik = Pengguna::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasi->Id)
            ->sole();
        $this->assertSame('dewi@sehat.test', $pemilik->Email);

        $langganan = Langganan::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasi->Id)
            ->sole();
        $this->assertSame(StatusLangganan::UjiCoba->value, $langganan->Status);
        $this->assertSame($paket->Id, $langganan->PaketLanggananId);

        $peranManajer = Peran::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasi->Id)
            ->where('Kode', 'MANAJER-ASET')
            ->sole();

        // Langkah 2 — pemilik masuk dengan email yang didaftarkannya.
        $this->masuk('dewi@sehat.test');
        $this->assertAuthenticatedAs($pemilik);

        // Langkah 3 — pemilik menambah pengguna kedua lalu memberinya peran Manajer Aset.
        $this->post(route('platform.pengguna.store'), [
            'Nama' => 'Bima Manajer',
            'Email' => 'bima@sehat.test',
            'KataSandi' => self::KATA_SANDI,
            'JenisPengguna' => 'Internal',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $manajer = Pengguna::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasi->Id)
            ->where('Email', 'bima@sehat.test')
            ->sole();
        $this->assertSame(2, Pengguna::query()->withoutGlobalScopes()->where('OrganisasiId', $organisasi->Id)->count());

        $this->post(route('platform.pengguna.peran.store', $manajer->Id), [
            'PeranId' => $peranManajer->Id,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(1, PenggunaPeran::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasi->Id)
            ->where('PenggunaId', $manajer->Id)
            ->where('PeranId', $peranManajer->Id)
            ->count());

        // Langkah 4 — pemilik membuat lokasi pertama.
        $this->post(route('platform.lokasi.store'), [
            'Kode' => 'LOK-RI',
            'Nama' => 'Gedung Rawat Inap',
            'ZonaWaktu' => 'Asia/Makassar',
            'Status' => 'Aktif',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $lokasi = Lokasi::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasi->Id)
            ->sole();
        $this->assertSame('Gedung Rawat Inap', $lokasi->Nama);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        // Langkah 5 — manajer masuk; perannya tidak memuat pengaturan lokasi.
        $this->masuk('bima@sehat.test');
        $this->assertAuthenticatedAs($manajer);

        $this->post(route('platform.lokasi.store'), [
            'Nama' => 'Lokasi Tanpa Hak',
            'Status' => 'Aktif',
        ])->assertForbidden();
        $this->assertSame(1, Lokasi::query()->withoutGlobalScopes()->where('OrganisasiId', $organisasi->Id)->count());

        // Langkah 6 — manajer menyiapkan kategori lalu mendaftarkan aset pertama di lokasi tadi.
        $this->post(route('aset-master.kategori.store'), [
            'Kode' => 'KAT-AM',
            'Nama' => 'Alat Medis',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $kategori = KategoriAset::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasi->Id)
            ->sole();

        $respons = $this->post(route('aset.store'), [
            'KodeAset' => 'AST-0001',
            'Nama' => 'Ventilator ICU',
            'KategoriAsetId' => $kategori->Id,
            'LokasiId' => $lokasi->Id,
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => 'SangatTinggi',
        ])->assertSessionHasNoErrors();

        $aset = Aset::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasi->Id)
            ->sole();
        $respons->assertRedirect("/aset/{$aset->Id}");
        $this->assertSame('Ventilator ICU', $aset->Nama);
        $this->assertSame($lokasi->Id, $aset->LokasiId);
        $this->assertSame($manajer->Id, $aset->DibuatOleh);
        $this->assertSame(1, RiwayatLokasiAset::query()->withoutGlobalScopes()
            ->where('AsetId', $aset->Id)
            ->where('LokasiTujuanId', $lokasi->Id)
            ->where('JenisPerpindahan', JenisRiwayatLokasiAset::Registrasi->value)
            ->count());

        // Tenant baru melihat asetnya sendiri — dan hanya itu.
        $this->get(route('aset.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Aset/Index')
                ->has('aset.data', 1)
                ->where('aset.data.0.Id', $aset->Id)
                ->etc());
        $this->get(route('aset.show', $aset->Id))->assertOk();
        $this->get(route('aset.show', $pembanding['aset']->Id))->assertNotFound();

        $this->post(route('logout'))->assertRedirect(route('login'));

        // Langkah 7 — organisasi lain tidak melihat apa pun milik tenant baru.
        $this->masuk($pembanding['pengguna']->Email);
        $this->assertAuthenticatedAs($pembanding['pengguna']);

        $this->get(route('aset.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->has('aset.data', 1)
                ->where('aset.data.0.Id', $pembanding['aset']->Id)
                ->etc());
        $this->get(route('aset.show', $aset->Id))->assertNotFound();

        $this->get(route('platform.lokasi.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->has('lokasi.data', 1)
                ->where('lokasi.data.0.Id', $pembanding['lokasi']->Id)
                ->etc());

        $this->get(route('platform.pengguna.show', $manajer->Id))->assertNotFound();

        // Tidak ada baris tenant baru yang bocor ke organisasi pembanding.
        $this->assertSame(1, Aset::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $pembanding['organisasi']->Id)->count());
        $this->assertSame(1, Pengguna::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $pembanding['organisasi']->Id)->count());
    }

    private function masuk(string $email): void
    {
        $this->post(route('login.store'), [
            'Email' => $email,
            'KataSandi' => self::KATA_SANDI,
        ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
    }

    /** Paket yang dipakai trial ditunjuk eksplisit supaya hasilnya tidak bergantung pada paket lain di basis data. */
    private function siapkanPaketTrial(): PaketLangganan
    {
        $paket = PaketLangganan::create([
            'Kode' => 'PKT-ALUR-TENANT',
            'Nama' => 'Paket Alur Tenant',
            'HargaBulanan' => 500_000,
            'HargaTahunan' => 5_000_000,
            'MataUang' => 'IDR',
            'Aktif' => true,
        ]);

        foreach (KatalogFitur::kode() as $kode) {
            PaketFitur::create([
                'PaketLanggananId' => $paket->Id,
                'FiturPaketId' => FiturPaket::query()->where('Kode', $kode)->firstOrFail()->Id,
                'Diizinkan' => true,
                'BatasNilai' => null,
            ]);
        }

        app(LayananKonfigurasiPemasaran::class)
            ->simpan(KatalogKonfigurasiPemasaran::TRIAL_PAKET_KODE, $paket->Kode);

        return $paket;
    }

    /**
     * Organisasi yang sudah berjalan lebih dulu, lengkap dengan aset, lokasi,
     * dan penggunanya sendiri. Tanpa data pembanding ini, "tidak melihat
     * apa-apa" hanya membandingkan daftar kosong dengan daftar kosong.
     *
     * @return array{organisasi: Organisasi, pengguna: Pengguna, lokasi: Lokasi, aset: Aset}
     */
    private function siapkanOrganisasiLain(): array
    {
        $organisasi = Organisasi::create([
            'Kode' => 'ORG-PEMBANDING',
            'Nama' => 'Klinik Pembanding',
            'Status' => 'Aktif',
        ]);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Admin Pembanding',
            'Email' => 'admin@pembanding.test',
            'KataSandi' => self::KATA_SANDI,
            'Status' => 'Aktif',
        ]);

        $peran = Peran::create(['OrganisasiId' => $organisasi->Id, 'Kode' => 'ADMIN-PEMBANDING', 'Nama' => 'Admin Pembanding']);
        foreach (['Aset.Lihat', 'Pengaturan.Kelola', 'Pengguna.Kelola'] as $kode) {
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => Izin::query()->where('Kode', $kode)->firstOrFail()->Id]);
        }
        PenggunaPeran::create(['OrganisasiId' => $organisasi->Id, 'PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        $lokasi = Lokasi::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'LOK-PMB',
            'Nama' => 'Ruang Pembanding',
            'Status' => 'Aktif',
        ]);
        $kategori = KategoriAset::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'KAT-PMB',
            'Nama' => 'Kategori Pembanding',
        ]);
        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategori->Id,
            'LokasiId' => $lokasi->Id,
            'KodeAset' => 'AST-PMB-01',
            'Nama' => 'Pompa Infus Lama',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
        ]);

        return ['organisasi' => $organisasi, 'pengguna' => $pengguna, 'lokasi' => $lokasi, 'aset' => $aset];
    }
}
