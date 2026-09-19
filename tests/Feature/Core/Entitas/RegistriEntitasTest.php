<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Entitas;

use App\Core\Entitas\RegistriEntitas;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistriEntitasTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_organisasi_dan_lokasi_terdaftar_bawaan(): void
    {
        $registri = app(RegistriEntitas::class);

        $this->assertTrue($registri->dikenal('UnitOrganisasi'));
        $this->assertTrue($registri->dikenal('Lokasi'));
        $this->assertSame('Pengaturan.Kelola', $registri->izinKelolaUntuk('UnitOrganisasi'));
    }

    public function test_jenis_entitas_tidak_dikenal_melempar_exception(): void
    {
        $registri = app(RegistriEntitas::class);

        $this->expectException(DataTidakDitemukan::class);
        $registri->izinKelolaUntuk('TidakAda');
    }

    public function test_cari_entitas_mengembalikan_baris_milik_organisasi_sendiri(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $unit = UnitOrganisasi::create(['Kode' => 'UNIT-1', 'Nama' => 'Unit Satu', 'Jenis' => 'Divisi']);

        $registri = app(RegistriEntitas::class);
        $ditemukan = $registri->cariEntitas('UnitOrganisasi', $unit->Id);

        $this->assertSame($unit->Id, $ditemukan->Id);
        $konteks->bersihkan();
    }

    public function test_cari_entitas_lintas_organisasi_tidak_ditemukan(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiB->Id);
        $unitB = UnitOrganisasi::create(['Kode' => 'UNIT-B', 'Nama' => 'Unit B', 'Jenis' => 'Divisi']);
        $konteks->bersihkan();

        $konteks->tetapkan($organisasiA->Id);
        $registri = app(RegistriEntitas::class);

        $this->expectException(DataTidakDitemukan::class);
        $registri->cariEntitas('UnitOrganisasi', $unitB->Id);
    }
}
