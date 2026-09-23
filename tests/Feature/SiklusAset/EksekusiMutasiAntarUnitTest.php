<?php

declare(strict_types=1);

namespace Tests\Feature\SiklusAset;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Domain\SiklusAset\Domain\Enums\JenisPermintaanMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mutasi antar unit yang hanya menyebut unit tujuan.
 *
 * BuatPermintaanMutasiAset sengaja menerima "lokasi tujuan ATAU unit tujuan".
 * Permintaan yang hanya memindahkan kepemilikan unit tidak menyatakan apa pun
 * tentang tempat fisik alatnya, jadi eksekusinya tidak boleh menghapus lokasi
 * aset yang sudah diketahui.
 */
final class EksekusiMutasiAntarUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_mutasi_antar_unit_tanpa_lokasi_tujuan_memindahkan_unit_tanpa_menghapus_lokasi_aset(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A', 'Status' => 'Aktif']);
        $peminta = $this->buatPengguna($organisasi, ['Aset.Lihat', 'Aset.Ubah']);
        $penyetuju = $this->buatPengguna($organisasi, []);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $unitAsal = UnitOrganisasi::create(['Kode' => 'UNIT-ASAL', 'Nama' => 'Instalasi Bedah', 'Status' => 'Aktif']);
        $unitTujuan = UnitOrganisasi::create(['Kode' => 'UNIT-TUJUAN', 'Nama' => 'Instalasi Rawat Inap', 'Status' => 'Aktif']);
        $lokasi = Lokasi::create(['Kode' => 'LOK-OK', 'Nama' => 'Kamar Operasi 1']);
        NomorDokumen::create([
            'JenisDokumen' => 'PermintaanMutasiAset',
            'Awalan' => 'MUT',
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'NomorTerakhir' => 0,
            'ResetPeriode' => 'Tahunan',
            'PeriodeAktif' => '',
        ]);
        $alur = AlurPersetujuan::create(['Kode' => 'ALUR-MUT', 'Nama' => 'Alur Mutasi', 'JenisEntitas' => 'PermintaanMutasiAset', 'Aktif' => false]);
        TahapPersetujuan::create([
            'AlurPersetujuanId' => $alur->Id,
            'Urutan' => 1,
            'Nama' => 'Tahap 1',
            'JenisPenyetuju' => 'Pengguna',
            'PenggunaId' => $penyetuju->Id,
            'JumlahMinimumPenyetuju' => 1,
        ]);
        $alur->update(['Aktif' => true]);
        $kategori = KategoriAset::create(['Kode' => 'KAT-1', 'Nama' => 'Mesin Anestesi']);
        $aset = Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'UnitOrganisasiId' => $unitAsal->Id,
            'LokasiId' => $lokasi->Id,
            'KodeAset' => 'AST-ANS-001',
            'Nama' => 'Mesin Anestesi',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Tinggi->value,
        ]);
        $konteks->bersihkan();

        $this->actingAs($peminta)->post(route('mutasiAset.store'), [
            'JenisMutasi' => JenisPermintaanMutasiAset::AntarUnit->value,
            'UnitAsalId' => $unitAsal->Id,
            'UnitTujuanId' => $unitTujuan->Id,
            'Alasan' => 'Alat dialihkan ke rawat inap.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $konteks->tetapkan($organisasi->Id);
        $permintaan = PermintaanMutasiAset::query()->firstOrFail();
        $this->assertNull($permintaan->LokasiTujuanId);

        $this->actingAs($peminta)->post(route('mutasiAset.detail.store', $permintaan->Id), ['AsetId' => $aset->Id])->assertRedirect();
        $this->actingAs($peminta)->post(route('mutasiAset.submit', $permintaan->Id))->assertRedirect();

        $konteks->tetapkan($organisasi->Id);
        $persetujuan = PermintaanPersetujuan::query()->where('EntitasId', $permintaan->Id)->firstOrFail();
        $this->actingAs($penyetuju)->post(route('persetujuan.permintaan.setujui', $persetujuan->Id))->assertRedirect();
        $this->actingAs($peminta)->post(route('mutasiAset.eksekusi', $permintaan->Id))->assertRedirect()->assertSessionHasNoErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertSame(StatusPermintaanMutasiAset::Selesai->value, $permintaan->refresh()->Status);
        $aset->refresh();
        $this->assertSame($unitTujuan->Id, $aset->UnitOrganisasiId, 'Unit aset seharusnya berpindah ke unit tujuan.');
        $this->assertSame(
            $lokasi->Id,
            $aset->LokasiId,
            'Mutasi yang tidak menyebut lokasi tujuan tidak boleh mengosongkan lokasi aset.',
        );
    }

    /**
     * @param  list<string>  $kodeIzin
     */
    private function buatPengguna(Organisasi $organisasi, array $kodeIzin): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== []) {
            app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            foreach ($kodeIzin as $kode) {
                $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            }
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            app(KonteksOrganisasi::class)->bersihkan();
        }

        return $pengguna;
    }
}
