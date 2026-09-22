<?php

declare(strict_types=1);

namespace App\Core\Cadangan;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

/** Tempat dan penamaan berkas cadangan; satu-satunya yang tahu di mana cadangan disimpan (FASE 25.04). */
final class BerkasCadangan
{
    /** Nama berkas menyimpan jenis dan waktunya, supaya urutan pemulihan terbaca tanpa membuka isinya. */
    private const POLA = '/^(basisdata|berkas)-(\d{8}-\d{6})\.(sql\.gz|tar\.gz)$/';

    public function folder(): string
    {
        $disk = (string) config('amanpoll.cadangan.disk', 'local');
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
     * Cadangan yang ada, terbaru lebih dulu.
     *
     * Waktu dibaca dari nama berkasnya, bukan dari mtime: berkas yang disalin
     * atau dipindahkan kehilangan mtime-nya, sedangkan namanya ikut.
     *
     * @return list<array{jalur: string, nama: string, ukuran: int, dibuat: CarbonImmutable}>
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

            if (preg_match(self::POLA, $nama, $cocok) !== 1) {
                continue;
            }

            $hasil[] = [
                'jalur' => $berkas->getRealPath(),
                'nama' => $nama,
                'ukuran' => $berkas->getSize(),
                'dibuat' => CarbonImmutable::createFromFormat('Ymd-His', $cocok[2]),
            ];
        }

        usort($hasil, fn (array $a, array $b): int => $b['dibuat'] <=> $a['dibuat']);

        return $hasil;
    }

    /** Cadangan basis data terbaru, atau null bila belum ada satu pun. */
    public function basisDataTerbaru(): ?string
    {
        foreach ($this->semua() as $satu) {
            if (str_starts_with($satu['nama'], 'basisdata-')) {
                return $satu['jalur'];
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
