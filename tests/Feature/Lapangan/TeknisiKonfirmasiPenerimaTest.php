<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use Inertia\Testing\AssertableInertia;

/**
 * Konfirmasi penerima dari sisi teknisi (PRD 8.22): menyelesaikan pekerjaan tidak pernah
 * dikunci konfirmasi; cara 3 (tanda tangan tamu di HP teknisi) tercatat sebagai konfirmasi,
 * tahan kiriman ulang antrean offline; setelan "Wajibkan konfirmasi penerima" hanya
 * mengunci verifikasi koordinator.
 *
 * Menggantikan TeknisiTandaTanganTest (TASK 39.10), yang mengunci perilaku lama: tanda
 * tangan wajib saat teknisi menyelesaikan tiket. PRD 8.22 memutuskan perilaku itu berubah.
 */
final class TeknisiKonfirmasiPenerimaTest extends KasusKonfirmasiPenerima
{
    public function test_setelan_wajib_tidak_menghalangi_teknisi_menyelesaikan_pekerjaan(): void
    {
        $this->wajibkanKonfirmasi();
        $pekerjaan = $this->pekerjaanDariKeluhan(StatusPerintahKerja::Dikerjakan);

        $this->selesaikanLewatAntrean($pekerjaan)->assertJsonPath('Antrean.0.Status', 'Selesai');
        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusPekerjaan($pekerjaan));

