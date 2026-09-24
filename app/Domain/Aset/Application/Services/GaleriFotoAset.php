<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Services;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Galeri foto aset (PRD 8.4 "Foto Aset"): lampiran Kolaborasi berkategori
 * `FotoAset` pada entitas `Aset`, dengan satu foto utama di
 * `Aset.FotoUtamaBerkasId`. Hanya membaca; perubahan lewat Action
 * `TambahFotoAset`, `HapusFotoAset`, dan `JadikanFotoUtamaAset`.
 */
final class GaleriFotoAset
{
    /** Kategori lampiran foto aset; dicadangkan, jalur lampiran umum menolaknya. */
    public const KATEGORI = 'FotoAset';

    public const JENIS_ENTITAS = 'Aset';

    public const MAKS_FOTO = 10;

    /**
     * URL thumbnail sebuah berkas foto, relatif terhadap host (pola layar
     * Lapangan). Dibentuk dari Id saja, jadi tidak menambah kueri per baris.
     */
    public static function urlThumbnail(?string $berkasId): ?string
    {
        return $berkasId === null ? null : route('kolaborasi.berkas.thumbnail', $berkasId, false);
    }

    /**
     * Lampiran foto satu aset, urut unggah.
     *
     * @return Builder<LampiranEntitas>
     */
    public function kueri(string $asetId): Builder
    {
        return LampiranEntitas::query()
            ->where('JenisEntitas', self::JENIS_ENTITAS)
            ->where('EntitasId', $asetId)
            ->where('Kategori', self::KATEGORI)
            ->whereHas('berkas')
            ->orderBy('DibuatPada')
            ->orderBy('Id');
    }

    public function jumlah(Aset $aset): int
    {
        return $this->kueri($aset->Id)->count();
    }

    /** @return Collection<int, LampiranEntitas> */
    public function daftar(Aset $aset): Collection
    {
        return $this->kueri($aset->Id)->with('berkas')->get();
    }

    /**
     * Bentuk `FotoAset` di `features/Aset/types.ts`.
     *
     * @return list<array{BerkasId: string, NamaAsli: string, UkuranByte: int, DibuatPada: string, Utama: bool, UrlThumbnail: string, UrlUnduh: string}>
     */
    public function ringkas(Aset $aset): array
    {
        $hasil = [];

        foreach ($this->daftar($aset) as $lampiran) {
            $berkas = $lampiran->berkas;
            if (! $berkas instanceof Berkas) {
                continue;
            }

            $hasil[] = [
                'BerkasId' => $berkas->Id,
                'NamaAsli' => $berkas->namaUnduhan(),
                'UkuranByte' => $berkas->UkuranByte,
                'DibuatPada' => $lampiran->DibuatPada->toIso8601String(),
                'Utama' => $berkas->Id === $aset->FotoUtamaBerkasId,
                'UrlThumbnail' => route('kolaborasi.berkas.thumbnail', $berkas->Id, false),
                'UrlUnduh' => route('kolaborasi.berkas.unduh', $berkas->Id, false),
            ];
        }

        return $hasil;
    }

    /** Menolak berkas yang bukan foto galeri aset ini (Id aset lain, lampiran kategori lain). */
    public function pastikanMilik(Aset $aset, Berkas $berkas): void
    {
        if (! $this->kueri($aset->Id)->where('BerkasId', $berkas->Id)->exists()) {
            throw new DataTidakDitemukan('Foto itu bukan bagian dari galeri aset ini.');
        }
    }

    /** Foto tertua yang tersisa selain `$kecuali`, calon foto utama pengganti. */
    public function berikutnya(Aset $aset, string $kecuali): ?string
    {
        $berkasId = $this->kueri($aset->Id)->where('BerkasId', '!=', $kecuali)->value('BerkasId');

        return is_string($berkasId) ? $berkasId : null;
    }
}
