<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\UrgensiPelapor;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Domain\Enums\ModeLapangan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

/** Lapor kerusakan tiga langkah (DESIGN §36.7 layar 04–07, TASK 39.07). */
final class PelaporLaporTest extends KasusPelapor
{
    private Lokasi $menara;

    private Lokasi $lantai;

    private Lokasi $lantaiLain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->menara = $this->lokasi('Menara A');
        $this->lantai = $this->lokasi('Lt. 12', $this->menara);
        $this->lantaiLain = $this->lokasi('Lt. 11', $this->menara);
    }

    public function test_langkah_lapor_menampilkan_lokasi_pelapor_dan_hanya_aset_di_lingkupnya(): void
    {
        $ruangRapat = $this->lokasi('Ruang Rapat', $this->lantai);
        $this->aset('Printer Lt. 12', $this->lantai);
        $this->aset('AC Ruang Rapat', $ruangRapat);
        $this->aset('Printer Lt. 11', $this->lantaiLain);
        $pelapor = $this->pelaporDi($this->lantai);

        $this->actingAs($pelapor)->get('/lapangan/pelapor/lapor')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/Pelapor/Lapor')
                ->where('bolehLihatAset', true)
                ->where('lokasi.Label', 'Menara A · Lt. 12')
                ->where('aset', fn ($aset) => collect($aset)->pluck('Nama')->sort()->values()->all() === ['AC Ruang Rapat', 'Printer Lt. 12'])
                ->where('pilihanLokasi', fn ($lokasi) => ! collect($lokasi)->pluck('Nama')->contains('Lt. 11'))
                ->where('asetTerpilih', null));
    }

    public function test_parameter_aset_mengisi_alat_dan_menandai_laporan_terbuka_tanpa_membuka_milik_orang_lain(): void
    {
        $printer = $this->aset('Printer Lt. 12', $this->lantai);
        $kategori = $this->kategori('IT & Printer');
        $pelapor = $this->pelaporDi($this->lantai);
        $rekan = $this->pelaporDi($this->lantai);
        $milikRekan = $this->keluhan($rekan, $kategori, $this->lantai, ['AsetId' => $printer->Id], [StatusKeluhan::Ditinjau, StatusKeluhan::Diterima, StatusKeluhan::Diproses]);
        $this->tugaskan($milikRekan, $this->penggunaDenganPeran(['TEKNISI']));
        $milikSaya = $this->keluhan($pelapor, $kategori, $this->lantai, ['AsetId' => $printer->Id]);
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $this->tugaskan($milikSaya, $teknisi);
        // Keluhan yang sudah selesai tidak dihitung sebagai laporan terbuka.
        $this->keluhan($rekan, $kategori, $this->lantai, ['AsetId' => $printer->Id], [StatusKeluhan::Ditinjau, StatusKeluhan::Diterima, StatusKeluhan::Diproses, StatusKeluhan::Selesai]);

        $this->actingAs($pelapor)->get("/lapangan/pelapor/lapor?aset={$printer->Id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('asetTerpilih.Id', $printer->Id)
                ->where('asetTerpilih.LokasiLabel', 'Menara A · Lt. 12')
                ->has('asetTerpilih.LaporanTerbuka', 2)
                ->where('asetTerpilih.LaporanTerbuka', function ($laporan) use ($milikSaya, $milikRekan, $teknisi): bool {
                    $peta = collect($laporan)->mapWithKeys(fn ($satu) => [$satu['Id'] => [$satu['MilikSaya'], $satu['NamaTeknisi']]])->all();
                    $harapan = [$milikSaya->Id => [true, $teknisi->Nama], $milikRekan->Id => [false, null]];
                    ksort($peta);
                    ksort($harapan);

                    return $peta === $harapan;
                })
                ->where('asetTidakDitemukan', false));
    }

    public function test_aset_di_luar_lingkup_atau_organisasi_lain_tidak_diisi(): void
    {
        $asetLantaiLain = $this->aset('Printer Lt. 11', $this->lantaiLain);
        $asetOrganisasiLain = $this->asetOrganisasiLain();
        $pelapor = $this->pelaporDi($this->lantai);
        $this->actingAs($pelapor);

        foreach (["aset={$asetLantaiLain->Id}", "kode={$asetLantaiLain->KodeQr}", "aset={$asetOrganisasiLain->Id}", "kode={$asetOrganisasiLain->KodeQr}"] as $kueri) {
            $this->get("/lapangan/pelapor/lapor?{$kueri}")
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                    ->where('asetTerpilih', null)
                    ->where('asetTidakDitemukan', true));
        }
    }

    public function test_kode_qr_yang_dipindai_mengisi_aset(): void
    {
        $printer = $this->aset('Printer Lt. 12', $this->lantai);

        $this->actingAs($this->pelaporDi($this->lantai))
            ->get('/lapangan/pelapor/lapor?kode='.urlencode((string) $printer->KodeQr))
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('asetTerpilih.Id', $printer->Id));
    }

    public function test_pelapor_tanpa_izin_melihat_aset_tetap_bisa_melapor_tanpa_daftar_aset(): void
    {
        $printer = $this->aset('Printer Lt. 12', $this->lantai);
        $pelapor = $this->buatPengguna();
        $this->dalamOrganisasi(function () use ($pelapor): void {
            $peran = Peran::create(['Kode' => 'LAPOR-SAJA', 'Nama' => 'Lapor saja', 'TampilanLapangan' => ModeLapangan::Pelapor->value]);
            PenggunaPeran::create(['PenggunaId' => $pelapor->Id, 'PeranId' => $peran->Id, 'LokasiId' => $this->lantai->Id]);
        });

        $this->actingAs($pelapor)->get("/lapangan/pelapor/lapor?aset={$printer->Id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('bolehLihatAset', false)
                ->where('aset', [])
                ->where('asetTerpilih', null));
    }

    public function test_mengirim_laporan_membuat_keluhan_lewat_domain_dengan_urgensi_sebagai_usulan(): void
    {
        Storage::fake('local');
        $printer = $this->aset('Printer Lt. 12', $this->lantai);
        $kategori = $this->kategori('IT & Printer');
        $pelapor = $this->pelaporDi($this->lantai);

        $respons = $this->actingAs($pelapor)->post('/lapangan/pelapor/lapor', [
            ...$this->muatan($kategori->Id, $printer->Id),
            'Urgensi' => 'Berbahaya',
            'Foto' => [UploadedFile::fake()->image('macet.jpg')],
        ]);

        $keluhan = $this->dalamOrganisasi(fn () => Keluhan::query()->sole());
        $respons->assertSessionDoesntHaveErrors()->assertRedirect("/lapangan/pelapor/laporan/{$keluhan->Id}/terkirim");
        $this->assertSame($pelapor->Id, $keluhan->PelaporId);
        $this->assertSame('Lapangan', $keluhan->Sumber);
        $this->assertSame(StatusKeluhan::Baru->value, $keluhan->Status);
        $this->assertStringStartsWith('KLH/', $keluhan->Nomor);
        $this->assertSame($this->lantai->Id, $keluhan->LokasiId);
        // Pelapor tanpa Keluhan.Kelola tidak menentukan prioritas (aturan yang sama dengan dasbor):
        // "Berbahaya" hanya tersimpan sebagai usulan, dan deskripsinya tetap apa yang ia tulis.
        $this->assertSame('Normal', $keluhan->Prioritas);
        $this->assertSame(UrgensiPelapor::Berbahaya, $keluhan->UsulanUrgensi);
        $this->assertSame('Kertas macet. Nyangkut di baki 2.', $keluhan->Deskripsi);
        $this->assertDatabaseHas('RiwayatStatusKeluhan', ['KeluhanId' => $keluhan->Id, 'StatusSesudah' => 'Baru', 'DiubahOleh' => $pelapor->Id]);
        $this->assertSame(1, $this->dalamOrganisasi(fn () => LampiranEntitas::query()->where('JenisEntitas', 'Keluhan')->where('EntitasId', $keluhan->Id)->count()));
    }

    public function test_urgensi_menjadi_prioritas_hanya_bagi_pemegang_keluhan_kelola(): void
    {
        $kategori = $this->kategori('Listrik');
        $koordinator = $this->penggunaMeja(['Keluhan.Kelola'], $this->pelaporDi($this->lantai));

        $this->actingAs($koordinator)->post('/lapangan/pelapor/lapor', [
            ...$this->muatan($kategori->Id),
            'Urgensi' => 'Berbahaya',
        ])->assertSessionDoesntHaveErrors();

        $keluhan = $this->dalamOrganisasi(fn () => Keluhan::query()->sole());
        $this->assertSame('Kritis', $keluhan->Prioritas);
        $this->assertSame(UrgensiPelapor::Berbahaya, $keluhan->UsulanUrgensi);
    }

    public function test_pelapor_tanpa_keluhan_kelola_tidak_pernah_menetapkan_prioritas_lewat_urgensi_atau_isian_prioritas(): void
    {
        $kategori = $this->kategori('Listrik', ['PrioritasBawaan' => 'Rendah']);
        $pelapor = $this->pelaporDi($this->lantai);
        $this->actingAs($pelapor);

        foreach (['TidakBuruBuru', 'MenggangguKerja', 'KerjaTerhenti', 'Berbahaya'] as $i => $urgensi) {
            $this->post('/lapangan/pelapor/lapor', [
                ...$this->muatan($kategori->Id),
                'Urgensi' => $urgensi,
                // Isian Prioritas selundupan diabaikan: aturan lapangan membuangnya.
                'Prioritas' => 'Kritis',
                'KunciLaporan' => "kunci-{$i}",
            ])->assertSessionDoesntHaveErrors();
        }

        $keluhan = $this->dalamOrganisasi(fn () => Keluhan::query()->get());
        $this->assertCount(4, $keluhan);
        // Semuanya berprioritas bawaan kategori; urgensinya hanya tercatat sebagai usulan.
        $this->assertSame(['Rendah'], $keluhan->pluck('Prioritas')->unique()->values()->all());
        $this->assertEqualsCanonicalizing(
            ['TidakBuruBuru', 'MenggangguKerja', 'KerjaTerhenti', 'Berbahaya'],
            $keluhan->map(fn (Keluhan $satu): ?string => $satu->UsulanUrgensi?->value)->all(),
        );
    }

    public function test_urgensi_di_luar_pilihan_ditolak(): void
    {
        $kategori = $this->kategori('Listrik');

        $this->actingAs($this->pelaporDi($this->lantai))
            ->post('/lapangan/pelapor/lapor', [...$this->muatan($kategori->Id), 'Urgensi' => 'Kritis'])
            ->assertSessionHasErrors('Urgensi');

        $this->assertSame(0, $this->dalamOrganisasi(fn () => Keluhan::query()->count()));
    }

    public function test_kunci_laporan_yang_sama_tidak_pernah_menjadi_dua_keluhan(): void
    {
        Storage::fake('local');
        $kategori = $this->kategori('Listrik');
        $pelapor = $this->pelaporDi($this->lantai);
        $muatan = $this->muatan($kategori->Id);
        $this->actingAs($pelapor);

        $pertama = $this->post('/lapangan/pelapor/lapor', [...$muatan, 'Foto' => [UploadedFile::fake()->image('a.jpg')]]);
        $kedua = $this->post('/lapangan/pelapor/lapor', [...$muatan, 'Foto' => [UploadedFile::fake()->image('b.jpg')]]);

        $keluhan = $this->dalamOrganisasi(fn () => Keluhan::query()->sole());
        $pertama->assertRedirect("/lapangan/pelapor/laporan/{$keluhan->Id}/terkirim");
        $kedua->assertRedirect("/lapangan/pelapor/laporan/{$keluhan->Id}/terkirim");
        $this->assertSame(1, $this->dalamOrganisasi(fn () => LampiranEntitas::query()->where('EntitasId', $keluhan->Id)->count()));
    }

    public function test_lokasi_atau_aset_di_luar_lingkup_ditolak_tanpa_menulis_keluhan(): void
    {
        $kategori = $this->kategori('Listrik');
        $asetLain = $this->aset('Printer Lt. 11', $this->lantaiLain);
        $this->actingAs($this->pelaporDi($this->lantai));

        $this->post('/lapangan/pelapor/lapor', $this->muatan($kategori->Id, null, $this->lantaiLain->Id))
            ->assertSessionHasErrors(['LokasiId' => 'Lokasi ini di luar area yang bisa kamu laporkan.']);
        $this->post('/lapangan/pelapor/lapor', $this->muatan($kategori->Id, $asetLain->Id))
            ->assertSessionHasErrors(['AsetId' => 'Alat ini di luar area yang bisa kamu laporkan.']);

        $this->assertSame(0, $this->dalamOrganisasi(fn () => Keluhan::query()->count()));
    }

    public function test_kategori_yang_mewajibkan_aset_menolak_lapor_lokasi_saja(): void
    {
        $kategori = $this->kategori('Lift', ['AsetWajib' => true]);

        $this->actingAs($this->pelaporDi($this->lantai))
            ->post('/lapangan/pelapor/lapor', $this->muatan($kategori->Id))
            ->assertSessionHasErrors(['AsetId' => 'Aset wajib dipilih untuk kategori keluhan ini.']);

        $this->assertSame(0, $this->dalamOrganisasi(fn () => Keluhan::query()->count()));
    }

    public function test_laporan_tanpa_isian_wajib_ditolak(): void
    {
        $this->actingAs($this->pelaporDi($this->lantai))
            ->post('/lapangan/pelapor/lapor', [])
            ->assertSessionHasErrors(['KategoriKeluhanId', 'LokasiId', 'Judul', 'Deskripsi', 'KunciLaporan']);
    }

    /** @return array<string, string|null> */
    private function muatan(string $kategoriId, ?string $asetId = null, ?string $lokasiId = null): array
    {
        return [
            'KategoriKeluhanId' => $kategoriId,
            'AsetId' => $asetId,
            'LokasiId' => $lokasiId ?? $this->lantai->Id,
            'Judul' => 'Printer Lt. 12 kertas macet',
            'Deskripsi' => 'Kertas macet. Nyangkut di baki 2.',
            'Urgensi' => 'MenggangguKerja',
            'KunciLaporan' => 'kunci-laporan-uji',
        ];
    }

    private function asetOrganisasiLain(): Aset
    {
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN-'.uniqid(), 'Nama' => 'Organisasi Lain', 'Status' => 'Aktif']);
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($lain->Id);

        try {
            $lokasi = Lokasi::create(['Nama' => 'Gudang Lain', 'Status' => 'Aktif']);
            $kategori = KategoriAset::create(['Nama' => 'Lain']);

            return Aset::create([
                'Nama' => 'Aset Lain', 'KategoriAsetId' => $kategori->Id, 'LokasiId' => $lokasi->Id,
                'Status' => 'Aktif', 'Kondisi' => 'Baik', 'KodeQr' => 'QR-LAIN-'.uniqid(), 'Versi' => 1,
            ]);
        } finally {
            $konteks->bersihkan();
        }
    }
}
