<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Services;

use App\Core\Izin\LingkupAkses;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as KueriDasar;
use Illuminate\Support\Facades\Auth;

/**
 * Stok, mutasi, reservasi, dan pemakaian suku cadang mengikuti lingkup gudangnya (PRD 8.21).
 *
 * Gudang sendiri sudah ber-ScopeLingkup (ruangan dan unit pengelolanya), jadi
 * "gudang yang terlihat" cukup dibaca dari `Gudang::query()`. Tabel transaksinya
 * tidak diberi global scope: Action seperti PostingMutasiStok mencari-atau-membuat
 * baris stok, dan scope yang menyembunyikan baris di sana akan menggandakan stok
 * alih-alih menolak. Karena itu daftar disaring di sini dan kiriman ditolak lebih
 * dulu oleh GudangTerlihat.
 *
 * Pengguna tanpa batas (dan konsol/antrian tanpa pengguna) tidak disaring sama
 * sekali, supaya kueri organisasi yang tidak memakai lingkup tetap persis seperti
 * sebelumnya.
 */
final class LingkupGudang
{
    public function __construct(private readonly LingkupAkses $lingkup) {}

    /** Pengguna web yang sedang masuk dibatasi lingkup unit atau ruangan. */
    public function berlaku(): bool
    {
        $pengguna = Auth::guard('web')->user();

        return $pengguna !== null && ! $this->lingkup->tanpaBatas((string) $pengguna->getAuthIdentifier());
    }

    /**
     * Membatasi kueri Eloquent pada baris yang salah satu kolom gudangnya terlihat.
     *
     * Subkueri, bukan daftar Id: gudang yang terlihat bisa banyak, dan basis data
     * menyelesaikannya dalam satu perjalanan.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $kueri
     * @return Builder<TModel>
     */
    public function saring(Builder $kueri, string ...$kolomGudang): Builder
    {
        if ($this->berlaku()) {
            $kueri->where(function (Builder $syarat) use ($kolomGudang): void {
                foreach ($kolomGudang as $kolom) {
                    $syarat->orWhereIn($kolom, $this->kueriIdTerlihat());
                }
            });
        }

        return $kueri;
    }

    /** Sama dengan saring(), untuk kueri `DB::table()` yang dipakai agregat stok. */
    public function saringDasar(KueriDasar $kueri, string $kolomGudang): KueriDasar
    {
        if ($this->berlaku()) {
            $kueri->whereIn($kolomGudang, $this->kueriIdTerlihat());
        }

        return $kueri;
    }

    /**
     * Penjaga policy untuk baris yang sudah terikat organisasi: pengguna tanpa batas
     * selalu lolos tanpa kueri tambahan.
     */
    public function terlihat(?string $gudangId): bool
    {
        if (! $this->berlaku()) {
            return true;
        }

        return $gudangId !== null && Gudang::query()->whereKey($gudangId)->exists();
    }

    /** @return Builder<Gudang> */
    private function kueriIdTerlihat(): Builder
    {
        return Gudang::query()->select('Gudang.Id');
    }
}
