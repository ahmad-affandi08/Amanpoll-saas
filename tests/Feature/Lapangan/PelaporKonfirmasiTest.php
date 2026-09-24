<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia;

/**
 * Konfirmasi selesai oleh pelapor (PRD 4.6, DESIGN §36.7 layar 12–13): "beres" menutup
 * keluhan dengan penilaian, "belum" membukanya lagi lewat transisi Selesai → Diproses.
 */
final class PelaporKonfirmasiTest extends KasusPelapor
{
    private Lokasi $lantai;

    private KategoriKeluhan $kategori;

    private Pengguna $pelapor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lantai = $this->lokasi('Lt. 12');
        $this->kategori = $this->kategori('Listrik');
        $this->pelapor = $this->pelaporDi($this->lantai);
    }

    public function test_pelapor_mengonfirmasi_beres_menutup_keluhan_dengan_penilaian(): void
    {
        $keluhan = $this->selesai($this->pelapor);

        $this->actingAs($this->pelapor)->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi", [
            'Beres' => true, 'Rating' => 4, 'Ulasan' => 'Cepat dan rapi.', 'Versi' => $keluhan->Versi,
        ])->assertSessionDoesntHaveErrors()->assertRedirect("/lapangan/pelapor/laporan/{$keluhan->Id}/terima-kasih");

        $segar = $this->segar($keluhan);
        $this->assertSame(StatusKeluhan::Ditutup->value, $segar->Status);
        $this->assertSame(4, (int) $segar->Rating);
        $this->assertSame('Cepat dan rapi.', $segar->Ulasan);
        $this->assertNotNull($segar->DitutupPada);
        $this->assertDatabaseHas('RiwayatStatusKeluhan', [
            'KeluhanId' => $keluhan->Id, 'StatusSebelum' => 'Selesai', 'StatusSesudah' => 'Ditutup', 'DiubahOleh' => $this->pelapor->Id,
        ]);
        $this->assertDatabaseHas('CatatanAudit', ['Aksi' => 'KonfirmasiPelapor', 'EntitasId' => $keluhan->Id]);

        $this->get("/lapangan/pelapor/laporan/{$keluhan->Id}/terima-kasih")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/Pelapor/TerimaKasih')
                ->where('laporan.Rating', 4));
    }

    public function test_pelapor_menjawab_belum_membuka_lagi_keluhan_dengan_alasannya(): void
    {
        $keluhan = $this->selesai($this->pelapor);

        $this->actingAs($this->pelapor)->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi", [
            'Beres' => false, 'Ulasan' => 'Lampu masih berkedip.', 'Versi' => $keluhan->Versi,
        ])->assertSessionDoesntHaveErrors()->assertRedirect("/lapangan/pelapor/laporan/{$keluhan->Id}");

        $segar = $this->segar($keluhan);
        $this->assertSame(StatusKeluhan::Diproses->value, $segar->Status);
        $this->assertNull($segar->Rating);
        $this->assertNull($segar->DiresolusikanPada);
        $this->assertDatabaseHas('RiwayatStatusKeluhan', [
            'KeluhanId' => $keluhan->Id, 'StatusSebelum' => 'Selesai', 'StatusSesudah' => 'Diproses',
            'Catatan' => 'Pelapor: masih bermasalah. Lampu masih berkedip.',
        ]);
    }

    public function test_jawaban_tanpa_bintang_atau_tanpa_alasan_ditolak(): void
    {
        $keluhan = $this->selesai($this->pelapor);
        $this->actingAs($this->pelapor);

        $this->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi", ['Beres' => true, 'Versi' => $keluhan->Versi])
            ->assertSessionHasErrors(['Rating' => 'Beri nilai 1 sampai 5 bintang.']);
        $this->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi", ['Beres' => true, 'Rating' => 6, 'Versi' => $keluhan->Versi])
            ->assertSessionHasErrors('Rating');
        $this->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi", ['Beres' => false, 'Versi' => $keluhan->Versi])
            ->assertSessionHasErrors(['Ulasan' => 'Ceritakan apa yang masih bermasalah.']);

        $this->assertSame(StatusKeluhan::Selesai->value, $this->segar($keluhan)->Status);
    }

    public function test_keluhan_yang_belum_selesai_tidak_dapat_dikonfirmasi(): void
    {
        $keluhan = $this->keluhan($this->pelapor, $this->kategori, $this->lantai, [], [StatusKeluhan::Ditinjau, StatusKeluhan::Diterima, StatusKeluhan::Diproses]);
        $this->actingAs($this->pelapor);

        $this->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi", ['Beres' => true, 'Rating' => 5, 'Versi' => $keluhan->Versi])
            ->assertForbidden();
        $this->get("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi")
            ->assertRedirect("/lapangan/pelapor/laporan/{$keluhan->Id}");
        $this->assertSame(StatusKeluhan::Diproses->value, $this->segar($keluhan)->Status);
    }

    public function test_hanya_pelapornya_sendiri_yang_boleh_mengonfirmasi(): void
    {
        $keluhan = $this->selesai($this->pelapor);
        $rekan = $this->pelaporDi($this->lantai);
        $koordinator = $this->penggunaMeja(['Keluhan.Kelola']);

        $this->actingAs($rekan)->post("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi", ['Beres' => true, 'Rating' => 5, 'Versi' => $keluhan->Versi])
            ->assertForbidden();
        $this->dalamOrganisasi(function () use ($koordinator, $keluhan): void {
            $this->assertTrue(Gate::forUser($koordinator)->denies('konfirmasi', $keluhan->fresh()));
            $this->assertTrue(Gate::forUser($this->pelapor)->allows('konfirmasi', $keluhan->fresh()));
        });
        $this->assertSame(StatusKeluhan::Selesai->value, $this->segar($keluhan)->Status);
    }

    public function test_halaman_konfirmasi_menampilkan_ringkasan_teknisi(): void
    {
        $keluhan = $this->selesai($this->pelapor);
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $this->tugaskan($keluhan, $teknisi, 'MenungguVerifikasi');

        $this->actingAs($this->pelapor)->get("/lapangan/pelapor/laporan/{$keluhan->Id}/konfirmasi")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/Pelapor/Konfirmasi')
                ->where('laporan.Teknisi.Nama', $teknisi->Nama)
                ->where('laporan.Teknisi.Ringkasan', 'Komponen diganti, sudah normal.'));
    }

    private function selesai(Pengguna $pelapor): Keluhan
    {
        return $this->keluhan($pelapor, $this->kategori, $this->lantai, [], [
            StatusKeluhan::Ditinjau, StatusKeluhan::Diterima, StatusKeluhan::Diproses, StatusKeluhan::Selesai,
        ]);
    }

    private function segar(Keluhan $keluhan): Keluhan
    {
        return $this->dalamOrganisasi(fn () => Keluhan::query()->findOrFail($keluhan->Id));
    }
}
