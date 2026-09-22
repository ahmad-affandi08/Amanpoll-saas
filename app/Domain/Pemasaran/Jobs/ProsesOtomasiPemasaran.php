<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Jobs;

use App\Domain\Pemasaran\Application\Services\PenjalanOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksekusiOtomasiPemasaran;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Menjalankan satu eksekusi otomasi; percobaan ulangnya dihitung di barisnya sendiri, bukan oleh antrean. */
final class ProsesOtomasiPemasaran implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private readonly string $eksekusiId)
    {
        $this->onConnection('database');
    }

    public function handle(PenjalanOtomasi $penjalan): void
    {
        // `event` dibaca `PenjalanOtomasi` untuk menyusun konteksnya; dimuat di sini supaya
        // kebutuhan itu tersurat, bukan muncul sebagai kueri kedua dari dalam layanan.
        $eksekusi = EksekusiOtomasiPemasaran::query()->with('event')->find($this->eksekusiId);

        if ($eksekusi !== null && ! $eksekusi->Status->final()) {
            $penjalan->jalankan($eksekusi);
        }
    }

    public function uniqueId(): string
    {
        return $this->eksekusiId;
    }
}
