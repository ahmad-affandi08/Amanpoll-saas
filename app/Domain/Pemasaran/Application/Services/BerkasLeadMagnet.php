<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Kompresi\MetodeKompresi;
use App\Shared\Infrastructure\Kompresi\PemampatBerkas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Berkas yang dijanjikan satu formulir lead magnet (MARKETING.md 10).
 *
 * Lead magnet milik platform, bukan organisasi, jadi tidak tercatat sebagai
 * `Berkas`; ia tetap melewati mesin kompresi (PRD 11.1). Isi ber-gzip dikenali
 * dari akhiran `.gz` lokasinya dan dibuka saat diunduh, sehingga pengunjung
 * selalu menerima berkas aslinya.
 */
final class BerkasLeadMagnet
{
    public function __construct(private readonly PemampatBerkas $pemampat) {}

    /** Disk privat: berkas lead magnet tidak pernah punya URL publik yang dapat ditebak. */
    public static function disk(): string
    {
        return (string) config('amanpoll.disk_berkas', 'local');
    }

    public function simpan(FormulirPemasaran $formulir, UploadedFile $berkas): FormulirPemasaran
    {
        $lama = $formulir->BerkasLokasi;
        $sumber = $berkas->getRealPath();

        if ($sumber === false) {
            throw new AturanBisnisDilanggar('Berkas lead magnet gagal dibaca.');
        }

        $namaAsli = $berkas->getClientOriginalName();
        $hasil = $this->pemampat->pampatkan(
            $sumber,
            $berkas->getMimeType() ?? 'application/octet-stream',
            $namaAsli,
            $berkas->extension() ?: null,
        );

        try {
            // Nama dari klien tidak pernah menjadi path fisik; ULID plus ekstensi hasil deteksi.
            $lokasi = 'lead-magnet/'.strtolower((string) Str::ulid()).'.'.$hasil->ekstensi;
            $aliran = $hasil->bukaIsi();

            try {
                $tertulis = Storage::disk(self::disk())->writeStream($lokasi, $aliran);
            } finally {
                if (is_resource($aliran)) {
                    fclose($aliran);
                }
            }

            if ($tertulis === false) {
                throw new AturanBisnisDilanggar('Berkas lead magnet gagal disimpan.');
            }

            $formulir->fill([
                'BerkasLokasi' => $lokasi,
                'BerkasNamaAsli' => self::namaUnduhan($namaAsli, $hasil->metode),
                'BerkasMime' => $hasil->jenisMime,
                'BerkasUkuranByte' => $hasil->ukuranUnduhan(),
            ]);
            $formulir->save();
        } finally {
            $hasil->bersihkan();
        }

        if ($lama !== null) {
            Storage::disk(self::disk())->delete($lama);
        }

        return $formulir;
    }

    public function hapus(FormulirPemasaran $formulir): FormulirPemasaran
    {
        $lokasi = $formulir->BerkasLokasi;

        if ($lokasi === null) {
            return $formulir;
        }

        $formulir->fill([
            'BerkasLokasi' => null,
            'BerkasNamaAsli' => null,
            'BerkasMime' => null,
            'BerkasUkuranByte' => null,
        ]);
        $formulir->save();

        Storage::disk(self::disk())->delete($lokasi);

        return $formulir;
    }

    /**
     * Unduhan berisi isi asli: gzip dibuka sambil dialirkan. Pemanggil yang
     * memastikan berkasnya ada.
     */
    public function responsUnduh(FormulirPemasaran $formulir): StreamedResponse
    {
        $disk = Storage::disk(self::disk());
        $lokasi = (string) $formulir->BerkasLokasi;
        $nama = (string) $formulir->BerkasNamaAsli;
        $header = ['Content-Type' => $formulir->BerkasMime ?: 'application/octet-stream'];

        if (! str_ends_with($lokasi, '.gz')) {
            return $disk->download($lokasi, $nama, $header);
        }

        if ($formulir->BerkasUkuranByte !== null) {
            $header['Content-Length'] = (string) $formulir->BerkasUkuranByte;
        }

        return response()->streamDownload(function () use ($disk, $lokasi): void {
            $aliran = $disk->readStream($lokasi);
            if (! is_resource($aliran)) {
                throw new RuntimeException('Isi berkas lead magnet tidak ditemukan.');
            }

            try {
                // window 31 = zlib dengan kepala gzip.
                stream_filter_append($aliran, 'zlib.inflate', STREAM_FILTER_READ, ['window' => 31]);
                fpassthru($aliran);
            } finally {
                fclose($aliran);
            }
        }, $nama, $header);
    }

    /** Gambar yang dikodekan ulang diunduh sebagai `.webp`, sama seperti `Berkas::namaUnduhan()`. */
    private static function namaUnduhan(string $namaAsli, MetodeKompresi $metode): string
    {
        if ($metode !== MetodeKompresi::GambarUlang) {
            return $namaAsli;
        }

        $dasar = pathinfo($namaAsli, PATHINFO_FILENAME);

        return ($dasar === '' ? 'gambar' : $dasar).'.webp';
    }
}
