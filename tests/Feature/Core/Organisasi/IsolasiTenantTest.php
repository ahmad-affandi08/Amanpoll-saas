<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Organisasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsolasiTenantTest extends TestCase
{
    use RefreshDatabase;

    private KonteksOrganisasi $konteks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->konteks = app(KonteksOrganisasi::class);
    }

    protected function tearDown(): void
    {
        $this->konteks->bersihkan();
        parent::tearDown();
    }

    private function buatUnit(Organisasi $organisasi, string $kode): UnitOrganisasi
    {
        $this->konteks->tetapkan($organisasi->Id);
        $unit = UnitOrganisasi::create(['Kode' => $kode, 'Nama' => $kode]);
        $this->konteks->bersihkan();

        return $unit;
    }

    public function test_organisasi_a_tidak_dapat_membaca_data_organisasi_b(): void
    {
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $unitB = $this->buatUnit($organisasiB, 'UNIT-B');

        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $this->konteks->tetapkan($organisasiA->Id);

        $this->assertNull(UnitOrganisasi::find($unitB->Id));
        $this->assertCount(0, UnitOrganisasi::all());
    }

    public function test_organisasi_a_tidak_dapat_update_data_organisasi_b(): void
    {
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $unitB = $this->buatUnit($organisasiB, 'UNIT-B');

        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $this->konteks->tetapkan($organisasiA->Id);

        $barisTerdampak = UnitOrganisasi::where('Id', $unitB->Id)->update(['Nama' => 'Diretas']);

        $this->assertSame(0, $barisTerdampak);
        $this->assertSame('UNIT-B', $unitB->fresh()?->Nama);
    }

    public function test_organisasi_a_tidak_dapat_menghapus_data_organisasi_b(): void
    {
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $unitB = $this->buatUnit($organisasiB, 'UNIT-B');

        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $this->konteks->tetapkan($organisasiA->Id);

        $barisTerhapus = UnitOrganisasi::destroy($unitB->Id);

        $this->assertSame(0, $barisTerhapus);
        $this->assertNotNull($unitB->fresh());
    }

    public function test_query_gagal_tertutup_saat_konteks_organisasi_tidak_ditetapkan(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $this->buatUnit($organisasiA, 'UNIT-A');

        $this->assertFalse($this->konteks->ada());
        $this->assertCount(0, UnitOrganisasi::all());
    }

    public function test_organisasi_a_hanya_melihat_datanya_sendiri(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $unitA = $this->buatUnit($organisasiA, 'UNIT-A');
        $this->buatUnit($organisasiB, 'UNIT-B');

        $this->konteks->tetapkan($organisasiA->Id);
        $hasil = UnitOrganisasi::all();

        $this->assertCount(1, $hasil);
        $this->assertSame($unitA->Id, $hasil->first()->Id);
    }

    public function test_menghapus_semua_scope_membuktikan_data_b_tetap_tersimpan(): void
    {
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $unitB = $this->buatUnit($organisasiB, 'UNIT-B');

        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $this->konteks->tetapkan($organisasiA->Id);
        UnitOrganisasi::destroy($unitB->Id);
        $this->konteks->bersihkan();

        $bukti = UnitOrganisasi::withoutGlobalScope(ScopeOrganisasi::class)->find($unitB->Id);
        $this->assertNotNull($bukti);
    }
}
