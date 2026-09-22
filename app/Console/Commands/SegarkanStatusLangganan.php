<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Application\Actions\KelolaLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use Illuminate\Console\Command;

/** Menyelaraskan kolom Status langganan dengan keadaan hari ini (22.04). */
final class SegarkanStatusLangganan extends Command
{
    protected $signature = 'langganan:segarkan-status';

    protected $description = 'Menyelaraskan status langganan dengan tanggal berlakunya.';

    public function handle(KelolaLangganan $aksi, KonteksOrganisasi $konteks): int
    {
        $berubah = 0;

        Langganan::query()
            ->withoutGlobalScopes()
            ->orderBy('Id')
            ->chunkById(200, function ($daftar) use ($aksi, $konteks, &$berubah): void {
                foreach ($daftar as $langganan) {
                    // Aksi membersihkan cache entitlement per organisasi.
                    $konteks->tetapkan((string) $langganan->OrganisasiId);

                    if ($aksi->segarkanStatus($langganan) !== null) {
                        $berubah++;
                    }
                }

                $konteks->bersihkan();
            }, 'Id');

        $this->info("Status langganan diperbarui: {$berubah}.");

        return self::SUCCESS;
    }
}
