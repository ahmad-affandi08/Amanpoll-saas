<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Shared\Domain\Exceptions\KonflikData;

final class UbahAset
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(Aset $aset, array $data): Aset
    {
        $versiDiharapkan = $data['Versi'] ?? null;
        unset($data['Versi'], $data['LokasiId'], $data['KodeQr']);

        if ($versiDiharapkan !== null && (int) $versiDiharapkan !== $aset->Versi) {
            throw new KonflikData('Aset ini sudah diubah oleh pengguna lain. Muat ulang data sebelum menyimpan kembali.');
        }

        $aset->fill($data);
        $aset->Versi++;
        $aset->save();

        return $aset;
    }
}
