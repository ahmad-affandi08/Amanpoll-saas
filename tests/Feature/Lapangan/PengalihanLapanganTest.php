<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Http\Middleware\ArahkanPenggunaLapangan;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Pengarahan pengguna lapangan (PRD 8.20, TASK 39.02): tujuan sesudah login,
 * middleware dasbor, peralihan pengguna campuran, dan rute `/lapangan`.
 */
final class PengalihanLapanganTest extends KasusLapangan
{
    public function test_teknisi_murni_diarahkan_ke_mode_lapangan_sesudah_login(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);

        $this->post('/login', $this->kredensial($teknisi->Email))->assertRedirect('/lapangan');
    }

    public function test_pengguna_campuran_tetap_masuk_dasbor_sesudah_login(): void
    {
        $campuran = $this->penggunaMeja(['Aset.Lihat'], $this->penggunaDenganPeran(['TEKNISI']));

        $this->post('/login', $this->kredensial($campuran->Email))->assertRedirect('/');
    }

    /** Email yang sama di organisasi lain: tujuan sesudah layar Pilih organisasi tetap mengikuti akun yang dipilih. */
    public function test_teknisi_murni_yang_memilih_organisasi_tetap_diarahkan_ke_mode_lapangan(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $organisasiLain = Organisasi::create(['Kode' => 'ORG-LAIN-'.uniqid(), 'Nama' => 'Organisasi Lain', 'Status' => 'Aktif']);
        Pengguna::create([
            'OrganisasiId' => $organisasiLain->Id,
            'Nama' => 'Akun Kedua',
            'Email' => $teknisi->Email,
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $this->post('/login', $this->kredensial($teknisi->Email))->assertRedirect(route('login.organisasi'));
        $this->get('/login/organisasi')->assertOk();

        $this->post('/login/organisasi', ['PenggunaId' => $teknisi->Id])->assertRedirect('/lapangan');
        $this->assertAuthenticatedAs($teknisi);
    }

    /** `intended()` tetap dihormati; halaman dasbornya yang lalu dialihkan middleware. */
    public function test_url_tujuan_sebelum_login_tetap_dihormati_lalu_dialihkan_middleware(): void
    {
        $pelapor = $this->penggunaDenganPeran(['PELAPOR']);

        $this->withSession(['url.intended' => 'http://localhost/aset'])
            ->post('/login', $this->kredensial($pelapor->Email))
            ->assertRedirect('http://localhost/aset');

        $this->get('/aset')->assertRedirect('/lapangan');
    }

    /** @return array<string, array{string}> */
    public static function halamanDasbor(): array
    {
        return [
            'beranda dasbor' => ['/'],
            'daftar aset' => ['/aset'],
            'perintah kerja' => ['/pemeliharaan/perintah-kerja'],
            'keluhan' => ['/pemeliharaan/keluhan'],
            'profil' => ['/platform/profil'],
            'peran' => ['/platform/peran'],
            'dasbor kustom' => ['/pelaporan/dasbor'],
        ];
    }

    #[DataProvider('halamanDasbor')]
    public function test_pengguna_lapangan_murni_yang_membuka_halaman_dasbor_dialihkan(string $url): void
    {
        $pelapor = $this->penggunaDenganPeran(['PELAPOR']);

        $this->actingAs($pelapor)->get($url)->assertRedirect('/lapangan');
    }

    public function test_kunjungan_inertia_ke_halaman_dasbor_juga_dialihkan(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);

        $versi = (string) app(HandleInertiaRequests::class)->version(request());

        $this->actingAs($teknisi)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $versi,
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'text/html, application/xhtml+xml',
            ])
            ->get('/pemeliharaan/perintah-kerja')
            ->assertRedirect('/lapangan');
    }

    public function test_endpoint_data_bersama_tetap_terbuka_bagi_pengguna_lapangan_murni(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $this->actingAs($teknisi);

        $this->getJson('/notifikasi/ringkasan')->assertOk()->assertJsonPath('jumlahBelumDibaca', 0);
        $this->getJson('/offline/ringkasan')->assertOk()->assertJsonPath('Konflik', 0);
        $this->getJson('/cari?q=pompa')->assertOk();
        // Permintaan data ke rute dasbor mendapat jawaban aslinya, bukan pengalihan.
        $this->getJson('/platform/izin')->assertForbidden();
    }

    public function test_unduhan_berkas_dan_permintaan_tulis_tidak_dialihkan(): void
    {
        $pelapor = $this->penggunaDenganPeran(['PELAPOR']);
        $this->actingAs($pelapor);

        Storage::fake('local');
        Storage::fake('public');
        $this->post('/kolaborasi/berkas', ['Berkas' => UploadedFile::fake()->image('bukti.jpg')])->assertRedirect();
        $berkas = $this->dalamOrganisasi(fn () => Berkas::query()->where('DiunggahOleh', $pelapor->Id)->firstOrFail());

        // Unduhan dibuka sebagai navigasi peramban, jadi jalurnya dibebaskan secara eksplisit.
        $this->get("/kolaborasi/berkas/{$berkas->Id}/unduh")->assertOk()->assertDownload('bukti.jpg');
        $this->postJson('/pemeliharaan/keluhan', [])->assertUnprocessable();
    }

    public function test_pengguna_meja_tidak_pernah_dialihkan(): void
    {
        $meja = $this->penggunaMeja(['Aset.Lihat']);

        $this->actingAs($meja)->get('/aset')->assertOk();
        $this->actingAs($meja)->get('/lapangan')->assertRedirect('/');
    }

    public function test_pengguna_campuran_tidak_dialihkan_dan_dapat_beralih_per_perangkat(): void
    {
        $campuran = $this->penggunaMeja(['Aset.Lihat'], $this->penggunaDenganPeran(['TEKNISI']));
        $this->actingAs($campuran);

        $this->get('/')->assertOk();
        $this->get('/aset')->assertOk();

        $this->post('/lapangan/tampilan', ['Tampilan' => 'lapangan'])
            ->assertRedirect('/lapangan')
            ->assertCookie(ArahkanPenggunaLapangan::COOKIE_TAMPILAN, 'lapangan');

        // Pilihan Mode Lapangan membawa beranda dasbor ke Mode Lapangan; halaman dasbor lain tetap terbuka.
        $this->withCookie(ArahkanPenggunaLapangan::COOKIE_TAMPILAN, 'lapangan')->get('/')->assertRedirect('/lapangan');
        $this->withCookie(ArahkanPenggunaLapangan::COOKIE_TAMPILAN, 'lapangan')->get('/aset')->assertOk();

        $this->post('/lapangan/tampilan', ['Tampilan' => 'dasbor'])
            ->assertRedirect('/')
            ->assertCookie(ArahkanPenggunaLapangan::COOKIE_TAMPILAN, 'dasbor');
        $this->withCookie(ArahkanPenggunaLapangan::COOKIE_TAMPILAN, 'dasbor')->get('/')->assertOk();
    }

    public function test_pengguna_lapangan_murni_tidak_dapat_beralih_ke_dasbor(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);

        $this->actingAs($teknisi)->post('/lapangan/tampilan', ['Tampilan' => 'dasbor'])->assertForbidden();
        $this->withCookie(ArahkanPenggunaLapangan::COOKIE_TAMPILAN, 'dasbor')->get('/aset')->assertRedirect('/lapangan');
    }

    public function test_pengguna_meja_tidak_dapat_memilih_mode_lapangan(): void
    {
        $meja = $this->penggunaMeja(['Aset.Lihat']);

        $this->actingAs($meja)->post('/lapangan/tampilan', ['Tampilan' => 'lapangan'])->assertForbidden();
    }

    public function test_pilihan_tampilan_yang_tidak_dikenal_ditolak(): void
    {
        $campuran = $this->penggunaMeja(['Aset.Lihat'], $this->penggunaDenganPeran(['PELAPOR']));

        $this->actingAs($campuran)->post('/lapangan/tampilan', ['Tampilan' => 'admin'])
            ->assertSessionHasErrors(['Tampilan' => 'Tampilan yang dipilih tidak valid.']);
    }

    public function test_jalur_lama_offline_teknisi_dialihkan_ke_mode_lapangan(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);

        $this->actingAs($teknisi)->get('/offline/teknisi')->assertRedirect('/lapangan');
    }

    public function test_beranda_mode_lapangan_mengikuti_mode_dan_teknisi_menang(): void
    {
        $this->actingAs($this->penggunaDenganPeran(['TEKNISI']))->get('/lapangan')->assertRedirect('/lapangan/teknisi');
        $this->actingAs($this->penggunaDenganPeran(['PELAPOR']))->get('/lapangan')->assertRedirect('/lapangan/pelapor');
        $this->actingAs($this->penggunaDenganPeran(['PELAPOR', 'TEKNISI']))->get('/lapangan')->assertRedirect('/lapangan/teknisi');
    }

    public function test_layar_teknisi_tertutup_bagi_pelapor_tetapi_layar_pelapor_terbuka_bagi_teknisi(): void
    {
        $pelapor = $this->penggunaDenganPeran(['PELAPOR']);
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);

        $this->actingAs($pelapor)->get('/lapangan/teknisi')->assertRedirect('/lapangan');
        $this->actingAs($pelapor)->getJson('/lapangan/teknisi')->assertForbidden();
        $this->actingAs($pelapor)->get('/lapangan/pelapor')
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman->component('Lapangan/Pelapor/Beranda'));

        $this->actingAs($teknisi)->get('/lapangan/teknisi')
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman->component('Lapangan/Teknisi/Beranda'));
        $this->actingAs($teknisi)->get('/lapangan/pelapor')->assertOk();
    }

    public function test_mode_lapangan_tertutup_bagi_tamu(): void
    {
        $this->get('/lapangan')->assertRedirect('/login');
        $this->post('/lapangan/tampilan', ['Tampilan' => 'lapangan'])->assertRedirect('/login');
    }

    public function test_prop_bersama_lapangan_menggambarkan_pengguna(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $campuran = $this->penggunaMeja(['Aset.Lihat'], $this->penggunaDenganPeran(['PELAPOR']));
        $meja = $this->penggunaMeja(['Aset.Lihat']);

        $this->actingAs($teknisi)->get('/lapangan/akun')
            ->assertInertia(fn ($halaman) => $halaman->component('Lapangan/Akun')
                ->where('lapangan', ['mode' => 'Teknisi', 'murni' => true, 'bisaBeralih' => false])
                ->where('namaPeran', 'Teknisi'));

        $this->actingAs($campuran)->get('/aset')
            ->assertInertia(fn ($halaman) => $halaman
                ->where('lapangan', ['mode' => 'Pelapor', 'murni' => false, 'bisaBeralih' => true]));

        $this->actingAs($meja)->get('/aset')
            ->assertInertia(fn ($halaman) => $halaman
                ->where('lapangan', ['mode' => null, 'murni' => false, 'bisaBeralih' => false]));
    }

    public function test_notifikasi_mode_lapangan_hanya_memuat_milik_pengguna(): void
    {
        $pelapor = $this->penggunaDenganPeran(['PELAPOR']);
        $lain = $this->penggunaDenganPeran(['PELAPOR']);

        $this->dalamOrganisasi(function () use ($pelapor, $lain): void {
            foreach ([$pelapor, $lain] as $pemilik) {
                Notifikasi::create([
                    'PenggunaId' => $pemilik->Id,
                    'Kanal' => 'InApp',
                    'JenisPeristiwa' => 'Uji',
                    'Judul' => 'Untuk '.$pemilik->Nama,
                    'Isi' => 'Isi',
                    'Status' => 'Terkirim',
                ]);
            }
        });

        $this->actingAs($pelapor)->get('/lapangan/notifikasi')
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman->component('Lapangan/Notifikasi')
                ->has('notifikasi', 1)
                ->where('notifikasi.0.Judul', 'Untuk '.$pelapor->Nama)
                ->where('jumlahBelumDibaca', 1));
    }

    /** @return array{Email: string, KataSandi: string} */
    private function kredensial(string $email): array
    {
        return ['Email' => $email, 'KataSandi' => 'rahasia'];
    }
}
