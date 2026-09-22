<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PemeriksaFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FiturPlatform;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class KasusPemasaran extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semaiFitur();
    }

    /** @param list<string> $izin */
    protected function buatAdmin(array $izin = [], bool $superAdmin = false): AdminPlatform
    {
        return AdminPlatform::create([
            'Nama' => 'Admin '.uniqid(),
            'Email' => 'admin+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
            'Izin' => $izin,
            'SuperAdmin' => $superAdmin,
        ]);
    }

    protected function nyalakanFitur(string $kode): void
    {
        FiturPlatform::query()->where('Kode', $kode)->update(['Aktif' => true]);
        app(PemeriksaFiturPlatform::class)->bersihkanCache();
    }

    protected function matikanFitur(string $kode): void
    {
        FiturPlatform::query()->where('Kode', $kode)->update(['Aktif' => false]);
        app(PemeriksaFiturPlatform::class)->bersihkanCache();
    }

    private function semaiFitur(): void
    {
        foreach (KatalogFiturPlatform::semua() as $kode => $definisi) {
            FiturPlatform::query()->firstOrCreate(
                ['Kode' => $kode],
                ['Nama' => $definisi['nama'], 'Keterangan' => $definisi['keterangan'], 'Aktif' => false],
            );
        }

        app(PemeriksaFiturPlatform::class)->bersihkanCache();
    }
}
