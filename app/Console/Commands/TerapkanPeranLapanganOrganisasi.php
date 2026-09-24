<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Platform\Application\Actions\TerapkanKatalogPeranLapangan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Console\Command;

/**
 * Menerapkan katalog peran lapangan FASE 39 ke organisasi yang sudah berdiri (PRD 8.20).
 *
 * Peran bawaan `TEKNISI` dan `PELAPOR` yang dipasang sebelum FASE 39 masih
 * memegang izin Kelola atas seluruh tiket dan keluhan, dan belum bertanda
 * Tampilan Lapangan. Perintah ini mencabut izin itu dan mengisi penandanya.
 *
 * Tidak ada yang berubah tanpa diminta: perintah ini selalu menampilkan
 * rencananya lebih dulu, `--pratinjau` berhenti di situ, dan tanpa `--paksa`
 * ia bertanya sebelum menulis. Setiap peran yang berubah tercatat di audit.
 * Aman dijalankan berulang; organisasi yang sudah selaras dilewati.
 */
final class TerapkanPeranLapanganOrganisasi extends Command
{
    protected $signature = 'platform:terapkan-peran-lapangan
        {--organisasi= : Id satu organisasi; kosongkan untuk seluruhnya}
        {--pratinjau : Tampilkan rencana perubahan tanpa mengubah apa pun}
        {--paksa : Lewati konfirmasi, untuk penerapan tidak interaktif}';

    protected $description = 'Cabut izin Kelola dari peran Teknisi dan Pelapor bawaan serta tandai Tampilan Lapangan-nya';

    public function __construct(private readonly TerapkanKatalogPeranLapangan $aksi)
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

        $akanDiubah = [];
        $jumlahPeran = 0;

        foreach ($organisasi as $satu) {
            $rencana = $this->aksi->rencana($satu->Id);
            $berubah = array_values(array_filter(
                $rencana,
                fn (array $baris): bool => $baris['IzinDicabut'] !== [] || $baris['TampilanSebelum'] !== $baris['TampilanSesudah'],
            ));

            foreach ($rencana as $baris) {
                $this->line("  {$satu->Nama}: ".$this->uraian($baris));
            }

            if ($berubah !== []) {
                $akanDiubah[] = $satu;
                $jumlahPeran += count($berubah);
            }
        }

        if ($akanDiubah === []) {
            $this->info('Seluruh peran lapangan bawaan sudah selaras dengan katalog.');

            return self::SUCCESS;
        }

        if ($this->option('pratinjau')) {
            $this->info("Pratinjau: {$jumlahPeran} peran pada ".count($akanDiubah).' organisasi akan diubah. Tidak ada yang ditulis.');

            return self::SUCCESS;
        }

        if (! $this->option('paksa')
            && ! $this->confirm("Terapkan perubahan pada {$jumlahPeran} peran di ".count($akanDiubah).' organisasi?', false)) {
            $this->warn('Penerapan dibatalkan.');

            return self::FAILURE;
        }

        foreach ($akanDiubah as $satu) {
            $this->aksi->jalankan($satu->Id);
        }

        $this->info("Menerapkan katalog peran lapangan pada {$jumlahPeran} peran di ".count($akanDiubah).' organisasi.');

        return self::SUCCESS;
    }

    /**
     * @param  array{PeranId: string, Kode: string, Nama: string, IzinDicabut: list<string>, TampilanSebelum: string|null, TampilanSesudah: string, TampilanDibiarkan: bool}  $baris
     */
    private function uraian(array $baris): string
    {
        $bagian = [];

        if ($baris['IzinDicabut'] !== []) {
            $bagian[] = 'cabut '.implode(', ', $baris['IzinDicabut']);
        }

        if ($baris['TampilanSebelum'] !== $baris['TampilanSesudah']) {
            $bagian[] = "tandai Tampilan Lapangan {$baris['TampilanSesudah']}";
        }

        if ($baris['TampilanDibiarkan']) {
            $bagian[] = "Tampilan Lapangan {$baris['TampilanSebelum']} pilihan organisasi dibiarkan";
        }

        return "{$baris['Kode']} ({$baris['Nama']}) ".implode('; ', $bagian);
    }
}
