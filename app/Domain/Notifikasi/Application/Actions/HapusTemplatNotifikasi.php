<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\Actions;

use App\Domain\Notifikasi\Infrastructure\Persistence\Models\TemplatNotifikasi;

final class HapusTemplatNotifikasi
{
    public function jalankan(TemplatNotifikasi $templatNotifikasi): void
    {
        $templatNotifikasi->delete();
    }
}
