<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Kompresi;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

/**
 * Mesin kompresi bersama (PRD 11.1): satu pintu untuk setiap berkas yang akan
 * disimpan sistem. Tidak menulis ke disk penyimpanan dan tidak tahu soal model
 * `Berkas`; hasilnya ditulis pemanggil (lihat `PenyimpanBerkas` di Kolaborasi).
 *
 * - Gambar JPEG/PNG/WebP/GIF statis/AVIF → PemampatGambar.
 * - Teks (`text/*` dan `kompresi.mime_teks`) → gzip bila lebih kecil.
 * - `kompresi.mime_gzip_bila_hemat` (PDF) → gzip bila hemat ≥ ambang.
 * - Selebihnya (XLSX, DOCX, ZIP, video, audio, HEIC) → apa adanya.
 *
 * Tidak pernah melempar karena kompresi: kegagalan apa pun dicatat di log dan
 * berkasnya disimpan apa adanya.
 */
final class PemampatBerkas
{
    public function __construct(
        private readonly PemampatGambar $pemampatGambar,
        private readonly PemampatGzip $pemampatGzip,
    ) {}

    /**
     * @param  string  $lokasiSumber  Berkas lokal yang dapat dibaca (mis. `UploadedFile::getRealPath()`).
     * @param  string  $jenisMime  MIME hasil deteksi server, bukan kiriman klien.
     * @param  string  $namaAsli  Nama untuk log dan tebakan ekstensi.
     * @param  ?string  $ekstensi  Ekstensi dasar nama penyimpanan; ditebak dari MIME bila null.
     */
    public function pampatkan(string $lokasiSumber, string $jenisMime, string $namaAsli, ?string $ekstensi = null): HasilPemampatan
    {
        $jenisMime = strtolower(trim($jenisMime)) ?: 'application/octet-stream';
        $ekstensi = self::ekstensiAman($ekstensi ?? self::tebakEkstensi($jenisMime, $namaAsli));
        $ukuranAsli = BerkasSementara::ukuran($lokasiSumber);
        $apaAdanya = HasilPemampatan::apaAdanya($lokasiSumber, $jenisMime, $ekstensi, $ukuranAsli);

        if (! (bool) config('amanpoll.kompresi.aktif', true) || $ukuranAsli === 0) {
            return $apaAdanya;
        }

        try {
            if (PemampatGambar::mimeDidukung($jenisMime)) {
                return $this->pemampatGambar->pampatkan($lokasiSumber, $jenisMime, $ekstensi, $ukuranAsli) ?? $apaAdanya;
            }

            $hematMinimal = $this->hematMinimalGzip($jenisMime);
            if ($hematMinimal === null) {
                return $apaAdanya;
            }

            $lokasiGzip = $this->pemampatGzip->pampatkan($lokasiSumber, $ukuranAsli, $hematMinimal);
            if ($lokasiGzip === null) {
                return $apaAdanya;
            }

            return new HasilPemampatan(
                $lokasiGzip,
                $jenisMime,
                $ekstensi.'.gz',
                MetodeKompresi::Gzip,
                $ukuranAsli,
                BerkasSementara::ukuran($lokasiGzip),
                null,
                [$lokasiGzip],
            );
        } catch (Throwable $galat) {
            Log::warning('Kompresi berkas gagal; berkas disimpan apa adanya.', [
                'nama' => $namaAsli,
                'mime' => $jenisMime,
                'ukuran' => $ukuranAsli,
                'galat' => $galat->getMessage(),
            ]);

            return $apaAdanya;
        }
    }

    /** Persen hemat minimal untuk gzip, atau null bila jenis ini tidak di-gzip. */
    private function hematMinimalGzip(string $jenisMime): ?float
    {
        if (str_starts_with($jenisMime, 'text/') || in_array($jenisMime, (array) config('amanpoll.kompresi.mime_teks', []), true)) {
            return 0.0;
        }

        if (in_array($jenisMime, (array) config('amanpoll.kompresi.mime_gzip_bila_hemat', []), true)) {
            return (float) config('amanpoll.kompresi.gzip.hemat_minimal_persen', 10);
        }

        return null;
    }

    private static function tebakEkstensi(string $jenisMime, string $namaAsli): string
    {
        $dariMime = MimeTypes::getDefault()->getExtensions($jenisMime)[0] ?? null;

        return $dariMime ?? pathinfo($namaAsli, PATHINFO_EXTENSION);
    }

    /** Ekstensi hanya huruf kecil dan angka: ia ikut membentuk path fisik. */
    private static function ekstensiAman(string $ekstensi): string
    {
        $bersih = preg_replace('/[^a-z0-9]/', '', strtolower($ekstensi)) ?? '';

        return $bersih === '' ? 'bin' : substr($bersih, 0, 10);
    }
}
