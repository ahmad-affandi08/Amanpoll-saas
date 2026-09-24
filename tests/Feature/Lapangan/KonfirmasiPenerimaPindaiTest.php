<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Application\Actions\SimpanTandaTanganPengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Inertia\Testing\AssertableInertia;

/**
 * Cara 2 konfirmasi penerima (PRD 8.22): teknisi menampilkan QR bertoken, penerima
 * memindai dengan HP-nya, masuk bila perlu, lalu menerima atau menolak pekerjaan.
 */
final class KonfirmasiPenerimaPindaiTest extends KasusKonfirmasiPenerima
{
    private Pengguna $penerima;

    protected function setUp(): void
    {
        parent::setUp();

        // Staf lokasi yang menerima pekerjaan: lapangan murni (peran PELAPOR), lingkup ruangan yang sama.
        $this->penerima = $this->pelaporDi($this->ruang);
    }

    private function tautanQr(PerintahKerja $pekerjaan): string
    {
        return (string) $this->actingAs($this->teknisi)
            ->postJson("/lapangan/teknisi/tugas/{$pekerjaan->Id}/konfirmasi-penerima/qr")
            ->assertOk()
            ->assertJsonStructure(['Url', 'Svg', 'BerlakuSampai'])
            ->json('Url');
    }

    public function test_tamu_dialihkan_ke_login_lalu_kembali_ke_halaman_konfirmasi(): void
    {
        $url = $this->tautanQr($this->pekerjaanDariKeluhan());
        auth('web')->logout();

        $this->get($url)->assertRedirect(route('login'));
        $this->assertSame($url, session('url.intended'));
    }

