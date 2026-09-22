<?php

declare(strict_types=1);

namespace App\Core\Izin;

use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;

/**
 * Otorisasi admin platform (MARKETING.md 26).
 *
 * Terpisah dari PemeriksaIzin milik tenant, dan memang harus terpisah: yang itu
 * membaca peran per organisasi dari konteks tenant yang sedang aktif,
 * sedangkan admin platform bekerja lintas tenant dan tidak punya konteks
 * semacam itu sama sekali.
 */
final class PemeriksaIzinPlatform
{
    public function boleh(?AdminPlatform $admin, string $kodeIzin): bool
    {
        if ($admin === null || ! $admin->aktif()) {
            return false;
        }

        // Super admin dipertahankan supaya platform tidak pernah dapat mengunci
        // dirinya sendiri di luar konsolnya.
        if ($admin->SuperAdmin === true) {
            return true;
        }

        return in_array($kodeIzin, $this->daftarKode($admin), true);
    }

    /** @return list<string> */
    public function daftarKode(AdminPlatform $admin): array
    {
        $izin = $admin->Izin;

        if (! is_array($izin)) {
            return [];
        }

        return array_values(array_filter($izin, is_string(...)));
    }
}
