<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Cadangan\LayananCadangan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Mencadangkan basis data dan berkas unggahan, menyalinnya ke disk luar, lalu
 * memangkas yang lewat retensi (FASE 25.04, FASE 45).
 */
final class JalankanCadangan extends Command
{
    protected $signature = 'cadangan:jalankan {--tanpa-berkas : Hanya mencadangkan basis data}';

    protected $description = 'Cadangkan basis data dan berkas unggahan, salin ke disk luar, lalu pangkas cadangan lama';

    public function __construct(private readonly LayananCadangan $layanan)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $basisData = $this->layanan->cadangkanBasisData();
            $this->info('Basis data dicadangkan ke '.basename($basisData).'.');

            if (! $this->option('tanpa-berkas')) {
                $berkas = $this->layanan->cadangkanBerkas();
                $this->info($berkas === null
                    ? 'Tidak ada folder berkas untuk dicadangkan.'
                    : 'Berkas dicadangkan ke '.basename($berkas).'.');
            }
        } catch (AturanBisnisDilanggar $galat) {
            return $this->gagal('Pencadangan gagal.', $galat);
        }

        // Salinan luar dan pemangkasan dijalankan sendiri-sendiri: gagal unggah tidak boleh
        // menahan pemangkasan lokal, dan pemangkasan hanya menghapus yang sudah aman di luar.
        $berhasil = $this->salinKeLuar();

        try {
            $dipangkas = $this->layanan->pangkas();
            $this->info("Memangkas {$dipangkas} cadangan lokal yang lewat retensi.");

            if ($this->layanan->salinanLuarAktif()) {
                $dipangkasLuar = $this->layanan->pangkasLuar();
                $this->info("Memangkas {$dipangkasLuar} cadangan di disk luar yang lewat retensi.");
            }
        } catch (AturanBisnisDilanggar $galat) {
            return $this->gagal('Pemangkasan cadangan gagal.', $galat);
        }

        return $berhasil ? self::SUCCESS : self::FAILURE;
    }

    private function salinKeLuar(): bool
    {
        try {
            $hasil = $this->layanan->kirimKeLuar();
        } catch (AturanBisnisDilanggar $galat) {
            $this->gagal('Salinan cadangan ke disk luar gagal.', $galat);

            return false;
        }

        if (! $hasil['aktif']) {
            // Tidak menggagalkan jalan: hosting tanpa object storage tetap sah, tetapi tidak boleh diam.
            $pesan = 'Disk luar cadangan belum diatur (AMANPOLL_CADANGAN_DISK_LUAR); cadangan hanya tersimpan '.
                'di server yang sama dengan datanya dan hilang bersama server itu.';
            Log::warning('Cadangan tidak disalin ke luar server.', ['Pesan' => $pesan]);
            $this->warn('PERINGATAN: '.$pesan);

            return true;
        }

        foreach ($hasil['terkirim'] as $nama) {
            $this->info("Disalin ke disk luar: {$nama}.");
        }

        foreach ($hasil['gagal'] as $nama => $pesan) {
            Log::error('Salinan cadangan ke disk luar gagal.', ['Berkas' => $nama, 'Pesan' => $pesan]);
            $this->error($pesan);
        }

        return $hasil['gagal'] === [];
    }

    private function gagal(string $judul, AturanBisnisDilanggar $galat): int
    {
        // Cadangan yang gagal harus berisik; diamnya persis seperti cadangan yang berhasil.
        Log::error($judul, ['Pesan' => $galat->getMessage()]);
        $this->error($galat->getMessage());

        return self::FAILURE;
    }
}