    public function test_pengguna_lapangan_murni_membuka_halaman_konfirmasi_dengan_ringkasan_pekerjaan(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $url = $this->tautanQr($pekerjaan);

        $this->actingAs($this->penerima)->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/KonfirmasiPenerima')
                ->where('keadaan', 'Siap')
                ->where('pekerjaan.Nomor', $pekerjaan->Nomor)
                ->where('pekerjaan.Teknisi.0', $this->teknisi->Nama));
    }

    public function test_pengguna_dasbor_juga_bisa_membuka_halaman_konfirmasi(): void
    {
        $url = $this->tautanQr($this->pekerjaanDariKeluhan());
        $meja = $this->penggunaMeja(['Aset.Lihat']);

        $this->actingAs($meja)->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('keadaan', 'Siap'));
    }

    public function test_belum_punya_tanda_tangan_menggambar_sekali_lalu_tersimpan_ke_profil_dan_dicap(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $url = $this->tautanQr($pekerjaan);
        $this->assertNull($this->tandaTanganProfil($this->penerima));

        $this->actingAs($this->penerima)->post($url, ['Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan()])
            ->assertSessionHasNoErrors()
            ->assertRedirect("/lapangan/konfirmasi-penerima/{$pekerjaan->Id}/hasil");

        $profil = $this->tandaTanganProfil($this->penerima);
        $this->assertNotNull($profil);
        [$catatan] = $this->konfirmasi($pekerjaan);
        $this->assertSame('PindaiQr', $catatan->Metode);
        $this->assertSame('Diterima', $catatan->Hasil);
        $this->assertSame($this->penerima->Id, $catatan->PenggunaId);
        $this->assertSame($this->penerima->Nama, $catatan->NamaPenerima);
        $this->assertSame($profil, $catatan->TandaTanganBerkasId);
        $this->assertTrue($catatan->Berlaku);
        // Tidak mengubah status: verifikasi tetap milik koordinator.
        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusPekerjaan($pekerjaan));
        $this->assertDatabaseHas('RiwayatStatusPerintahKerja', ['PerintahKerjaId' => $pekerjaan->Id, 'StatusSesudah' => 'MenungguVerifikasi', 'DiubahOleh' => $this->penerima->Id]);
        $this->assertDatabaseHas('CatatanAudit', ['Aksi' => 'KonfirmasiPenerima', 'EntitasId' => $pekerjaan->Id]);
        $this->assertDatabaseHas('Notifikasi', ['PenggunaId' => $this->teknisi->Id, 'JenisPeristiwa' => 'PerintahKerja.DikonfirmasiPenerima']);

        $this->get("/lapangan/konfirmasi-penerima/{$pekerjaan->Id}/hasil")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->component('Lapangan/KonfirmasiPenerimaHasil')->where('hasil', 'Diterima'));
    }

    public function test_sudah_punya_tanda_tangan_konfirmasi_berikutnya_mencap_tanpa_menggambar(): void
    {
        $tersimpan = $this->dalamOrganisasi(fn () => app(SimpanTandaTanganPengguna::class)->jalankan($this->penerima, $this->gambarTandaTangan('lama.png')));
        $pekerjaan = $this->pekerjaanDariKeluhan();

        $url = $this->tautanQr($pekerjaan);

        $this->actingAs($this->penerima)->post($url, ['Hasil' => 'Diterima'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame($tersimpan->Id, $this->konfirmasi($pekerjaan)[0]->TandaTanganBerkasId);
    }

    public function test_tanpa_tanda_tangan_tersimpan_dan_tanpa_gambar_ditolak(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();

        $url = $this->tautanQr($pekerjaan);

        $this->actingAs($this->penerima)->from('/lapangan')->post($url, ['Hasil' => 'Diterima'])
            ->assertSessionHasErrors('Konfirmasi');

        $this->assertSame([], $this->konfirmasi($pekerjaan));
    }

    public function test_mengganti_tanda_tangan_profil_tidak_mengubah_konfirmasi_lama(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $url = $this->tautanQr($pekerjaan);
        $this->actingAs($this->penerima)->post($url, ['Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan('pertama.png')]);
        $dicap = $this->konfirmasi($pekerjaan)[0]->TandaTanganBerkasId;

        $this->actingAs($this->penerima)->postJson('/profil/tanda-tangan', ['TandaTangan' => $this->gambarTandaTangan('kedua.png')])->assertOk();

        $this->assertNotSame($dicap, $this->tandaTanganProfil($this->penerima));
        $this->assertSame($dicap, $this->konfirmasi($pekerjaan)[0]->TandaTanganBerkasId);
        $this->assertDatabaseHas('Berkas', ['Id' => $dicap, 'DihapusPada' => null]);
    }

    public function test_masih_bermasalah_mengembalikan_pekerjaan_ke_dikerjakan_dan_memberi_tahu_teknisi(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $url = $this->tautanQr($pekerjaan);

        $this->actingAs($this->penerima)->from($url)->post($url, ['Hasil' => 'MasihBermasalah'])
            ->assertSessionHasErrors('Alasan');
        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusPekerjaan($pekerjaan));

        $this->actingAs($this->penerima)->post($url, ['Hasil' => 'MasihBermasalah', 'Alasan' => 'Lampu masih berkedip.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(StatusPerintahKerja::Dikerjakan->value, $this->statusPekerjaan($pekerjaan));
        [$catatan] = $this->konfirmasi($pekerjaan);
        $this->assertSame('MasihBermasalah', $catatan->Hasil);
        $this->assertSame('Lampu masih berkedip.', $catatan->Alasan);
        $this->assertFalse($catatan->Berlaku);
        $this->assertNull($catatan->TandaTanganBerkasId);
        $this->assertDatabaseHas('RiwayatStatusPerintahKerja', [
            'PerintahKerjaId' => $pekerjaan->Id, 'StatusSebelum' => 'MenungguVerifikasi', 'StatusSesudah' => 'Dikerjakan', 'DiubahOleh' => $this->penerima->Id,
        ]);
        $this->assertDatabaseHas('Notifikasi', ['PenggunaId' => $this->teknisi->Id, 'JenisPeristiwa' => 'PerintahKerja.MasihBermasalah']);
    }

    public function test_token_kedaluwarsa_ditolak(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $url = $this->tautanQr($pekerjaan);
        $this->travel(11)->minutes();

        $this->actingAs($this->penerima)->get($url)
            ->assertForbidden()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('keadaan', 'Kedaluwarsa')->where('pekerjaan', null));
        $this->actingAs($this->penerima)->post($url, ['Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan()])->assertForbidden();

        $this->assertSame([], $this->konfirmasi($pekerjaan));
    }

    public function test_token_untuk_pekerjaan_lain_ditolak(): void
    {
        $pekerjaanA = $this->pekerjaanDariKeluhan();
        $pekerjaanB = $this->pekerjaanDariKeluhan();
        $kueri = (string) parse_url($this->tautanQr($pekerjaanA), PHP_URL_QUERY);
        $palsu = "/lapangan/konfirmasi-penerima/{$pekerjaanB->Id}?{$kueri}";

        $this->actingAs($this->penerima)->get($palsu)
            ->assertForbidden()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('keadaan', 'TautanTidakSah'));
        $this->actingAs($this->penerima)->post($palsu, ['Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan()])->assertForbidden();

        $this->assertSame([], $this->konfirmasi($pekerjaanB));
    }

    public function test_tautan_tanpa_tanda_tangan_server_ditolak(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();

        $this->actingAs($this->penerima)->get("/lapangan/konfirmasi-penerima/{$pekerjaan->Id}")->assertForbidden();
    }

    public function test_pengguna_organisasi_lain_tidak_menemukan_pekerjaannya(): void
    {
        $url = $this->tautanQr($this->pekerjaanDariKeluhan());
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN-'.uniqid(), 'Nama' => 'Organisasi Lain', 'Status' => 'Aktif']);
        $orangLain = Pengguna::create([
            'OrganisasiId' => $lain->Id, 'Nama' => 'Orang Lain', 'Email' => 'lain+'.uniqid().'@amanpoll.test', 'KataSandi' => 'rahasia', 'Status' => 'Aktif',
        ]);

        $this->actingAs($orangLain)->get($url)->assertNotFound();
        $this->actingAs($orangLain)->post($url, ['Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan()])->assertNotFound();
    }

    public function test_penerima_di_luar_lingkup_ditolak(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $url = $this->tautanQr($pekerjaan);
        $unitLain = $this->dalamOrganisasi(fn () => UnitOrganisasi::create(['Kode' => 'UNIT-LAIN', 'Nama' => 'Gudang Timur', 'Status' => 'Aktif']));
        $diLuar = $this->penggunaDenganPeran(['PELAPOR'], $unitLain->Id);

        $this->actingAs($diLuar)->get($url)
            ->assertForbidden()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('keadaan', 'TanpaAkses'));
        $this->actingAs($diLuar)->post($url, ['Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan()])->assertForbidden();

        $this->assertSame([], $this->konfirmasi($pekerjaan));
    }

    public function test_teknisi_yang_ditugaskan_tidak_bisa_mengonfirmasi_pekerjaannya_sendiri(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $url = $this->tautanQr($pekerjaan);

        $this->actingAs($this->teknisi)->get($url)
            ->assertForbidden()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('keadaan', 'TanpaAkses'));
        $this->actingAs($this->teknisi)->post($url, ['Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan()])->assertForbidden();

        $this->assertSame([], $this->konfirmasi($pekerjaan));
    }

    public function test_qr_hanya_untuk_pekerjaan_yang_menunggu_verifikasi_dan_belum_dikonfirmasi(): void
    {
        $dikerjakan = $this->pekerjaanDariKeluhan(StatusPerintahKerja::Dikerjakan);

        $this->actingAs($this->teknisi)->postJson("/lapangan/teknisi/tugas/{$dikerjakan->Id}/konfirmasi-penerima/qr")
            ->assertStatus(422);

        $pekerjaan = $this->pekerjaanDariKeluhan();
        $url = $this->tautanQr($pekerjaan);
        $this->actingAs($this->penerima)->post($url, ['Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan()]);

        $this->actingAs($this->teknisi)->postJson("/lapangan/teknisi/tugas/{$pekerjaan->Id}/konfirmasi-penerima/qr")
            ->assertStatus(422)
            ->assertJsonPath('pesan', 'Pekerjaan ini sudah dikonfirmasi penerima.');
        // Layar teknisi membaca status berkala dan melihat konfirmasinya masuk.
        $this->actingAs($this->teknisi)->getJson("/lapangan/teknisi/tugas/{$pekerjaan->Id}/konfirmasi-penerima")
            ->assertOk()
            ->assertJsonPath('Konfirmasi.NamaPenerima', $this->penerima->Nama)
            ->assertJsonPath('Konfirmasi.Metode', 'PindaiQr');
    }

    public function test_konfirmasi_kedua_pada_siklus_yang_sama_ditolak(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $url = $this->tautanQr($pekerjaan);
        $this->actingAs($this->penerima)->post($url, ['Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan()]);
        $lain = $this->pelaporDi($this->ruang);

        $this->actingAs($lain)->get($url)->assertOk()->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('keadaan', 'SudahDikonfirmasi'));
        $this->actingAs($lain)->from($url)->post($url, ['Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan()])
            ->assertSessionHasErrors('Konfirmasi');

        $this->assertCount(1, $this->konfirmasi($pekerjaan));
    }
}
