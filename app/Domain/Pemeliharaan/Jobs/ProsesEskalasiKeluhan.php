<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Jobs;

use App\Domain\Pemeliharaan\Application\Services\LayananEskalasiSla;
use App\Jobs\PekerjaanOrganisasi;
use Illuminate\Contracts\Queue\ShouldBeUnique;

final class ProsesEskalasiKeluhan extends PekerjaanOrganisasi implements ShouldBeUnique
{
    public int $uniqueFor = 300;

    public function uniqueId(): string
    {
        return $this->OrganisasiId;
    }

    protected function jalankan(): void
    {
        app(LayananEskalasiSla::class)->proses();
    }
}
