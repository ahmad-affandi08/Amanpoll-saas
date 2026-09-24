<?php

declare(strict_types=1);

namespace Tests\Feature\Pemeliharaan;

use App\Domain\Pemeliharaan\Application\Services\AturanKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Platform\Application\Actions\SimpanTandaTanganPengguna;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Lapangan\KasusKonfirmasiPenerima;

/**
 * Konfirmasi penerima di dasbor (PRD 8.22): keterangan "Menunggu konfirmasi penerima" di
 * daftar dan detail, kartu konfirmasi dengan gambar tanda tangan lewat rute terotorisasi,
 * alasan verifikasi dikunci, dan setelan lama yang dipindahkan migrasi.
 */
final class KonfirmasiPenerimaDasborTest extends KasusKonfirmasiPenerima
{
    public function test_daftar_dan_detail_menandai_menunggu_konfirmasi_penerima(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();

        $this->actingAs($this->koordinator)->get('/pemeliharaan/perintah-kerja')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('perintahKerja.data.0.MenungguKonfirmasiPenerima', true));
        $this->actingAs($this->koordinator)->get("/pemeliharaan/perintah-kerja/{$pekerjaan->Id}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('perintahKerja.MenungguKonfirmasiPenerima', true)
                ->where('konfirmasiPenerima', [])
                ->where('konfirmasiWajib', false)
                ->where('alasanVerifikasiDiblokir', null));
    }

    public function test_detail_menampilkan_alasan_verifikasi_dikunci_saat_setelan_menyala(): void
    {
        $this->wajibkanKonfirmasi();
        $pekerjaan = $this->pekerjaanDariKeluhan();

        $this->actingAs($this->koordinator)->get("/pemeliharaan/perintah-kerja/{$pekerjaan->Id}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('konfirmasiWajib', true)
                ->where('alasanVerifikasiDiblokir', AturanKonfirmasiPenerima::PESAN_VERIFIKASI_DIBLOKIR));
    }

    public function test_kartu_konfirmasi_dan_gambar_tanda_tangan_hanya_bagi_yang_boleh_melihat_pekerjaan(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $this->dalamOrganisasi(fn () => app(SimpanTandaTanganPengguna::class)->jalankan($this->pelapor, $this->gambarTandaTangan()));
        $this->actingAs($this->pelapor)->post("/lapangan/pelapor/laporan/{$pekerjaan->KeluhanId}/konfirmasi-pekerjaan", [
            'Hasil' => 'Diterima', 'Penilaian' => 4,
        ])->assertSessionHasNoErrors();
        [$catatan] = $this->konfirmasi($pekerjaan);
        $urlGambar = "/pemeliharaan/perintah-kerja/{$pekerjaan->Id}/konfirmasi-penerima/{$catatan->Id}/tanda-tangan";

        $this->actingAs($this->koordinator)->get("/pemeliharaan/perintah-kerja/{$pekerjaan->Id}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('perintahKerja.MenungguKonfirmasiPenerima', false)
                ->where('konfirmasiPenerima.0.Metode', 'Pelapor')
                ->where('konfirmasiPenerima.0.NamaPenerima', $this->pelapor->Nama)
                ->where('konfirmasiPenerima.0.Penilaian', 4)
                ->where('konfirmasiPenerima.0.UrlTandaTangan', $urlGambar));
        $this->actingAs($this->koordinator)->get($urlGambar)->assertOk();

        // Pengguna yang tidak boleh melihat perintah kerja tidak mendapat gambarnya.
        $this->actingAs($this->penggunaMeja(['Aset.Lihat']))->getJson($urlGambar)->assertForbidden();
        // Konfirmasi dari perintah kerja lain tidak bisa dipinjam lewat jalur ini.
        $lain = $this->pekerjaanDariKeluhan();
        $this->actingAs($this->koordinator)->get("/pemeliharaan/perintah-kerja/{$lain->Id}/konfirmasi-penerima/{$catatan->Id}/tanda-tangan")->assertNotFound();
    }

    public function test_migrasi_memindahkan_nilai_setelan_tanda_tangan_lama(): void
    {
        DB::table('KonfigurasiOrganisasi')->insert([
            'Id' => (string) str()->ulid(),
            'OrganisasiId' => $this->organisasi->Id,
            'Kunci' => 'Pemeliharaan.WajibTandaTanganPenerima',
            'Nilai' => json_encode(true),
            'Rahasia' => false,
        ]);

        $migrasi = require database_path('migrations/2026_09_24_141429_pindahkan_setelan_wajib_konfirmasi_penerima.php');
        $migrasi->up();

        $this->assertDatabaseMissing('KonfigurasiOrganisasi', ['Kunci' => 'Pemeliharaan.WajibTandaTanganPenerima']);
        $this->assertDatabaseHas('KonfigurasiOrganisasi', ['OrganisasiId' => $this->organisasi->Id, 'Kunci' => AturanKonfirmasiPenerima::KUNCI_KONFIGURASI]);
        $this->assertTrue($this->dalamOrganisasi(fn () => app(AturanKonfirmasiPenerima::class)->wajib($this->organisasi->Id)));

        // Organisasi yang sudah wajib dan selesai diverifikasi tetap tunduk pada setelan barunya.
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $this->verifikasi($pekerjaan)->assertStatus(422);
        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusPekerjaan($pekerjaan));
    }
}
