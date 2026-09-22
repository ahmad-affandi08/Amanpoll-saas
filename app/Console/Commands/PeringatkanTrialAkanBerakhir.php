<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/** Menyalakan TrialAkanBerakhir sekali per trial; tanpa itu pekerjaan harian ini memicu ulang tiap pagi (MARKETING.md 17). */
final class PeringatkanTrialAkanBerakhir extends Command
{
    protected $signature = 'pemasaran:peringatkan-trial-akan-berakhir {--hari=3 : Berapa hari sebelum berakhir}';

    protected $description = 'Catat peristiwa TrialAkanBerakhir sekali untuk tiap trial yang mendekati akhir';

    public function __construct(private readonly PerekamEventPemasaran $event)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $hari = max((int) $this->option('hari'), 1);
        $batas = CarbonImmutable::now()->addDays($hari);

        $trial = Trial::query()
            ->whereIn('Status', $this->statusBerjalan())
            ->where('BerakhirPada', '>', CarbonImmutable::now())
            ->where('BerakhirPada', '<=', $batas)
            ->get();

        $dicatat = 0;

        foreach ($trial as $satu) {
            if ($this->sudahDiperingatkan($satu)) {
                continue;
            }

            $this->event->catat(
                KatalogPeristiwaPemasaran::TRIAL_AKAN_BERAKHIR,
                pengenalPengunjung: $satu->PengenalPengunjung,
                dataTambahan: [
                    'TrialId' => $satu->Id,
                    'ProspekId' => $satu->ProspekId,
                    'BerakhirPada' => $satu->BerakhirPada->toIso8601String(),
                ],
                organisasiId: $satu->OrganisasiId,
            );

            $dicatat++;
        }

        $this->info("Mencatat {$dicatat} peringatan trial akan berakhir.");

        return self::SUCCESS;
    }

    private function sudahDiperingatkan(Trial $trial): bool
    {
        return EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::TRIAL_AKAN_BERAKHIR)
            ->where('OrganisasiId', $trial->OrganisasiId)
            ->whereJsonContains('DataTambahan->TrialId', $trial->Id)
            ->exists();
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
