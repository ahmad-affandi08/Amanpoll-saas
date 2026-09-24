<?php

declare(strict_types=1);

namespace Tests\Feature\Aset;

use App\Core\Izin\ScopeLingkup;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Unit pengelola pada aset (PRD 8.21, TASK 40.02): isian formulir, penyaring,
 * ekspor, detail, dan ubah massal. Organisasi yang tidak menandai satu unit pun
 * sebagai Mengelola Aset tidak melihat perubahan apa pun.
 */
class UnitPengelolaAsetTest extends TestCase
{
    use RefreshDatabase;

    private const IZIN_PENUH = ['Aset.Lihat', 'Aset.Buat', 'Aset.Ubah'];

    private Organisasi $organisasi;

    private KategoriAset $kategori;

    private UnitOrganisasi $it;

    private UnitOrganisasi $ipsrs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-UPA', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        $this->dalamOrganisasi(function (): void {
            $this->kategori = KategoriAset::create(['Kode' => 'KAT-UPA', 'Nama' => 'Peralatan']);
            $this->it = UnitOrganisasi::create(['Kode' => 'IT', 'Nama' => 'Instalasi IT', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
            $this->ipsrs = UnitOrganisasi::create(['Kode' => 'IPS', 'Nama' => 'IPSRS', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
        });
    }

    public function test_aset_baru_menyimpan_unit_pengelola_yang_sah(): void
    {
        $admin = $this->buatPengguna(self::IZIN_PENUH);

        $this->actingAs($admin)->post('/aset', $this->isianAset(['Nama' => 'Printer ICU', 'UnitPengelolaId' => $this->it->Id]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame($this->it->Id, $this->dalamOrganisasi(fn () => Aset::query()->where('Nama', 'Printer ICU')->value('UnitPengelolaId')));
    }

    public function test_aset_menolak_unit_yang_bukan_unit_pengelola_atau_nonaktif(): void
    {
        $admin = $this->buatPengguna(self::IZIN_PENUH);
        [$bukanPengelola, $nonaktif] = $this->dalamOrganisasi(fn (): array => [
            UnitOrganisasi::create(['Kode' => 'ICU', 'Nama' => 'ICU', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']),
            UnitOrganisasi::create(['Kode' => 'ME', 'Nama' => 'ME Lama', 'Jenis' => 'Instalasi', 'Status' => 'Nonaktif', 'MengelolaAset' => true]),
        ]);

        $this->actingAs($admin)->post('/aset', $this->isianAset(['UnitPengelolaId' => $bukanPengelola->Id]))
            ->assertSessionHasErrors(['UnitPengelolaId' => 'Unit yang dipilih belum ditandai Mengelola Aset di halaman Unit Organisasi.']);
        $this->actingAs($admin)->post('/aset', $this->isianAset(['UnitPengelolaId' => $nonaktif->Id]))
            ->assertSessionHasErrors(['UnitPengelolaId' => 'Unit pengelola yang dipilih sedang nonaktif.']);

        $this->assertSame(0, $this->dalamOrganisasi(fn () => Aset::query()->count()));
    }

    /** Nilai tersimpan tetap sah walau unitnya kini nonaktif; formulir yang tidak mengirim isian tidak mengosongkannya. */
    public function test_ubah_aset_mempertahankan_unit_pengelola_tersimpan(): void
    {
        $admin = $this->buatPengguna(self::IZIN_PENUH);
        $aset = $this->buatAset('Monitor', $this->ipsrs);
        $this->dalamOrganisasi(fn () => $this->ipsrs->update(['Status' => 'Nonaktif']));

        $this->actingAs($admin)->put('/aset/'.$aset->Id, $this->isianAset(['Nama' => 'Monitor Baru', 'UnitPengelolaId' => $this->ipsrs->Id]))
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->put('/aset/'.$aset->Id, $this->isianAset(['Nama' => 'Monitor Lagi']))
            ->assertSessionHasNoErrors();

        $segar = $this->dalamOrganisasi(fn () => Aset::query()->findOrFail($aset->Id));
        $this->assertSame('Monitor Lagi', $segar->Nama);
        $this->assertSame($this->ipsrs->Id, $segar->UnitPengelolaId);
    }

    public function test_daftar_aset_menampilkan_dan_menyaring_unit_pengelola_termasuk_belum_ada(): void
    {
        $admin = $this->buatPengguna(self::IZIN_PENUH);
        $this->buatAset('Printer', $this->it);
        $this->buatAset('Ventilator', $this->ipsrs);
        $this->buatAset('Kursi', null);

        $this->actingAs($admin)->get('/aset?unitPengelolaId='.$this->it->Id)
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Aset/Index')
                ->where('unitPengelolaDipakai', true)
                ->has('penyaringUnitPengelola', 2)
                ->has('pilihanUnitPengelola', 2)
                ->has('aset.data', 1)
                ->where('aset.data.0.Nama', 'Printer')
                ->where('aset.data.0.NamaUnitPengelola', 'Instalasi IT'));

        $this->actingAs($admin)->get('/aset?unitPengelolaId=tanpa')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->has('aset.data', 1)
                ->where('aset.data.0.Nama', 'Kursi'));
    }

    public function test_ekspor_aset_memuat_kolom_unit_pengelola(): void
    {
        $admin = $this->buatPengguna(self::IZIN_PENUH);
        $this->buatAset('Printer', $this->it);

        $isi = $this->actingAs($admin)->get('/aset/ekspor')->streamedContent();

        $this->assertStringContainsString('Unit Pengelola', $isi);
        $this->assertStringContainsString('Instalasi IT', $isi);
    }

    public function test_detail_aset_mengirim_unit_pengelola_dan_pilihannya(): void
    {
        $admin = $this->buatPengguna(self::IZIN_PENUH);
        $aset = $this->buatAset('Printer', $this->it);

        $this->actingAs($admin)->get('/aset/'.$aset->Id)
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('aset.UnitPengelolaId', $this->it->Id)
                ->where('aset.NamaUnitPengelola', 'Instalasi IT')
                ->where('unitPengelolaDipakai', true)
                ->has('pilihanUnitPengelola', 2));
    }

    public function test_ubah_massal_menetapkan_unit_pengelola_menaikkan_versi_dan_diaudit(): void
    {
        $admin = $this->buatPengguna(self::IZIN_PENUH);
        $printer = $this->buatAset('Printer', null);
        $laptop = $this->buatAset('Laptop', $this->ipsrs);
        $sudahIt = $this->buatAset('Server', $this->it);

        $this->actingAs($admin)->put('/aset/unit-pengelola', [
            'AsetId' => [$printer->Id, $laptop->Id, $sudahIt->Id],
            'UnitPengelolaId' => $this->it->Id,
        ])->assertRedirect()->assertSessionHas('sukses', 'Unit pengelola 2 aset berhasil diperbarui.');

        $this->dalamOrganisasi(function () use ($printer, $laptop, $sudahIt): void {
            foreach ([$printer, $laptop] as $aset) {
                $segar = Aset::query()->findOrFail($aset->Id);
                $this->assertSame($this->it->Id, $segar->UnitPengelolaId);
                $this->assertSame($aset->Versi + 1, $segar->Versi);
            }
            $this->assertSame($sudahIt->Versi, Aset::query()->findOrFail($sudahIt->Id)->Versi, 'Aset yang sudah sama tidak disentuh.');

            $audit = CatatanAudit::query()->where('Aksi', 'Aset.UnitPengelolaDiubahMassal')->sole();
            $this->assertSame([$printer->Id => null, $laptop->Id => $this->ipsrs->Id], $audit->DataSebelum);
            $this->assertSame($this->it->Id, $audit->DataSesudah['UnitPengelolaId'] ?? null);
        });
    }

    public function test_ubah_massal_boleh_mengosongkan_unit_pengelola(): void
    {
        $admin = $this->buatPengguna(self::IZIN_PENUH);
        $aset = $this->buatAset('Printer', $this->it);

        $this->actingAs($admin)->put('/aset/unit-pengelola', ['AsetId' => [$aset->Id], 'UnitPengelolaId' => null])
            ->assertSessionHasNoErrors();

        $this->assertNull($this->dalamOrganisasi(fn () => Aset::query()->whereKey($aset->Id)->value('UnitPengelolaId')));
    }

    public function test_ubah_massal_menolak_unit_yang_bukan_unit_pengelola(): void
    {
        $admin = $this->buatPengguna(self::IZIN_PENUH);
        $aset = $this->buatAset('Printer', $this->it);
        $icu = $this->dalamOrganisasi(fn () => UnitOrganisasi::create(['Kode' => 'ICU', 'Nama' => 'ICU', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']));

        $this->actingAs($admin)->put('/aset/unit-pengelola', ['AsetId' => [$aset->Id], 'UnitPengelolaId' => $icu->Id])
            ->assertSessionHasErrors(['UnitPengelolaId' => 'Unit yang dipilih belum ditandai Mengelola Aset di halaman Unit Organisasi.']);

        $this->assertSame($this->it->Id, $this->dalamOrganisasi(fn () => Aset::query()->whereKey($aset->Id)->value('UnitPengelolaId')));
    }

    /** Satu Id di luar lingkup menggagalkan seluruh permintaan, bukan diproses sebagian diam-diam. */
    public function test_ubah_massal_menolak_aset_di_luar_lingkup_pengguna(): void
    {
        $stafIt = $this->buatPengguna(self::IZIN_PENUH, $this->it);
        $milikIt = $this->buatAset('Printer', null, ['UnitOrganisasiId' => $this->it->Id]);
        $milikIpsrs = $this->buatAset('Ventilator', $this->ipsrs);

        $this->actingAs($stafIt)->put('/aset/unit-pengelola', [
            'AsetId' => [$milikIt->Id, $milikIpsrs->Id],
            'UnitPengelolaId' => $this->it->Id,
        ])->assertSessionHasErrors(['AsetId' => 'Sebagian aset yang dipilih tidak ditemukan atau di luar lingkup akses Anda.']);

        // Dibaca lepas dari ScopeLingkup: staf IT masih masuk dan memang tidak melihat aset IPSRS.
        $this->dalamOrganisasi(function () use ($milikIt, $milikIpsrs): void {
            $kueri = fn () => Aset::query()->withoutGlobalScope(ScopeLingkup::class);
            $this->assertNull($kueri()->whereKey($milikIt->Id)->value('UnitPengelolaId'));
            $this->assertSame($this->ipsrs->Id, $kueri()->whereKey($milikIpsrs->Id)->value('UnitPengelolaId'));
            $this->assertSame(0, CatatanAudit::query()->where('Aksi', 'Aset.UnitPengelolaDiubahMassal')->count());
        });
    }

    public function test_ubah_massal_butuh_izin_ubah_aset(): void
    {
        $pembaca = $this->buatPengguna(['Aset.Lihat']);
        $aset = $this->buatAset('Printer', null);

        $this->actingAs($pembaca)->put('/aset/unit-pengelola', ['AsetId' => [$aset->Id], 'UnitPengelolaId' => $this->it->Id])
            ->assertForbidden();

        $this->assertNull($this->dalamOrganisasi(fn () => Aset::query()->whereKey($aset->Id)->value('UnitPengelolaId')));
    }

    public function test_organisasi_tanpa_unit_pengelola_tidak_melihat_isian_kolom_maupun_kolom_ekspor(): void
    {
        $this->dalamOrganisasi(function (): void {
            $this->it->update(['MengelolaAset' => false]);
            $this->ipsrs->update(['MengelolaAset' => false]);
        });
        $admin = $this->buatPengguna(self::IZIN_PENUH);
        $aset = $this->buatAset('Printer', null);

        $this->actingAs($admin)->get('/aset')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('unitPengelolaDipakai', false)
                ->where('pilihanUnitPengelola', [])
                ->where('penyaringUnitPengelola', []));
        $this->actingAs($admin)->get('/aset/'.$aset->Id)
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('unitPengelolaDipakai', false));
        $isi = $this->actingAs($admin)->get('/aset/ekspor')->streamedContent();

        $this->assertStringNotContainsString('Unit Pengelola', $isi);
    }

    /**
     * @param  array<string, mixed>  $lain
     * @return array<string, mixed>
     */
    private function isianAset(array $lain = []): array
    {
        return [
            'KategoriAsetId' => $this->kategori->Id,
            'Nama' => 'Aset Uji',
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
            'TingkatKritis' => 'Normal',
            ...$lain,
        ];
    }

    /** @param  array<string, mixed>  $lain */
    private function buatAset(string $nama, ?UnitOrganisasi $pengelola, array $lain = []): Aset
    {
        return $this->dalamOrganisasi(fn (): Aset => Aset::create([
            'KategoriAsetId' => $this->kategori->Id,
            'UnitPengelolaId' => $pengelola?->Id,
            'Nama' => $nama,
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
            ...$lain,
        ])->refresh());
    }

    /** @param  list<string>  $kodeIzin */
    private function buatPengguna(array $kodeIzin, ?UnitOrganisasi $lingkupUnit = null): Pengguna
    {
        return $this->dalamOrganisasi(function () use ($kodeIzin, $lingkupUnit): Pengguna {
            $pengguna = Pengguna::create([
                'OrganisasiId' => $this->organisasi->Id,
                'Nama' => 'Pengguna '.uniqid(),
                'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
                'KataSandi' => 'rahasia',
                'Status' => 'Aktif',
            ]);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);

            foreach ($kodeIzin as $kode) {
                $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            }

            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id, 'UnitOrganisasiId' => $lingkupUnit?->Id]);

            return $pengguna;
        });
    }

    /**
     * @template T
     *
     * @param  callable(): T  $aksi
     * @return T
     */
    private function dalamOrganisasi(callable $aksi): mixed
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($this->organisasi->Id);

        try {
            return $aksi();
        } finally {
            $konteks->bersihkan();
        }
    }
}
