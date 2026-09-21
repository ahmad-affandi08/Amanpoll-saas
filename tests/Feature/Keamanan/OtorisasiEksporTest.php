<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Pelaporan\Application\Services\LayananEksporLaporan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Berkas ekspor memuat angka yang sudah disaring menurut izin pemesannya, jadi
 * hanya pemesan itu yang boleh mengunduhnya — sekalipun rekan satu organisasi
 * memegang izin laporan yang sama (24).
 */
final class OtorisasiEksporTest extends KasusKeamanan
{
    private Organisasi $organisasi;

    private Pengguna $pemesan;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->organisasi = $this->buatOrganisasi('ORG-EKSPOR');
        $this->pemesan = $this->buatPengguna($this->organisasi, ['Laporan.Lihat']);
    }

    public function test_pemesan_dapat_mengunduh_ekspornya_sendiri(): void
    {
        $berkas = $this->buatBerkasEkspor($this->pemesan);

        $this->actingAs($this->pemesan)
            ->get(route('pelaporan.ekspor.unduh', $berkas))
            ->assertOk();
    }

    public function test_rekan_seorganisasi_tidak_dapat_mengunduh_ekspor_orang_lain(): void
    {
        $berkas = $this->buatBerkasEkspor($this->pemesan);
        $rekan = $this->buatPengguna($this->organisasi, ['Laporan.Lihat']);

        $this->actingAs($rekan)
            ->get(route('pelaporan.ekspor.unduh', $berkas))
            ->assertForbidden();
    }

    public function test_pengguna_organisasi_lain_tidak_menemukan_berkasnya(): void
    {
        $berkas = $this->buatBerkasEkspor($this->pemesan);

        $organisasiLain = $this->buatOrganisasi('ORG-EKSPOR-LAIN');
        $penyerang = $this->buatPengguna($organisasiLain, ['Laporan.Lihat']);

        $this->actingAs($penyerang)
            ->get(route('pelaporan.ekspor.unduh', $berkas))
            ->assertNotFound();
    }

    public function test_berkas_biasa_tidak_dapat_diunduh_lewat_rute_ekspor(): void
    {
        $lampiran = $this->buatBerkasEkspor($this->pemesan, jenis: 'Lampiran');

        $this->actingAs($this->pemesan)
            ->get(route('pelaporan.ekspor.unduh', $lampiran))
            ->assertForbidden();
    }

    public function test_permintaan_ekspor_menolak_kpi_di_luar_kewenangan(): void
    {
        // Pengguna ini boleh melihat aset, tetapi nilai persediaan menuntut
        // Stok.Kelola yang tidak ia punya.
        $terbatas = $this->buatPengguna($this->organisasi, ['Aset.Lihat']);

        $this->actingAs($terbatas)
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Percobaan',
                'Format' => 'Csv',
                'KunciKpi' => ['aset.jumlah', 'stok.nilai'],
            ])
            ->assertForbidden();
    }

    public function test_permintaan_ekspor_diterima_untuk_kpi_yang_diizinkan(): void
    {
        $this->actingAs($this->buatPengguna($this->organisasi, ['Aset.Lihat']))
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Rekap Aset',
                'Format' => 'Csv',
                'KunciKpi' => ['aset.jumlah'],
            ])
            ->assertRedirect()
            ->assertSessionHas('sukses');
    }

    private function buatBerkasEkspor(Pengguna $pemilik, string $jenis = LayananEksporLaporan::JENIS_BERKAS): Berkas
    {
        return $this->dalamOrganisasi($this->organisasi, function () use ($pemilik, $jenis): Berkas {
            $nama = (string) Str::ulid().'.csv';
            Storage::disk('local')->put("berkas/{$this->organisasi->Id}/{$nama}", 'Label,Nilai');

            return Berkas::create([
                'NamaAsli' => 'laporan.csv',
                'NamaPenyimpanan' => $nama,
                'MediaPenyimpanan' => 'local',
                'LokasiPenyimpanan' => "berkas/{$this->organisasi->Id}/{$nama}",
                'JenisMime' => 'text/csv',
                'UkuranByte' => 11,
                'HashSha256' => hash('sha256', 'Label,Nilai'),
                'DataTambahan' => ['Jenis' => $jenis],
                'DiunggahOleh' => $pemilik->Id,
            ]);
        });
    }
}
