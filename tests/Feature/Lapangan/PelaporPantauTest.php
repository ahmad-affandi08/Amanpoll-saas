<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Core\Izin\ScopeLingkup;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Application\Actions\UbahStatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia;

/**
 * Pantau laporan rekan (TASK 39.10 butir 2, PRD 8.20): hanya garis waktu status keluhan
 * orang lain dalam lingkup unit/ruangan pelapor, tanpa nama, keterangan, atau foto.
 */
final class PelaporPantauTest extends KasusPelapor
{
    private Lokasi $lantai;

    private Lokasi $lantaiLain;

    private KategoriKeluhan $kategori;

    private Aset $printer;

    private Pengguna $pelapor;

    private Pengguna $rekan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lantai = $this->lokasi('Lt. 12', $this->lokasi('Menara A'));
        $this->lantaiLain = $this->lokasi('Lt. 11');
        $this->kategori = $this->kategori('IT & Printer');
        $this->printer = $this->aset('Printer Lt. 12', $this->lantai);
        $this->pelapor = $this->pelaporDi($this->lantai);
        $this->rekan = $this->pelaporDi($this->lantai);
    }

    public function test_pelapor_memantau_laporan_rekan_hanya_sebagai_garis_waktu_status(): void
    {
        $keluhan = $this->keluhan($this->rekan, $this->kategori, $this->lantai, [
            'AsetId' => $this->printer->Id,
            'Judul' => 'Printer kertas macet',
            'Deskripsi' => 'Deskripsi pribadi milik rekan.',
        ], [StatusKeluhan::Ditinjau, StatusKeluhan::Diterima]);
        $keluhan = $this->dalamOrganisasi(fn () => app(UbahStatusKeluhan::class)
            ->jalankan($keluhan, StatusKeluhan::Diproses, 'Catatan internal koordinator.', $keluhan->Versi, $this->rekan->Id));
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $this->tugaskan($keluhan, $teknisi);

        $respons = $this->actingAs($this->pelapor)->get("/lapangan/pelapor/pantau/{$keluhan->Id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Lapangan/Pelapor/Pantau')
                ->where('laporan', [
                    'Nomor' => $keluhan->Nomor,
                    'Judul' => 'Printer kertas macet',
                    'Status' => 'Diproses',
                    'Aset' => ['KodeAset' => $this->printer->KodeAset, 'Nama' => 'Printer Lt. 12', 'Kategori' => 'Peralatan Gedung'],
                    'LokasiLabel' => 'Menara A · Lt. 12',
                ])
                ->has('riwayat', 4)
                ->where('riwayat', fn ($riwayat) => collect($riwayat)->every(fn ($satu) => array_keys($satu) === ['Status', 'Pada'] && $satu['Pada'] !== null)
                    && collect($riwayat)->pluck('Status')->all() === ['Baru', 'Ditinjau', 'Diterima', 'Diproses']));

        // Bukan hanya bentuknya: isi yang dilarang tidak ada di props halaman sama sekali.
        $propsHalaman = $respons->viewData('page')['props'];
        $this->assertSame(['laporan', 'riwayat'], array_values(array_intersect(array_keys($propsHalaman), ['laporan', 'riwayat', 'foto', 'teknisi', 'komentar', 'lampiran'])));
        $isi = json_encode(['laporan' => $propsHalaman['laporan'], 'riwayat' => $propsHalaman['riwayat']], JSON_UNESCAPED_UNICODE);
        foreach ([$this->rekan->Nama, $teknisi->Nama, 'Catatan internal koordinator.', 'Keluhan dibuat.', 'Deskripsi pribadi milik rekan.', 'PelaporId', 'Teknisi', 'Catatan', 'Deskripsi', 'Foto', 'BerkasId', $keluhan->Id] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, (string) $isi, "Props pantau memuat '{$terlarang}'.");
        }
        $this->assertStringNotContainsString($this->rekan->Nama, $respons->getContent());
        $this->assertStringNotContainsString($teknisi->Nama, $respons->getContent());
    }

    public function test_keluhan_milik_sendiri_diarahkan_ke_lacak_laporan(): void
    {
        $keluhan = $this->keluhan($this->pelapor, $this->kategori, $this->lantai, ['AsetId' => $this->printer->Id]);

        $this->actingAs($this->pelapor)->get("/lapangan/pelapor/pantau/{$keluhan->Id}")
            ->assertRedirect("/lapangan/pelapor/laporan/{$keluhan->Id}");
    }

    public function test_keluhan_di_luar_lingkup_atau_organisasi_lain_tidak_bisa_dipantau(): void
    {
        $rekanLantaiLain = $this->pelaporDi($this->lantaiLain);
        $luarLingkup = $this->keluhan($rekanLantaiLain, $this->kategori, $this->lantaiLain);
        $organisasiLain = $this->keluhanOrganisasiLain();
        $this->actingAs($this->pelapor);

        $this->get("/lapangan/pelapor/pantau/{$luarLingkup->Id}")->assertNotFound();
        $this->get("/lapangan/pelapor/pantau/{$organisasiLain->Id}")->assertNotFound();
    }

    public function test_policy_pantau_menolak_keluhan_di_luar_lingkup_walau_dimuat_tanpa_scope(): void
    {
        $dalam = $this->keluhan($this->rekan, $this->kategori, $this->lantai);
        $luarLingkup = $this->keluhan($this->pelaporDi($this->lantaiLain), $this->kategori, $this->lantaiLain);
        $organisasiLain = $this->keluhanOrganisasiLain();
        $this->actingAs($this->pelapor);

        $this->dalamOrganisasi(function () use ($dalam, $luarLingkup, $organisasiLain): void {
            $mentah = fn (Keluhan $keluhan): Keluhan => Keluhan::query()->withoutGlobalScopes()->findOrFail($keluhan->Id);
            $gerbang = Gate::forUser($this->pelapor);

            $this->assertTrue($gerbang->allows('pantau', $mentah($dalam)));
            $this->assertFalse($gerbang->allows('pantau', $mentah($luarLingkup)));
            $this->assertFalse($gerbang->allows('pantau', $mentah($organisasiLain)));
            // Tanpa ScopeLingkup pun keluhan lantai lain tetap bisa dimuat: policy-lah penjaganya.
            $this->assertNotNull(Keluhan::query()->withoutGlobalScope(ScopeLingkup::class)->find($luarLingkup->Id));
        });
    }

    public function test_layar_pantau_selalu_melewati_policy_pantau(): void
    {
        $keluhan = $this->keluhan($this->rekan, $this->kategori, $this->lantai);
        // Keluhan di lingkup lolos pengikatan rute; penolakan policy tetap harus berlaku.
        Gate::before(fn ($pengguna, string $kemampuan) => $kemampuan === 'pantau' ? false : null);

        $this->actingAs($this->pelapor)->get("/lapangan/pelapor/pantau/{$keluhan->Id}")->assertForbidden();
    }

    public function test_pelapor_tanpa_batas_lingkup_memantau_seluruh_organisasinya_saja(): void
    {
        $pelaporTanpaBatas = $this->penggunaDenganPeran(['PELAPOR']);
        $lantaiLain = $this->keluhan($this->rekan, $this->kategori, $this->lantaiLain);
        $organisasiLain = $this->keluhanOrganisasiLain();
        $this->actingAs($pelaporTanpaBatas);

        $this->get("/lapangan/pelapor/pantau/{$lantaiLain->Id}")->assertOk();
        $this->dalamOrganisasi(function () use ($pelaporTanpaBatas, $organisasiLain): void {
            $mentah = Keluhan::query()->withoutGlobalScopes()->findOrFail($organisasiLain->Id);
            $this->assertFalse(Gate::forUser($pelaporTanpaBatas)->allows('pantau', $mentah));
        });
    }

    private function keluhanOrganisasiLain(): Keluhan
    {
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN-'.uniqid(), 'Nama' => 'Organisasi Lain', 'Status' => 'Aktif']);
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($lain->Id);

        try {
            $lokasi = Lokasi::create(['Nama' => 'Gudang Lain', 'Status' => 'Aktif']);
            $kategori = KategoriKeluhan::create(['Nama' => 'Lain', 'PrioritasBawaan' => 'Normal', 'AsetWajib' => false, 'Aktif' => true]);

            return Keluhan::create([
                'Nomor' => 'KLH-LAIN-'.uniqid(), 'KategoriKeluhanId' => $kategori->Id, 'LokasiId' => $lokasi->Id,
                'Judul' => 'Keluhan lain', 'Deskripsi' => 'Lain.', 'PelaporId' => null,
                'DilaporkanPada' => now(),
            ]);
        } finally {
            $konteks->bersihkan();
        }
    }
}
