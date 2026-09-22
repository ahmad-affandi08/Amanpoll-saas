<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemasaran\Application\Actions\PindahkanStatusTrial;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use Illuminate\Console\Command;
use Throwable;

/** Menutup trial yang masa berlakunya sudah lewat (MARKETING.md 12). */
final class KedaluwarsakanTrial extends Command
{
    protected $signature = 'pemasaran:kedaluwarsakan-trial';

    protected $description = 'Tandai trial yang sudah melewati BerakhirPada sebagai kedaluwarsa';

    public function __construct(private readonly PindahkanStatusTrial $pindahkanStatus)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $trial = Trial::query()
            ->whereIn('Status', $this->statusBerjalan())
            ->where('BerakhirPada', '<', now())
            ->get();

        $berhasil = 0;

        foreach ($trial as $satu) {
            try {
                $this->pindahkanStatus->jalankan($satu, StatusTrial::Kadaluarsa, 'Masa trial berakhir.');
                $berhasil++;
            } catch (Throwable $galat) {
                $this->error("Trial {$satu->Id} gagal ditutup: {$galat->getMessage()}");
            }
        }

        $this->info("Menutup {$berhasil} trial yang kedaluwarsa.");

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function statusBerjalan(): array
    {
        return array_values(array_map(
            fn (StatusTrial $status): string => $status->value,
            array_filter(StatusTrial::cases(), fn (StatusTrial $status): bool => $status->berjalan()),
        ));
    }
}
