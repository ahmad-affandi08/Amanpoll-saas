<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Inertia\Testing\AssertableInertia;

/** Laporan Saya, Lacak laporan, Laporan terkirim (DESIGN §36.7 layar 08–10, TASK 39.08). */
final class PelaporLaporanTest extends KasusPelapor
{
    private Lokasi $lantai;

    private KategoriKeluhan $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lantai = $this->lokasi('Lt. 12', $this->lokasi('Menara A'));
        $this->kategori = $this->kategori('Listrik');
    }

    public function test_laporan_saya_hanya_berisi_keluhan_sendiri_dengan_jumlah_per_tab(): void
    {
        $pelapor = $this->pelaporDi($this->lantai);
        $rekan = $this->pelaporDi($this->lantai);
        $this->keluhan($pelapor, $this->kategori, $this->lantai, ['Judul' => 'Baru saja']);
        $this->keluhan($pelapor, $this->kategori, $this->lantai, ['Judul' => 'Menunggu konfirmasi'], $this->sampai(StatusKeluhan::Selesai));
        $this->keluhan($pelapor, $this->kategori, $this->lantai, ['Judul' => 'Sudah ditutup'], [...$this->sampai(StatusKeluhan::Selesai), StatusKeluhan::Ditutup]);
        $this->keluhan($rekan, $this->kategori, $this->lantai, ['Judul' => 'Milik rekan']);

        $this->actingAs($pelapor)->get('/lapangan/pelapor/laporan')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/Pelapor/Laporan')
                ->has('laporan', 3)
                ->where('laporan', fn ($laporan) => ! collect($laporan)->pluck('Judul')->contains('Milik rekan'))
                ->where('jumlah', ['Aktif' => 2, 'PerluKonfirmasi' => 1, 'Selesai' => 1]));
    }

    public function test_lacak_menyusun_riwayat_status_dan_teknisi_yang_ditugaskan(): void
    {
        $pelapor = $this->pelaporDi($this->lantai);
        $keluhan = $this->keluhan($pelapor, $this->kategori, $this->lantai, [], $this->sampai(StatusKeluhan::Diproses));
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $this->dalamOrganisasi(fn () => $teknisi->forceFill(['Telepon' => '0812-1111-2222'])->save());
        $this->tugaskan($keluhan, $teknisi);

        $this->actingAs($pelapor)->get("/lapangan/pelapor/laporan/{$keluhan->Id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/Pelapor/Lacak')
                ->where('laporan.Status', 'Diproses')
                ->where('laporan.LokasiLabel', 'Menara A · Lt. 12')
                ->where('laporan.Teknisi.Nama', $teknisi->Nama)
                ->where('laporan.Teknisi.Telepon', '0812-1111-2222')
                ->where('riwayat', fn ($riwayat) => collect($riwayat)->pluck('StatusSesudah')->all() === ['Baru', 'Ditinjau', 'Diterima', 'Diproses'])
                ->where('riwayat.0.OlehSaya', true));
    }

    public function test_keluhan_pelapor_lain_tidak_dapat_dibuka(): void
    {
        $pelapor = $this->pelaporDi($this->lantai);
        $milikRekan = $this->keluhan($this->pelaporDi($this->lantai), $this->kategori, $this->lantai);
        $this->actingAs($pelapor);

        $this->get("/lapangan/pelapor/laporan/{$milikRekan->Id}")->assertForbidden();
        $this->get("/lapangan/pelapor/laporan/{$milikRekan->Id}/terkirim")->assertForbidden();
    }

    public function test_pemegang_kelola_pun_tidak_membuka_keluhan_orang_lain_di_layar_pelapor(): void
    {
        $koordinator = $this->penggunaMeja(['Keluhan.Kelola'], $this->pelaporDi($this->lantai));
        $milikRekan = $this->keluhan($this->pelaporDi($this->lantai), $this->kategori, $this->lantai);

        $this->actingAs($koordinator)->get("/lapangan/pelapor/laporan/{$milikRekan->Id}")->assertNotFound();
    }

    public function test_keluhan_organisasi_lain_tidak_ditemukan(): void
    {
        $pelapor = $this->pelaporDi($this->lantai);
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN-'.uniqid(), 'Nama' => 'Organisasi Lain', 'Status' => 'Aktif']);
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($lain->Id);
        $keluhanLain = Keluhan::create([
            'Nomor' => 'KLH-LAIN-1', 'Judul' => 'Keluhan lain', 'Deskripsi' => 'Lain.',
            'Status' => 'Baru', 'DilaporkanPada' => now(),
        ]);
        $konteks->bersihkan();

        $this->actingAs($pelapor)->get("/lapangan/pelapor/laporan/{$keluhanLain->Id}")->assertNotFound();
    }

    public function test_laporan_terkirim_menampilkan_nomor_dan_target_ditinjau(): void
    {
        $pelapor = $this->pelaporDi($this->lantai);
        $keluhan = $this->keluhan($pelapor, $this->kategori, $this->lantai);

        $this->actingAs($pelapor)->get("/lapangan/pelapor/laporan/{$keluhan->Id}/terkirim")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/Pelapor/Terkirim')
                ->where('laporan.Nomor', $keluhan->Nomor));
    }

    /** @return list<StatusKeluhan> */
    private function sampai(StatusKeluhan $tujuan): array
    {
        $alur = [StatusKeluhan::Ditinjau, StatusKeluhan::Diterima, StatusKeluhan::Diproses, StatusKeluhan::Selesai];

        return array_slice($alur, 0, (int) array_search($tujuan, $alur, true) + 1);
    }
}
