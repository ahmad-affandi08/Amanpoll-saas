<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KodeReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/** Menolak self-referral; diperiksa saat klik dan lagi sebelum imbalannya, sebab pengunjung awalnya masih anonim (MARKETING.md 20). */
final class PenjagaSelfReferral
{
    /** Alasan penolakan, atau null bila referralnya sah. */
    public function alasanTolak(
        KodeReferral $kode,
        ?string $pengenalPengunjung = null,
        ?Prospek $prospek = null,
        ?string $organisasiBaruId = null,
    ): ?string {
        $perujuk = (string) $kode->OrganisasiId;

        if ($organisasiBaruId !== null && $organisasiBaruId === $perujuk) {
            return 'Organisasi yang diajak sama dengan organisasi perujuknya.';
        }

        if ($prospek !== null && $prospek->OrganisasiId === $perujuk) {
            return 'Prospek ini sudah menjadi bagian dari organisasi perujuknya.';
        }

        if ($prospek !== null && $this->emailMilikPerujuk((string) $prospek->Email, $perujuk)) {
            return 'Alamat email yang diajak terdaftar sebagai pengguna organisasi perujuknya.';
        }

        if ($pengenalPengunjung !== null && $this->pengunjungMilikPerujuk($pengenalPengunjung, $perujuk)) {
            return 'Pengunjung ini sudah tertaut ke organisasi perujuknya.';
        }

        return null;
    }

    private function emailMilikPerujuk(string $email, string $perujuk): bool
    {
        $bersih = mb_strtolower(trim($email));

        if ($bersih === '') {
            return false;
        }

        return Pengguna::query()
            ->withoutGlobalScopes()
            ->where('OrganisasiId', $perujuk)
            ->whereRaw('lower(Email) = ?', [$bersih])
            ->exists();
    }

    /** Pengunjung yang jejaknya sudah menjadi prospek milik organisasi perujuk. */
    private function pengunjungMilikPerujuk(string $pengenalPengunjung, string $perujuk): bool
    {
        return Prospek::query()
            ->where('PengenalPengunjung', $pengenalPengunjung)
            ->where('OrganisasiId', $perujuk)
            ->exists();
    }
}
