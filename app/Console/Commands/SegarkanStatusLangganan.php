<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Application\Actions\KelolaLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use Illuminate\Console\Command;

/**
 * Menyelaraskan kolom Status langganan dengan keadaan hari ini (22.04).
 *
 * Penegakan tidak bergantung pada perintah ini — status efektif selalu dihitung
 * ulang saat permintaan diproses — sehingga terlambat dijalankan tidak membuat
 * tenant kedaluwarsa tetap dapat menulis. Yang dikerjakan di sini adalah
 * membuat kolomnya dapat dipercaya untuk daftar dan laporan.
 */
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
                    // Aksi membersihkan cache entitlement per organisasi, dan
                    // audit mencatat tenant yang sedang disentuh, jadi konteks
                    // ditetapkan per baris.
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
