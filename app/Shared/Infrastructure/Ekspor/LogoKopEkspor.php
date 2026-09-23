<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

use Illuminate\Support\Facades\Storage;

/**
 * Menyiapkan logo organisasi untuk kop PDF, sebagai data URI.
 *
 * `Organisasi.LogoUrl` adalah URL, dan isinya ditentukan tenant. Menyerahkannya
 * mentah-mentah ke Dompdf berarti Dompdf yang mengambilnya -- dan itu hanya
 * mungkin bila `isRemoteEnabled` dihidupkan, yang mengubah setiap ekspor PDF
 * menjadi pengambil URL atas nama server: SSRF ke jaringan internal, metadata
 * cloud, atau layanan yang hanya terjangkau dari dalam. Karena itu opsi tersebut
 * tetap mati dan kelas ini yang membaca berkasnya sendiri.
 *
 * Yang dibaca hanya berkas yang benar-benar ada di disk `public` aplikasi, yaitu
 * berkas yang memang diunggah lewat UnggahLogoOrganisasi. URL apa pun di luar
 * awalan disk itu -- http ke host lain, `file://`, path relatif karangan --
 * tidak menghasilkan apa-apa, dan kopnya tetap dicetak tanpa logo: berkas
 * ekspor tanpa logo masih berguna, ekspor yang gagal tidak.
 *
 * Hasilnya data URI, bukan path berkas, supaya Dompdf tidak perlu diberi akses
 * berkas sama sekali; satu-satunya jalan masuk gambar adalah byte yang sudah
 * kita periksa di sini.
 */
final class LogoKopEkspor
{
    /** Logo kop tidak perlu besar; batas ini menjaga PDF tetap ringan di hosting bersama. */
    private const BATAS_BYTE = 512 * 1024;

    /** @var list<string> */
    private const MIME_DIIZINKAN = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    public static function dataUri(?string $logoUrl): ?string
    {
        $path = self::pathDiDiskPublik($logoUrl);

        if ($path === null) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $berkas = $disk->path($path);
        $ukuran = @filesize($berkas);

        if ($ukuran === false || $ukuran === 0 || $ukuran > self::BATAS_BYTE) {
            return null;
        }

        // getimagesize sekaligus membuktikan berkasnya benar-benar gambar:
        // ekstensi dan header Content-Type tidak mengikat isi berkas.
        $info = @getimagesize($berkas);

        if ($info === false || ! in_array($info['mime'], self::MIME_DIIZINKAN, true)) {
            return null;
        }

        $isi = @file_get_contents($berkas);

        if ($isi === false) {
            return null;
        }

        return 'data:'.$info['mime'].';base64,'.base64_encode($isi);
    }

    /**
     * Mengembalikan path relatif di disk `public` bila URL-nya memang menunjuk ke sana.
     *
     * Perbandingan dilakukan terhadap awalan URL disknya sendiri, bukan terhadap
     * daftar host yang diizinkan: hanya berkas yang ditulis aplikasi ini yang
     * boleh ikut ke dalam PDF.
     */
    private static function pathDiDiskPublik(?string $logoUrl): ?string
    {
        if ($logoUrl === null || trim($logoUrl) === '') {
            return null;
        }

        $awalan = Storage::disk('public')->url('');

        if ($awalan === '' || ! str_starts_with($logoUrl, $awalan)) {
            return null;
        }

        // Didekode lebih dulu, baru diperiksa: `%2e%2e` yang diperiksa sebelum
        // didekode akan lolos dan berubah menjadi `..` sesudahnya.
        $path = rawurldecode(ltrim(substr($logoUrl, strlen($awalan)), '/'));

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }
}
