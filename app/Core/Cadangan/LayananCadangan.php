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

    public function __construct(
        private readonly BerkasCadangan $berkas,
        private readonly SalinanLuarCadangan $luar,
    ) {}

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

    /**
     * Mencadangkan berkas unggahan; kembaliannya jalur absolut arsipnya, atau null bila tidak ada apa pun.
     *
     * Arsip penuh dibuat bila arsip penuh terakhir sudah berumur
     * `berkas_penuh_tiap_hari`; di antaranya hanya arsip selisih berisi berkas
     * yang berubah sejak arsip penuh itu. Pemulihan cukup dua langkah: arsip
     * penuh lalu arsip selisih terbaru — tidak ada rantai inkremental yang
     * satu mata rantainya hilang merusak semuanya.
     *
     * Arsipnya tar polos, tanpa gzip: unggahan tenant sebagian besar foto dan
     * PDF yang sudah terkompresi, jadi gzip hanya membakar CPU shared hosting.
     */
    public function cadangkanBerkas(?CarbonImmutable $pada = null): ?string
    {
        $pada ??= CarbonImmutable::now();
        $sumber = $this->folderSumber();

        if ($sumber === []) {
            return null;
        }

        $dasar = $this->dasarSelisih($pada);
        $jenis = $dasar === null ? BerkasCadangan::JENIS_BERKAS_PENUH : BerkasCadangan::JENIS_BERKAS_SELISIH;
        $tujuan = $this->berkas->jalurBaru($jenis, 'tar', $pada);

        $argumen = ['tar', '-cf', $tujuan];
        if ($dasar !== null) {
            // Waktu di nama arsip penuh adalah saat ia MULAI dibuat; berkas yang berubah selama itu ikut di sini.
            $argumen[] = '--newer-mtime=@'.$dasar->getTimestamp();
        }
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
        $jalurDump = $this->jalurLokalAtauAmbilDariLuar($jalurDump);

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

    /**
     * Menyalin setiap cadangan lokal yang belum ada (utuh) di disk luar, terbaru lebih dulu.
     *
     * Yang gagal kemarin ikut dicoba lagi hari ini, jadi satu malam jaringan
     * putus tidak meninggalkan lubang permanen di salinan luar. Kegagalan satu
     * berkas tidak menghentikan yang lain.
     *
     * @return array{aktif: bool, terkirim: list<string>, gagal: array<string, string>}
     */
    public function kirimKeLuar(): array
    {
        if (! $this->luar->aktif()) {
            return ['aktif' => false, 'terkirim' => [], 'gagal' => []];
        }

        $diLuar = $this->ukuranDiLuar();
        $terkirim = [];
        $gagal = [];

        foreach ($this->berkas->semua() as $satu) {
            if (($diLuar[$satu['nama']] ?? null) === $satu['ukuran']) {
                continue;
            }

            try {
                $this->luar->kirim($satu['jalur']);
                $terkirim[] = $satu['nama'];
            } catch (AturanBisnisDilanggar $galat) {
                $gagal[$satu['nama']] = $galat->getMessage();
            }
        }

        return ['aktif' => true, 'terkirim' => $terkirim, 'gagal' => $gagal];
    }

    /**
     * Memangkas cadangan lokal; kembaliannya jumlah berkas yang dihapus.
     *
     * Tanpa disk luar, retensinya `retensi_hari`. Dengan disk luar, salinan
     * lokal yang sudah utuh di luar dipangkas setelah `retensi_lokal_hari`,
     * sedangkan yang belum berhasil disalin tetap ditahan sampai
     * `retensi_hari` — unggahan yang gagal tidak pernah menghapus satu-satunya
     * salinan.
     */
    public function pangkas(?CarbonImmutable $pada = null): int
    {
        $pada ??= CarbonImmutable::now();
        $lokal = $this->berkas->semua();

        $hapus = $this->lewatRetensi($lokal, $pada->subDays($this->hari('retensi_hari', 14)));

        if ($this->luar->aktif()) {
            try {
                $diLuar = $this->ukuranDiLuar();
            } catch (AturanBisnisDilanggar) {
                // Disk luar tak terjangkau sudah dilaporkan kirimKeLuar(); tanpa bukti salinan luar, tahan semuanya.
                $diLuar = [];
            }

            $ukuranLokal = array_column($lokal, 'ukuran', 'nama');

            foreach ($this->lewatRetensi($lokal, $pada->subDays($this->hari('retensi_lokal_hari', 2))) as $nama) {
                if (($diLuar[$nama] ?? null) === $ukuranLokal[$nama]) {
                    $hapus[] = $nama;
                }
            }
        }

        $hapus = array_unique($hapus);

        foreach ($lokal as $satu) {
            if (in_array($satu['nama'], $hapus, true)) {
                $this->berkas->hapus($satu['jalur']);
            }
        }

        return count($hapus);
    }

    /** Memangkas salinan di disk luar menurut `retensi_luar_hari`; kembaliannya jumlah berkas yang dihapus. */
    public function pangkasLuar(?CarbonImmutable $pada = null): int
    {
        if (! $this->luar->aktif()) {
            return 0;
        }

        $pada ??= CarbonImmutable::now();
        $hapus = $this->lewatRetensi($this->luar->daftar(), $pada->subDays($this->hari('retensi_luar_hari', 30)));

        foreach ($hapus as $nama) {
            $this->luar->hapus($nama);
        }

        return count($hapus);
    }

    public function salinanLuarAktif(): bool
    {
        return $this->luar->aktif();
    }

    /**
     * @return list<array{jalur: string, nama: string, jenis: string, ukuran: int, dibuat: CarbonImmutable}>
     */
    public function daftar(): array
    {
        return $this->berkas->semua();
    }

    /**
     * @return list<array{nama: string, jenis: string, ukuran: int, dibuat: CarbonImmutable}>
     */
    public function daftarLuar(): array
    {
        return $this->luar->daftar();
    }

    /** Nama cadangan basis data terbaru di disk luar, atau null. */
    public function basisDataTerbaruDiLuar(): ?string
    {
        foreach ($this->luar->daftar() as $satu) {
            if ($satu['jenis'] === BerkasCadangan::JENIS_BASIS_DATA) {
                return $satu['nama'];
            }
        }

        return null;
    }

    /** Mengunduh satu cadangan dari disk luar ke folder kerja lokal; kembaliannya jalur lokalnya. */
    public function ambilDariLuar(string $nama): string
    {
        return $this->luar->ambil(basename($nama));
    }

    /**
     * Nama cadangan yang lewat retensi dan boleh dihapus.
     *
     * Tiga pengecualian menjaga agar pemangkasan tidak pernah menyisakan
     * cadangan yang tidak dapat dipulihkan:
     * - cadangan basis data terbaru selalu disimpan, sekalipun sudah tua
     *   (pencadangan yang berhenti berminggu-minggu tidak boleh berakhir tanpa
     *   satu dump pun);
     * - arsip berkas penuh terbaru beserta selisih sesudahnya selalu disimpan;
     * - arsip penuh yang menjadi dasar selisih yang masih disimpan ikut disimpan,
     *   karena selisih tanpa dasarnya tidak berguna.
     *
     * @param  list<array{nama: string, jenis: string, dibuat: CarbonImmutable}>  $daftar
     * @return list<string>
     */
    private function lewatRetensi(array $daftar, CarbonImmutable $batas): array
    {
        usort($daftar, fn (array $a, array $b): int => $a['dibuat'] <=> $b['dibuat']);

        $simpan = [];
        $dasarDari = [];
        $penuhTerbaru = null;
        $basisDataTerbaru = null;

        foreach ($daftar as $satu) {
            if ($satu['dibuat']->greaterThanOrEqualTo($batas)) {
                $simpan[$satu['nama']] = true;
            }

            if ($satu['jenis'] === BerkasCadangan::JENIS_BASIS_DATA) {
                $basisDataTerbaru = $satu['nama'];
            } elseif ($satu['jenis'] === BerkasCadangan::JENIS_BERKAS_PENUH) {
                $penuhTerbaru = $satu['nama'];
            } else {
                $dasarDari[$satu['nama']] = $penuhTerbaru;
            }
        }

        if ($basisDataTerbaru !== null) {
            $simpan[$basisDataTerbaru] = true;
        }

        if ($penuhTerbaru !== null) {
            $simpan[$penuhTerbaru] = true;
        }

        foreach ($dasarDari as $selisih => $dasar) {
            if ($dasar !== null && $dasar === $penuhTerbaru) {
                $simpan[$selisih] = true;
            }
        }

        foreach ($dasarDari as $selisih => $dasar) {
            if ($dasar !== null && isset($simpan[$selisih])) {
                $simpan[$dasar] = true;
            }
        }

        $hapus = [];
        foreach ($daftar as $satu) {
            if (! isset($simpan[$satu['nama']])) {
                $hapus[] = $satu['nama'];
            }
        }

        return $hapus;
    }

    /** @return array<string, int> nama berkas di disk luar => ukurannya */
    private function ukuranDiLuar(): array
    {
        return array_column($this->luar->daftar(), 'ukuran', 'nama');
    }

    private function hari(string $kunci, int $bawaan): int
    {
        return max((int) config("amanpoll.cadangan.{$kunci}", $bawaan), 1);
    }

    /**
     * Waktu arsip penuh yang menjadi dasar arsip selisih, atau null bila kali ini harus arsip penuh.
     *
     * Satu jam kelonggaran supaya jadwal harian yang mulai beberapa detik lebih
     * awal dari pekan lalu tidak menunda arsip penuh satu hari.
     */
    private function dasarSelisih(CarbonImmutable $pada): ?CarbonImmutable
    {
        $hari = (int) config('amanpoll.cadangan.berkas_penuh_tiap_hari', 7);
        $penuh = $this->berkas->berkasPenuhTerbaru();

        if ($hari <= 1 || $penuh === null) {
            return null;
        }

        return $penuh['dibuat']->greaterThan($pada->subDays($hari)->addHour()) ? $penuh['dibuat'] : null;
    }

    /**
     * Jalur yang tidak ada di lokal tetapi bernama cadangan diambil dari disk
     * luar lebih dulu — pemulihan setelah server hilang dimulai dari sini.
     */
    private function jalurLokalAtauAmbilDariLuar(string $jalurAtauNama): string
    {
        if (File::exists($jalurAtauNama) || ! $this->luar->aktif()) {
            return $jalurAtauNama;
        }

        $nama = basename($jalurAtauNama);

        return $this->berkas->urai($nama) === null ? $jalurAtauNama : $this->luar->ambil($nama);
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
