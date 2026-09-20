<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KodeKegagalan;

final class SimpanKodeKegagalan
{
    public function __construct(private readonly LayananAudit $audit) {}

    /** @param array<string, mixed> $data */
    public function jalankan(array $data, ?KodeKegagalan $kode = null): KodeKegagalan
    {
        $kode ??= new KodeKegagalan;
        $sebelum = $kode->exists ? $kode->toArray() : null;
        $kode->fill($data)->save();
        $this->audit->catat($sebelum === null ? 'Buat' : 'Ubah', 'KodeKegagalan', $kode->Id, $sebelum, $kode->toArray());

        return $kode;
    }
}
