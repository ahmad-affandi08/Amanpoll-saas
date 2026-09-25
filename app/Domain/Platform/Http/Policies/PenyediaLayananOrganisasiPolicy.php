<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * Kredensial email dan WhatsApp organisasi setara kunci API: pemegangnya dapat mengirim
 * atas nama organisasi, jadi izinnya sama dengan pengelolaan integrasi (PRD 8.23).
 */
final class PenyediaLayananOrganisasiPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Integrasi.Kelola');
    }

    public function update(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Integrasi.Kelola');
    }
}