        // Jalur dasbor teknisi juga lolos.
        $lain = $this->pekerjaanDariKeluhan(StatusPerintahKerja::Dikerjakan);
        $this->actingAs($this->teknisi)->putJson("/pemeliharaan/perintah-kerja/{$lain->Id}/status", [
            'Status' => 'MenungguVerifikasi', 'RingkasanPenyelesaian' => self::RINGKASAN, 'Versi' => $this->versi($lain),
        ])->assertRedirect();
        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusPekerjaan($lain));
    }

    public function test_setelan_wajib_menolak_verifikasi_tanpa_konfirmasi_diterima(): void
    {
        $this->wajibkanKonfirmasi();
        $pekerjaan = $this->pekerjaanDariKeluhan();

        $this->verifikasi($pekerjaan)
            ->assertStatus(422)
            ->assertJsonPath('pesan', 'Penerima belum mengonfirmasi pekerjaan ini. Organisasimu mewajibkan konfirmasi penerima sebelum perintah kerja diverifikasi.');
        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusPekerjaan($pekerjaan));

        $this->actingAs($this->teknisi)->post("/lapangan/teknisi/tugas/{$pekerjaan->Id}/konfirmasi-penerima/tanda-tangan", [
            'NamaPenerima' => 'Bu Sari', 'TandaTangan' => $this->gambarTandaTangan(),
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->verifikasi($pekerjaan)->assertRedirect();
        $this->assertSame(StatusPerintahKerja::Selesai->value, $this->statusPekerjaan($pekerjaan));
    }

    public function test_tanpa_setelan_verifikasi_tetap_lolos_tanpa_konfirmasi(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();

        $this->verifikasi($pekerjaan)->assertRedirect();

        $this->assertSame(StatusPerintahKerja::Selesai->value, $this->statusPekerjaan($pekerjaan));
    }

    public function test_tanda_tangan_di_hp_teknisi_tercatat_sebagai_konfirmasi_tamu(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();

        $this->actingAs($this->teknisi)->postJson("/lapangan/teknisi/tugas/{$pekerjaan->Id}/konfirmasi-penerima/tanda-tangan", [
            'NamaPenerima' => 'Pak Joko', 'JabatanPenerima' => 'Penyewa unit 3A', 'TandaTangan' => $this->gambarTandaTangan(),
        ])->assertCreated()->assertJsonPath('Konfirmasi.Metode', 'TandaTanganPerangkat');

        [$catatan] = $this->konfirmasi($pekerjaan);
        $this->assertSame('TandaTanganPerangkat', $catatan->Metode);
        $this->assertSame('Diterima', $catatan->Hasil);
        $this->assertNull($catatan->PenggunaId);
        $this->assertSame($this->teknisi->Id, $catatan->DicatatOleh);
        $this->assertSame('Pak Joko', $catatan->NamaPenerima);
        $this->assertSame('Penyewa unit 3A', $catatan->JabatanPenerima);
        $this->assertNotNull($catatan->TandaTanganBerkasId);
        $this->assertTrue($catatan->Berlaku);
        // Tanda tangan tamu tidak pernah menjadi tanda tangan profil siapa pun.
        $this->assertNull($this->tandaTanganProfil($this->teknisi));
        // Sudah dikonfirmasi: pelapor tidak diminta lagi.
        $this->actingAs($this->pelapor)->get("/lapangan/pelapor/laporan/{$pekerjaan->KeluhanId}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('laporan.KonfirmasiPekerjaan', null));
    }

    public function test_nama_penerima_dan_gambar_wajib(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();

        $this->actingAs($this->teknisi)->postJson("/lapangan/teknisi/tugas/{$pekerjaan->Id}/konfirmasi-penerima/tanda-tangan", ['NamaPenerima' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['NamaPenerima', 'TandaTangan']);

        $this->assertSame([], $this->konfirmasi($pekerjaan));
    }

    public function test_hanya_teknisi_yang_ditugaskan_yang_dapat_mencatat_tanda_tangan_di_hp(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();
        $teknisiLain = $this->penggunaDenganPeran(['TEKNISI']);

        $this->actingAs($teknisiLain)->postJson("/lapangan/teknisi/tugas/{$pekerjaan->Id}/konfirmasi-penerima/tanda-tangan", [
            'NamaPenerima' => 'Pak Joko', 'TandaTangan' => $this->gambarTandaTangan(),
        ])->assertForbidden();

        $this->assertSame([], $this->konfirmasi($pekerjaan));
    }

    /**
     * Urutan pengirim antrean offline: draf tanda tangan diunggah lebih dulu (tiket masih
     * Dikerjakan), baru mutasi "selesai" dikirim. Kiriman ulang draf yang sama tidak
     * menggandakan konfirmasi.
     */
    public function test_cara_tiga_offline_terkirim_sebelum_mutasi_selesai_dan_kiriman_ulang_tidak_ganda(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan(StatusPerintahKerja::Dikerjakan);
        $kiriman = fn () => $this->actingAs($this->teknisi)->postJson("/lapangan/teknisi/tugas/{$pekerjaan->Id}/konfirmasi-penerima/tanda-tangan", [
            'NamaPenerima' => 'Bu Rina', 'TandaTangan' => $this->gambarTandaTangan(), 'KunciPerangkat' => 'draf-ttd-123',
        ]);

        $kiriman()->assertCreated();
        $kiriman()->assertOk();
        $this->selesaikanLewatAntrean($pekerjaan)->assertJsonPath('Antrean.0.Status', 'Selesai');

        $this->assertCount(1, $this->konfirmasi($pekerjaan));
        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusPekerjaan($pekerjaan));
        // Sudah ditandatangani tamu: pelapor tidak diminta mengonfirmasi lagi.
        $this->assertDatabaseMissing('Notifikasi', ['PenggunaId' => $this->pelapor->Id, 'JenisPeristiwa' => 'Keluhan.MenungguKonfirmasi']);
    }

    public function test_layar_kerjakan_dan_paket_offline_tidak_lagi_membawa_setelan_tanda_tangan(): void
    {
        $this->wajibkanKonfirmasi();
        $pekerjaan = $this->pekerjaanDariKeluhan(StatusPerintahKerja::Dikerjakan);

        $this->actingAs($this->teknisi)->get("/lapangan/teknisi/tugas/{$pekerjaan->Id}/kerjakan")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/Teknisi/Kerjakan')
                ->missing('tandaTanganWajib')
                ->where('konfirmasiPenerima', null));
        $this->actingAs($this->teknisi)->postJson('/offline/paket', self::PERANGKAT)
            ->assertOk()
            ->assertJsonMissingPath('Paket.Pengaturan');
    }

    public function test_tiket_teknisi_menandai_menunggu_konfirmasi_penerima(): void
    {
        $pekerjaan = $this->pekerjaanDariKeluhan();

        $this->actingAs($this->teknisi)->get('/lapangan/teknisi/tugas')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('selesai.0.Id', $pekerjaan->Id)
                ->where('selesai.0.MenungguKonfirmasiPenerima', true));
        $this->actingAs($this->teknisi)->get("/lapangan/teknisi/tugas/{$pekerjaan->Id}/kerjakan")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('tiket.MenungguKonfirmasiPenerima', true));
    }
}
