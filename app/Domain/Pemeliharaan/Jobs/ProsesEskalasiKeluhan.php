<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Jobs;

use App\Domain\Pemeliharaan\Application\Services\LayananEskalasiSla;
use App\Jobs\PekerjaanOrganisasi;
use Illuminate\Contracts\Queue\ShouldBeUnique;

final class ProsesEskalasiKeluhan extends PekerjaanOrganisasi implements ShouldBeUnique
{
    public int $uniqueFor = 300;

    /**
     * Eskalasi mengirim notifikasi ke orang sungguhan. Jadwalnya kembali tiap
     * lima menit, jadi kegagalan lebih baik ditunggu jalan berikutnya daripada
     * diulang dan berisiko mengirim dua kali.
     */
    public int $tries = 1;

    public function uniqueId(): string
    {
        return $this->OrganisasiId;
    }

    protected function jalankan(): void
    {
        app(LayananEskalasiSla::class)->proses();
    }
}
