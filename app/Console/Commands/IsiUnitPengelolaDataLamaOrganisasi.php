<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Pemeliharaan\Application\Actions\IsiUnitPengelolaDataLama;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mengisi unit pengelola yang kosong pada rencana, keluhan, dan perintah kerja lama (PRD 8.21, TASK 40.06).
 *
 * Migrasi FASE 40 sengaja tidak mengisi apa pun; organisasi yang baru
 * menandai unit pengelolanya menjalankan perintah ini sesudah aset, kategori
 * keluhan, dan gudangnya diberi unit pengelola. Perintah ini selalu
 * menampilkan rencananya lebih dulu, `--pratinjau` berhenti di situ, dan
 * tanpa `--paksa` ia bertanya sebelum menulis. Nilai yang sudah terisi tidak
 * pernah ditimpa, jadi aman dijalankan berulang; setiap pengisian tercatat di audit.
 */
final class IsiUnitPengelolaDataLamaOrganisasi extends Command
{
    protected $signature = 'pemeliharaan:isi-unit-pengelola
        {--organisasi= : Id satu organisasi; kosongkan untuk seluruhnya}
        {--pratinjau : Tampilkan rencana pengisian tanpa mengubah apa pun}
        {--paksa : Lewati konfirmasi, untuk penerapan tidak interaktif}';

    protected $description = 'Isi unit pengelola yang kosong pada rencana, keluhan, dan perintah kerja dari kategori dan aset';

    public function __construct(private readonly IsiUnitPengelolaDataLama $aksi)
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

        $akanDiisi = [];
        $jumlahBaris = 0;

        foreach ($organisasi as $satu) {
            $rencana = $this->aksi->rencana($satu->Id);
            $jumlah = array_sum(array_column($rencana, 'Diisi'));

            $this->tampilkan($satu, $rencana, 'akan diisi');

            if ($jumlah > 0) {
                $akanDiisi[] = $satu;
                $jumlahBaris += $jumlah;
            }
        }

        if ($akanDiisi === []) {
            $this->info('Tidak ada unit pengelola kosong yang dapat diturunkan dari kategori atau aset.');

            return self::SUCCESS;
        }

        if ($this->option('pratinjau')) {
            $this->info("Pratinjau: {$jumlahBaris} baris pada ".count($akanDiisi).' organisasi akan diisi. Tidak ada yang ditulis.');

            return self::SUCCESS;
        }

        if (! $this->option('paksa')
            && ! $this->confirm("Isi unit pengelola pada {$jumlahBaris} baris di ".count($akanDiisi).' organisasi?', false)) {
            $this->warn('Pengisian dibatalkan.');

            return self::FAILURE;
        }

        $terisi = 0;

        foreach ($akanDiisi as $satu) {
            $hasil = $this->aksi->jalankan($satu->Id);
            $terisi += array_sum(array_column($hasil, 'Diisi'));
            $this->tampilkan($satu, $hasil, 'diisi');
        }

        $this->info("Mengisi unit pengelola pada {$terisi} baris di ".count($akanDiisi).' organisasi.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{Kosong: int, Diisi: int, PerUnit: array<string, int>}>  $ringkasan
     */
    private function tampilkan(Organisasi $organisasi, array $ringkasan, string $kata): void
    {
        $unitIds = [];

        foreach ($ringkasan as $satu) {
            foreach (array_keys($satu['PerUnit']) as $unitId) {
                $unitIds[$unitId] = true;
            }
        }

        $namaUnit = $unitIds === [] ? collect() : DB::table('UnitOrganisasi')
            ->where('OrganisasiId', $organisasi->Id)
            ->whereIn('Id', array_keys($unitIds))
            ->pluck('Nama', 'Id');

        foreach ($ringkasan as $tabel => $satu) {
            if ($satu['Kosong'] === 0) {
                continue;
            }

            $rincian = [];

            foreach ($satu['PerUnit'] as $unitId => $jumlah) {
                $rincian[] = ($namaUnit[$unitId] ?? $unitId).' '.$jumlah;
            }

            sort($rincian, SORT_NATURAL | SORT_FLAG_CASE);
            $sisa = $satu['Kosong'] - $satu['Diisi'];

            $this->line(sprintf(
                '  %s: %s %d kosong, %d %s%s, %d tetap kosong',
                $organisasi->Nama,
                $tabel,
                $satu['Kosong'],
                $satu['Diisi'],
                $kata,
                $rincian === [] ? '' : ' ('.implode(', ', $rincian).')',
                $sisa,
            ));
        }
    }
}
