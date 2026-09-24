<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Kompresi;

use GdImage;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Strategi gambar PRD 11.1 dengan GD: orientasi EXIF diluruskan, sisi terpanjang
 * dibatasi, dikodekan ulang ke WebP (transparansi dipertahankan), dan thumbnail
 * WebP dibuat. WebP hanya dipakai bila lebih kecil dari aslinya — kecuali gambar
 * yang perlu diluruskan; bila aslinya yang disimpan, metadatanya dibuang tanpa
 * mengodekan ulang.
 *
 * Mengembalikan null untuk gambar yang sengaja disimpan apa adanya (format yang
 * tidak dibaca GD seperti HEIC, GIF/WebP/PNG animasi, gambar terlalu besar untuk
 * didekode) dan melempar bila GD gagal; PemampatBerkas yang mencatat dan
 * menyimpannya apa adanya.
 */
final class PemampatGambar
{
    /** Perkiraan byte per piksel gambar truecolor GD, ditambah cadangan. */
    private const BYTE_PER_PIKSEL = 5;

    /** @var array<int, string> */
    private const MIME_DIDUKUNG = [
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_PNG => 'image/png',
        IMAGETYPE_WEBP => 'image/webp',
        IMAGETYPE_GIF => 'image/gif',
        IMAGETYPE_AVIF => 'image/avif',
    ];

    private ?string $pesanGalatTerakhir = null;

    public function __construct(private readonly PembersihMetadataGambar $pembersih) {}

    /** MIME yang GD coba baca; MIME gambar lain (HEIC, SVG, BMP) disimpan apa adanya. */
    public static function mimeDidukung(string $jenisMime): bool
    {
        return in_array($jenisMime, self::MIME_DIDUKUNG, true) || $jenisMime === 'image/jpg';
    }

    public function pampatkan(string $lokasiSumber, string $jenisMime, string $ekstensi, int $ukuranAsli): ?HasilPemampatan
    {
        $info = $this->aman(fn () => getimagesize($lokasiSumber));
        if ($info === false) {
            throw new RuntimeException('Gambar tidak dapat dibaca: '.($this->pesanGalatTerakhir ?? 'format tidak dikenal'));
        }

        [$lebar, $tinggi, $jenis] = [(int) $info[0], (int) $info[1], (int) $info[2]];
        if (! isset(self::MIME_DIDUKUNG[$jenis]) || $lebar < 1 || $tinggi < 1 || ! $this->dapatDidekode($jenis)) {
            return null;
        }
        if ($this->beranimasi($lokasiSumber, $jenis)) {
            return null;
        }
        if (! $this->bolehDidekode($lebar, $tinggi)) {
            Log::info('Gambar terlalu besar untuk didekode; disimpan apa adanya.', [
                'lebar' => $lebar,
                'tinggi' => $tinggi,
            ]);

            return null;
        }

        $orientasi = $jenis === IMAGETYPE_JPEG ? PembacaOrientasiExif::baca($lokasiSumber) : 1;
        [$lokasiWebp, $lokasiThumbnail] = $this->kodekanUlang($lokasiSumber, $jenis, $orientasi);

        $ukuranWebp = BerkasSementara::ukuran($lokasiWebp);
        if ($orientasi !== 1 || $ukuranWebp < $ukuranAsli) {
            return new HasilPemampatan(
                $lokasiWebp,
                'image/webp',
                'webp',
                MetodeKompresi::GambarUlang,
                $ukuranAsli,
                $ukuranWebp,
                $lokasiThumbnail,
                array_values(array_filter([$lokasiWebp, $lokasiThumbnail])),
            );
        }

        BerkasSementara::hapus($lokasiWebp);

        try {
            $lokasiBersih = $this->pembersih->bersihkan($lokasiSumber, $jenis);
        } catch (\Throwable $galat) {
            BerkasSementara::hapus($lokasiThumbnail);
            throw $galat;
        }

        return new HasilPemampatan(
            $lokasiBersih ?? $lokasiSumber,
            $jenisMime,
            $ekstensi,
            MetodeKompresi::Tidak,
            $ukuranAsli,
            $lokasiBersih === null ? $ukuranAsli : BerkasSementara::ukuran($lokasiBersih),
            $lokasiThumbnail,
            array_values(array_filter([$lokasiBersih, $lokasiThumbnail])),
        );
    }

