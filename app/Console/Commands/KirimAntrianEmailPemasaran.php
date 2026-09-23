<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use App\Domain\Pemasaran\Jobs\KirimEmailPemasaran;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/** Mengantrekan email pemasaran yang jadwalnya sudah tiba (MARKETING.md 15, 27). */
final class KirimAntrianEmailPemasaran extends Command
{
    protected $signature = 'pemasaran:kirim-antrian-email';

    protected $description = 'Antrekan email pemasaran yang sudah jatuh tempo, sebatas cap harian';

    public function __construct(private readonly LayananKonfigurasiPemasaran $konfigurasi)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $sisa = $this->sisaCapHarian();

        if ($sisa < 1) {
            $this->info('Cap pengiriman harian sudah tercapai.');

            return self::SUCCESS;
        }

        $jatuhTempo = PengirimanEmailPemasaran::query()
            ->where('Status', StatusPengirimanEmail::Terjadwal->value)
            ->where('JadwalPada', '<=', now())
            ->orderBy('JadwalPada')
            ->limit($sisa)
            ->pluck('Id');

        foreach ($jatuhTempo as $id) {
            KirimEmailPemasaran::dispatch((string) $id);
        }

        $this->info("Mengantrekan {$jatuhTempo->count()} email pemasaran.");

        return self::SUCCESS;
    }

    /** Cap dihitung dari yang benar-benar sudah berangkat hari ini, bukan dari yang diantrekan. */
    private function sisaCapHarian(): int
    {
        $cap = $this->konfigurasi->angka(KatalogKonfigurasiPemasaran::EMAIL_CAP_HARIAN);

        $terkirim = PengirimanEmailPemasaran::query()
            ->whereNotNull('DikirimPada')
            ->where('DikirimPada', '>=', CarbonImmutable::now(KalenderOrganisasi::zonaBawaan())->startOfDay()->utc())
            ->count();

        return max($cap - $terkirim, 0);
    }
}
