<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Domain\Enums\SiklusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Domain\Pemasaran\Application\Actions\CatatProspek;
use App\Domain\Pemasaran\Application\Actions\MulaiTrial;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Carbon\CarbonImmutable;
use Database\Seeders\FiturPaketSeeder;
use Illuminate\Support\Str;

/** Dasar test trial: organisasi, paket, dan prospek yang siap dipakai. */
abstract class KasusTrial extends KasusProspek
{
    protected Organisasi $organisasi;

    protected PaketLangganan $paket;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-06-15 09:00:00');
        $this->seed(FiturPaketSeeder::class);

        $this->organisasi = Organisasi::create([
            'Kode' => 'ORG-TRIAL-'.uniqid(),
            'Nama' => 'Organisasi Trial',
            'Status' => 'Aktif',
        ]);

        $this->paket = PaketLangganan::create([
            'Kode' => 'PKT-TRIAL-'.uniqid(),
            'Nama' => 'Paket Trial',
            'HargaBulanan' => 250_000,
            'HargaTahunan' => 2_500_000,
            'MataUang' => 'IDR',
            'Aktif' => true,
        ]);

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    protected function buatProspek(?string $pengenal = null): Prospek
    {
        return app(CatatProspek::class)->jalankan(
            ['Nama' => 'Budi', 'Email' => 'budi+'.uniqid().'@pabrik.test'],
            SumberProspek::Website,
            $pengenal ?? (string) Str::ulid(),
        );
    }

    protected function mulaiTrial(?Prospek $prospek = null): Trial
    {
        return app(MulaiTrial::class)->jalankan($this->organisasi->Id, $prospek);
    }

    protected function buatLangganan(?string $ujiCobaSampai = null): Langganan
    {
        return Langganan::create([
            'OrganisasiId' => $this->organisasi->Id,
            'PaketLanggananId' => $this->paket->Id,
            'Siklus' => SiklusLangganan::Bulanan->value,
            'MulaiPada' => CarbonImmutable::now()->toDateString(),
            'BerakhirPada' => CarbonImmutable::now()->addMonth()->toDateString(),
            'UjiCobaSampai' => $ujiCobaSampai ?? CarbonImmutable::now()->addDays(14)->toDateString(),
            'Status' => StatusLangganan::UjiCoba->value,
        ]);
    }
}
