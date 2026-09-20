<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\Actions;

use App\Domain\Notifikasi\Infrastructure\Persistence\Models\TemplatNotifikasi;

final class UbahTemplatNotifikasi
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(TemplatNotifikasi $templatNotifikasi, array $data): TemplatNotifikasi
    {
        $templatNotifikasi->fill($data);
        $templatNotifikasi->save();

        return $templatNotifikasi;
    }
}
