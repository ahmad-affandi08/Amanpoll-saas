<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananOrganisasi;

/**
 * Menghapus kredensial email atau WhatsApp milik organisasi (PRD 8.23).
 *
 * Notifikasi sesudahnya kembali lewat penyedia platform. Menonaktifkan saja sudah
 * cukup untuk berhenti memakainya; penghapusan untuk membuang kredensial seutuhnya,
 * mis. setelah akun penyedia ditutup atau tokennya bocor.
 */
final class HapusPenyediaLayananOrganisasi
{
    public function __construct(private readonly LayananAudit $audit) {}

    public function jalankan(string $organisasiId, KategoriPenyediaLayanan $kategori, string $kode): void
    {
        $baris = PenyediaLayananOrganisasi::query()
            ->where('OrganisasiId', $organisasiId)
            ->where('Kategori', $kategori->value)
            ->where('Kode', $kode)
            ->first();

        if ($baris === null) {
            return;
        }

        $baris->delete();

        $this->audit->catat(
            'PenyediaLayananOrganisasi.Dihapus',
            'PenyediaLayananOrganisasi',
            $baris->Id,
            dataSebelum: ['Kategori' => $kategori->value, 'Kode' => $kode, 'Aktif' => $baris->Aktif],
        );
    }
}
