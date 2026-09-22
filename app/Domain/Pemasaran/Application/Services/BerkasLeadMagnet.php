<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Berkas yang dijanjikan satu formulir lead magnet (MARKETING.md 10). */
final class BerkasLeadMagnet
{
    /** Disk privat: berkas lead magnet tidak pernah punya URL publik yang dapat ditebak. */
    public static function disk(): string
    {
        return (string) config('amanpoll.disk_berkas', 'local');
    }

    public function simpan(FormulirPemasaran $formulir, UploadedFile $berkas): FormulirPemasaran
    {
        $lama = $formulir->BerkasLokasi;

        // Nama dari klien tidak pernah menjadi path fisik; ULID plus ekstensi hasil deteksi.
        $nama = (string) Str::ulid().'.'.$berkas->extension();
        $lokasi = $berkas->storeAs('lead-magnet', $nama, self::disk());

        if (! is_string($lokasi) || $lokasi === '') {
            throw new AturanBisnisDilanggar('Berkas lead magnet gagal disimpan.');
        }

        $formulir->fill([
            'BerkasLokasi' => $lokasi,
            'BerkasNamaAsli' => $berkas->getClientOriginalName(),
            'BerkasMime' => $berkas->getMimeType(),
            'BerkasUkuranByte' => $berkas->getSize(),
        ]);
        $formulir->save();

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
}
