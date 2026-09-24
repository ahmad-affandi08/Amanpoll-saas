<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Platform\Domain\Enums\ModeLapangan;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use Inertia\Testing\AssertableInertia;

/** Beranda pelapor dan tab Aset (DESIGN §36.7 layar 02 dan 14). */
final class PelaporBerandaAsetTest extends KasusPelapor
{
    public function test_beranda_mendahulukan_laporan_yang_menunggu_konfirmasi_dan_hanya_milik_sendiri(): void
    {
        $lantai = $this->lokasi('Lt. 12', $this->lokasi('Menara A'));
        $kategori = $this->kategori('Listrik');
        $this->kategori('Nonaktif', ['Aktif' => false]);
        $pelapor = $this->pelaporDi($lantai);
        $selesai = [StatusKeluhan::Ditinjau, StatusKeluhan::Diterima, StatusKeluhan::Diproses, StatusKeluhan::Selesai];
        $this->keluhan($pelapor, $kategori, $lantai, ['Judul' => 'Menunggu konfirmasi'], $selesai);
        $this->keluhan($pelapor, $kategori, $lantai, ['Judul' => 'Baru saja']);
        $this->keluhan($pelapor, $kategori, $lantai, ['Judul' => 'Sudah ditutup'], [...$selesai, StatusKeluhan::Ditutup]);
        $this->keluhan($this->pelaporDi($lantai), $kategori, $lantai, ['Judul' => 'Milik rekan']);

        $this->actingAs($pelapor)->get('/lapangan/pelapor')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/Pelapor/Beranda')
                ->where('lokasi.Label', 'Menara A · Lt. 12')
                ->where('kategori', fn ($daftar) => collect($daftar)->pluck('Nama')->all() === ['Listrik'])
                ->where('laporanAktif', fn ($laporan) => collect($laporan)->pluck('Judul')->all() === ['Menunggu konfirmasi', 'Baru saja'])
                ->where('jumlahAktif', 2));
    }

    public function test_tab_aset_hanya_menampilkan_aset_di_lingkup_pelapor_beserta_laporan_terbukanya(): void
    {
        $lantai = $this->lokasi('Lt. 12');
        $lantaiLain = $this->lokasi('Lt. 11');
        $lift = $this->aset('Lift 3', $lantai, 'Rusak');
        $this->aset('APAR', $lantai);
        $this->aset('Printer Lt. 11', $lantaiLain);
        $pelapor = $this->pelaporDi($lantai);
        $this->keluhan($this->pelaporDi($lantai), $this->kategori('Lift'), $lantai, ['AsetId' => $lift->Id], [StatusKeluhan::Ditinjau, StatusKeluhan::Diterima, StatusKeluhan::Diproses]);

        $this->actingAs($pelapor)->get("/lapangan/pelapor/aset?lokasi={$lantaiLain->Id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/Pelapor/Aset')
                ->where('bolehLihat', true)
                // Lokasi di luar lingkup diabaikan; yang tampil lokasi pelapor sendiri.
                ->where('lokasi.Id', $lantai->Id)
                ->where('aset', fn ($aset) => collect($aset)->pluck('Nama')->all() === ['APAR', 'Lift 3'])
                ->where('aset.1.LaporanTerbuka.0.Status', 'Diproses')
                ->where('aset.1.LaporanTerbuka.0.MilikSaya', false));
    }

    public function test_tab_aset_tanpa_izin_melihat_aset_menampilkan_keadaan_tanpa_izin(): void
    {
        $lantai = $this->lokasi('Lt. 12');
        $this->aset('APAR', $lantai);
        $pelapor = $this->buatPengguna();
        $this->dalamOrganisasi(function () use ($pelapor, $lantai): void {
            $peran = Peran::create(['Kode' => 'LAPOR-SAJA', 'Nama' => 'Lapor saja', 'TampilanLapangan' => ModeLapangan::Pelapor->value]);
            PenggunaPeran::create(['PenggunaId' => $pelapor->Id, 'PeranId' => $peran->Id, 'LokasiId' => $lantai->Id]);
        });

        $this->actingAs($pelapor)->get('/lapangan/pelapor/aset')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('bolehLihat', false)
                ->where('aset', [])
                ->where('pilihanLokasi', []));
    }
}
