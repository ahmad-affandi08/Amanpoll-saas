<?php

declare(strict_types=1);

namespace App\Core\Cadangan;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Pencadangan basis data dan berkas unggahan (FASE 25.04).
 *
 * Seluruh kegagalan diangkat sebagai pengecualian, tidak pernah menghasilkan
 * berkas kosong: cadangan yang gagal diam-diam baru ketahuan tidak berguna pada
 * hari ia dibutuhkan, dan pada hari itu tidak ada lagi yang bisa diperbuat.
 */
final class LayananCadangan
{
    /** Potongan awal dump yang diperiksa keutuhannya, dalam byte setelah dekompresi. */
    private const BATAS_PERIKSA_BYTE = 1_048_576;

    public function __construct(private readonly BerkasCadangan $berkas) {}

    /** Mencadangkan basis data; kembaliannya jalur absolut berkas dump terkompresi. */
    public function cadangkanBasisData(?CarbonImmutable $pada = null): string
    {
        $pada ??= CarbonImmutable::now();
        $tujuan = $this->berkas->jalurBaru('basisdata', 'sql.gz', $pada);

        $proses = Process::fromShellCommandline(
            $this->perintahDump().' | gzip > '.escapeshellarg($tujuan),
        );
        $proses->setTimeout($this->batasDetik());
        $proses->run();

        if (! $proses->isSuccessful()) {
            $this->berkas->hapus($tujuan);

            throw new AturanBisnisDilanggar(
                'Pencadangan basis data gagal: '.trim($proses->getErrorOutput() ?: $proses->getOutput()),
            );
        }

        $this->pastikanDumpBerisiSkema($tujuan);

        return $tujuan;
    }

    /** Mencadangkan berkas unggahan; kembaliannya jalur absolut arsipnya, atau null bila tidak ada apa pun. */
    public function cadangkanBerkas(?CarbonImmutable $pada = null): ?string
    {
        $pada ??= CarbonImmutable::now();
        $sumber = $this->folderSumber();

        if ($sumber === []) {
            return null;
        }

        $tujuan = $this->berkas->jalurBaru('berkas', 'tar.gz', $pada);

        $argumen = ['tar', '-czf', $tujuan];
        foreach ($this->polaDikecualikan($sumber) as $pola) {
            $argumen[] = '--exclude='.$pola;
        }
        array_push($argumen, '-C', storage_path('app'));
        foreach ($sumber as $folder) {
            $argumen[] = $folder;
        }

        $proses = new Process($argumen);
        $proses->setTimeout($this->batasDetik());
        $proses->run();

        if (! $proses->isSuccessful()) {
            $this->berkas->hapus($tujuan);

            throw new AturanBisnisDilanggar(
                'Pencadangan berkas gagal: '.trim($proses->getErrorOutput() ?: $proses->getOutput()),
            );
        }

        $this->pastikanTidakKosong($tujuan, 'berkas');

        return $tujuan;
    }

    /**
     * Memulihkan basis data dari satu berkas dump.
     *
     * Ini menimpa seluruh isi basis data tujuan. Pemanggilnya yang memastikan
     * itu memang yang dikehendaki; layanan ini hanya menolak berkas yang tidak
     * ada atau kosong.
     */
    public function pulihkanBasisData(string $jalurDump, ?string $namaBasisData = null): void
    {
        if (! File::exists($jalurDump)) {
            throw new AturanBisnisDilanggar("Berkas cadangan {$jalurDump} tidak ditemukan.");
        }

        if (File::size($jalurDump) === 0) {
            throw new AturanBisnisDilanggar("Berkas cadangan {$jalurDump} kosong.");
        }

        $baca = str_ends_with($jalurDump, '.gz')
            ? 'gunzip -c '.escapeshellarg($jalurDump)
            : 'cat '.escapeshellarg($jalurDump);

        $proses = Process::fromShellCommandline(
            $baca.' | '.$this->perintahMasuk($namaBasisData),
        );
        $proses->setTimeout($this->batasDetik());
        $proses->run();

        if (! $proses->isSuccessful()) {
            throw new AturanBisnisDilanggar(
                'Pemulihan basis data gagal: '.trim($proses->getErrorOutput() ?: $proses->getOutput()),
            );
        }
    }

    /** Menghapus cadangan yang lewat masa retensinya; kembaliannya jumlah berkas yang dihapus. */
    public function pangkas(?CarbonImmutable $pada = null): int
    {
        $pada ??= CarbonImmutable::now();
        $hari = (int) config('amanpoll.cadangan.retensi_hari', 14);
        $batas = $pada->subDays(max($hari, 1));
        $dihapus = 0;

        foreach ($this->berkas->semua() as $satu) {
            if ($satu['dibuat']->lessThan($batas)) {
                $this->berkas->hapus($satu['jalur']);
                $dihapus++;
            }
        }

        return $dihapus;
    }

    /**
     * @return list<array{jalur: string, nama: string, ukuran: int, dibuat: CarbonImmutable}>
     */
    public function daftar(): array
    {
        return $this->berkas->semua();
    }

    /**
     * Folder di bawah storage/app yang benar-benar ada; yang belum dibuat dilewati tanpa menggagalkan.
     *
     * @return list<string>
     */
    private function folderSumber(): array
    {
        /** @var list<string> $dikonfigurasi */
        $dikonfigurasi = (array) config('amanpoll.cadangan.folder_berkas', []);

        return array_values(array_filter(
            $dikonfigurasi,
            fn (string $folder): bool => File::isDirectory(storage_path('app/'.$folder)),
        ));
    }

