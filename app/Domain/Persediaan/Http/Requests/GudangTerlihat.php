<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use App\Domain\Persediaan\Application\Services\LingkupGudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Isian `GudangId` (dan kawan-kawannya) harus menunjuk gudang yang terlihat pengirimnya (PRD 8.21).
 *
 * Menggantikan `Rule::exists('Gudang', ...)`: membaca lewat model, jadi
 * ScopeOrganisasi dan ScopeLingkup Gudang ikut berlaku. Pengguna berlingkup IT
 * tidak bisa mengirim mutasi, reservasi, atau penerimaan ke gudang IPSRS walau
 * tahu Id-nya; pengguna tanpa batas diperiksa seperti `exists` biasa.
 *
 * Dipakai juga oleh jalur reservasi perintah kerja dan Mode Lapangan, karena
 * keduanya memakai SimpanReservasiSukuCadangRequest yang sama.
 */
final class GudangTerlihat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (is_string($value) && Gudang::query()->whereKey($value)->exists()) {
            return;
        }

        $fail(app(LingkupGudang::class)->berlaku()
            ? 'Gudang yang dipilih tidak ditemukan atau di luar lingkup akses Anda.'
            : 'Gudang yang dipilih tidak ditemukan.');
    }
}
