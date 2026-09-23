<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Services;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;

/**
 * Menemukan aset dari kode yang menempel di fisiknya.
 *
 * Satu aset bisa dipindai lewat QR, barcode, NFC, atau kode asetnya yang
 * dibaca manual saat stikernya rusak, jadi keempatnya dicoba sekaligus.
 */
final class PencariAsetLewatKode
{
    public function cari(string $kode): ?Aset
    {
        $kode = trim($kode);

        if ($kode === '') {
            return null;
        }

        // Alternatifnya dikurung dalam satu grup supaya batas OR-nya terbaca di
        // tempat, tidak bergantung pada Eloquent yang kebetulan menyarangkan
        // where yang sudah ada ketika ScopeOrganisasi dipasang.
        return Aset::query()
            ->where(function ($kueri) use ($kode): void {
                $kueri->where('KodeQr', $kode)
                    ->orWhere('KodeBatang', $kode)
                    ->orWhere('NfcUid', $kode)
                    ->orWhere('KodeAset', $kode);
            })
            ->first();
    }
}
