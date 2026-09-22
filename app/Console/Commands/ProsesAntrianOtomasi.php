<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusEksekusiOtomasi;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksekusiOtomasiPemasaran;
use App\Domain\Pemasaran\Jobs\ProsesOtomasiPemasaran;
use Illuminate\Console\Command;

/** Mengantrekan eksekusi otomasi yang jedanya sudah lewat atau yang menunggu dicoba lagi (MARKETING.md 17). */
final class ProsesAntrianOtomasi extends Command
{
    protected $signature = 'pemasaran:proses-antrian-otomasi';

    protected $description = 'Antrekan eksekusi otomasi yang sudah waktunya dilanjutkan, sebatas cap eksekusi';

    public function __construct(private readonly LayananKonfigurasiPemasaran $konfigurasi)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $cap = max($this->konfigurasi->angka(KatalogKonfigurasiPemasaran::OTOMASI_CAP_EKSEKUSI), 0);

        if ($cap < 1) {
            $this->info('Cap eksekusi otomasi menutup seluruh pemrosesan.');

            return self::SUCCESS;
        }

        $siap = EksekusiOtomasiPemasaran::query()
            ->whereIn('Status', $this->statusSiap())
            ->where(function ($kueri): void {
                $kueri->whereNull('LanjutPada')->orWhere('LanjutPada', '<=', now());
            })
            ->orderBy('DimulaiPada')
            ->limit($cap)
            ->pluck('Id');

        foreach ($siap as $id) {
            ProsesOtomasiPemasaran::dispatch((string) $id);
        }

        $this->info("Mengantrekan {$siap->count()} eksekusi otomasi.");

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function statusSiap(): array
    {
        return array_values(array_map(
            fn (StatusEksekusiOtomasi $status): string => $status->value,
            array_filter(
                StatusEksekusiOtomasi::cases(),
                fn (StatusEksekusiOtomasi $status): bool => $status->siapDiproses(),
            ),
        ));
    }
}
