<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Cadangan\LayananCadangan;
use Illuminate\Console\Command;

/** Menampilkan cadangan yang tersedia beserta umur dan ukurannya (FASE 25.04). */
final class DaftarCadangan extends Command
{
    protected $signature = 'cadangan:daftar';

    protected $description = 'Tampilkan cadangan yang tersedia';

    public function __construct(private readonly LayananCadangan $layanan)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $daftar = $this->layanan->daftar();

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
