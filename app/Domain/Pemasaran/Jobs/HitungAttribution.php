<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Jobs;

use App\Domain\Pemasaran\Application\Services\PenyusunUlangAttribution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Menyusun ulang attribution satu pengunjung dari sesi-sesinya
 * (MARKETING.md 29).
 *
 * Attribution ditulis langsung saat kunjungan terjadi, jadi pekerjaan ini bukan
 * jalur utamanya. Ia dibutuhkan untuk dua hal yang pasti terjadi: mengisi
 * kembali attribution setelah kampanye baru didaftarkan — kunjungan lama
 * menyimpan `utm_campaign` tetapi belum tertaut ke barisnya — dan memperbaiki
 * baris yang rusak tanpa menyentuh data mentahnya.
 */
final class HitungAttribution implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $pengenalPengunjung) {}

    public function handle(PenyusunUlangAttribution $penyusun): void
    {
        $penyusun->susunUlang($this->pengenalPengunjung);
    }

    /** Satu pengunjung tidak perlu disusun ulang berkali-kali sekaligus. */
    public function uniqueId(): string
    {
        return $this->pengenalPengunjung;
    }
}