    /**
     * Folder cadangan sendiri bila ia berada di dalam folder sumber (bawaan:
     * `private/cadangan`). Tanpa pengecualian ini setiap arsip berkas memuat
     * seluruh cadangan sebelumnya, sehingga ukurannya berlipat tiap malam.
     *
     * @param  list<string>  $sumber
     * @return list<string>
     */
    private function polaDikecualikan(array $sumber): array
    {
        $akar = rtrim(storage_path('app'), '/').'/';
        $folderCadangan = rtrim($this->berkas->folder(), '/');

        if (! str_starts_with($folderCadangan, $akar)) {
            return [];
        }

        $relatif = substr($folderCadangan, strlen($akar));

        foreach ($sumber as $folder) {
            if ($relatif === $folder || str_starts_with($relatif, rtrim($folder, '/').'/')) {
                return [$relatif];
            }
        }

        return [];
    }

    private function perintahDump(): string
    {
        $koneksi = $this->koneksi();

        return implode(' ', [
            escapeshellarg($this->biner('mysqldump')),
            '--host='.escapeshellarg((string) $koneksi['host']),
            '--port='.escapeshellarg((string) $koneksi['port']),
            '--user='.escapeshellarg((string) $koneksi['username']),
            '--password='.escapeshellarg((string) $koneksi['password']),
            // Transaksi tunggal menjaga dump tetap konsisten tanpa mengunci tabel yang sedang dipakai.
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            escapeshellarg((string) $koneksi['database']),
        ]);
    }

    private function perintahMasuk(?string $namaBasisData): string
    {
        $koneksi = $this->koneksi();

        return implode(' ', [
            escapeshellarg($this->biner('mysql')),
            '--host='.escapeshellarg((string) $koneksi['host']),
            '--port='.escapeshellarg((string) $koneksi['port']),
            '--user='.escapeshellarg((string) $koneksi['username']),
            '--password='.escapeshellarg((string) $koneksi['password']),
            '--default-character-set=utf8mb4',
            escapeshellarg($namaBasisData ?? (string) $koneksi['database']),
        ]);
    }

    /** @return array<string, mixed> */
    private function koneksi(): array
    {
        $nama = (string) config('database.default');
        /** @var array<string, mixed>|null $koneksi */
        $koneksi = config("database.connections.{$nama}");

        if ($koneksi === null || ($koneksi['driver'] ?? null) !== 'mysql') {
            throw new AturanBisnisDilanggar(
                'Pencadangan hanya mendukung koneksi MySQL/MariaDB.',
            );
        }

        return $koneksi;
    }

    /** Biner yang tidak ada lebih baik ketahuan sekarang daripada saat pemulihan dibutuhkan. */
    private function biner(string $kunci): string
    {
        $jalur = (string) config("amanpoll.cadangan.{$kunci}", $kunci);

        $cek = new Process(['sh', '-c', 'command -v '.escapeshellarg($jalur)]);
        $cek->run();

        if (! $cek->isSuccessful()) {
            throw new AturanBisnisDilanggar(
                "Biner {$kunci} tidak ditemukan pada '{$jalur}'. ".
                'Setel AMANPOLL_CADANGAN_'.mb_strtoupper($kunci).' ke jalur yang benar.',
            );
        }

        return $jalur;
    }

    private function pastikanTidakKosong(string $jalur, string $jenis): void
    {
        if (! File::exists($jalur) || File::size($jalur) === 0) {
            $this->berkas->hapus($jalur);

            throw new AturanBisnisDilanggar("Cadangan {$jenis} menghasilkan berkas kosong.");
        }
    }

    /**
     * Memastikan dump benar-benar berisi skema, bukan sekadar berkas yang ada.
     *
     * Memeriksa ukuran saja tidak cukup: gzip atas masukan kosong tetap
     * menghasilkan berkas belasan byte, sehingga dump yang tidak mengeluarkan
     * apa pun tetap lolos. Yang dicari adalah pernyataan pembuatan tabel, yang
     * pasti ada pada dump sehat dan pasti tidak ada pada dump gagal.
     */
    private function pastikanDumpBerisiSkema(string $jalur): void
    {
        $this->pastikanTidakKosong($jalur, 'basis data');

        $berkas = gzopen($jalur, 'rb');

        if ($berkas === false) {
            $this->berkas->hapus($jalur);

            throw new AturanBisnisDilanggar('Cadangan basis data tidak dapat dibaca kembali.');
        }

        // Cukup awal berkas; mysqldump menaruh CREATE TABLE tabel pertama jauh sebelum batas ini.
        $awal = (string) gzread($berkas, self::BATAS_PERIKSA_BYTE);
        gzclose($berkas);

        if (! str_contains($awal, 'CREATE TABLE')) {
            $this->berkas->hapus($jalur);

            throw new AturanBisnisDilanggar(
                'Cadangan basis data tidak memuat satu pun definisi tabel; dumpnya tidak sehat.',
            );
        }
    }

    private function batasDetik(): float
    {
        return (float) config('amanpoll.cadangan.batas_detik', 600);
    }
}
