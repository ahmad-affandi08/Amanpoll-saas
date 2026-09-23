<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Platform\Application\Actions\PasangPeranAwal;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Console\Command;

/**
 * Memasang peran bawaan ke organisasi yang sudah berdiri.
 *
 * Pendaftaran trial memasangnya sendiri, jadi perintah ini untuk organisasi
 * lama -- termasuk yang dibuat sebelum katalognya ada. Aksinya idempotent,
 * sehingga aman dijalankan berulang atas seluruh organisasi.
 */
final class PasangPeranAwalOrganisasi extends Command
{
    protected $signature = 'platform:pasang-peran-awal {--organisasi= : Id satu organisasi; kosongkan untuk seluruhnya}';

    protected $description = 'Pasang peran bawaan (Teknisi, Operator Gudang, Auditor, dan seterusnya) ke organisasi';

    public function __construct(private readonly PasangPeranAwal $aksi)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $pilihan = $this->option('organisasi');
        $kueri = Organisasi::query()->orderBy('Nama');

        if (is_string($pilihan) && $pilihan !== '') {
            $kueri->where('Id', $pilihan);
        }

        $organisasi = $kueri->get();

        if ($organisasi->isEmpty()) {
            $this->error('Tidak ada organisasi yang cocok.');

            return self::FAILURE;
        }

        $jumlahPeran = 0;

        foreach ($organisasi as $satu) {
            $baru = $this->aksi->jalankan($satu->Id);
            $jumlahPeran += count($baru);

            $this->line($baru === []
                ? "  {$satu->Nama}: sudah lengkap"
                : "  {$satu->Nama}: +".count($baru).' peran ('.implode(', ', $baru).')');
        }

        $this->info("Memasang {$jumlahPeran} peran pada {$organisasi->count()} organisasi.");

        return self::SUCCESS;
    }
}
