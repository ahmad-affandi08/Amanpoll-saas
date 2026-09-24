<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use App\Core\Izin\ScopeLingkup;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;

/**
 * Satu sumber pilihan unit pengelola untuk seluruh formulir dan penyaring (PRD 8.21).
 *
 * Isinya unit organisasi aktif bertanda Mengelola Aset milik organisasi yang
 * sedang berlaku (ScopeOrganisasi). Sengaja lepas dari ScopeLingkup: daftar
 * ini sama bagi setiap pengguna, dan koordinator berlingkup tetap perlu
 * melihat unit pengelola lain untuk mengalihkan tiket. Selaras dengan
 * UnitPengelolaSah, yang juga tidak terikat lingkup.
 */
final class OpsiUnitPengelola
{
    /**
     * @param  string|null  $sertakanId  Unit yang tetap dimuat walau nonaktif -- nilai tersimpan pada
     *                                   baris yang sedang diubah, supaya pemilihnya tidak tampil kosong.
     * @param  bool  $termasukNonaktif  Untuk penyaring daftar dan laporan: tiket lama bisa saja
     *                                  milik unit pengelola yang kini nonaktif.
     * @return list<array{Id: string, Kode: string, Nama: string}>
     */
    public static function daftar(?string $sertakanId = null, bool $termasukNonaktif = false): array
    {
        $daftar = UnitOrganisasi::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->where('MengelolaAset', true)
            ->when(! $termasukNonaktif, function ($kueri) use ($sertakanId): void {
                $kueri->where(function ($syarat) use ($sertakanId): void {
                    $syarat->where('Status', 'Aktif');

                    if ($sertakanId !== null) {
                        $syarat->orWhere('Id', $sertakanId);
                    }
                });
            })
            ->orderBy('Nama')
            ->orderBy('Id')
            ->get(['Id', 'Kode', 'Nama'])
            ->map(fn (UnitOrganisasi $unit): array => [
                'Id' => $unit->Id,
                'Kode' => $unit->Kode,
                'Nama' => $unit->Nama,
            ])
            ->all();

        return array_values($daftar);
    }

    /**
     * Organisasi ini memakai unit pengelola: setidaknya satu unit bertanda Mengelola Aset.
     *
     * Dipakai untuk menampilkan isian dan peringatan unit pengelola hanya bila
     * fiturnya memang dipakai; organisasi dengan satu bagian pemeliharaan tidak
     * melihat perbedaan apa pun.
     */
    public static function dipakai(): bool
    {
        return UnitOrganisasi::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->where('MengelolaAset', true)
            ->exists();
    }
}
