<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Shared\Domain\Exceptions\AksesDitolak;

final class HapusPerangkatSendiri
{
    public function jalankan(Pengguna $pengguna, PerangkatPengguna $perangkat): void
    {
        if ($perangkat->PenggunaId !== $pengguna->Id) {
            throw new AksesDitolak('Perangkat ini bukan milik Anda.');
        }

        $perangkat->delete();
    }
}
