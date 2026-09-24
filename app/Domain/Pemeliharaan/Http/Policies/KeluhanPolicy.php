<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Policies;

use App\Core\Izin\LingkupAkses;
use App\Core\Izin\PemeriksaIzin;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class KeluhanPolicy
{
    public function __construct(
        private readonly PemeriksaIzin $izin,
        private readonly LingkupAkses $lingkup,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return true;
    }

    public function view(Pengguna $pengguna, Keluhan $keluhan): bool
    {
        return $keluhan->PelaporId === $pengguna->Id || $this->dapatMengelola($pengguna);
    }

    /**
     * Memantau garis waktu status keluhan rekan (PRD 8.20): hanya keluhan yang
     * lokasinya ada di lingkup unit/ruangan pengguna. Diperiksa di sini, bukan
     * hanya lewat `ScopeLingkup` pada pengikatan rute, supaya pemanggil yang
     * memuat keluhan tanpa scope tetap tertolak. Yang boleh dilihat terbatas
     * pada nomor, judul, alat/lokasi, status, dan jamnya — penyusun layarnya
     * yang memastikan.
     */
    public function pantau(Pengguna $pengguna, Keluhan $keluhan): bool
    {
        if ($pengguna->Status !== 'Aktif' || $keluhan->OrganisasiId !== $this->konteks->id()) {
            return false;
        }

        if ($keluhan->PelaporId === $pengguna->Id || $this->lingkup->tanpaBatas($pengguna->Id)) {
            return true;
        }

        return in_array($keluhan->LokasiId, $this->lingkup->lokasiDiizinkan($pengguna->Id), true);
    }

    public function create(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function ubahStatus(Pengguna $pengguna, Keluhan $keluhan, string $statusTujuan): bool
    {
        if ($this->dapatMengelola($pengguna)) {
            return true;
        }

        return $keluhan->PelaporId === $pengguna->Id
            && $statusTujuan === StatusKeluhan::Dibatalkan->value
            && in_array($keluhan->Status, [StatusKeluhan::Baru->value, StatusKeluhan::Ditinjau->value], true);
    }

    /**
     * Verifikasi hasil perbaikan (PRD 4.6): hanya pelapornya sendiri, dan hanya
     * selagi keluhan berstatus Selesai. Izin Kelola tidak membukanya, karena
     * yang ditanyakan adalah pendapat orang yang melapor.
     */
    public function konfirmasi(Pengguna $pengguna, Keluhan $keluhan): bool
    {
        return $keluhan->PelaporId === $pengguna->Id
            && $keluhan->Status === StatusKeluhan::Selesai->value;
    }

    public function ubahPrioritas(Pengguna $pengguna, Keluhan $keluhan): bool
    {
        return $this->dapatMengelola($pengguna);
    }

    private function dapatMengelola(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Keluhan.Kelola');
    }
}
