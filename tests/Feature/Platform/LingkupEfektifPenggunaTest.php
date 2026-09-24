<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Izin\LingkupAkses;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Lingkup efektif di halaman Pengguna dan konfirmasi pelebaran ke seluruh organisasi (PRD 8.21, TASK 40.06).
 */
class LingkupEfektifPenggunaTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $admin;

    private Peran $teknisi;

    private Peran $pelapor;

    private UnitOrganisasi $it;

    private Lokasi $icu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-LEP', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($this->organisasi->Id);

        $izin = Izin::firstOrCreate(['Kode' => 'Pengguna.Kelola'], ['Nama' => 'Kelola Pengguna', 'Modul' => 'IAM']);
        $peranAdmin = Peran::create(['Kode' => 'ADMIN', 'Nama' => 'Administrator']);
        PeranIzin::create(['PeranId' => $peranAdmin->Id, 'IzinId' => $izin->Id]);
        $this->admin = $this->buatPengguna('Admin');
        PenggunaPeran::create(['PenggunaId' => $this->admin->Id, 'PeranId' => $peranAdmin->Id]);

        $this->teknisi = Peran::create(['Kode' => 'TEKNISI', 'Nama' => 'Teknisi']);
        $this->pelapor = Peran::create(['Kode' => 'PELAPOR', 'Nama' => 'Pelapor']);

        $this->it = UnitOrganisasi::create(['Kode' => 'IT', 'Nama' => 'Instalasi IT', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
        UnitOrganisasi::create(['Kode' => 'IT-JAR', 'Nama' => 'Jaringan', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'IndukId' => $this->it->Id]);
        $icuUnit = UnitOrganisasi::create(['Kode' => 'ICU', 'Nama' => 'ICU', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $this->icu = Lokasi::create(['Kode' => 'R-ICU', 'Nama' => 'Ruang ICU', 'UnitOrganisasiId' => $icuUnit->Id]);

        $konteks->bersihkan();
    }

    public function test_detail_menampilkan_unit_dan_ruangan_beserta_peran_sumbernya(): void
    {
        $sasaran = $this->buatPengguna('Teknisi IT');
        $this->tetapkan($sasaran, $this->teknisi, unit: $this->it);
        $this->tetapkan($sasaran, $this->pelapor, lokasi: $this->icu);

        $this->actingAs($this->admin)
            ->get('/platform/pengguna/'.$sasaran->Id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Pengguna/Show')
                ->where('lingkupEfektif.SeluruhOrganisasi', false)
                ->where('lingkupEfektif.TanpaPeran', false)
                ->where('lingkupEfektif.Unit', [[
                    'Id' => $this->it->Id,
                    'Nama' => 'Instalasi IT',
                    'MengelolaAset' => true,
                    'Peran' => ['Teknisi'],
                ]])
                ->where('lingkupEfektif.Lokasi', [[
                    'Id' => $this->icu->Id,
                    'Nama' => 'Ruang ICU',
                    'Peran' => ['Pelapor'],
                ]])
                // Sub-unit Jaringan ikut tercakup lewat LingkupAkses.
                ->where('lingkupEfektif.JumlahUnit', 2));
    }

    public function test_satu_peran_tanpa_lingkup_membuat_detail_menyebut_seluruh_organisasi_dan_sumbernya(): void
    {
        $sasaran = $this->buatPengguna('Campuran');
        $this->tetapkan($sasaran, $this->teknisi, unit: $this->it);
        $this->tetapkan($sasaran, $this->pelapor);

        $this->actingAs($this->admin)
            ->get('/platform/pengguna/'.$sasaran->Id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('lingkupEfektif.SeluruhOrganisasi', true)
                ->where('lingkupEfektif.PeranTanpaLingkup', ['Pelapor'])
                ->where('lingkupEfektif.Unit', []));
    }

    /** Penetapan tanpa lingkup yang sudah lewat masa berlakunya tidak lagi membuka apa pun, sama seperti LingkupAkses. */
    public function test_penetapan_tanpa_lingkup_yang_kedaluwarsa_tidak_dihitung(): void
    {
        $sasaran = $this->buatPengguna('Kedaluwarsa');
        $this->tetapkan($sasaran, $this->teknisi, unit: $this->it);
        $this->tetapkan($sasaran, $this->pelapor, berlakuSampai: now()->subDay()->toDateTimeString());

        $this->actingAs($this->admin)
            ->get('/platform/pengguna/'.$sasaran->Id)
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('lingkupEfektif.SeluruhOrganisasi', false)
                ->where('lingkupEfektif.Unit.0.Peran', ['Teknisi']));
    }

    public function test_daftar_membawa_putusan_lingkup_per_pengguna(): void
    {
        $berlingkup = $this->buatPengguna('Berlingkup');
        $this->tetapkan($berlingkup, $this->teknisi, unit: $this->it);
        $tanpaPeran = $this->buatPengguna('Tanpa Peran');

        $this->actingAs($this->admin)
            ->get('/platform/pengguna')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('lingkupSeluruhOrganisasi.'.$berlingkup->Id, false)
                ->where('lingkupSeluruhOrganisasi.'.$tanpaPeran->Id, true)
                ->where('lingkupSeluruhOrganisasi.'.$this->admin->Id, true));
    }

    /**
     * Putusan banyak-pengguna harus sama persis dengan tanpaBatas() per pengguna,
     * termasuk penetapan yang kedaluwarsa atau belum berlaku.
     */
    public function test_putusan_banyak_pengguna_sama_dengan_putusan_satu_per_satu(): void
    {
        $kasus = [
            'tanpa peran' => [],
            'unit saja' => [['unit' => $this->it]],
            'lokasi saja' => [['lokasi' => $this->icu]],
            'campuran' => [['unit' => $this->it], []],
            'tanpa lingkup kedaluwarsa' => [['unit' => $this->it], ['sampai' => now()->subDay()->toDateTimeString()]],
            'tanpa lingkup belum berlaku' => [['unit' => $this->it], ['mulai' => now()->addDay()->toDateTimeString()]],
            'hanya penetapan kedaluwarsa' => [['unit' => $this->it, 'sampai' => now()->subDay()->toDateTimeString()]],
        ];

        $penggunaPerKasus = [];

        foreach ($kasus as $nama => $daftarPenetapan) {
            $pengguna = $this->buatPengguna($nama);
            $penggunaPerKasus[$nama] = (string) $pengguna->Id;

            foreach ($daftarPenetapan as $ke => $penetapan) {
                $this->tetapkan(
                    $pengguna,
                    $ke === 0 ? $this->teknisi : $this->pelapor,
                    unit: $penetapan['unit'] ?? null,
                    lokasi: $penetapan['lokasi'] ?? null,
                    berlakuMulai: $penetapan['mulai'] ?? null,
                    berlakuSampai: $penetapan['sampai'] ?? null,
                );
            }
        }

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $lingkup = app(LingkupAkses::class);
        $sekaligus = $lingkup->tanpaBatasUntuk(array_values($penggunaPerKasus));

        $diharapkan = [
            'tanpa peran' => true,
            'unit saja' => false,
            'lokasi saja' => false,
            'campuran' => true,
            'tanpa lingkup kedaluwarsa' => false,
            'tanpa lingkup belum berlaku' => false,
            'hanya penetapan kedaluwarsa' => true,
        ];

        foreach ($penggunaPerKasus as $nama => $penggunaId) {
            $this->assertSame($diharapkan[$nama], $lingkup->tanpaBatas($penggunaId), "tanpaBatas: {$nama}");
            $this->assertSame($lingkup->tanpaBatas($penggunaId), $sekaligus[$penggunaId], "tanpaBatasUntuk: {$nama}");
        }
    }

    public function test_daftar_menghitung_putusan_lingkup_dalam_satu_kueri(): void
    {
        foreach (range(1, 6) as $ke) {
            $this->tetapkan($this->buatPengguna('Staf '.$ke), $this->teknisi, unit: $ke % 2 === 0 ? $this->it : null);
        }

        $this->actingAs($this->admin)->get('/platform/pengguna')->assertOk();

        DB::enableQueryLog();
        $this->actingAs($this->admin)->get('/platform/pengguna')->assertOk();
        $kueriPenetapan = collect(DB::getQueryLog())
            ->filter(fn (array $kueri): bool => str_contains($kueri['query'], '`PenggunaPeran`') && str_contains($kueri['query'], '`BerlakuMulai`'))
            ->count();
        DB::disableQueryLog();

        // Satu untuk putusan halaman; LingkupAkses admin (tercache) tidak menambah.
        $this->assertSame(1, $kueriPenetapan);
    }

    public function test_peran_tanpa_lingkup_untuk_pengguna_berlingkup_ditolak_tanpa_konfirmasi(): void
    {
        $sasaran = $this->buatPengguna('Teknisi IT');
        $this->tetapkan($sasaran, $this->teknisi, unit: $this->it);

        $this->actingAs($this->admin)
            ->post('/platform/pengguna/'.$sasaran->Id.'/peran', ['PeranId' => $this->pelapor->Id])
            ->assertSessionHasErrors('KonfirmasiSeluruhOrganisasi');

        $this->assertSame(1, $this->jumlahPenetapan($sasaran));
    }

    public function test_peran_tanpa_lingkup_dengan_konfirmasi_tetap_disimpan_dan_membuka_seluruh_organisasi(): void
    {
        $sasaran = $this->buatPengguna('Teknisi IT');
        $this->tetapkan($sasaran, $this->teknisi, unit: $this->it);

        $this->actingAs($this->admin)
            ->post('/platform/pengguna/'.$sasaran->Id.'/peran', [
                'PeranId' => $this->pelapor->Id,
                'KonfirmasiSeluruhOrganisasi' => true,
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(2, $this->jumlahPenetapan($sasaran));
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $this->assertTrue(app(LingkupAkses::class)->tanpaBatas((string) $sasaran->Id));
    }

    /** Konfirmasi hanya diminta saat benar-benar melebarkan; tenant tanpa lingkup tidak melihat perubahan apa pun. */
    public function test_konfirmasi_tidak_diminta_bila_tidak_ada_yang_melebar(): void
    {
        $belumBerperan = $this->buatPengguna('Baru');
        $sudahSeluruh = $this->buatPengguna('Seluruh');
        $this->tetapkan($sudahSeluruh, $this->teknisi);
        $berlingkup = $this->buatPengguna('Berlingkup');
        $this->tetapkan($berlingkup, $this->teknisi, unit: $this->it);

        $this->actingAs($this->admin)
            ->post('/platform/pengguna/'.$belumBerperan->Id.'/peran', ['PeranId' => $this->pelapor->Id])
            ->assertSessionDoesntHaveErrors();
        $this->actingAs($this->admin)
            ->post('/platform/pengguna/'.$sudahSeluruh->Id.'/peran', ['PeranId' => $this->pelapor->Id])
            ->assertSessionDoesntHaveErrors();
        $this->actingAs($this->admin)
            ->post('/platform/pengguna/'.$berlingkup->Id.'/peran', ['PeranId' => $this->pelapor->Id, 'LokasiId' => $this->icu->Id])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, $this->jumlahPenetapan($belumBerperan));
        $this->assertSame(2, $this->jumlahPenetapan($sudahSeluruh));
        $this->assertSame(2, $this->jumlahPenetapan($berlingkup));
    }

    private function buatPengguna(string $nama): Pengguna
    {
        return Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => $nama,
            'Email' => 'p+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
    }

    private function tetapkan(
        Pengguna $pengguna,
        Peran $peran,
        ?UnitOrganisasi $unit = null,
        ?Lokasi $lokasi = null,
        ?string $berlakuMulai = null,
        ?string $berlakuSampai = null,
    ): void {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($this->organisasi->Id);

        PenggunaPeran::create([
            'PenggunaId' => $pengguna->Id,
            'PeranId' => $peran->Id,
            'UnitOrganisasiId' => $unit?->Id,
            'LokasiId' => $lokasi?->Id,
            'BerlakuMulai' => $berlakuMulai,
            'BerlakuSampai' => $berlakuSampai,
        ]);

        $konteks->bersihkan();
    }

    private function jumlahPenetapan(Pengguna $pengguna): int
    {
        return DB::table('PenggunaPeran')->where('PenggunaId', $pengguna->Id)->count();
    }
}
