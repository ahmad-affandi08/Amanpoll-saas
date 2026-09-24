<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Kompresi;

use RuntimeException;

/**
 * Hasil PemampatBerkas yang siap ditulis ke disk.
 *
 * `lokasiIsi` bisa berupa berkas sumber itu sendiri (disimpan apa adanya) atau
 * berkas sementara; panggil `bersihkan()` setelah isinya ditulis, dan berkas
 * sumber tidak pernah ikut terhapus.
 */
final class HasilPemampatan
{
    /**
     * @param  string  $lokasiIsi  Isi yang ditulis ke disk.
     * @param  string  $jenisMime  MIME berkas yang diterima pengguna saat mengunduh.
     * @param  string  $ekstensi  Ekstensi nama penyimpanan (`webp`, `csv.gz`, `pdf`).
     * @param  ?string  $lokasiThumbnail  Thumbnail WebP sementara, bila dibuat.
     * @param  list<string>  $berkasSementara  Berkas kerja yang dihapus `bersihkan()`.
     */
    public function __construct(
        public readonly string $lokasiIsi,
        public readonly string $jenisMime,
        public readonly string $ekstensi,
        public readonly MetodeKompresi $metode,
        public readonly int $ukuranAsli,
        public readonly int $ukuranTersimpan,
        public readonly ?string $lokasiThumbnail = null,
        private readonly array $berkasSementara = [],
    ) {}

    public static function apaAdanya(string $lokasiSumber, string $jenisMime, string $ekstensi, int $ukuran): self
    {
        return new self($lokasiSumber, $jenisMime, $ekstensi, MetodeKompresi::Tidak, $ukuran, $ukuran);
    }

    /** Ukuran yang diterima pengguna saat mengunduh (kolom `Berkas.UkuranByte`). */
    public function ukuranUnduhan(): int
    {
        return $this->metode === MetodeKompresi::Gzip ? $this->ukuranAsli : $this->ukuranTersimpan;
    }

    /** @return resource */
    public function bukaIsi()
    {
        $aliran = fopen($this->lokasiIsi, 'rb');
        if ($aliran === false) {
            throw new RuntimeException('Hasil kompresi tidak dapat dibaca.');
        }

        return $aliran;
    }

    /** @return resource|null */
    public function bukaThumbnail()
    {
        if ($this->lokasiThumbnail === null) {
            return null;
        }

        $aliran = fopen($this->lokasiThumbnail, 'rb');

        return $aliran === false ? null : $aliran;
    }

    public function bersihkan(): void
    {
        foreach ($this->berkasSementara as $lokasi) {
            BerkasSementara::hapus($lokasi);
        }
    }
}
