<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

/**
 * Layar tiket Teknisi Mode Lapangan (TASK 39.04–39.05): beranda, Tiket Saya, detail,
 * dan halaman kerja. Teknisi hanya melihat dan membuka tiket yang ditugaskan kepadanya;
 * tiket teknisi lain ditolak policy dasbor, tiket tenant lain tidak ditemukan.
 */
final class TeknisiTiketTest extends KasusTeknisi
{
    private const PERANGKAT = ['IdentitasPerangkat' => 'hp-teknisi-uji', 'NamaPerangkat' => 'Ponsel', 'Platform' => 'Android'];

    public function test_beranda_dan_tiket_saya_hanya_berisi_tiket_yang_ditugaskan_kepadanya(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $rekan = $this->penggunaDenganPeran(['TEKNISI']);
        $milikku = $this->buatTiket($teknisi, aset: $this->buatAset());
        $this->buatTiket($rekan);
        $this->buatTiket(null);
        $this->tiketOrganisasiLain();

        foreach (['/lapangan/teknisi' => 'Lapangan/Teknisi/Beranda', '/lapangan/teknisi/tugas' => 'Lapangan/Teknisi/Tugas'] as $url => $halaman) {
            $this->actingAs($teknisi)->get($url)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                    ->component($halaman)
                    ->has('tiket', 1)
                    ->where('tiket.0.Id', $milikku->Id)
                    ->where('tiket.0.PerluRespons', true)
                    ->where('tiket.0.Aset.Nama', 'Lift Penumpang 3')
                    ->has('selesai', 0));
        }
    }

    public function test_tiket_yang_sudah_diselesaikan_teknisi_pindah_ke_daftar_selesai(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $aktif = $this->buatTiket($teknisi, StatusPerintahKerja::Dikerjakan);
        $menunggu = $this->buatTiket($teknisi, StatusPerintahKerja::MenungguVerifikasi);

        $this->actingAs($teknisi)->get('/lapangan/teknisi/tugas')
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->has('tiket', 1)
                ->where('tiket.0.Id', $aktif->Id)
                ->has('selesai', 1)
                ->where('selesai.0.Id', $menunggu->Id)
                ->where('selesai.0.DapatDibuka', true));
    }

    public function test_detail_tiket_menyusun_asal_keluhan_checklist_dan_transisi_yang_diizinkan(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $aset = $this->buatAset();
        $keluhan = $this->dalamOrganisasi(fn (): Keluhan => Keluhan::create([
            'Nomor' => 'KLH-UJI-01',
            'Judul' => 'Lift berhenti',
            'Deskripsi' => 'Lift 3 berhenti di antara Lt. 7 dan 8.',
            'NamaPelaporEksternal' => 'Agus Pratama',
            'LokasiId' => Lokasi::create(['Kode' => 'LOK-'.uniqid(), 'Nama' => 'Pos Keamanan Lobi'])->Id,
            'Prioritas' => 'Kritis',
            'Status' => 'Diproses',
            'DilaporkanPada' => now()->subHour(),
        ]));
        $tiket = $this->buatTiket($teknisi, StatusPerintahKerja::Diterima, $aset, ['KeluhanId' => $keluhan->Id]);
        $this->buatChecklist($tiket, 3);

        $this->actingAs($teknisi)->get("/lapangan/teknisi/tugas/{$tiket->Id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->component('Lapangan/Teknisi/DetailTiket')
                ->where('tiket.Id', $tiket->Id)
                ->where('tiket.StatusTujuan', ['Dikerjakan'])
                ->where('keluhan.Nomor', 'KLH-UJI-01')
                ->where('keluhan.Pelapor', 'Agus Pratama')
                ->where('keluhan.Lokasi', 'Pos Keamanan Lobi')
                ->where('keluhan.Deskripsi', 'Lift 3 berhenti di antara Lt. 7 dan 8.')
                ->where('daftarPeriksa.JumlahButir', 3)
                ->where('riwayatAset.JumlahPekerjaan', 1));
    }

    public function test_tiket_teknisi_lain_ditolak_dan_tiket_tenant_lain_tidak_ditemukan(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $milikRekan = $this->buatTiket($this->penggunaDenganPeran(['TEKNISI']));
        $tenantLain = $this->tiketOrganisasiLain();

        foreach (["/lapangan/teknisi/tugas/{$milikRekan->Id}", "/lapangan/teknisi/tugas/{$milikRekan->Id}/kerjakan"] as $url) {
            $this->actingAs($teknisi)->get($url)->assertForbidden();
        }
        foreach (["/lapangan/teknisi/tugas/{$tenantLain->Id}", "/lapangan/teknisi/tugas/{$tenantLain->Id}/kerjakan"] as $url) {
            $this->actingAs($teknisi)->get($url)->assertNotFound();
        }
    }

    public function test_tiket_di_luar_lingkup_unit_teknisi_tidak_terlihat_walau_ditugaskan(): void
    {
        [$lokasiKu, $lokasiLain] = $this->dalamOrganisasi(fn (): array => [
            Lokasi::create(['Kode' => 'LOK-A-'.uniqid(), 'Nama' => 'Menara A']),
            Lokasi::create(['Kode' => 'LOK-B-'.uniqid(), 'Nama' => 'Menara B']),
        ]);
        $teknisi = $this->penggunaDenganPeran(['TEKNISI'], lokasiId: $lokasiKu->Id);
        $diLingkup = $this->buatTiket($teknisi, lain: ['LokasiId' => $lokasiKu->Id]);
        $luarLingkup = $this->buatTiket($teknisi, lain: ['LokasiId' => $lokasiLain->Id]);

        $this->actingAs($teknisi)->get('/lapangan/teknisi/tugas')
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia->has('tiket', 1)->where('tiket.0.Id', $diLingkup->Id));
        $this->actingAs($teknisi)->get("/lapangan/teknisi/tugas/{$luarLingkup->Id}")->assertNotFound();
    }

    public function test_layar_teknisi_tidak_terbuka_bagi_pelapor_maupun_pengguna_meja(): void
    {
        $tiket = $this->buatTiket(null);

        $this->actingAs($this->penggunaDenganPeran(['PELAPOR']))
            ->get("/lapangan/teknisi/tugas/{$tiket->Id}")
            ->assertRedirect(route('lapangan.beranda'));
        $this->actingAs($this->penggunaMeja(['PerintahKerja.Kelola']))
            ->get('/lapangan/teknisi/tugas')
            ->assertRedirect(route('dashboard'));
    }

    public function test_halaman_kerja_membawa_checklist_kode_kegagalan_dan_foto_tiket_saja(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $tiket = $this->buatTiket($teknisi, StatusPerintahKerja::Dikerjakan, $this->buatAset());
        $this->buatChecklist($tiket, 2);
        $this->dalamOrganisasi(function () use ($tiket, $teknisi): void {
            foreach (['FotoSebelum', 'TandaTangan', 'KontrakVendor'] as $kategori) {
                $berkas = Berkas::create([
                    'NamaAsli' => "{$kategori}.jpg", 'NamaPenyimpanan' => uniqid().'.jpg', 'MediaPenyimpanan' => 'local',
                    'LokasiPenyimpanan' => 'uji/'.uniqid().'.jpg', 'JenisMime' => 'image/jpeg', 'UkuranByte' => 10,
                    'HashSha256' => str_repeat('a', 64), 'DiunggahOleh' => $teknisi->Id,
                ]);
                LampiranEntitas::create([
                    'JenisEntitas' => 'PerintahKerja', 'EntitasId' => $tiket->Id, 'BerkasId' => $berkas->Id,
                    'Kategori' => $kategori, 'DibuatOleh' => $teknisi->Id,
                ]);
            }
        });

        $this->actingAs($teknisi)->get("/lapangan/teknisi/tugas/{$tiket->Id}/kerjakan")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->component('Lapangan/Teknisi/Kerjakan')
                ->has('daftarPeriksa.Butir', 2)
                ->where('tiket.StatusTujuan', ['MenungguSukuCadang', 'MenungguPenyedia', 'Dijeda', 'MenungguVerifikasi'])
                // Lampiran TandaTangan lama tidak lagi ditampilkan: tanda tangan penerima kini
                // konfirmasi penerima, bukan lampiran (PRD 8.22).
                ->has('foto', 1)
                ->where('foto.0.Kategori', 'FotoSebelum'));
    }

    /**
     * Alur yang dikirim layar kerja lewat antrean FASE 20, dengan versi berantai seperti
     * `rencanaMulai`: terima → mulai → checklist → sesi waktu kerja → selesai. Hasil akhirnya
     * Menunggu Verifikasi; menutup ke Selesai tetap milik koordinator.
     */
    public function test_alur_terima_sampai_selesai_berakhir_di_menunggu_verifikasi(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $tiket = $this->buatTiket($teknisi, aset: $this->buatAset());
        $pelaksanaan = $this->buatChecklist($tiket, 1);
        $butir = $this->dalamOrganisasi(fn () => ButirTemplatDaftarPeriksa::query()->where('TemplatDaftarPeriksaId', $pelaksanaan->TemplatDaftarPeriksaId)->firstOrFail());
        $versi = $this->versiTiket($tiket);

        $respons = $this->dorong($teknisi, [
            $this->mutasi('terima', 'PerintahKerja.ResponsPenugasan', $tiket->Id, $versi, ['Respons' => 'Terima']),
            $this->mutasi('mulai', 'PerintahKerja.UbahStatus', $tiket->Id, $versi + 1, ['Status' => 'Dikerjakan', 'Catatan' => 'Mulai dikerjakan dari Mode Lapangan.']),
            $this->mutasi('jawab', 'DaftarPeriksa.SimpanJawaban', $pelaksanaan->Id, null, ['Jawaban' => [['ButirTemplatDaftarPeriksaId' => $butir->Id, 'NilaiBoolean' => true]]]),
            $this->mutasi('final', 'DaftarPeriksa.Finalisasi', $pelaksanaan->Id, null, ['Catatan' => null]),
            $this->mutasi('waktu', 'PerintahKerja.CatatWaktuKerja', $tiket->Id, null, ['MulaiPada' => now()->subMinutes(42)->toIso8601String(), 'SelesaiPada' => now()->subMinute()->toIso8601String()]),
            $this->mutasi('selesai', 'PerintahKerja.UbahStatus', $tiket->Id, $versi + 2, ['Status' => 'MenungguVerifikasi', 'Ringkasan' => "Tindakan: Ganti kontaktor K1\nKondisi aset: Berfungsi normal"]),
        ])->assertOk();

        $this->assertSame(
            ['Selesai', 'Selesai', 'Selesai', 'Selesai', 'Selesai', 'Selesai'],
            collect($respons->json('Antrean'))->pluck('Status')->all(),
        );
        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusTiket($tiket));
        $this->assertSame('Selesai', $this->dalamOrganisasi(fn () => $pelaksanaan->fresh()?->Status));

        // Mutasi "Selesai" dari lapangan ditolak policy: teknisi tidak menutup tiketnya sendiri.
        $tutup = $this->dorong($teknisi, [$this->mutasi('tutup', 'PerintahKerja.UbahStatus', $tiket->Id, $versi + 3, ['Status' => 'Selesai'])]);
        $this->assertSame('Gagal', collect($tutup->json('Antrean'))->firstWhere('KunciOperasi', 'op-tutup')['Status']);
        $this->assertSame(StatusPerintahKerja::MenungguVerifikasi->value, $this->statusTiket($tiket));

        $this->actingAs($teknisi)->get("/lapangan/teknisi/tugas/{$tiket->Id}/kerjakan")
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->where('tiket.Status', 'MenungguVerifikasi')
                ->where('waktuKerja.TotalMenit', 41));
    }

    public function test_menyiapkan_mode_lapangan_menghitung_tiket_aset_dan_templat_milik_teknisi(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $tiket = $this->buatTiket($teknisi, aset: $this->buatAset());
        $this->buatChecklist($tiket, 2);
        $this->buatTiket($this->penggunaDenganPeran(['TEKNISI']), aset: $this->buatAset('Genset rekan'));

        $this->actingAs($teknisi)->get('/lapangan/teknisi/siapkan')
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->component('Lapangan/Teknisi/Siapkan')
                ->where('tiketId', [$tiket->Id])
                ->where('jumlah.Tiket', 1)
                ->where('jumlah.Aset', 1)
                ->where('jumlah.Templat', 1));
    }

    private function buatChecklist(PerintahKerja $tiket, int $jumlahButir): PelaksanaanDaftarPeriksa
    {
        return $this->dalamOrganisasi(function () use ($tiket, $jumlahButir): PelaksanaanDaftarPeriksa {
            $templat = TemplatDaftarPeriksa::create(['Kode' => 'CL-'.uniqid(), 'Nama' => 'Checklist lift darurat', 'Jenis' => 'Korektif', 'Aktif' => true]);
            for ($i = 1; $i <= $jumlahButir; $i++) {
                ButirTemplatDaftarPeriksa::create([
                    'TemplatDaftarPeriksaId' => $templat->Id, 'Urutan' => $i, 'Pertanyaan' => "Langkah {$i}",
                    'TipeJawaban' => 'YaTidak', 'Wajib' => true,
                ]);
            }

            return PelaksanaanDaftarPeriksa::create([
                'TemplatDaftarPeriksaId' => $templat->Id, 'PerintahKerjaId' => $tiket->Id, 'Status' => 'Draft', 'MulaiPada' => now(),
            ]);
        });
    }

    /** @param  array<string, mixed>  $muatan */
    private function mutasi(string $kunci, string $operasi, string $entitasId, ?int $versi, array $muatan): array
    {
        return ['KunciOperasi' => "op-{$kunci}", 'Operasi' => $operasi, 'EntitasId' => $entitasId, 'VersiKlien' => $versi, 'MuatanData' => $muatan];
    }

    /** @param  list<array<string, mixed>>  $mutasi */
    private function dorong(Pengguna $teknisi, array $mutasi): TestResponse
    {
        return $this->actingAs($teknisi)->postJson('/offline/antrian', [...self::PERANGKAT, 'Mutasi' => $mutasi]);
    }
}
