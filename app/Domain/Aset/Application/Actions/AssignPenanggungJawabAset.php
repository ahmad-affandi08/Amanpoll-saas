<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatPenanggungJawabAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;

/**
 * "Ganti" penanggung jawab = tutup baris yang masih terbuka (SelesaiPada
 * null) lalu buat baris baru -- tidak pernah update baris lama isinya,
 * supaya histori append-oriented (pola sama seperti KeputusanPersetujuan).
 */
final class AssignPenanggungJawabAset
{
    public function __construct(private readonly TransaksiDatabase $transaksi) {}

    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(Aset $aset, array $data): RiwayatPenanggungJawabAset
    {
        return $this->transaksi->jalankan(function () use ($aset, $data): RiwayatPenanggungJawabAset {
            $aset->riwayatPenanggungJawab()->whereNull('SelesaiPada')->update(['SelesaiPada' => now()]);

            $data['AsetId'] = $aset->Id;
            $data['MulaiPada'] = now();

            return RiwayatPenanggungJawabAset::create($data);
        });
    }
}
