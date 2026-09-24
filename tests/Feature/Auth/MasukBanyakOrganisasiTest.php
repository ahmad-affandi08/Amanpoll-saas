<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAkses;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Masuk tanpa kode organisasi bila email yang sama terdaftar di beberapa
 * organisasi (PRD 8.1, TASK 41): layar Pilih organisasi dan penjaga daftar sesinya.
 */
class MasukBanyakOrganisasiTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'rina@amanpoll.test';

    private const KATA_SANDI = 'kata-sandi-benar';

    private function buatAkun(
        string $namaOrganisasi,
        string $kataSandi = self::KATA_SANDI,
        string $statusOrganisasi = 'Aktif',
        string $statusPengguna = 'Aktif',
    ): Pengguna {
        $organisasi = Organisasi::create([
            'Kode' => 'ORG-'.uniqid(),
            'Nama' => $namaOrganisasi,
            'Status' => $statusOrganisasi,
        ]);

        return Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Rina',
            'Email' => self::EMAIL,
            'KataSandi' => $kataSandi,
            'Status' => $statusPengguna,
        ]);
    }

    /**
     * @param  array<string, mixed>  $tambahan
     * @return TestResponse<Response>
     */
    private function kirimLogin(array $tambahan = []): TestResponse
    {
        return $this->post('/login', array_merge(['Email' => self::EMAIL, 'KataSandi' => self::KATA_SANDI], $tambahan));
    }

    public function test_email_di_dua_organisasi_dengan_kata_sandi_sama_menampilkan_pilihan_lalu_masuk_ke_yang_dipilih(): void
    {
        $diRumahSakit = $this->buatAkun('RS Sehat');
        $diKlinik = $this->buatAkun('Klinik Prima');

        $this->kirimLogin()->assertRedirect(route('login.organisasi'));
        $this->assertGuest();

        $this->get('/login/organisasi')
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->component('Auth/PilihOrganisasi')
                ->has('pilihan', 2)
                ->where('pilihan.0.NamaOrganisasi', 'Klinik Prima')
                ->where('pilihan.0.PenggunaId', $diKlinik->Id)
                ->where('pilihan.1.NamaOrganisasi', 'RS Sehat')
                ->where('pilihan.1.PenggunaId', $diRumahSakit->Id));

        $this->post('/login/organisasi', ['PenggunaId' => $diKlinik->Id])
            ->assertRedirect(route('dashboard'))
            ->assertSessionMissing(LoginController::SESI_PILIHAN);

        $this->assertAuthenticatedAs($diKlinik);
        $this->assertNotNull($diKlinik->fresh()->TerakhirMasukPada);
        $this->assertNull($diRumahSakit->fresh()->TerakhirMasukPada);
        $this->assertTrue(CatatanAkses::withoutGlobalScope(ScopeOrganisasi::class)
            ->where('Jenis', 'Login')->where('Berhasil', true)
            ->where('OrganisasiId', $diKlinik->OrganisasiId)
            ->where('PenggunaId', $diKlinik->Id)
            ->exists());
    }

    public function test_sesi_diganti_saat_daftar_pilihan_disimpan(): void
    {
        $this->buatAkun('RS Sehat');
        $this->buatAkun('Klinik Prima');

        $this->get('/login');
        $idSesiSebelum = session()->getId();

        // Cookie sesi dibawa eksplisit: tanpa itu tiap permintaan test sudah mendapat Id baru.
        $this->withCookie((string) config('session.cookie'), $idSesiSebelum)
            ->kirimLogin()
            ->assertRedirect(route('login.organisasi'));

        $this->assertNotSame($idSesiSebelum, session()->getId());
    }

    public function test_kata_sandi_hanya_cocok_di_satu_organisasi_langsung_masuk_tanpa_layar_pilih(): void
    {
        $cocok = $this->buatAkun('RS Sehat');
        $this->buatAkun('Klinik Prima', kataSandi: 'kata-sandi-lain');

        $this->kirimLogin()
            ->assertRedirect(route('dashboard'))
            ->assertSessionMissing(LoginController::SESI_PILIHAN);

        $this->assertAuthenticatedAs($cocok);
    }

    public function test_layar_pilih_hanya_memuat_organisasi_yang_kata_sandinya_cocok(): void
    {
        $this->buatAkun('RS Sehat');
        $this->buatAkun('Klinik Prima');
        $this->buatAkun('Puskesmas Lain', kataSandi: 'kata-sandi-lain');

        $this->kirimLogin();

        $this->get('/login/organisasi')
            ->assertInertia(fn ($halaman) => $halaman
                ->has('pilihan', 2)
                ->where('pilihan.0.NamaOrganisasi', 'Klinik Prima')
                ->where('pilihan.1.NamaOrganisasi', 'RS Sehat'));
    }

    public function test_pengguna_id_di_luar_daftar_sesi_ditolak(): void
    {
        $this->buatAkun('RS Sehat');
        $this->buatAkun('Klinik Prima');
        $sandiLain = $this->buatAkun('Puskesmas Lain', kataSandi: 'kata-sandi-lain');

        $this->kirimLogin();

        $this->from('/login/organisasi')
            ->post('/login/organisasi', ['PenggunaId' => $sandiLain->Id])
            ->assertRedirect('/login/organisasi')
            ->assertSessionHasErrors('PenggunaId');

        $this->assertGuest();
    }

    public function test_daftar_pilihan_yang_kedaluwarsa_ditolak(): void
    {
        $akun = $this->buatAkun('RS Sehat');
        $this->buatAkun('Klinik Prima');

        $this->kirimLogin();
        $this->travel(LoginController::KEDALUWARSA_PILIHAN_MENIT + 1)->minutes();

        $this->post('/login/organisasi', ['PenggunaId' => $akun->Id])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('Email')
            ->assertSessionMissing(LoginController::SESI_PILIHAN);

        $this->assertGuest();
    }

    public function test_daftar_pilihan_masih_berlaku_sebelum_batas_waktu(): void
    {
        $akun = $this->buatAkun('RS Sehat');
        $this->buatAkun('Klinik Prima');

        $this->kirimLogin();
        $this->travel(LoginController::KEDALUWARSA_PILIHAN_MENIT - 1)->minutes();

        $this->post('/login/organisasi', ['PenggunaId' => $akun->Id])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($akun);
    }

    public function test_tanpa_daftar_sesi_layar_pilih_dan_pemilihan_kembali_ke_login(): void
    {
        $akun = $this->buatAkun('RS Sehat');

        $this->get('/login/organisasi')->assertRedirect(route('login'));
        $this->post('/login/organisasi', ['PenggunaId' => $akun->Id])->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_kembali_membersihkan_daftar_pilihan(): void
    {
        $akun = $this->buatAkun('RS Sehat');
        $this->buatAkun('Klinik Prima');

        $this->kirimLogin();

        $this->delete('/login/organisasi')
            ->assertRedirect(route('login'))
            ->assertSessionMissing(LoginController::SESI_PILIHAN);

        $this->post('/login/organisasi', ['PenggunaId' => $akun->Id])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_akun_nonaktif_dan_organisasi_nonaktif_tidak_ikut_dicocokkan(): void
    {
        $aktif = $this->buatAkun('RS Sehat');
        $this->buatAkun('Klinik Nonaktif', statusOrganisasi: 'Nonaktif');
        $this->buatAkun('Puskesmas', statusPengguna: 'Nonaktif');

        $this->kirimLogin()->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($aktif);
    }

    public function test_akun_yang_dinonaktifkan_sesudah_daftar_disimpan_tidak_dapat_dipilih(): void
    {
        $akun = $this->buatAkun('RS Sehat');
        $this->buatAkun('Klinik Prima');

        $this->kirimLogin();
        $akun->update(['Status' => 'Nonaktif']);

        $this->from('/login/organisasi')
            ->post('/login/organisasi', ['PenggunaId' => $akun->Id])
            ->assertSessionHasErrors('PenggunaId');
        $this->get('/login/organisasi')->assertInertia(fn ($halaman) => $halaman->has('pilihan', 1));

        $this->assertGuest();
    }

    public function test_organisasi_yang_dinonaktifkan_sesudah_daftar_disimpan_tidak_dapat_dipilih(): void
    {
        $akun = $this->buatAkun('RS Sehat');
        $this->buatAkun('Klinik Prima');

        $this->kirimLogin();
        Organisasi::query()->whereKey($akun->OrganisasiId)->update(['Status' => 'Nonaktif']);

        $this->from('/login/organisasi')
            ->post('/login/organisasi', ['PenggunaId' => $akun->Id])
            ->assertSessionHasErrors('PenggunaId');

        $this->assertGuest();
    }

    public function test_kata_sandi_salah_di_dua_organisasi_memberi_pesan_umum_dan_tercatat_di_tiap_organisasi(): void
    {
        $diRumahSakit = $this->buatAkun('RS Sehat');
        $diKlinik = $this->buatAkun('Klinik Prima');

        $this->kirimLogin(['KataSandi' => 'salah'])
            ->assertSessionHasErrors(['Email' => 'Email atau kata sandi tidak sesuai.'])
            ->assertSessionMissing(LoginController::SESI_PILIHAN);

        $this->assertGuest();
        $gagal = CatatanAkses::withoutGlobalScope(ScopeOrganisasi::class)
            ->where('Jenis', 'Login')->where('Berhasil', false)
            ->pluck('OrganisasiId')->sort()->values()->all();
        $this->assertSame(collect([$diRumahSakit->OrganisasiId, $diKlinik->OrganisasiId])->sort()->values()->all(), $gagal);
    }

    public function test_batas_percobaan_berlaku_per_email_sehingga_email_lain_tetap_dapat_masuk(): void
    {
        $this->buatAkun('RS Sehat');
        $lain = Pengguna::create([
            'OrganisasiId' => Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'Organisasi Lain'])->Id,
            'Nama' => 'Budi',
            'Email' => 'budi@amanpoll.test',
            'KataSandi' => self::KATA_SANDI,
            'Status' => 'Aktif',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->kirimLogin(['KataSandi' => 'salah']);
        }

        $this->kirimLogin()->assertSessionHasErrors('Email');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->get('Email')[0]);
        $this->assertGuest();

        $this->post('/login', ['Email' => 'BUDI@amanpoll.test', 'KataSandi' => self::KATA_SANDI])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($lain);
    }

    public function test_ingat_saya_dibawa_melewati_layar_pilih(): void
    {
        $akun = $this->buatAkun('RS Sehat');
        $this->buatAkun('Klinik Prima');

        $this->kirimLogin(['IngatSaya' => true]);

        $this->post('/login/organisasi', ['PenggunaId' => $akun->Id])
            ->assertCookie($this->namaCookieIngat());

        $this->assertNotNull($akun->fresh()->TokenIngat);
    }

    public function test_tanpa_ingat_saya_layar_pilih_tidak_memasang_cookie_ingat(): void
    {
        $akun = $this->buatAkun('RS Sehat');
        $this->buatAkun('Klinik Prima');

        $this->kirimLogin();

        $this->post('/login/organisasi', ['PenggunaId' => $akun->Id])
            ->assertCookieMissing($this->namaCookieIngat());

        $this->assertNull($akun->fresh()->TokenIngat);
    }

    public function test_layar_pilih_tertutup_bagi_pengguna_yang_sudah_masuk(): void
    {
        $akun = $this->buatAkun('RS Sehat');

        $this->actingAs($akun)->get('/login/organisasi')->assertRedirect(route('dashboard'));
    }

    /** Nama cookie "ingat saya" guard `web`, sama dengan `SessionGuard::getRecallerName()`. */
    private function namaCookieIngat(): string
    {
        return 'remember_web_'.sha1(SessionGuard::class);
    }
}
