<?php

declare(strict_types=1);

namespace Tests\Feature\Persetujuan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persetujuan\Domain\Enums\StatusPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\KeputusanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aturan persetujuan: penugasan peran yang sudah berakhir atau belum mulai
 * berlaku tidak menjadikan seseorang penyetuju.
 *
 * PenggunaPeran membawa BerlakuMulai/BerlakuSampai supaya penugasan sementara
 * (pelaksana tugas, cuti) berhenti dengan sendirinya. Pemeriksa izin sudah
 * menghormatinya; mesin persetujuan harus sama, baik untuk tahap berbasis peran
 * maupun berbasis unit.
 */
final class MasaBerlakuPenyetujuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-10 03:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_penugasan_peran_kedaluwarsa_atau_belum_berlaku_tidak_berhak_menyetujui_tahap_berbasis_peran(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $kedaluwarsa = $this->buatPengguna($organisasi);
        $belumBerlaku = $this->buatPengguna($organisasi);
        $aktif = $this->buatPengguna($organisasi);

        $this->konteks()->tetapkan($organisasi->Id);
        $lokasi = Lokasi::create(['Kode' => 'LOK-1', 'Nama' => 'Lokasi Uji']);
        $peranManajer = Peran::create(['Kode' => 'MANAJER', 'Nama' => 'Manajer']);
        PenggunaPeran::create(['PenggunaId' => $kedaluwarsa->Id, 'PeranId' => $peranManajer->Id, 'BerlakuSampai' => CarbonImmutable::now()->subDay()]);
        PenggunaPeran::create(['PenggunaId' => $belumBerlaku->Id, 'PeranId' => $peranManajer->Id, 'BerlakuMulai' => CarbonImmutable::now()->addDay()]);
        PenggunaPeran::create([
            'PenggunaId' => $aktif->Id,
            'PeranId' => $peranManajer->Id,
            'BerlakuMulai' => CarbonImmutable::now()->subDay(),
            'BerlakuSampai' => CarbonImmutable::now()->addDay(),
        ]);
        $alur = $this->buatAlurSatuTahap(['JenisPenyetuju' => 'Peran', 'PeranId' => $peranManajer->Id]);
        $this->konteks()->bersihkan();

        $permintaan = $this->ajukan($organisasi, $peminta, $alur, $lokasi);

        $this->actingAs($kedaluwarsa)->post(route('persetujuan.permintaan.setujui', $permintaan->Id))->assertForbidden();
        $this->actingAs($belumBerlaku)->post(route('persetujuan.permintaan.setujui', $permintaan->Id))->assertForbidden();

        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertSame(StatusPermintaanPersetujuan::Menunggu->value, $permintaan->refresh()->Status);
        $this->assertSame(0, KeputusanPersetujuan::query()->where('PermintaanPersetujuanId', $permintaan->Id)->count());
        $this->konteks()->bersihkan();

        $this->actingAs($aktif)->post(route('persetujuan.permintaan.setujui', $permintaan->Id))->assertRedirect()->assertSessionHasNoErrors();

        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertSame(StatusPermintaanPersetujuan::Disetujui->value, $permintaan->refresh()->Status);
    }

    public function test_anggota_unit_yang_penugasannya_kedaluwarsa_tidak_berhak_menyetujui_tahap_berbasis_unit(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $mantanAnggota = $this->buatPengguna($organisasi);
        $anggotaAktif = $this->buatPengguna($organisasi);

        $this->konteks()->tetapkan($organisasi->Id);
        $unit = UnitOrganisasi::create(['Kode' => 'UNIT-1', 'Nama' => 'Instalasi Radiologi', 'Jenis' => 'Divisi']);
        $lokasi = Lokasi::create(['Kode' => 'LOK-1', 'Nama' => 'Ruang Radiologi', 'UnitOrganisasiId' => $unit->Id]);
        $peranStaf = Peran::create(['Kode' => 'STAF', 'Nama' => 'Staf']);
        PenggunaPeran::create([
            'PenggunaId' => $mantanAnggota->Id,
            'PeranId' => $peranStaf->Id,
            'UnitOrganisasiId' => $unit->Id,
            'BerlakuSampai' => CarbonImmutable::now()->subDay(),
        ]);
        PenggunaPeran::create(['PenggunaId' => $anggotaAktif->Id, 'PeranId' => $peranStaf->Id, 'UnitOrganisasiId' => $unit->Id]);
        $alur = $this->buatAlurSatuTahap(['JenisPenyetuju' => 'Unit']);
        $this->konteks()->bersihkan();

        $permintaan = $this->ajukan($organisasi, $peminta, $alur, $lokasi);

        // Penugasan di unit ini sudah berakhir kemarin; ia tidak lagi mewakili unitnya.
        $this->actingAs($mantanAnggota)->post(route('persetujuan.permintaan.setujui', $permintaan->Id))->assertForbidden();

        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertSame(StatusPermintaanPersetujuan::Menunggu->value, $permintaan->refresh()->Status);
        $this->konteks()->bersihkan();

        $this->actingAs($anggotaAktif)->post(route('persetujuan.permintaan.setujui', $permintaan->Id))->assertRedirect()->assertSessionHasNoErrors();

        $this->konteks()->tetapkan($organisasi->Id);
        $this->assertSame(StatusPermintaanPersetujuan::Disetujui->value, $permintaan->refresh()->Status);
    }

    private function konteks(): KonteksOrganisasi
    {
        return app(KonteksOrganisasi::class);
    }

    private function ajukan(Organisasi $organisasi, Pengguna $peminta, AlurPersetujuan $alur, Lokasi $lokasi): PermintaanPersetujuan
    {
        $this->actingAs($peminta)
            ->post(route('persetujuan.permintaan.store'), ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id])
            ->assertSessionHasNoErrors();

        $this->konteks()->tetapkan($organisasi->Id);
        $permintaan = PermintaanPersetujuan::query()->where('EntitasId', $lokasi->Id)->firstOrFail();
        $this->konteks()->bersihkan();

        return $permintaan;
    }

    /**
     * @param  array<string, mixed>  $penyetuju
     */
    private function buatAlurSatuTahap(array $penyetuju): AlurPersetujuan
    {
        $alur = AlurPersetujuan::create(['Kode' => 'ALUR-1', 'Nama' => 'Alur Uji', 'JenisEntitas' => 'Lokasi']);
        TahapPersetujuan::create(array_merge([
            'AlurPersetujuanId' => $alur->Id,
            'Urutan' => 1,
            'Nama' => 'Tahap 1',
            'JumlahMinimumPenyetuju' => 1,
        ], $penyetuju));
        $alur->Aktif = true;
        $alur->save();

        return $alur;
    }

    /**
     * @param  list<string>  $kodeIzin
     */
    private function buatPengguna(Organisasi $organisasi, array $kodeIzin = []): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== []) {
            $this->konteks()->tetapkan($organisasi->Id);
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
}
