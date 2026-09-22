<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Contracts;

use App\Domain\Langganan\Domain\ValueObjects\PeristiwaPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;

/** Abstraksi penyedia pembayaran (22.06, PRD 8.19). */
interface PenyediaPembayaran
{
    /** Kode stabil yang disimpan pada PembayaranLangganan.PenyediaPembayaran. */
    public function kode(): string;

    public function nama(): string;

    /**
     * Menyiapkan pembayaran untuk sebuah tagihan dan mengembalikan instruksi
     * bagi pengguna: URL pengalihan untuk gateway, atau rincian transfer untuk
     * penyedia manual.
     *
     * @return array<string, mixed>
     */
    public function mulaiPembayaran(TagihanLangganan $tagihan): array;

    /**
     * @param  array<string, mixed>  $muatan
     * @param  array<string, string>  $header
     */
    public function webhookSah(array $muatan, array $header): bool;

    /** @param  array<string, mixed>  $muatan */
    public function terjemahkanWebhook(array $muatan): PeristiwaPembayaran;
}
