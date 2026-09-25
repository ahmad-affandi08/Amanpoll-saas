<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Domain\Persetujuan\Application\Services\PemilihTahapPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class AktifkanAlurPersetujuan
{
    public function __construct(private readonly PemilihTahapPersetujuan $pemilihTahap) {}

    public function jalankan(AlurPersetujuan $alurPersetujuan): AlurPersetujuan
    {
        $tahapPertama = $this->pemilihTahap->tahapPertama($alurPersetujuan->Id);

        if ($tahapPertama === null) {
            throw new AturanBisnisDilanggar('Alur persetujuan tidak dapat diaktifkan tanpa tahap.');
        }

        if (PemilihTahapPersetujuan::ambangNilai($tahapPertama) !== null) {
            throw new AturanBisnisDilanggar('Tahap pertama berlaku untuk semua nilai. Pindahkan ambang nilai ke tahap sesudahnya.');
        }

        $alurPersetujuan->Aktif = true;
        $alurPersetujuan->save();

        return $alurPersetujuan;
    }
}
