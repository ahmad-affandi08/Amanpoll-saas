<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemasaran\Application\Actions\TerbitkanHalaman;
use App\Domain\Pemasaran\Application\Actions\UbahStatusHalaman;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use Illuminate\Console\Command;
use Throwable;

/**
 * Menjalankan terbit dan tarik terjadwal (MARKETING.md 8).
 *
 * Yang dibandingkan adalah waktu yang sudah lewat, bukan waktu yang persis
 * sekarang: penjadwal yang terlambat berjalan — dan pada shared hosting itu
 * sering terjadi — tetap menerbitkan halaman yang jamnya sudah tiba, alih-alih
 * melewatkannya untuk selamanya.
 *
 * Satu halaman yang gagal tidak menghentikan sisanya. Kampanye yang isinya
 * sepuluh landing page tidak boleh batal seluruhnya karena satu di antaranya
 * kehilangan versinya.
 */
final class JalankanJadwalHalamanPemasaran extends Command
{
    protected $signature = 'pemasaran:jalankan-jadwal-halaman';

    protected $description = 'Terbitkan dan tarik halaman pemasaran yang jadwalnya sudah tiba';

    public function __construct(
        private readonly TerbitkanHalaman $terbitkan,
        private readonly UbahStatusHalaman $ubahStatus,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info("Menerbitkan {$this->terbitkanYangTiba()} halaman terjadwal.");
        $this->info("Menarik {$this->tarikYangTiba()} halaman terbit.");

        return self::SUCCESS;
    }

    private function terbitkanYangTiba(): int
    {
        $halaman = HalamanPemasaran::query()
            ->where('Status', StatusHalamanPemasaran::Terjadwal->value)
            ->whereNotNull('TerbitPada')
            ->where('TerbitPada', '<=', now())
            ->get();

        $berhasil = 0;

        foreach ($halaman as $satu) {
            $versi = $satu->versiDraf ?? $satu->versiTerbit;

            try {
                $this->terbitkan->jalankan($satu, $versi);
                $berhasil++;
            } catch (Throwable $galat) {
                $this->error("Halaman {$satu->Slug} gagal diterbitkan: {$galat->getMessage()}");
            }
        }

        return $berhasil;
    }

    private function tarikYangTiba(): int
    {
        $halaman = HalamanPemasaran::query()
            ->where('Status', StatusHalamanPemasaran::Terbit->value)
            ->whereNotNull('TarikPada')
            ->where('TarikPada', '<=', now())
            ->get();

        $berhasil = 0;

        foreach ($halaman as $satu) {
            try {
                $this->ubahStatus->jalankan($satu, StatusHalamanPemasaran::Diarsipkan);
                $berhasil++;
            } catch (Throwable $galat) {
                $this->error("Halaman {$satu->Slug} gagal ditarik: {$galat->getMessage()}");
            }
        }

        return $berhasil;
    }
}
