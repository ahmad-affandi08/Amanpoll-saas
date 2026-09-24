<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Aturan bersama isian `UnitPengelolaId` (PRD 8.21): unit organisasi yang sama,
 * belum dihapus, aktif, dan bertanda Mengelola Aset.
 *
 * Satu kelas untuk seluruh formulir -- aset, gudang, kategori keluhan,
 * keluhan, perintah kerja, rencana -- supaya "unit pengelola yang sah" tidak
 * didefinisikan ulang dengan syarat yang berbeda-beda di tiap domain.
 *
 * Membaca tabel langsung seperti `Rule::exists`, jadi tidak terikat
 * ScopeLingkup: koordinator berlingkup IT tetap boleh mengalihkan keluhan ke
 * IPSRS walau unit itu di luar lingkupnya. Tenancy dijaga eksplisit lewat
 * `OrganisasiId`.
 */
final class UnitPengelolaSah implements ValidationRule
{
    /**
     * @param  string|null  $nilaiTersimpan  Unit pengelola yang sudah tersimpan pada baris yang
     *                                       sedang diubah. Nilai itu tetap diterima walau unitnya kini nonaktif,
     *                                       supaya menyimpan ulang formulir tanpa menyentuh isian ini tidak ditolak.
     */
    public function __construct(private readonly ?string $nilaiTersimpan = null) {}

    /**
     * Aturan lengkap untuk dipasang di FormRequest.
     *
     * @return list<string|ValidationRule>
     */
    public static function aturan(?string $nilaiTersimpan = null): array
    {
        return ['nullable', 'string', new self($nilaiTersimpan)];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $alasan = is_string($value)
            ? $this->alasanDitolak($value)
            : 'Unit pengelola tidak valid.';

        if ($alasan !== null) {
            $fail($alasan);
        }
    }

    /**
     * Pemeriksaan yang sama untuk pemanggil di luar FormRequest (impor, Action).
     *
     * @return string|null Pesan penolakan, atau null bila unitnya sah.
     */
    public function alasanDitolak(string $unitId): ?string
    {
        $unit = DB::table('UnitOrganisasi')
            ->where('OrganisasiId', app(KonteksOrganisasi::class)->id())
            ->where('Id', $unitId)
            ->whereNull('DihapusPada')
            ->first(['Status', 'MengelolaAset']);

        if ($unit === null) {
            return 'Unit pengelola yang dipilih tidak ditemukan.';
        }

        if ($this->nilaiTersimpan !== null && $unitId === $this->nilaiTersimpan) {
            return null;
        }

        if ($unit->Status !== 'Aktif') {
            return 'Unit pengelola yang dipilih sedang nonaktif.';
        }

        if (! (bool) $unit->MengelolaAset) {
            return 'Unit yang dipilih belum ditandai Mengelola Aset di halaman Unit Organisasi.';
        }

        return null;
    }
}
