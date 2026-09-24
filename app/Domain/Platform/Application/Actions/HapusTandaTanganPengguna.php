<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * Melepas tanda tangan tersimpan dari profil (PRD 8.22). Berkasnya tetap ada
 * karena bisa dirujuk konfirmasi penerima yang sudah tercatat.
 */
final class HapusTandaTanganPengguna
{
    public function __construct(private readonly LayananAudit $audit) {}

    public function jalankan(Pengguna $pengguna): void
    {
        $sebelum = $pengguna->TandaTanganBerkasId;
        if ($sebelum === null) {
            return;
        }

        $pengguna->forceFill(['TandaTanganBerkasId' => null])->save();

        $this->audit->catat('Pengguna.TandaTanganDihapus', 'Pengguna', $pengguna->Id, ['TandaTanganBerkasId' => $sebelum], ['TandaTanganBerkasId' => null]);
    }
}
