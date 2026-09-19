<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class UnggahLogoOrganisasi
{
    public function jalankan(Organisasi $organisasi, UploadedFile $berkas): Organisasi
    {
        $logoLama = $organisasi->LogoUrl;

        $path = $berkas->store("organisasi/{$organisasi->Id}", 'public');
        if ($path === false) {
            throw new RuntimeException('Gagal menyimpan berkas logo.');
        }

        $organisasi->LogoUrl = Storage::disk('public')->url($path);
        $organisasi->save();

        if ($logoLama) {
            $pathLama = str_replace(Storage::disk('public')->url(''), '', $logoLama);
            Storage::disk('public')->delete($pathLama);
        }

        return $organisasi;
    }
}
