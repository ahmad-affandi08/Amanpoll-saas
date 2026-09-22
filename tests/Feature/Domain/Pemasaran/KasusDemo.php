<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Domain\Enums\FiturDibatasiDemo;
use App\Domain\Pemasaran\Domain\Enums\ModulDemo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DemoPemasaran;
use App\Domain\Pemasaran\Infrastructure\Services\DatasetDemoManufaktur;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Support\Str;

/** Dasar test demo: satu demo aktif beserta tenant sandboxnya. */
abstract class KasusDemo extends KasusPemasaran
{
    protected function buatDemo(
        bool $aktif = true,
        int $maksSesiSerentak = 5,
        int $maksDurasiMenit = 30,
        bool $denganTenant = true,
    ): DemoPemasaran {
        return DemoPemasaran::create([
            'Kode' => 'produk',
            'Nama' => 'Demo Produk Amanpoll',
            'Aktif' => $aktif,
            'Dataset' => DatasetDemoManufaktur::KODE,
            'OrganisasiDemoId' => $denganTenant ? $this->buatTenantDemo()->Id : null,
            'ResetIntervalMenit' => 60,
            'ModulTampil' => [
                ModulDemo::Aset->value,
                ModulDemo::PerintahKerja->value,
                ModulDemo::Preventif->value,
            ],
            'FiturDibatasi' => [FiturDibatasiDemo::Ekspor->value],
            'CtaLabel' => 'Coba Gratis',
            'CtaUrl' => 'https://dashboard.amanpoll.test/trial',
            'MaksDurasiMenit' => $maksDurasiMenit,
            'MaksSesiSerentak' => $maksSesiSerentak,
        ]);
    }

    protected function buatTenantDemo(): Organisasi
    {
        return Organisasi::create([
            'Kode' => 'DEMO-'.Str::upper(Str::random(6)),
            'Nama' => 'Sandbox Demo Amanpoll',
            'Status' => 'Aktif',
            'Demo' => true,
        ]);
    }

    /** Tenant sungguhan: persis seperti tenant demo kecuali penandanya. */
    protected function buatTenantSungguhan(): Organisasi
    {
        return Organisasi::create([
            'Kode' => 'NYATA-'.Str::upper(Str::random(6)),
            'Nama' => 'PT Pelanggan Sungguhan',
            'Status' => 'Aktif',
            'Demo' => false,
        ]);
    }

    protected function pengunjung(): string
    {
        return (string) Str::ulid();
    }
}
