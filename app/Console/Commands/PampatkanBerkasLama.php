<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Kolaborasi\Application\Services\PemampatBerkasLama;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Console\Command;

/**
 * Memadatkan berkas lama lewat mesin kompresi (PRD 11.1, TASK 42.03).
 *
 * Berkas yang tersimpan sebelum mesin kompresi ada tidak disentuh otomatis;
 * perintah ini yang memadatkannya, per organisasi. `--pratinjau` menghitung
 * penghematan tanpa menulis apa pun, dan menjalankannya berulang aman karena
 * berkas yang sudah dipadatkan dilewati. Salinan lama baru dihapus sesudah
 * salinan baru tersimpan dan terverifikasi; ringkasan tiap organisasi dicatat di audit.
 */
final class PampatkanBerkasLama extends Command
{
    protected $signature = 'berkas:pampatkan
        {--organisasi= : Id atau kode satu organisasi; kosongkan untuk seluruhnya}
        {--pratinjau : Hitung penghematan tanpa mengubah apa pun}
        {--batas= : Paling banyak salinan fisik yang diperiksa per organisasi}';

    protected $description = 'Padatkan berkas lama (gambar ke WebP dan thumbnail, teks dan PDF ke gzip) dan tampilkan penghematannya';

    public function __construct(private readonly PemampatBerkasLama $pemampat)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $batas = $this->option('batas');
        if ($batas !== null && (! ctype_digit($batas) || (int) $batas < 1)) {
            $this->error('--batas harus bilangan bulat positif.');

            return self::INVALID;
        }

        $pilihan = $this->option('organisasi');
        $kueri = Organisasi::query()->orderBy('Nama');
        if (is_string($pilihan) && $pilihan !== '') {
            $kueri->where(fn ($q) => $q->where('Id', $pilihan)->orWhere('Kode', $pilihan));
        }

        $organisasi = $kueri->get();
        if ($organisasi->isEmpty()) {
            $this->error('Tidak ada organisasi yang cocok.');

            return self::FAILURE;
        }

        $pratinjau = (bool) $this->option('pratinjau');
        $baris = [];
        $total = ['Diperiksa' => 0, 'Dipadatkan' => 0, 'Thumbnail' => 0, 'Gagal' => 0, 'JumlahBaris' => 0, 'UkuranSebelum' => 0, 'UkuranSesudah' => 0];

        foreach ($organisasi as $satu) {
            $ringkasan = $this->pemampat->jalankan(
                $satu->Id,
                $pratinjau,
                $batas === null ? null : (int) $batas,
                fn (Berkas $berkas, string $pesan) => $this->warn("  {$satu->Nama}: {$berkas->NamaAsli} gagal dipadatkan ({$pesan}); berkas asli tetap."),
            );

            foreach (array_keys($total) as $kunci) {
                $total[$kunci] += $ringkasan[$kunci];
            }

            $baris[] = [
                $satu->Nama,
                $ringkasan['Diperiksa'],
                $ringkasan['Dipadatkan'],
                $ringkasan['Thumbnail'],
                $ringkasan['Gagal'],
                self::ukuran($ringkasan['UkuranSebelum']),
                self::ukuran($ringkasan['UkuranSesudah']),
                self::hemat($ringkasan['UkuranSebelum'], $ringkasan['UkuranSesudah']),
            ];
        }

        $this->table(
            ['Organisasi', 'Diperiksa', $pratinjau ? 'Akan dipadatkan' : 'Dipadatkan', 'Thumbnail', 'Gagal', 'Sebelum', 'Sesudah', 'Hemat'],
            $baris,
        );

        $hemat = self::ukuran(max($total['UkuranSebelum'] - $total['UkuranSesudah'], 0));
        $persen = self::hemat($total['UkuranSebelum'], $total['UkuranSesudah']);

        if ($pratinjau) {
            $this->info("Pratinjau: {$total['Dipadatkan']} salinan dapat dipadatkan dan {$total['Thumbnail']} gambar dibuatkan thumbnail; hemat {$hemat} ({$persen}). Tidak ada yang ditulis.");

            return self::SUCCESS;
        }

        $this->info("Memadatkan {$total['Dipadatkan']} salinan ({$total['JumlahBaris']} baris berkas) dan membuat {$total['Thumbnail']} thumbnail; hemat {$hemat} ({$persen}).");

        if ($total['Gagal'] > 0) {
            $this->warn("{$total['Gagal']} salinan gagal dipadatkan dan dibiarkan apa adanya; rinciannya ada di log.");
        }

        return self::SUCCESS;
    }

    private static function ukuran(int $byte): string
    {
        $satuan = ['B', 'KB', 'MB', 'GB', 'TB'];
        $nilai = (float) $byte;
        $indeks = 0;

        while ($nilai >= 1024 && $indeks < count($satuan) - 1) {
            $nilai /= 1024;
            $indeks++;
        }

        return ($indeks === 0 ? (string) $byte : number_format($nilai, 1, ',', '.')).' '.$satuan[$indeks];
    }

    private static function hemat(int $sebelum, int $sesudah): string
    {
        if ($sebelum <= 0) {
            return '0%';
        }

        return number_format(max($sebelum - $sesudah, 0) / $sebelum * 100, 1, ',', '.').'%';
    }
}
