<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use Illuminate\Http\UploadedFile;

/**
 * Unggahan pengguna ke `Berkas`. Nama file dari klien tidak pernah dipakai untuk
 * path fisik (ULID + ekstensi hasil deteksi MIME); isinya dipadatkan dan salinan
 * fisiknya dipakai bersama lewat PenyimpanBerkas (PRD 11.1).
 */
final class UnggahBerkas
{
    public function __construct(private readonly PenyimpanBerkas $penyimpan) {}

    public function jalankan(UploadedFile $berkas, ?string $pengunggahId): Berkas
    {
        return $this->penyimpan->simpanUnggahan($berkas, $pengunggahId);
    }
}
