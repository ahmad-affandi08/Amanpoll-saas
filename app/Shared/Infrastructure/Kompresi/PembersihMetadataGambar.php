<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Kompresi;

use RuntimeException;

/**
 * Membuang metadata (EXIF termasuk GPS, XMP, IPTC, komentar) dari gambar yang
 * disimpan tanpa dikodekan ulang, tanpa menyentuh data pikselnya.
 *
 * Dipakai saat WebP hasil kode ulang tidak lebih kecil dari aslinya (PRD 11.1):
 * aslinya yang disimpan, tetapi lokasi GPS tetap tidak boleh ikut. Profil warna
 * (ICC) dan penanda JFIF/Adobe dipertahankan karena memengaruhi tampilan warna.
 * Gambar ber-Orientation selain 1 tidak pernah lewat sini: ia selalu dikodekan
 * ulang, karena membuang EXIF-nya akan memutar gambar.
 */
final class PembersihMetadataGambar
{
    private const CHUNK_PNG_DIBUANG = ['tEXt', 'zTXt', 'iTXt', 'eXIf', 'tIME'];

    private const CHUNK_WEBP_DIBUANG = ['EXIF', 'XMP '];

    /** @return ?string Lokasi salinan bersih (sementara), atau null bila tidak ada yang dibuang. */
    public function bersihkan(string $lokasi, int $jenisGambar): ?string
    {
        return match ($jenisGambar) {
            IMAGETYPE_JPEG => $this->jpeg($lokasi),
            IMAGETYPE_PNG => $this->png($lokasi),
            IMAGETYPE_WEBP => $this->webp($lokasi),
            default => null,
        };
    }

    private function jpeg(string $lokasi): ?string
    {
        $masuk = $this->buka($lokasi);

        try {
            if (fread($masuk, 2) !== "\xFF\xD8") {
                return null;
            }

            $kepala = "\xFF\xD8";
            $adaYangDibuang = false;

            while (true) {
                $penanda = PenandaJpeg::bacaBerikutnya($masuk);
                if ($penanda === null) {
                    return null;
                }
                if (PenandaJpeg::akhirKepala($penanda)) {
                    $posisiSos = (int) ftell($masuk) - 2;
                    break;
                }
                if (PenandaJpeg::tanpaPanjang($penanda)) {
                    $kepala .= "\xFF".chr($penanda);

                    continue;
                }

                $panjang = PenandaJpeg::bacaPanjang($masuk);
                if ($panjang === null) {
                    return null;
                }
                $isi = $panjang > 0 ? (string) fread($masuk, $panjang) : '';
                if (strlen($isi) !== $panjang) {
                    return null;
                }

                if ($this->segmenJpegDibuang($penanda, $isi)) {
                    $adaYangDibuang = true;

                    continue;
                }

                $kepala .= "\xFF".chr($penanda).pack('n', $panjang + 2).$isi;
            }

            if (! $adaYangDibuang) {
                return null;
            }

            return $this->tulis(function ($keluar) use ($masuk, $kepala, $posisiSos): void {
                fwrite($keluar, $kepala);
                fseek($masuk, $posisiSos);
                stream_copy_to_stream($masuk, $keluar);
            });
        } finally {
            fclose($masuk);
        }
    }

    private function segmenJpegDibuang(int $penanda, string $isi): bool
    {
        if ($penanda === 0xFE) {
            return true;
        }
        if ($penanda < 0xE0 || $penanda > 0xEF) {
            return false;
        }

        return match ($penanda) {
            0xE0, 0xEE => false,
            0xE2 => ! str_starts_with($isi, "ICC_PROFILE\0"),
            default => true,
        };
    }

