<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\Actions;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\KontakPenyedia;
use App\Shared\Domain\Contracts\TransaksiDatabase;

final class UbahKontakPenyedia
{
    public function __construct(private readonly TransaksiDatabase $transaksi) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(KontakPenyedia $kontakPenyedia, array $data): KontakPenyedia
    {
        return $this->transaksi->jalankan(function () use ($kontakPenyedia, $data): KontakPenyedia {
            if ($data['Utama'] ?? false) {
                KontakPenyedia::query()
                    ->where('PenyediaId', $kontakPenyedia->PenyediaId)
                    ->where('Id', '!=', $kontakPenyedia->Id)
                    ->update(['Utama' => false]);
            }

            $kontakPenyedia->fill($data);
            $kontakPenyedia->save();

            return $kontakPenyedia;
        });
    }
}
