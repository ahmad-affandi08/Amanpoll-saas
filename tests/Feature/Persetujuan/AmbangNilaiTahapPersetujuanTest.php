<?php

declare(strict_types=1);

namespace Tests\Feature\Persetujuan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persetujuan\Application\Actions\AjukanPermintaanPersetujuan;
use App\Domain\Persetujuan\Application\Actions\SetujuiPermintaanPersetujuan;
use App\Domain\Persetujuan\Domain\Enums\StatusPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ambang nilai tahap persetujuan: tahap berkondisi `NilaiMinimum` hanya dilalui
 * permintaan yang nilainya mencapai ambang. Tahap pertama selalu berlaku, dan nilai
 * yang tidak diketahui atau dikirim sendiri oleh peminta tidak pernah melewati tahap.
 */
final class AmbangNilaiTahapPersetujuanTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $peminta;

    private Pengguna $kepalaUnit;

    private Pengguna $direktur;

    private Pengguna $keuangan;

    private Lokasi $lokasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $this->peminta = $this->buatPengguna(['Pengaturan.Kelola', 'Persetujuan.Kelola']);
        $this->kepalaUnit = $this->buatPengguna();
        $this->direktur = $this->buatPengguna();
        $this->keuangan = $this->buatPengguna();

        $this->konteks()->tetapkan($this->organisasi->Id);
        $this->lokasi = Lokasi::create(['Kode' => 'LOK-1', 'Nama' => 'Lokasi Uji']);
    }

    public function test_permintaan_di_bawah_ambang_melewati_tahap_berambang(): void
    {
        $alur = $this->buatAlurTigaTahap();
        $permintaan = $this->ajukan($alur, ['Nilai' => 4_000_000]);

        $this->setujui($permintaan, $this->kepalaUnit);

        $this->assertSame(3, $permintaan->refresh()->TahapSaatIni, 'Tahap direktur (ambang 10 juta) dilewati.');
        $this->assertSame(StatusPermintaanPersetujuan::Menunggu->value, $permintaan->Status);
    }

    public function test_permintaan_mencapai_ambang_melalui_tahap_berambang(): void
    {
        $alur = $this->buatAlurTigaTahap();
        $permintaan = $this->ajukan($alur, ['Nilai' => 10_000_000]);

        $this->setujui($permintaan, $this->kepalaUnit);

        $this->assertSame(2, $permintaan->refresh()->TahapSaatIni);
    }

    public function test_tahap_terakhir_berambang_yang_tidak_tercapai_menyelesaikan_permintaan(): void
    {
        $alur = $this->buatAlur([
            ['Nama' => 'Kepala Unit', 'PenggunaId' => $this->kepalaUnit->Id],
            ['Nama' => 'Direktur', 'PenggunaId' => $this->direktur->Id, 'Kondisi' => ['NilaiMinimum' => 10_000_000]],
        ]);
        $permintaan = $this->ajukan($alur, ['Nilai' => 2_500_000]);

        $this->setujui($permintaan, $this->kepalaUnit);

        $this->assertSame(StatusPermintaanPersetujuan::Disetujui->value, $permintaan->refresh()->Status);
    }

    public function test_nilai_tidak_diketahui_tidak_melewati_tahap_berambang(): void
    {
        $alur = $this->buatAlurTigaTahap();
        $permintaan = $this->ajukan($alur, null);

        $this->setujui($permintaan, $this->kepalaUnit);

        $this->assertSame(2, $permintaan->refresh()->TahapSaatIni);
    }

    public function test_nilai_kiriman_peminta_lewat_endpoint_umum_diabaikan(): void
    {
        $alur = $this->buatAlurTigaTahap();
        $this->konteks()->bersihkan();

        $this->actingAs($this->peminta)
            ->post(route('persetujuan.permintaan.store'), [
                'AlurPersetujuanId' => $alur->Id,
                'EntitasId' => $this->lokasi->Id,
                'DataTambahan' => ['Nilai' => 1, 'Catatan' => 'Pembelian kecil'],
            ])
            ->assertSessionHasNoErrors();

        $this->konteks()->tetapkan($this->organisasi->Id);
        $permintaan = PermintaanPersetujuan::query()->where('EntitasId', $this->lokasi->Id)->firstOrFail();
        $this->assertSame(['Catatan' => 'Pembelian kecil'], $permintaan->DataTambahan);

        $this->setujui($permintaan, $this->kepalaUnit);

        $this->assertSame(2, $permintaan->refresh()->TahapSaatIni, 'Peminta tidak boleh melewati tahap direktur dengan nilai karangan.');
    }

    public function test_alur_dengan_ambang_di_tahap_pertama_tidak_dapat_diaktifkan(): void
    {
        $alur = $this->buatAlur([
            ['Nama' => 'Direktur', 'PenggunaId' => $this->direktur->Id, 'Kondisi' => ['NilaiMinimum' => 10_000_000]],
            ['Nama' => 'Keuangan', 'PenggunaId' => $this->keuangan->Id],
        ], aktif: false);
        $this->konteks()->bersihkan();

        $this->actingAs($this->peminta)
            ->post(route('persetujuan.alur.aktifkan', $alur->Id))
            ->assertStatus(422);

        $this->konteks()->tetapkan($this->organisasi->Id);
        $this->assertFalse($alur->refresh()->Aktif);
    }

    public function test_simpan_tahap_menyimpan_ambang_dan_ambang_kosong_berarti_tanpa_syarat(): void
    {
        $alur = $this->buatAlur([['Nama' => 'Kepala Unit', 'PenggunaId' => $this->kepalaUnit->Id]], aktif: false);
        $this->konteks()->bersihkan();

        $dasar = ['Nama' => 'Direktur', 'JenisPenyetuju' => 'Pengguna', 'PenggunaId' => $this->direktur->Id, 'JumlahMinimumPenyetuju' => 1];

        $this->actingAs($this->peminta)
            ->post(route('persetujuan.tahap.store', $alur->Id), [...$dasar, 'Urutan' => 2, 'Kondisi' => ['NilaiMinimum' => '25000000']])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->peminta)
            ->post(route('persetujuan.tahap.store', $alur->Id), [...$dasar, 'Urutan' => 3, 'Kondisi' => ['NilaiMinimum' => '']])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->peminta)
            ->post(route('persetujuan.tahap.store', $alur->Id), [...$dasar, 'Urutan' => 4, 'Kondisi' => ['NilaiMinimum' => -5]])
            ->assertSessionHasErrors('Kondisi.NilaiMinimum');

        $this->konteks()->tetapkan($this->organisasi->Id);
        $tahap = TahapPersetujuan::query()->where('AlurPersetujuanId', $alur->Id)->orderBy('Urutan')->get();
        $this->assertCount(3, $tahap);
        $this->assertEquals(25_000_000, $tahap[1]->Kondisi['NilaiMinimum'] ?? null);
        $this->assertNull($tahap[2]->Kondisi);
    }

    private function buatAlurTigaTahap(): AlurPersetujuan
    {
        return $this->buatAlur([
            ['Nama' => 'Kepala Unit', 'PenggunaId' => $this->kepalaUnit->Id],
            ['Nama' => 'Direktur', 'PenggunaId' => $this->direktur->Id, 'Kondisi' => ['NilaiMinimum' => 10_000_000]],
            ['Nama' => 'Keuangan', 'PenggunaId' => $this->keuangan->Id],
        ]);
    }

    /**
     * @param  list<array{Nama: string, PenggunaId: string, Kondisi?: array<string, mixed>}>  $daftarTahap
     */
    private function buatAlur(array $daftarTahap, bool $aktif = true): AlurPersetujuan
    {
        $alur = AlurPersetujuan::create(['Kode' => 'ALUR-'.uniqid(), 'Nama' => 'Alur Uji', 'JenisEntitas' => 'Lokasi']);

        foreach ($daftarTahap as $indeks => $tahap) {
            TahapPersetujuan::create([
                ...$tahap,
                'AlurPersetujuanId' => $alur->Id,
                'Urutan' => $indeks + 1,
                'JenisPenyetuju' => 'Pengguna',
                'JumlahMinimumPenyetuju' => 1,
            ]);
        }

        $alur->Aktif = $aktif;
        $alur->save();

        return $alur;
    }

    /**
     * @param  array<string, mixed>|null  $dataTambahan
     */
    private function ajukan(AlurPersetujuan $alur, ?array $dataTambahan): PermintaanPersetujuan
    {
        return app(AjukanPermintaanPersetujuan::class)->jalankan($alur, $this->lokasi->Id, $dataTambahan, $this->peminta->Id);
    }

    private function setujui(PermintaanPersetujuan $permintaan, Pengguna $penyetuju): void
    {
        app(SetujuiPermintaanPersetujuan::class)->jalankan($permintaan, $penyetuju, null);
    }

    /**
     * @param  list<string>  $kodeIzin
     */
    private function buatPengguna(array $kodeIzin = []): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== []) {
            $this->konteks()->tetapkan($this->organisasi->Id);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            foreach ($kodeIzin as $kode) {
                $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            }
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            $this->konteks()->bersihkan();
        }

        return $pengguna;
    }

    private function konteks(): KonteksOrganisasi
    {
        return app(KonteksOrganisasi::class);
    }
}