    private function png(string $lokasi): ?string
    {
        $masuk = $this->buka($lokasi);

        try {
            $tanda = (string) fread($masuk, 8);
            if ($tanda !== "\x89PNG\r\n\x1a\n") {
                return null;
            }
            $dipertahankan = [];
            $adaYangDibuang = false;

            while (true) {
                $posisi = (int) ftell($masuk);
                $kepala = fread($masuk, 8);
                if ($kepala === false || strlen($kepala) < 8) {
                    return null;
                }
                $hasil = unpack('N', substr($kepala, 0, 4));
                $panjang = $hasil === false ? 0 : (int) $hasil[1];
                $jenis = substr($kepala, 4, 4);
                $total = $panjang + 12;

                if (in_array($jenis, self::CHUNK_PNG_DIBUANG, true)) {
                    $adaYangDibuang = true;
                } else {
                    $dipertahankan[] = [$posisi, $total];
                }

                if ($jenis === 'IEND') {
                    break;
                }
                if (fseek($masuk, $panjang + 4, SEEK_CUR) !== 0) {
                    return null;
                }
            }

            if (! $adaYangDibuang) {
                return null;
            }

            return $this->tulis(function ($keluar) use ($masuk, $tanda, $dipertahankan): void {
                fwrite($keluar, $tanda);
                foreach ($dipertahankan as [$posisi, $panjang]) {
                    stream_copy_to_stream($masuk, $keluar, $panjang, $posisi);
                }
            });
        } finally {
            fclose($masuk);
        }
    }

    private function webp(string $lokasi): ?string
    {
        $masuk = $this->buka($lokasi);

        try {
            $kepala = (string) fread($masuk, 12);
            if (strlen($kepala) < 12 || ! str_starts_with($kepala, 'RIFF') || substr($kepala, 8, 4) !== 'WEBP') {
                return null;
            }
            $dipertahankan = [];
            $adaYangDibuang = false;

            while (true) {
                $posisi = (int) ftell($masuk);
                $potongan = fread($masuk, 8);
                if ($potongan === false || $potongan === '') {
                    break;
                }
                if (strlen($potongan) < 8) {
                    return null;
                }
                $jenis = substr($potongan, 0, 4);
                $hasil = unpack('V', substr($potongan, 4, 4));
                $panjang = max(0, $hasil === false ? 0 : (int) $hasil[1]);
                $total = 8 + $panjang + ($panjang % 2);

                if (in_array($jenis, self::CHUNK_WEBP_DIBUANG, true)) {
                    $adaYangDibuang = true;
                } else {
                    $dipertahankan[] = ['jenis' => $jenis, 'posisi' => $posisi, 'panjang' => $total];
                }

                if (fseek($masuk, $panjang + ($panjang % 2), SEEK_CUR) !== 0) {
                    return null;
                }
            }

            if (! $adaYangDibuang) {
                return null;
            }

            $ukuranRiff = 4 + array_sum(array_column($dipertahankan, 'panjang'));

            return $this->tulis(function ($keluar) use ($masuk, $dipertahankan, $ukuranRiff): void {
                fwrite($keluar, 'RIFF'.pack('V', $ukuranRiff).'WEBP');
                foreach ($dipertahankan as $chunk) {
                    if ($chunk['jenis'] === 'VP8X') {
                        fseek($masuk, $chunk['posisi']);
                        $isi = (string) fread($masuk, $chunk['panjang']);
                        // Bit 3 (EXIF) dan bit 2 (XMP) pada bendera VP8X dimatikan.
                        $isi[8] = chr(ord($isi[8]) & ~0x0C);
                        fwrite($keluar, $isi);

                        continue;
                    }
                    stream_copy_to_stream($masuk, $keluar, $chunk['panjang'], $chunk['posisi']);
                }
            });
        } finally {
            fclose($masuk);
        }
    }

    /** @return resource */
    private function buka(string $lokasi)
    {
        $aliran = fopen($lokasi, 'rb');
        if ($aliran === false) {
            throw new RuntimeException('Gambar tidak dapat dibaca.');
        }

        return $aliran;
    }

    /** @param callable(resource): void $isi */
    private function tulis(callable $isi): string
    {
        $tujuan = BerkasSementara::buat();
        $keluar = fopen($tujuan, 'wb');
        if ($keluar === false) {
            BerkasSementara::hapus($tujuan);
            throw new RuntimeException('Salinan gambar tidak dapat ditulis.');
        }

        try {
            $isi($keluar);
        } catch (\Throwable $galat) {
            fclose($keluar);
            BerkasSementara::hapus($tujuan);
            throw $galat;
        }
        fclose($keluar);

        return $tujuan;
    }
}
