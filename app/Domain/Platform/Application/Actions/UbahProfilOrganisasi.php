<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;

final class UbahProfilOrganisasi
{
    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(Organisasi $organisasi, array $data): Organisasi
    {
        $organisasi->fill($data);
        $organisasi->save();

        return $organisasi;
    }
}
