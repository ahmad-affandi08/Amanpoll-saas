<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Organisasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Jobs\PekerjaanOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PekerjaanOrganisasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pekerjaan_menetapkan_konteks_organisasi_selama_dijalankan(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'JOB-A', 'Nama' => 'Job Organisasi A']);

        (new PekerjaanUjiBuatUnit($organisasi->Id, 'UNIT-JOB'))->handle();

        $unit = UnitOrganisasi::withoutGlobalScope(ScopeOrganisasi::class)
            ->where('Kode', 'UNIT-JOB')
            ->first();

        $this->assertNotNull($unit);
        $this->assertSame($organisasi->Id, $unit->OrganisasiId);
    }

    public function test_konteks_organisasi_dibersihkan_setelah_pekerjaan_selesai(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'JOB-B', 'Nama' => 'Job Organisasi B']);

        (new PekerjaanUjiBuatUnit($organisasi->Id, 'UNIT-JOB-B'))->handle();

        $this->assertFalse(app(KonteksOrganisasi::class)->ada());
    }

    public function test_dua_pekerjaan_berurutan_tidak_saling_bocor_konteks(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'JOB-X', 'Nama' => 'Job Organisasi X']);
        $organisasiB = Organisasi::create(['Kode' => 'JOB-Y', 'Nama' => 'Job Organisasi Y']);

        (new PekerjaanUjiBuatUnit($organisasiA->Id, 'UNIT-X'))->handle();
        (new PekerjaanUjiBuatUnit($organisasiB->Id, 'UNIT-Y'))->handle();

        $unitX = UnitOrganisasi::withoutGlobalScope(ScopeOrganisasi::class)->where('Kode', 'UNIT-X')->first();
        $unitY = UnitOrganisasi::withoutGlobalScope(ScopeOrganisasi::class)->where('Kode', 'UNIT-Y')->first();

        $this->assertSame($organisasiA->Id, $unitX->OrganisasiId);
        $this->assertSame($organisasiB->Id, $unitY->OrganisasiId);
    }
}

final class PekerjaanUjiBuatUnit extends PekerjaanOrganisasi
{
    public function __construct(string $organisasiId, private readonly string $kode)
    {
        parent::__construct($organisasiId);
    }

    protected function jalankan(): void
    {
        UnitOrganisasi::create(['Kode' => $this->kode, 'Nama' => $this->kode]);
    }
}
