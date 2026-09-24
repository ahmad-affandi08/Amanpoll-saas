<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Pemeliharaan\Application\Services\AturanTandaTanganPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Application\Actions\SimpanKonfigurasiOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tanda tangan penerima (TASK 39.10 butir 4): opsional secara bawaan, bisa diwajibkan per
 * organisasi. Saat diwajibkan, Action Pemeliharaan menolak teknisi yang menyelesaikan tiket ke
 * Menunggu Verifikasi tanpa lampiran berkategori TandaTangan, baik lewat antrean offline
 * maupun rute dasbor. Koordinator yang tidak ditugaskan tidak terkena.
 */
final class TeknisiTandaTanganTest extends KasusTeknisi
{
    private const PERANGKAT = ['IdentitasPerangkat' => 'hp-ttd-uji', 'NamaPerangkat' => 'Ponsel', 'Platform' => 'Android'];

    private const RINGKASAN = "Tindakan: Ganti kontaktor K1\nKondisi aset: Berfungsi normal";

    public function test_bawaan_mati_penyelesaian_tanpa_tanda_tangan_tetap_lolos_dan_ringkasan_menandai_opsional(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $tiket = $this->buatTiket($teknisi, StatusPerintahKerja::Dikerjakan, $this->buatAset());

        $this->actingAs($teknisi)->get("/lapangan/teknisi/tugas/{$tiket->Id}/kerjakan")
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia->where('tandaTanganWajib', false));
        $this->actingAs($teknisi)->postJson('/offline/paket', self::PERANGKAT)
            ->assertOk()
            ->assertJsonPath('Paket.Pengaturan.TandaTanganPenerimaWajib', false);

