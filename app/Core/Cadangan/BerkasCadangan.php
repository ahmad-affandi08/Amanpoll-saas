<?php

declare(strict_types=1);

namespace App\Core\Cadangan;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

/**
 * Tempat dan penamaan berkas cadangan lokal; satu-satunya yang tahu di mana cadangan disimpan (FASE 25.04).
 *
 * Folder lokal ini adalah tempat kerja: dump dan arsip dibuat di sini lebih
 * dulu, lalu disalin ke disk luar oleh SalinanLuarCadangan (FASE 45).
 */
final class BerkasCadangan
{
    /**
     * Nama berkas menyimpan jenis dan waktunya, supaya urutan pemulihan terbaca tanpa membuka isinya.
     *
     * `berkas-*.tar` arsip penuh, `berkas-selisih-*.tar` hanya berkas yang
     * berubah sejak arsip penuh sebelumnya; `berkas-*.tar.gz` arsip penuh lama
     * (sebelum FASE 45) yang tetap dikenali untuk pemulihan dan pemangkasan.
     */
    private const POLA = '/^(basisdata|berkas|berkas-selisih)-(\d{8}-\d{6})\.(sql\.gz|tar\.gz|tar)$/';

    public const JENIS_BASIS_DATA = 'basisdata';

    public const JENIS_BERKAS_PENUH = 'berkas';

    public const JENIS_BERKAS_SELISIH = 'berkas-selisih';

    /**
     * Folder kerja lokal.
     *
     * Disk-nya harus berdriver `local`. Dulu `AMANPOLL_CADANGAN_DISK=s3` diam-diam
     * tetap menulis ke storage lokal karena disk s3 tidak punya `root`; kini itu
     * ditolak terang-terangan dan salinan luar diatur lewat `disk_luar`.
     */
    public function folder(): string
    {
        $disk = (string) config('amanpoll.cadangan.disk', 'local');
        $driver = config("filesystems.disks.{$disk}.driver");

        if ($driver !== 'local') {
            throw new AturanBisnisDilanggar(
                "Disk kerja cadangan '{$disk}' harus berdriver local. ".
                'Salinan ke S3/R2 diatur lewat AMANPOLL_CADANGAN_DISK_LUAR, bukan AMANPOLL_CADANGAN_DISK.',
            );
        }

        $akar = (string) config("filesystems.disks.{$disk}.root", storage_path('app/private'));
        $folder = (string) config('amanpoll.cadangan.folder', 'cadangan');

        return rtrim($akar, '/').'/'.trim($folder, '/');
    }

    public function jalurBaru(string $jenis, string $ekstensi, CarbonImmutable $pada): string
    {
        $folder = $this->folder();
        File::ensureDirectoryExists($folder);

        return $folder.'/'.$jenis.'-'.$pada->format('Ymd-His').'.'.$ekstensi;
    }

    /**
     * Jenis dan waktu sebuah nama berkas cadangan, atau null bila namanya bukan pola cadangan.
     *
     * Waktu dibaca dari nama berkasnya, bukan dari mtime: berkas yang disalin
     * atau dipindahkan kehilangan mtime-nya, sedangkan namanya ikut.
     *
     * @return array{jenis: string, dibuat: CarbonImmutable}|null
     */
    public function urai(string $nama): ?array
    {
        if (preg_match(self::POLA, $nama, $cocok) !== 1) {
            return null;
        }

        $dibuat = CarbonImmutable::createFromFormat('Ymd-His', $cocok[2]);

        if (! $dibuat instanceof CarbonImmutable) {
            return null;
        }

        return ['jenis' => $cocok[1], 'dibuat' => $dibuat];
    }

    /**
     * Cadangan lokal yang ada, terbaru lebih dulu.
     *
     * @return list<array{jalur: string, nama: string, jenis: string, ukuran: int, dibuat: CarbonImmutable}>
     */
    public function semua(): array
    {
        $folder = $this->folder();

        if (! File::isDirectory($folder)) {
            return [];
        }

        $hasil = [];

        foreach (File::files($folder) as $berkas) {
            $nama = $berkas->getFilename();
            $urai = $this->urai($nama);

            if ($urai === null) {
                continue;
            }

            $hasil[] = [
                'jalur' => (string) $berkas->getRealPath(),
                'nama' => $nama,
                'jenis' => $urai['jenis'],
                'ukuran' => (int) $berkas->getSize(),
                'dibuat' => $urai['dibuat'],
            ];
        }

        usort($hasil, fn (array $a, array $b): int => $b['dibuat'] <=> $a['dibuat']);

        return $hasil;
    }

    /** Cadangan basis data lokal terbaru, atau null bila belum ada satu pun. */
    public function basisDataTerbaru(): ?string
    {
        foreach ($this->semua() as $satu) {
            if ($satu['jenis'] === self::JENIS_BASIS_DATA) {
                return $satu['jalur'];
            }
        }

        return null;
    }

    /**
     * Arsip berkas penuh lokal terbaru; dasar arsip selisih berikutnya.
     *
     * @return array{jalur: string, nama: string, jenis: string, ukuran: int, dibuat: CarbonImmutable}|null
     */
    public function berkasPenuhTerbaru(): ?array
    {
        foreach ($this->semua() as $satu) {
            if ($satu['jenis'] === self::JENIS_BERKAS_PENUH) {
                return $satu;
            }
        }

        return null;
    }

    public function hapus(string $jalur): void
    {
        if (File::exists($jalur)) {
            File::delete($jalur);
        }
    }
}
