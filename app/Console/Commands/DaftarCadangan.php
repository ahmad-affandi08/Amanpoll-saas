<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Cadangan\LayananCadangan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Console\Command;

/** Menampilkan cadangan yang tersedia beserta umur dan ukurannya, di lokal atau di disk luar (FASE 25.04, FASE 45). */
final class DaftarCadangan extends Command
{
    protected $signature = 'cadangan:daftar {--luar : Tampilkan cadangan di disk luar}';

    protected $description = 'Tampilkan cadangan yang tersedia';

    public function __construct(private readonly LayananCadangan $layanan)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $daftar = $this->option('luar') ? $this->layanan->daftarLuar() : $this->layanan->daftar();
        } catch (AturanBisnisDilanggar $galat) {
            $this->error($galat->getMessage());

            return self::FAILURE;
        }

        if ($daftar === []) {
            $this->warn('Belum ada cadangan sama sekali.');

            return self::SUCCESS;
        }

        $this->table(
            ['Berkas', 'Dibuat', 'Ukuran'],
            array_map(fn (array $satu): array => [
                $satu['nama'],
                $satu['dibuat']->toDateTimeString(),
                $this->ukuran($satu['ukuran']),
            ], $daftar),
        );

        return self::SUCCESS;
    }

    private function ukuran(int $byte): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $satuan) {
            if ($byte < 1024) {
                return round($byte, 1).' '.$satuan;
            }

            $byte = (int) round($byte / 1024);
        }

        return $byte.' TB';
    }
}
