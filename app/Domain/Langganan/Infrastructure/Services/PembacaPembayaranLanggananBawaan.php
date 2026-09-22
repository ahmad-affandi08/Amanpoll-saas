<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Services;

use App\Domain\Langganan\Domain\Contracts\PembacaPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\ValueObjects\PembayaranTerkonfirmasi;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PembayaranLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;

/** Pembacaan berjalan lepas dari scope tenant: webhook pembayaran tiba tanpa konteks organisasi. */
final class PembacaPembayaranLanggananBawaan implements PembacaPembayaranLangganan
{
    public function konfirmasi(string $pembayaranId): ?PembayaranTerkonfirmasi
    {
        $pembayaran = PembayaranLangganan::query()
            ->withoutGlobalScopes()
            ->where('Id', $pembayaranId)
            ->where('Status', StatusPembayaranLangganan::Berhasil->value)
            ->whereNotNull('DibayarPada')
            ->first();

        if ($pembayaran === null) {
            return null;
        }

        $jumlah = (float) $pembayaran->Jumlah;

        if ($jumlah <= 0.0) {
            return null;
        }

        $tagihan = TagihanLangganan::query()
            ->withoutGlobalScopes()
            ->find((string) $pembayaran->TagihanLanggananId);

        return new PembayaranTerkonfirmasi(
            pembayaranId: (string) $pembayaran->Id,
            organisasiId: (string) $pembayaran->OrganisasiId,
            langgananId: $tagihan === null ? null : (string) $tagihan->LanggananId,
            jumlah: $jumlah,
            dibayarPada: $pembayaran->DibayarPada,
        );
    }
}
