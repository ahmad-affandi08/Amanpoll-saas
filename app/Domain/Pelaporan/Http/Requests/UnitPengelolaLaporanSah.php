<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Requests;

use App\Domain\Pelaporan\Application\Services\PenjagaFilterMetrik;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Satu nilai filter unit pengelola pada laporan tersimpan dan permintaan ekspor (PRD 8.21).
 *
 * Lebih longgar daripada UnitPengelolaSah milik formulir: unit pengelola yang
 * kini nonaktif tetap boleh dipakai menyaring, karena tiket lamanya masih ada.
 * Yang ditolak adalah unit organisasi yang bukan unit pengelola, unit
 * organisasi lain, dan id karangan -- laporan yang tersimpan dengan nilai
 * seperti itu akan selalu kosong tanpa penjelasan.
 */
final class UnitPengelolaLaporanSah implements ValidationRule
{
    /** @var list<string>|null */
    private ?array $idSah = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $this->idSah ??= app(PenjagaFilterMetrik::class)->idSah();

        if (! is_string($value) || ! in_array($value, $this->idSah, true)) {
            $fail('Unit pengelola yang dipilih tidak ditemukan.');
        }
    }
}
