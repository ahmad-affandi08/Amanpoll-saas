<?php

declare(strict_types=1);

namespace Tests\Feature\Persetujuan;

use App\Core\Organisasi\KonteksOrganisasi;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersetujuanEngineTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(Organisasi $organisasi, array $kodeIzin = [], ?string $unitOrganisasiId = null): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'UnitOrganisasiId' => $unitOrganisasiId,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== []) {
            $konteks = app(KonteksOrganisasi::class);
            $konteks->tetapkan($organisasi->Id);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            foreach ($kodeIzin as $kode) {
                $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            }
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            $konteks->bersihkan();
        }

        return $pengguna;
    }

    private function buatLokasi(Organisasi $organisasi, ?string $unitOrganisasiId = null): Lokasi
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $lokasi = Lokasi::create(['Kode' => 'LOK-'.uniqid(), 'Nama' => 'Lokasi Uji', 'UnitOrganisasiId' => $unitOrganisasiId]);
        $konteks->bersihkan();

        return $lokasi;
    }

    private function buatAlurSatuTahap(Organisasi $organisasi, Pengguna $penyetuju, bool $bolehSendiri = false): AlurPersetujuan
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $alur = AlurPersetujuan::create(['Kode' => 'ALUR-'.uniqid(), 'Nama' => 'Alur Uji', 'JenisEntitas' => 'Lokasi']);
        TahapPersetujuan::create([
            'AlurPersetujuanId' => $alur->Id, 'Urutan' => 1, 'Nama' => 'Tahap 1',
            'JenisPenyetuju' => 'Pengguna', 'PenggunaId' => $penyetuju->Id,
            'JumlahMinimumPenyetuju' => 1, 'BolehMenyetujuiSendiri' => $bolehSendiri,
        ]);
        $alur->Aktif = true;
        $alur->save();
        $konteks->bersihkan();

        return $alur;
    }

    public function test_alur_baru_selalu_tidak_aktif(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi, ['Persetujuan.Kelola']);

        $this->actingAs($admin)->post('/persetujuan/alur', [
            'Kode' => 'ALUR-1', 'Nama' => 'Alur Satu', 'JenisEntitas' => 'Lokasi',
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $alur = AlurPersetujuan::where('Kode', 'ALUR-1')->first();
        $konteks->bersihkan();

        $this->assertNotNull($alur);
        $this->assertFalse($alur->Aktif);
    }

    public function test_alur_tidak_bisa_diaktifkan_tanpa_tahap(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi, ['Persetujuan.Kelola']);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $alur = AlurPersetujuan::create(['Kode' => 'ALUR-1', 'Nama' => 'Alur Satu', 'JenisEntitas' => 'Lokasi']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->post("/persetujuan/alur/{$alur->Id}/aktifkan");
        $response->assertStatus(422);
    }

    public function test_tidak_bisa_ubah_tahap_saat_alur_aktif(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi, ['Persetujuan.Kelola']);
        $alur = $this->buatAlurSatuTahap($organisasi, $admin);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $tahap = TahapPersetujuan::where('AlurPersetujuanId', $alur->Id)->first();
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->put("/persetujuan/tahap/{$tahap->Id}", [
            'Urutan' => 1, 'Nama' => 'Tahap Baru', 'JenisPenyetuju' => 'Pengguna', 'PenggunaId' => $admin->Id, 'JumlahMinimumPenyetuju' => 1,
        ]);
        $response->assertStatus(422);
    }

    public function test_ajukan_permintaan_persetujuan_untuk_lokasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penyetuju = $this->buatPengguna($organisasi);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);
        $alur = $this->buatAlurSatuTahap($organisasi, $penyetuju);

        $response = $this->actingAs($peminta)->post('/persetujuan/permintaan', [
            'AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id,
        ]);
        $response->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $permintaan = PermintaanPersetujuan::where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $this->assertNotNull($permintaan);
        $this->assertSame(PermintaanPersetujuan::STATUS_MENUNGGU, $permintaan->Status);
        $this->assertSame(1, $permintaan->TahapSaatIni);
    }

    public function test_ajukan_ditolak_jika_alur_tidak_aktif(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $alur = AlurPersetujuan::create(['Kode' => 'ALUR-1', 'Nama' => 'Alur Satu', 'JenisEntitas' => 'Lokasi']);
        $konteks->bersihkan();

        $response = $this->actingAs($peminta)->post('/persetujuan/permintaan', [
            'AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id,
        ]);
        $response->assertStatus(422);
    }

    public function test_ajukan_ditolak_jika_sudah_ada_permintaan_menunggu(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penyetuju = $this->buatPengguna($organisasi);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);
        $alur = $this->buatAlurSatuTahap($organisasi, $penyetuju);

        $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id])
            ->assertSessionDoesntHaveErrors();

        $response = $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id]);
        $response->assertStatus(422);
    }

    public function test_setujui_oleh_penyetuju_yang_sah_menyelesaikan_alur_satu_tahap(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penyetuju = $this->buatPengguna($organisasi);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);
        $alur = $this->buatAlurSatuTahap($organisasi, $penyetuju);

        $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $permintaan = PermintaanPersetujuan::where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $response = $this->actingAs($penyetuju)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui", ['Catatan' => 'Oke']);
        $response->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $permintaan->refresh();
        $keputusan = KeputusanPersetujuan::where('PermintaanPersetujuanId', $permintaan->Id)->first();
        $konteks->bersihkan();

        $this->assertSame(PermintaanPersetujuan::STATUS_DISETUJUI, $permintaan->Status);
        $this->assertNotNull($permintaan->SelesaiPada);
        $this->assertNotNull($keputusan);
        $this->assertSame(KeputusanPersetujuan::KEPUTUSAN_DISETUJUI, $keputusan->Keputusan);
    }

    public function test_setujui_lanjut_ke_tahap_berikutnya_pada_alur_dua_tahap(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penyetuju1 = $this->buatPengguna($organisasi);
        $penyetuju2 = $this->buatPengguna($organisasi);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $alur = AlurPersetujuan::create(['Kode' => 'ALUR-1', 'Nama' => 'Alur Dua Tahap', 'JenisEntitas' => 'Lokasi']);
        TahapPersetujuan::create(['AlurPersetujuanId' => $alur->Id, 'Urutan' => 1, 'Nama' => 'Tahap 1', 'JenisPenyetuju' => 'Pengguna', 'PenggunaId' => $penyetuju1->Id, 'JumlahMinimumPenyetuju' => 1]);
        TahapPersetujuan::create(['AlurPersetujuanId' => $alur->Id, 'Urutan' => 2, 'Nama' => 'Tahap 2', 'JenisPenyetuju' => 'Pengguna', 'PenggunaId' => $penyetuju2->Id, 'JumlahMinimumPenyetuju' => 1]);
        $alur->Aktif = true;
        $alur->save();
        $konteks->bersihkan();

        $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id]);

        $konteks->tetapkan($organisasi->Id);
        $permintaan = PermintaanPersetujuan::where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $this->actingAs($penyetuju1)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui")->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $permintaan->refresh();
        $konteks->bersihkan();
        $this->assertSame(PermintaanPersetujuan::STATUS_MENUNGGU, $permintaan->Status);
        $this->assertSame(2, $permintaan->TahapSaatIni);

        // Penyetuju tahap 1 tidak berhak di tahap 2.
        $this->actingAs($penyetuju1)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui")->assertForbidden();

        $this->actingAs($penyetuju2)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui")->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $permintaan->refresh();
        $konteks->bersihkan();
        $this->assertSame(PermintaanPersetujuan::STATUS_DISETUJUI, $permintaan->Status);
    }

    public function test_bukan_penyetuju_yang_sah_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penyetuju = $this->buatPengguna($organisasi);
        $bukanPenyetuju = $this->buatPengguna($organisasi);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);
        $alur = $this->buatAlurSatuTahap($organisasi, $penyetuju);

        $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $permintaan = PermintaanPersetujuan::where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $this->actingAs($bukanPenyetuju)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui")->assertForbidden();
    }

    public function test_tidak_boleh_menyetujui_permintaan_sendiri_secara_default(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);
        $alur = $this->buatAlurSatuTahap($organisasi, $peminta, bolehSendiri: false);

        $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $permintaan = PermintaanPersetujuan::where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $this->actingAs($peminta)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui")->assertForbidden();
    }

    public function test_boleh_menyetujui_sendiri_jika_diizinkan_tahap(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);
        $alur = $this->buatAlurSatuTahap($organisasi, $peminta, bolehSendiri: true);

        $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $permintaan = PermintaanPersetujuan::where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $this->actingAs($peminta)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui")->assertSessionDoesntHaveErrors();
    }

    public function test_tolak_langsung_mengakhiri_permintaan(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penyetuju = $this->buatPengguna($organisasi);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);
        $alur = $this->buatAlurSatuTahap($organisasi, $penyetuju);

        $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $permintaan = PermintaanPersetujuan::where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $this->actingAs($penyetuju)->post("/persetujuan/permintaan/{$permintaan->Id}/tolak", ['Catatan' => 'Tidak sesuai'])
            ->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $permintaan->refresh();
        $konteks->bersihkan();

        $this->assertSame(PermintaanPersetujuan::STATUS_DITOLAK, $permintaan->Status);
        $this->assertNotNull($permintaan->SelesaiPada);
    }

    public function test_batalkan_permintaan_oleh_peminta(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penyetuju = $this->buatPengguna($organisasi);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);
        $alur = $this->buatAlurSatuTahap($organisasi, $penyetuju);

        $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $permintaan = PermintaanPersetujuan::where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $this->actingAs($peminta)->delete("/persetujuan/permintaan/{$permintaan->Id}")->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $permintaan->refresh();
        $konteks->bersihkan();
        $this->assertSame(PermintaanPersetujuan::STATUS_DIBATALKAN, $permintaan->Status);
    }

    public function test_penyetuju_berbasis_peran(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);
        $penyetuju = $this->buatPengguna($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $peranManajer = Peran::create(['Kode' => 'MANAJER', 'Nama' => 'Manajer']);
        PenggunaPeran::create(['PenggunaId' => $penyetuju->Id, 'PeranId' => $peranManajer->Id]);

        $alur = AlurPersetujuan::create(['Kode' => 'ALUR-1', 'Nama' => 'Alur Peran', 'JenisEntitas' => 'Lokasi']);
        TahapPersetujuan::create(['AlurPersetujuanId' => $alur->Id, 'Urutan' => 1, 'Nama' => 'Tahap 1', 'JenisPenyetuju' => 'Peran', 'PeranId' => $peranManajer->Id, 'JumlahMinimumPenyetuju' => 1]);
        $alur->Aktif = true;
        $alur->save();
        $konteks->bersihkan();

        $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id]);

        $konteks->tetapkan($organisasi->Id);
        $permintaan = PermintaanPersetujuan::where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $this->actingAs($penyetuju)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui")->assertSessionDoesntHaveErrors();
    }

    public function test_penyetuju_berbasis_unit(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $unit = UnitOrganisasi::create(['Kode' => 'UNIT-1', 'Nama' => 'Unit Satu', 'Jenis' => 'Divisi']);
        $konteks->bersihkan();

        $lokasi = $this->buatLokasi($organisasi, $unit->Id);
        $penyetujuDiUnit = $this->buatPengguna($organisasi);
        $penyetujuDiUnitLain = $this->buatPengguna($organisasi);

        $konteks->tetapkan($organisasi->Id);
        PenggunaPeran::create(['PenggunaId' => $penyetujuDiUnit->Id, 'PeranId' => Peran::create(['Kode' => 'STAF1', 'Nama' => 'Staf'])->Id, 'UnitOrganisasiId' => $unit->Id]);
        PenggunaPeran::create(['PenggunaId' => $penyetujuDiUnitLain->Id, 'PeranId' => Peran::create(['Kode' => 'STAF2', 'Nama' => 'Staf'])->Id]);

        $alur = AlurPersetujuan::create(['Kode' => 'ALUR-1', 'Nama' => 'Alur Unit', 'JenisEntitas' => 'Lokasi']);
        TahapPersetujuan::create(['AlurPersetujuanId' => $alur->Id, 'Urutan' => 1, 'Nama' => 'Tahap 1', 'JenisPenyetuju' => 'Unit', 'JumlahMinimumPenyetuju' => 1]);
        $alur->Aktif = true;
        $alur->save();
        $konteks->bersihkan();

        $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id]);

        $konteks->tetapkan($organisasi->Id);
        $permintaan = PermintaanPersetujuan::where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $this->actingAs($penyetujuDiUnitLain)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui")->assertForbidden();
        $this->actingAs($penyetujuDiUnit)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui")->assertSessionDoesntHaveErrors();
    }

    public function test_keputusan_ganda_pada_tahap_sama_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penyetuju1 = $this->buatPengguna($organisasi);
        $penyetuju2 = $this->buatPengguna($organisasi);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $peran = Peran::create(['Kode' => 'REVIEWER', 'Nama' => 'Reviewer']);
        PenggunaPeran::create(['PenggunaId' => $penyetuju1->Id, 'PeranId' => $peran->Id]);
        PenggunaPeran::create(['PenggunaId' => $penyetuju2->Id, 'PeranId' => $peran->Id]);

        $alur = AlurPersetujuan::create(['Kode' => 'ALUR-1', 'Nama' => 'Alur Kuorum', 'JenisEntitas' => 'Lokasi']);
        TahapPersetujuan::create(['AlurPersetujuanId' => $alur->Id, 'Urutan' => 1, 'Nama' => 'Tahap 1', 'JenisPenyetuju' => 'Peran', 'PeranId' => $peran->Id, 'JumlahMinimumPenyetuju' => 2]);
        $alur->Aktif = true;
        $alur->save();
        $konteks->bersihkan();

        $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id]);

        $konteks->tetapkan($organisasi->Id);
        $permintaan = PermintaanPersetujuan::where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $this->actingAs($penyetuju1)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui")->assertSessionDoesntHaveErrors();
        // Coba setujui lagi dengan penyetuju yang sama -> konflik.
        $this->actingAs($penyetuju1)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui")->assertStatus(409);

        $konteks->tetapkan($organisasi->Id);
        $permintaan->refresh();
        $konteks->bersihkan();
        $this->assertSame(PermintaanPersetujuan::STATUS_MENUNGGU, $permintaan->Status);

        $this->actingAs($penyetuju2)->post("/persetujuan/permintaan/{$permintaan->Id}/setujui")->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $permintaan->refresh();
        $konteks->bersihkan();
        $this->assertSame(PermintaanPersetujuan::STATUS_DISETUJUI, $permintaan->Status);
    }

    public function test_inbox_menampilkan_permintaan_yang_perlu_ditindak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $penyetuju = $this->buatPengguna($organisasi);
        $lainnya = $this->buatPengguna($organisasi);
        $peminta = $this->buatPengguna($organisasi, ['Pengaturan.Kelola']);
        $lokasi = $this->buatLokasi($organisasi);
        $alur = $this->buatAlurSatuTahap($organisasi, $penyetuju);

        $this->actingAs($peminta)->post('/persetujuan/permintaan', ['AlurPersetujuanId' => $alur->Id, 'EntitasId' => $lokasi->Id]);

        $responsePenyetuju = $this->actingAs($penyetuju)->get('/persetujuan/permintaan/inbox');
        $responsePenyetuju->assertOk();
        $this->assertCount(1, $responsePenyetuju->json());

        $responseLainnya = $this->actingAs($lainnya)->get('/persetujuan/permintaan/inbox');
        $responseLainnya->assertOk();
        $this->assertCount(0, $responseLainnya->json());
    }
}
