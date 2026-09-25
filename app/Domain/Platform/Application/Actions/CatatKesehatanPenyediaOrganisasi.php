<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananOrganisasi;
use Illuminate\Database\Eloquent\Builder;

/**
 * Merekam hasil kiriman lewat penyedia milik organisasi (PRD 8.23).
 *
 * Halaman Email & WhatsApp membacanya untuk memperingatkan admin. Dipanggil dari
 * transport email dan worker antrian, jadi scope organisasi dilepas.
 */
final class CatatKesehatanPenyediaOrganisasi
{
    /** Keberhasilan beruntun tidak perlu menulis setiap kali; cukup sekali dalam jeda ini. */
    private const JEDA_MENIT_BERHASIL = 10;

    public function berhasil(string $penyediaId): void
    {
        $this->kueri($penyediaId)
            ->where(fn ($q) => $q
                ->whereNull('TerakhirBerhasilPada')
                ->orWhere('TerakhirBerhasilPada', '<', now()->subMinutes(self::JEDA_MENIT_BERHASIL))
                ->orWhereColumn('TerakhirGagalPada', '>', 'TerakhirBerhasilPada'))
            ->update(['TerakhirBerhasilPada' => now()]);
    }

    /**
     * Mengembalikan true bila penyedia ini baru saja berubah dari sehat menjadi bermasalah,
     * supaya pemanggil cukup memberi tahu admin sekali, bukan pada setiap kegagalan.
     */
    public function gagal(string $penyediaId, string $pesan): bool
    {
        $baris = $this->kueri($penyediaId)->first();

        if ($baris === null) {
            return false;
        }

        $sudahBermasalah = $baris->sedangBermasalah();

        $this->kueri($penyediaId)->update([
            'TerakhirGagalPada' => now(),
            'GalatTerakhir' => mb_substr(trim($pesan), 0, 300),
        ]);

        return ! $sudahBermasalah;
    }

    /** @return Builder<PenyediaLayananOrganisasi> */
    private function kueri(string $penyediaId): Builder
    {
        return PenyediaLayananOrganisasi::query()->withoutGlobalScope(ScopeOrganisasi::class)->whereKey($penyediaId);
    }
}