        $this->dorongSelesai($teknisi, $tiket)->assertJsonPath('Antrean.0.Status', 'Selesai');

        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusTiket($tiket));
    }

    public function test_saat_diwajibkan_penyelesaian_lewat_antrean_offline_ditolak_tanpa_tanda_tangan(): void
    {
        $this->wajibkanTandaTangan();
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $tiket = $this->buatTiket($teknisi, StatusPerintahKerja::Dikerjakan, $this->buatAset());
        // Foto lain pada tiket tidak menggantikan tanda tangan.
        $this->unggahLampiran($teknisi, $tiket, 'FotoSesudah')->assertRedirect()->assertSessionHasNoErrors();

        $this->dorongSelesai($teknisi, $tiket)
            ->assertJsonPath('Antrean.0.Status', 'Gagal')
            ->assertJsonPath('Antrean.0.Konflik.Alasan', 'ATURAN_BISNIS_DILANGGAR')
            ->assertJsonPath('Antrean.0.Konflik.Pesan', 'Tanda tangan penerima wajib dilampirkan sebelum pekerjaan dikirim untuk verifikasi.');

        $this->assertSame(StatusPerintahKerja::Dikerjakan->value, $this->statusTiket($tiket));
    }

    /**
     * Urutan yang dipakai layar Ringkasan dan pengirim antrean: tanda tangan diunggah ke
     * endpoint Kolaborasi lebih dulu, baru mutasi "selesai" dikirim.
     */
    public function test_saat_diwajibkan_penyelesaian_lolos_setelah_tanda_tangan_diunggah(): void
    {
        $this->wajibkanTandaTangan();
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $tiket = $this->buatTiket($teknisi, StatusPerintahKerja::Dikerjakan, $this->buatAset());

        $this->unggahLampiran($teknisi, $tiket, AturanTandaTanganPenerima::KATEGORI_LAMPIRAN)->assertRedirect()->assertSessionHasNoErrors();
        $this->dorongSelesai($teknisi, $tiket)->assertJsonPath('Antrean.0.Status', 'Selesai');

        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusTiket($tiket));
    }

    public function test_saat_diwajibkan_teknisi_yang_menyelesaikan_lewat_rute_dasbor_juga_ditolak(): void
    {
        $this->wajibkanTandaTangan();
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $tiket = $this->buatTiket($teknisi, StatusPerintahKerja::Dikerjakan, $this->buatAset());

        $this->actingAs($teknisi)->putJson("/pemeliharaan/perintah-kerja/{$tiket->Id}/status", [
            'Status' => StatusPerintahKerja::MenungguVerifikasi->value,
            'RingkasanPenyelesaian' => self::RINGKASAN,
            'Versi' => $this->versiTiket($tiket),
        ])
            ->assertStatus(422)
            ->assertJsonPath('pesan', 'Tanda tangan penerima wajib dilampirkan sebelum pekerjaan dikirim untuk verifikasi.');

        $this->assertSame(StatusPerintahKerja::Dikerjakan->value, $this->statusTiket($tiket));
    }

    /** Dasbor tidak punya kotak tanda tangan; koordinator yang tidak ditugaskan tetap bisa memindahkan status. */
    public function test_saat_diwajibkan_koordinator_yang_tidak_ditugaskan_tetap_bisa_menyelesaikan_dari_dasbor(): void
    {
        $this->wajibkanTandaTangan();
        $koordinator = $this->penggunaMeja(['PerintahKerja.Kelola']);
        $tiket = $this->buatTiket($this->penggunaDenganPeran(['TEKNISI']), StatusPerintahKerja::Dikerjakan, $this->buatAset());

        $this->actingAs($koordinator)->put("/pemeliharaan/perintah-kerja/{$tiket->Id}/status", [
            'Status' => StatusPerintahKerja::MenungguVerifikasi->value,
            'RingkasanPenyelesaian' => self::RINGKASAN,
            'Versi' => $this->versiTiket($tiket),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusTiket($tiket));
    }

    public function test_saat_diwajibkan_ringkasan_dan_paket_offline_menandai_tanda_tangan_wajib(): void
    {
        $this->wajibkanTandaTangan();
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $tiket = $this->buatTiket($teknisi, StatusPerintahKerja::Dikerjakan, $this->buatAset());

        $this->actingAs($teknisi)->get("/lapangan/teknisi/tugas/{$tiket->Id}/kerjakan")
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->component('Lapangan/Teknisi/Kerjakan')
                ->where('tandaTanganWajib', true));
        $this->actingAs($teknisi)->postJson('/offline/paket', self::PERANGKAT)
            ->assertOk()
            ->assertJsonPath('Paket.Pengaturan.TandaTanganPenerimaWajib', true);
    }

    private function wajibkanTandaTangan(): void
    {
        $this->dalamOrganisasi(fn () => app(SimpanKonfigurasiOrganisasi::class)
            ->jalankan($this->organisasi->Id, AturanTandaTanganPenerima::KUNCI_KONFIGURASI, true));
    }

    /**
     * Unggahan layar Ringkasan/Foto teknisi: endpoint Kolaborasi dengan kategori lampiran.
     *
     * @return TestResponse<Response>
     */
    private function unggahLampiran(Pengguna $teknisi, PerintahKerja $tiket, string $kategori): TestResponse
    {
        Storage::fake('local');

        return $this->actingAs($teknisi)->postJson('/kolaborasi/berkas', [
            'Berkas' => UploadedFile::fake()->create("{$kategori}.png", 4, 'image/png'),
            'JenisEntitas' => 'PerintahKerja',
            'EntitasId' => $tiket->Id,
            'Kategori' => $kategori,
        ]);
    }

    /** @return TestResponse<Response> */
    private function dorongSelesai(Pengguna $teknisi, PerintahKerja $tiket): TestResponse
    {
        return $this->actingAs($teknisi)->postJson('/offline/antrian', [...self::PERANGKAT, 'Mutasi' => [[
            'KunciOperasi' => 'op-selesai-'.uniqid(),
            'Operasi' => 'PerintahKerja.UbahStatus',
            'EntitasId' => $tiket->Id,
            'VersiKlien' => $this->versiTiket($tiket),
            'MuatanData' => ['Status' => 'MenungguVerifikasi', 'Ringkasan' => self::RINGKASAN],
        ]]])->assertOk();
    }
}
