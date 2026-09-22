<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Cadangan\BerkasCadangan;
use App\Core\Cadangan\LayananCadangan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Memulihkan basis data dari satu cadangan (FASE 25.04).
 *
 * Perintah ini menimpa seluruh isi basis data tujuan, jadi ia selalu bertanya
 * lebih dulu dan menyebut nama basis data yang akan ditimpa. Di produksi ia
 * menuntut konfirmasi mengetik ulang nama itu, supaya tidak ada yang memulihkan
 * ke basis data yang salah hanya karena menekan enter.
 */
final class PulihkanCadangan extends Command
{
    protected $signature = 'cadangan:pulihkan
        {berkas? : Jalur berkas dump; kosong berarti cadangan basis data terbaru}
        {--ke= : Nama basis data tujuan; kosong berarti basis data koneksi aktif}
        {--paksa : Lewati konfirmasi, hanya untuk pemulihan tidak interaktif}';

    protected $description = 'Pulihkan basis data dari sebuah cadangan';

    public function __construct(
        private readonly LayananCadangan $layanan,
        private readonly BerkasCadangan $berkas,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $jalur = $this->jalurDump();

        if ($jalur === null) {
            $this->error('Tidak ada cadangan basis data yang dapat dipulihkan.');

            return self::FAILURE;
        }

        $tujuan = $this->namaTujuan();

        if (! $this->dikonfirmasi($jalur, $tujuan)) {
            $this->warn('Pemulihan dibatalkan.');

            return self::FAILURE;
        }

        try {
            $this->layanan->pulihkanBasisData($jalur, $tujuan);
        } catch (AturanBisnisDilanggar $galat) {
            $this->error($galat->getMessage());

            return self::FAILURE;
        }

        // Pemulihan mengganti seluruh isi basis data; itu selalu pantas tercatat.
        Log::warning('Basis data dipulihkan dari cadangan.', [
            'Berkas' => basename($jalur),
            'BasisData' => $tujuan,
        ]);

        $this->info('Basis data '.$tujuan.' dipulihkan dari '.basename($jalur).'.');

        return self::SUCCESS;
    }

    private function jalurDump(): ?string
    {
        $diminta = $this->argument('berkas');

        if (is_string($diminta) && $diminta !== '') {
            return $diminta;
        }

        return $this->berkas->basisDataTerbaru();
    }

    private function namaTujuan(): string
    {
        $ke = $this->option('ke');

        if (is_string($ke) && $ke !== '') {
            return $ke;
        }

        $koneksi = (string) config('database.default');

        return (string) config("database.connections.{$koneksi}.database");
    }

    private function dikonfirmasi(string $jalur, string $tujuan): bool
    {
        if ($this->option('paksa')) {
            return true;
        }

        $this->warn("Seluruh isi basis data {$tujuan} akan ditimpa oleh ".basename($jalur).'.');

        if (! $this->getLaravel()->isProduction()) {
            return $this->confirm('Lanjutkan?', false);
        }

        // Di produksi menekan enter tidak cukup; namanya harus diketik ulang.
        return $this->ask('Ketik ulang nama basis data untuk melanjutkan') === $tujuan;
    }
}
