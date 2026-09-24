<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Kompresi\MetodeKompresi;
use App\Shared\Infrastructure\Kompresi\PemampatBerkas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Logo organisasi di disk `public` (bukan `Berkas`), tetap lewat mesin kompresi
 * (PRD 11.1): gambar dikodekan ulang ke WebP bila lebih kecil dan metadatanya
 * dibuang. Dompdf membaca WebP, jadi logo di kop PDF ekspor tetap tercetak.
 */
final class UnggahLogoOrganisasi
{
    public function __construct(private readonly PemampatBerkas $pemampat) {}

    public function jalankan(Organisasi $organisasi, UploadedFile $berkas): Organisasi
    {
        $logoLama = $organisasi->LogoUrl;

        $path = $this->simpan("organisasi/{$organisasi->Id}", $berkas);

        $organisasi->LogoUrl = Storage::disk('public')->url($path);
        $organisasi->save();

        if ($logoLama) {
            $pathLama = str_replace(Storage::disk('public')->url(''), '', $logoLama);
            Storage::disk('public')->delete($pathLama);
        }

        return $organisasi;
    }

    /**
     * Disk `public` dilayani web server apa adanya, tanpa `Content-Encoding`;
     * hasil gzip di sana akan sampai ke peramban sebagai byte mentah. Karena itu
     * hanya hasil gambar yang dipakai, dan selebihnya disimpan seperti aslinya.
     */
    private function simpan(string $direktori, UploadedFile $berkas): string
    {
        $sumber = $berkas->getRealPath();
        $hasil = $sumber === false ? null : $this->pemampat->pampatkan(
            $sumber,
            $berkas->getMimeType() ?? 'application/octet-stream',
            $berkas->getClientOriginalName(),
            $berkas->extension() ?: null,
        );

        try {
            if ($hasil === null || $hasil->metode === MetodeKompresi::Gzip) {
                $path = $berkas->store($direktori, 'public');
            } else {
                $path = $direktori.'/'.strtolower((string) Str::ulid()).'.'.$hasil->ekstensi;
                $aliran = $hasil->bukaIsi();

                try {
                    if (Storage::disk('public')->writeStream($path, $aliran) === false) {
                        $path = false;
                    }
                } finally {
                    if (is_resource($aliran)) {
                        fclose($aliran);
                    }
                }
            }
        } finally {
            $hasil?->bersihkan();
        }

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Gagal menyimpan berkas logo.');
        }

        return $path;
    }
}
