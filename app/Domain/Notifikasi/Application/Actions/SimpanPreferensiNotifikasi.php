<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\Actions;

use App\Domain\Notifikasi\Infrastructure\Persistence\Models\PreferensiNotifikasi;

final class SimpanPreferensiNotifikasi
{
    public function jalankan(string $penggunaId, string $jenisPeristiwa, string $kanal, bool $aktif): PreferensiNotifikasi
    {
        return PreferensiNotifikasi::updateOrCreate(
            ['PenggunaId' => $penggunaId, 'JenisPeristiwa' => $jenisPeristiwa, 'Kanal' => $kanal],
            ['Aktif' => $aktif],
        );
    }
}
