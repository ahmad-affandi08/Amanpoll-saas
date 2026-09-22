<?php

declare(strict_types=1);

namespace Tests\Feature\Aset;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Http\Requests\CetakLabelAsetRequest;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Cetak label aset.
 *
 * KodeQr sudah lama tersimpan dan AsetPindaiController sudah bisa membacanya,
 * tetapi tidak pernah dirender -- jadi alur pindai PRD 10 buntu selama ini.
 */
class LabelAsetTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $pengguna;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-LBL', 'Nama' => 'Organisasi Label']);
        $this->pengguna = $this->buatPengguna($this->organisasi);
    }

    private function konteks(): KonteksOrganisasi
    {
        return app(KonteksOrganisasi::class);
    }

    private function buatPengguna(Organisasi $organisasi, bool $denganIzin = true): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($denganIzin) {
            $this->konteks()->tetapkan($organisasi->Id);
            $izin = Izin::firstOrCreate(['Kode' => 'Aset.Lihat'], ['Nama' => 'Lihat Aset', 'Modul' => 'Aset']);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            $this->konteks()->bersihkan();
        }

        return $pengguna;
    }

    /** @param  array<string, mixed>  $atribut */
    private function buatAset(Organisasi $organisasi, array $atribut = []): Aset
    {
        $this->konteks()->tetapkan($organisasi->Id);
        $kategori = KategoriAset::firstOrCreate(['Kode' => 'KAT-LBL'], ['Nama' => 'Mesin']);
        $aset = Aset::create(array_merge([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => 'Aset '.uniqid(),
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
            'KodeQr' => (string) Str::ulid(),
            'Versi' => 1,
        ], $atribut));
        $this->konteks()->bersihkan();

        return $aset;
    }

    /** @param  list<string>  $id */
    private function urlLabel(array $id): string
    {
        return '/aset/label?'.http_build_query(['id' => $id]);
    }

    public function test_lembar_label_memuat_qr_dan_identitas_tiap_aset(): void
    {
        $satu = $this->buatAset($this->organisasi, ['Nama' => 'Kompresor Utama', 'NomorSeri' => 'SN-001']);
        $dua = $this->buatAset($this->organisasi, ['Nama' => 'Genset Cadangan']);

        $this->actingAs($this->pengguna)->get($this->urlLabel([$satu->Id, $dua->Id]))
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->component('Aset/Label')
                ->has('label', 2)
                ->where('label.0.Nama', 'Kompresor Utama')
                ->where('label.0.NomorSeri', 'SN-001')
                ->where('label.0.Kategori', 'Mesin')
                ->etc());
    }

    /** Tanpa QR yang dirender, label tidak ada gunanya ditempel. */
    public function test_setiap_label_membawa_svg_qr_yang_dapat_dipindai(): void
    {
        $aset = $this->buatAset($this->organisasi);

        $props = $this->actingAs($this->pengguna)->get($this->urlLabel([$aset->Id]))
            ->assertOk()
            ->viewData('page')['props'];

        $svg = $props['label'][0]['Svg'];

        $this->assertIsString($svg);
        $this->assertStringContainsString('<svg', $svg);
        // Kode mentah tidak pernah masuk ke markup; QR-nya gambar, bukan teks.
        $this->assertStringNotContainsString((string) $aset->KodeQr, $svg);
    }

    /** Aset lama boleh belum punya KodeQr; barisnya tetap tercetak, bukan hilang diam-diam. */
    public function test_aset_tanpa_kode_qr_tetap_tercetak_tanpa_svg(): void
    {
        $tanpaQr = $this->buatAset($this->organisasi, ['KodeQr' => null, 'Nama' => 'Aset Lama']);

        $this->actingAs($this->pengguna)->get($this->urlLabel([$tanpaQr->Id]))
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->has('label', 1)
                ->where('label.0.Nama', 'Aset Lama')
                ->where('label.0.Svg', null)
                ->etc());
    }

    /** Menebak id aset organisasi lain tidak boleh membocorkan apa pun. */
    public function test_label_aset_organisasi_lain_tidak_ikut_tercetak(): void
    {
        $milikSendiri = $this->buatAset($this->organisasi, ['Nama' => 'Milik Sendiri']);

        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'Organisasi Lain']);
        $milikTetangga = $this->buatAset($lain, ['Nama' => 'Milik Tetangga']);

        $this->actingAs($this->pengguna)->get($this->urlLabel([$milikSendiri->Id, $milikTetangga->Id]))
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->has('label', 1)
                ->where('label.0.Nama', 'Milik Sendiri')
                ->etc());
    }

    public function test_hanya_aset_organisasi_lain_menghasilkan_galat_bukan_lembar_kosong(): void
    {
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'Organisasi Lain']);
        $milikTetangga = $this->buatAset($lain);

        $this->actingAs($this->pengguna)
            ->get($this->urlLabel([$milikTetangga->Id]))
            ->assertStatus(422);
    }

    public function test_pengguna_tanpa_izin_lihat_aset_ditolak(): void
    {
        $aset = $this->buatAset($this->organisasi);
        $tanpaIzin = $this->buatPengguna($this->organisasi, denganIzin: false);

        $this->actingAs($tanpaIzin)->get($this->urlLabel([$aset->Id]))->assertForbidden();
    }

    /**
     * Tombol cetak di daftar aset mematikan dirinya sendiri di atas batas ini.
     * Kalau angkanya diambil dari konstanta frontend sendiri, suatu saat ia akan
     * berbeda dari validasinya dan pengguna ditolak setelah memilih 200 aset.
     */
    public function test_daftar_aset_mengirim_batas_cetak_yang_sama_dengan_validasinya(): void
    {
        $this->actingAs($this->pengguna)->get('/aset')
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->where('maksLabel', CetakLabelAsetRequest::MAKS_LABEL)
                ->etc());
    }

    public function test_permintaan_melebihi_batas_ditolak_validasi(): void
    {
        $id = array_map(
            fn (int $ke): string => (string) Str::ulid(),
            range(1, CetakLabelAsetRequest::MAKS_LABEL + 1),
        );

        $this->actingAs($this->pengguna)
            ->get($this->urlLabel($id))
            ->assertSessionHasErrors('id');
    }
}