    /**
     * @return array{0: string, 1: ?string} WebP utama dan thumbnail (sementara).
     */
    private function kodekanUlang(string $lokasiSumber, int $jenis, int $orientasi): array
    {
        $sisiMaks = max(1, (int) config('amanpoll.kompresi.gambar.sisi_maks', 2560));
        $sisiThumbnail = max(1, (int) config('amanpoll.kompresi.gambar.sisi_thumbnail', 480));
        $kualitas = max(1, min(100, (int) config('amanpoll.kompresi.gambar.kualitas_webp', 80)));
        $kualitasThumbnail = max(1, min(100, (int) config('amanpoll.kompresi.gambar.kualitas_thumbnail', 75)));

        $asli = $this->dekode($lokasiSumber, $jenis);
        // Diskalakan dulu baru diluruskan: rotasi menyalin gambar, dan menyalin
        // gambar kecil jauh lebih hemat memori daripada menyalin gambar asli.
        $utama = $this->luruskan($this->skalakan($asli, $sisiMaks), $orientasi);
        unset($asli);

        $lokasiWebp = BerkasSementara::buat();
        $lokasiThumbnail = null;

        try {
            $this->tulisWebp($utama, $lokasiWebp, $kualitas);

            if (max(imagesx($utama), imagesy($utama)) > $sisiThumbnail) {
                $lokasiThumbnail = BerkasSementara::buat();
                $this->tulisWebp($this->skalakan($utama, $sisiThumbnail), $lokasiThumbnail, $kualitasThumbnail);
            }
        } catch (\Throwable $galat) {
            BerkasSementara::hapus($lokasiWebp);
            BerkasSementara::hapus($lokasiThumbnail);
            throw $galat;
        }

        return [$lokasiWebp, $lokasiThumbnail];
    }

