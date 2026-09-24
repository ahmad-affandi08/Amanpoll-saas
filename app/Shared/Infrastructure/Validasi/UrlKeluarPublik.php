<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validasi;

use App\Shared\Infrastructure\Keamanan\PenjagaUrlKeluar;
use App\Shared\Infrastructure\Keamanan\UrlKeluarDitolak;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * URL isian yang kelak dipanggil server harus menunjuk host publik.
 *
 * Hanya memberi pesan lebih awal saat menyimpan. Jawaban DNS dapat berubah
 * sesudahnya, jadi setiap pengiriman tetap memeriksa ulang lewat
 * `PenjagaUrlKeluar::periksa()`.
 */
final class UrlKeluarPublik implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('Alamat tidak valid.');

            return;
        }

        try {
            app(PenjagaUrlKeluar::class)->periksa($value);
        } catch (UrlKeluarDitolak $galat) {
            $fail($galat->getMessage());
        }
    }
}
