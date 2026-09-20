<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\Actions;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\KontakPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Shared\Domain\Contracts\TransaksiDatabase;

final class BuatKontakPenyedia
{
    public function __construct(private readonly TransaksiDatabase $transaksi) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(Penyedia $penyedia, array $data): KontakPenyedia
    {
        return $this->transaksi->jalankan(function () use ($penyedia, $data): KontakPenyedia {
            if ($data['Utama'] ?? false) {
                $penyedia->kontakPenyedia()->update(['Utama' => false]);
            }

            return $penyedia->kontakPenyedia()->create($data);
        });
    }
}
