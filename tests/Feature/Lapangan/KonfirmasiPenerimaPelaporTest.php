<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Application\Actions\SimpanTandaTanganPengguna;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia;

/**
 * Cara 1 konfirmasi penerima (PRD 8.22): pelapor diminta mengonfirmasi dari Mode Lapangan
 * begitu teknisi menyerahkan pekerjaan. "Sudah beres" membuat keluhannya tertutup otomatis
 * saat koordinator memverifikasi, tanpa konfirmasi kedua.
 */
final class KonfirmasiPenerimaPelaporTest extends KasusKonfirmasiPenerima
{
    public function test_teknisi_menyelesaikan_pekerjaan_pelapor_diberi_tahu_dan_melihat_permintaan_konfirmasi(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan(StatusPerintahKerja::Dikerjakan);
        $keluhan = $this->keluhanDari($pekerjaan);

        $this->selesaikanLewatAntrean($pekerjaan)->assertJsonPath('Antrean.0.Status', 'Selesai');

        $this->assertDatabaseHas('Notifikasi', ['PenggunaId' => $this->pelapor->Id, 'JenisPeristiwa' => 'Keluhan.MenungguKonfirmasi', 'EntitasId' => $keluhan->Id]);
        $this->actingAs($this->pelapor)->get('/lapangan/pelapor')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('laporanAktif.0.KonfirmasiPekerjaan', 'Diminta'));
        $this->actingAs($this->pelapor)->get('/lapangan/pelapor/laporan')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('jumlah.PerluKonfirmasi', 1));
        $this->actingAs($this->pelapor)->get("/lapangan/pelapor/laporan/{$keluhan->Id}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('laporan.KonfirmasiPekerjaan', 'Diminta'));
        $this->actingAs($this->pelapor)->get("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/Pelapor/Konfirmasi')
                ->where('tahap', 'Pekerjaan')
                ->where('pekerjaan.Id', $pekerjaan->Id));
    }

    public function test_sudah_beres_mencatat_konfirmasi_lalu_keluhan_ditutup_otomatis_saat_diverifikasi_tanpa_konfirmasi_kedua(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $keluhan = $this->keluhanDari($pekerjaan);

        $this->actingAs($this->pelapor)->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi-pekerjaan", [
            'Hasil' => 'Diterima', 'Penilaian' => 5, 'Ulasan' => 'Rapi sekali.', 'TandaTangan' => $this->gambarTandaTangan(),
        ])->assertSessionHasNoErrors()->assertRedirect("/lapangan/pelapor/laporan/{$keluhan->Id}");

        [$catatan] = $this->konfirmasi($pekerjaan);
        $this->assertSame('Pelapor', $catatan->Metode);
        $this->assertSame($this->pelapor->Id, $catatan->PenggunaId);
        $this->assertSame(5, $catatan->Penilaian);
        $this->assertSame($this->tandaTanganProfil($this->pelapor), $catatan->TandaTanganBerkasId);
        $this->assertNotNull($catatan->TandaTanganBerkasId);
        // Keluhan belum berubah sampai koordinator memverifikasi.
        $this->assertSame(StatusKeluhan::Diproses->value, $this->keluhanDari($pekerjaan)->Status);

        $this->verifikasi($pekerjaan)->assertRedirect();

        $segar = $this->keluhanDari($pekerjaan);
        $this->assertSame(StatusKeluhan::Ditutup->value, $segar->Status);
        $this->assertSame(5, (int) $segar->Rating);
        $this->assertSame('Rapi sekali.', $segar->Ulasan);
        $this->assertDatabaseHas('RiwayatStatusKeluhan', ['KeluhanId' => $keluhan->Id, 'StatusSebelum' => 'Diproses', 'StatusSesudah' => 'Selesai']);
        $this->assertDatabaseHas('RiwayatStatusKeluhan', ['KeluhanId' => $keluhan->Id, 'StatusSebelum' => 'Selesai', 'StatusSesudah' => 'Ditutup']);
        // Tidak ada permintaan konfirmasi kedua.
        $this->assertFalse($this->dalamOrganisasi(fn () => Gate::forUser($this->pelapor)->allows('konfirmasi', $segar)));
        $this->actingAs($this->pelapor)->get("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi")
            ->assertRedirect("/lapangan/pelapor/laporan/{$keluhan->Id}");
    }

    /**
     * Koordinator menandai keluhan Selesai lebih dulu dari dasbor, sebelum memverifikasi
     * perintah kerjanya: pelapor yang sudah menjawab tidak ditanya lagi, dan keluhannya
     * tertutup begitu perintah kerja diverifikasi.
     */
    public function test_keluhan_yang_sudah_selesai_tidak_meminta_konfirmasi_kedua_dan_tertutup_saat_diverifikasi(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $keluhan = $this->keluhanDari($pekerjaan);
        $this->dalamOrganisasi(fn () => app(SimpanTandaTanganPengguna::class)->jalankan($this->pelapor, $this->gambarTandaTangan()));
        $this->actingAs($this->pelapor)->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi-pekerjaan", ['Hasil' => 'Diterima'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->koordinator)->put("/pemeliharaan/keluhan/{$keluhan->Id}/status", [
            'Status' => 'Selesai', 'Versi' => $this->keluhanDari($pekerjaan)->Versi,
        ])->assertSessionHasNoErrors();
        $selesai = $this->keluhanDari($pekerjaan);
        $this->assertSame(StatusKeluhan::Selesai->value, $selesai->Status);
        $this->assertFalse($this->dalamOrganisasi(fn () => Gate::forUser($this->pelapor)->allows('konfirmasi', $selesai)));

        $this->verifikasi($pekerjaan)->assertRedirect();

        $this->assertSame(StatusKeluhan::Ditutup->value, $this->keluhanDari($pekerjaan)->Status);
    }

    public function test_konfirmasi_keluhan_lama_tetap_berjalan_bila_pelapor_belum_mengonfirmasi_di_tahap_pekerjaan(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $keluhan = $this->keluhanDari($pekerjaan);

        $this->verifikasi($pekerjaan)->assertRedirect();
        // Tanpa konfirmasi pelapor, verifikasi tidak menyentuh keluhan.
        $this->assertSame(StatusKeluhan::Diproses->value, $this->keluhanDari($pekerjaan)->Status);

        $this->actingAs($this->koordinator)->put("/pemeliharaan/keluhan/{$keluhan->Id}/status", [
            'Status' => 'Selesai', 'Versi' => $this->keluhanDari($pekerjaan)->Versi,
        ])->assertSessionHasNoErrors();
        $selesai = $this->keluhanDari($pekerjaan);
        $this->assertSame(StatusKeluhan::Selesai->value, $selesai->Status);
        $this->assertTrue($this->dalamOrganisasi(fn () => Gate::forUser($this->pelapor)->allows('konfirmasi', $selesai)));
        $this->actingAs($this->pelapor)->get("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('tahap', 'Keluhan'));
    }

    public function test_masih_bermasalah_mengembalikan_pekerjaan_dan_memberi_tahu_teknisi(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $keluhan = $this->keluhanDari($pekerjaan);

        $this->actingAs($this->pelapor)->from("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi")
            ->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi-pekerjaan", ['Hasil' => 'MasihBermasalah'])
            ->assertSessionHasErrors('Alasan');

        $this->actingAs($this->pelapor)->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi-pekerjaan", [
            'Hasil' => 'MasihBermasalah', 'Alasan' => 'Stopkontak masih mati.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(StatusPerintahKerja::Dikerjakan->value, $this->statusPekerjaan($pekerjaan));
        $this->assertSame(StatusKeluhan::Diproses->value, $this->keluhanDari($pekerjaan)->Status);
        $this->assertDatabaseHas('Notifikasi', ['PenggunaId' => $this->teknisi->Id, 'JenisPeristiwa' => 'PerintahKerja.MasihBermasalah']);
        $this->assertDatabaseHas('RiwayatStatusPerintahKerja', ['PerintahKerjaId' => $pekerjaan->Id, 'StatusSesudah' => 'Dikerjakan', 'DiubahOleh' => $this->pelapor->Id]);
    }

    public function test_hanya_pelapor_keluhan_asal_yang_dapat_mengonfirmasi_dan_teknisi_tidak_mengonfirmasi_sendiri(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $keluhan = $this->keluhanDari($pekerjaan);
        $pelaporLain = $this->pelaporDi($this->ruang);

        $this->actingAs($pelaporLain)->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi-pekerjaan", [
            'Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan(),
        ])->assertForbidden();
        $this->assertSame([], $this->konfirmasi($pekerjaan));

        // Teknisi yang melapor lewat aksi cepat lalu mengerjakannya sendiri.
        $keluhanTeknisi = $this->keluhan($this->teknisi, $this->kategoriListrik, $this->ruang, transisi: [
            StatusKeluhan::Ditinjau, StatusKeluhan::Diterima, StatusKeluhan::Diproses,
        ]);
        $milikSendiri = $this->tugaskan($keluhanTeknisi, $this->teknisi, StatusPerintahKerja::MenungguVerifikasi->value);
        $this->assertFalse($this->dalamOrganisasi(fn () => Gate::forUser($this->teknisi)->allows('konfirmasiSebagaiPelapor', $milikSendiri)));
        $this->actingAs($this->teknisi)->post("/lapangan/pelapor/laporan/{$keluhanTeknisi->Id}/konfirmasi-pekerjaan", [
            'Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan(),
        ])->assertRedirect();
        $this->assertSame([], $this->konfirmasi($milikSendiri));
    }

    public function test_pekerjaan_yang_belum_diserahkan_tidak_dapat_dikonfirmasi(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan(StatusPerintahKerja::Dikerjakan);
        $keluhan = $this->keluhanDari($pekerjaan);

        $this->actingAs($this->pelapor)->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi-pekerjaan", [
            'Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan(),
        ])->assertRedirect("/lapangan/pelapor/laporan/{$keluhan->Id}");

        $this->assertSame([], $this->konfirmasi($pekerjaan));
        $this->assertNull($this->tandaTanganProfil($this->pelapor));
    }

    /** Dibuka lagi koordinator: siklus baru, konfirmasi lama tidak dihitung dan pelapor diminta lagi. */
    public function test_pekerjaan_yang_dikembalikan_koordinator_mencabut_konfirmasi_siklus_lama(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $keluhan = $this->keluhanDari($pekerjaan);
        $this->actingAs($this->pelapor)->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi-pekerjaan", [
            'Hasil' => 'Diterima', 'TandaTangan' => $this->gambarTandaTangan(),
        ]);

        $this->actingAs($this->koordinator)->putJson("/pemeliharaan/perintah-kerja/{$pekerjaan->Id}/status", [
            'Status' => 'Dikerjakan', 'Catatan' => 'Hasil belum sesuai standar.', 'Versi' => $this->versi($pekerjaan),
        ])->assertRedirect();

        $this->assertFalse($this->konfirmasi($pekerjaan)[0]->Berlaku);
        $this->dalamOrganisasi(fn () => PerintahKerja::query()->withoutGlobalScopes()->whereKey($pekerjaan->Id)->update(['Status' => 'MenungguVerifikasi']));
        $this->actingAs($this->pelapor)->get("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('tahap', 'Pekerjaan'));
    }
}