    private function dapatDidekode(int $jenis): bool
    {
        return match ($jenis) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg'),
            IMAGETYPE_PNG => function_exists('imagecreatefrompng'),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') && function_exists('imagewebp'),
            IMAGETYPE_GIF => function_exists('imagecreatefromgif'),
            IMAGETYPE_AVIF => function_exists('imagecreatefromavif'),
            default => false,
        } && function_exists('imagewebp');
    }

    private function dekode(string $lokasi, int $jenis): GdImage
    {
        // Bawaan PHP menelan peringatan libjpeg; dinyalakan agar JPEG terpotong ketahuan.
        $abaikanPeringatanJpeg = ini_set('gd.jpeg_ignore_warning', '0');

        try {
            $gambar = $this->aman(fn () => match ($jenis) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($lokasi),
                IMAGETYPE_PNG => imagecreatefrompng($lokasi),
                IMAGETYPE_WEBP => imagecreatefromwebp($lokasi),
                IMAGETYPE_GIF => imagecreatefromgif($lokasi),
                IMAGETYPE_AVIF => imagecreatefromavif($lokasi),
                default => false,
            });
        } finally {
            if ($abaikanPeringatanJpeg !== false) {
                ini_set('gd.jpeg_ignore_warning', $abaikanPeringatanJpeg);
            }
        }

        // Peringatan "recoverable" (mis. JPEG terpotong) tetap dianggap gagal: GD
        // mengisi bagian yang hilang dengan abu-abu, dan hasil itu tidak boleh
        // menggantikan berkas pengguna.
        if (! $gambar instanceof GdImage || $this->pesanGalatTerakhir !== null) {
            throw new RuntimeException('GD gagal mendekode gambar: '.($this->pesanGalatTerakhir ?? 'tanpa pesan'));
        }

        if (! imageistruecolor($gambar)) {
            imagepalettetotruecolor($gambar);
        }
        imagealphablending($gambar, false);
        imagesavealpha($gambar, true);

        return $gambar;
    }

    /** Memperkecil agar sisi terpanjang ≤ $sisiMaks; gambar yang sudah muat dikembalikan apa adanya. */
    private function skalakan(GdImage $gambar, int $sisiMaks): GdImage
    {
        $lebar = imagesx($gambar);
        $tinggi = imagesy($gambar);
        $skala = $sisiMaks / max($lebar, $tinggi);
        if ($skala >= 1) {
            return $gambar;
        }

        $lebarBaru = max(1, (int) round($lebar * $skala));
        $tinggiBaru = max(1, (int) round($tinggi * $skala));
        $hasil = imagecreatetruecolor($lebarBaru, $tinggiBaru);
        imagealphablending($hasil, false);
        imagesavealpha($hasil, true);
        $transparan = imagecolorallocatealpha($hasil, 0, 0, 0, 127);
        if ($transparan !== false) {
            imagefilledrectangle($hasil, 0, 0, $lebarBaru - 1, $tinggiBaru - 1, $transparan);
        }
        imagecopyresampled($hasil, $gambar, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);

        return $hasil;
    }

    /** Menerapkan tag Orientation EXIF (1..8) sehingga gambar tampil lurus tanpa EXIF. */
    private function luruskan(GdImage $gambar, int $orientasi): GdImage
    {
        if (in_array($orientasi, [2, 5, 7], true)) {
            imageflip($gambar, IMG_FLIP_HORIZONTAL);
        }
        if ($orientasi === 4) {
            imageflip($gambar, IMG_FLIP_VERTICAL);
        }

        // imagerotate berputar berlawanan arah jarum jam.
        $sudut = match ($orientasi) {
            3 => 180,
            6, 7 => 270,
            5, 8 => 90,
            default => 0,
        };
        if ($sudut === 0) {
            return $gambar;
        }

        $latar = imagecolorallocatealpha($gambar, 0, 0, 0, 127);
        $hasil = imagerotate($gambar, $sudut, $latar === false ? 0 : $latar);
        if ($hasil === false) {
            throw new RuntimeException('GD gagal meluruskan orientasi gambar.');
        }
        imagealphablending($hasil, false);
        imagesavealpha($hasil, true);

        return $hasil;
    }

    private function tulisWebp(GdImage $gambar, string $tujuan, int $kualitas): void
    {
        $berhasil = $this->aman(fn () => imagewebp($gambar, $tujuan, $kualitas));
        if ($berhasil !== true || BerkasSementara::ukuran($tujuan) === 0) {
            throw new RuntimeException('GD gagal menulis WebP: '.($this->pesanGalatTerakhir ?? 'tanpa pesan'));
        }
    }

    /**
     * Batas piksel dari konfigurasi dan perkiraan memori: gambar asli, hasil
     * skala, dan salinan rotasinya harus muat di sisa `memory_limit`.
     */
    private function bolehDidekode(int $lebar, int $tinggi): bool
    {
        $piksel = $lebar * $tinggi;
        if ($piksel > (int) config('amanpoll.kompresi.gambar.piksel_maks', 40_000_000)) {
            return false;
        }

        $batas = $this->batasMemori();
        if ($batas <= 0) {
            return true;
        }

        $sisiMaks = max(1, (int) config('amanpoll.kompresi.gambar.sisi_maks', 2560));
        $skala = min(1, $sisiMaks / max($lebar, $tinggi));
        $pikselHasil = (int) ceil($lebar * $skala) * (int) ceil($tinggi * $skala);
        $perlu = ($piksel + 2 * $pikselHasil) * self::BYTE_PER_PIKSEL;

        return memory_get_usage(true) + $perlu < $batas;
    }

    private function batasMemori(): int
    {
        $nilai = trim((string) ini_get('memory_limit'));
        if ($nilai === '' || $nilai === '-1') {
            return 0;
        }

        $angka = (int) $nilai;

        return match (strtolower(substr($nilai, -1))) {
            'g' => $angka * 1024 ** 3,
            'm' => $angka * 1024 ** 2,
            'k' => $angka * 1024,
            default => $angka,
        };
    }

    /** GIF beranimasi, WebP beranimasi, dan APNG disimpan apa adanya: GD hanya membaca bingkai pertama. */
    private function beranimasi(string $lokasi, int $jenis): bool
    {
        if (! in_array($jenis, [IMAGETYPE_GIF, IMAGETYPE_WEBP, IMAGETYPE_PNG], true)) {
            return false;
        }

        $aliran = fopen($lokasi, 'rb');
        if ($aliran === false) {
            return false;
        }

        try {
            if ($jenis === IMAGETYPE_WEBP) {
                $kepala = (string) fread($aliran, 21);

                return substr($kepala, 12, 4) === 'VP8X' && strlen($kepala) === 21 && (ord($kepala[20]) & 0x02) !== 0;
            }

            if ($jenis === IMAGETYPE_PNG) {
                // acTL wajib muncul sebelum IDAT pertama.
                $kepala = (string) fread($aliran, 65_536);
                $posisiIdat = strpos($kepala, 'IDAT');
                $posisiActl = strpos($kepala, 'acTL');

                return $posisiActl !== false && ($posisiIdat === false || $posisiActl < $posisiIdat);
            }

            $bingkai = 0;
            $sisa = '';
            while (! feof($aliran)) {
                $potongan = $sisa.fread($aliran, 65_536);
                $bingkai += preg_match_all('/\x00\x21\xF9\x04.{4}\x00[\x2C\x21]/s', $potongan);
                if ($bingkai > 1) {
                    return true;
                }
                $sisa = substr($potongan, -9);
            }

            return false;
        } finally {
            fclose($aliran);
        }
    }

    /**
     * Menjalankan fungsi GD tanpa membiarkan peringatannya bocor sebagai error
     * PHP; pesannya disimpan untuk log bila fungsinya gagal.
     *
     * @template T
     *
     * @param  callable(): T  $aksi
     * @return T
     */
    private function aman(callable $aksi): mixed
    {
        $this->pesanGalatTerakhir = null;
        set_error_handler(function (int $nomor, string $pesan): bool {
            $this->pesanGalatTerakhir = $pesan;

            return true;
        });

        try {
            return $aksi();
        } finally {
            restore_error_handler();
        }
    }
}
