<?php

declare(strict_types=1);

namespace App\Core\Audit;

use App\Domain\IntegrasiAudit\Domain\Repositories\CatatanAksesRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAkses;

/**
 * Untuk akses security-sensitive saja (mis. login), bukan setiap GET biasa --
 * OrganisasiId diterima eksplisit karena percobaan gagal (organisasi/kredensial
 * salah) bisa terjadi sebelum KonteksOrganisasi ditetapkan.
 */
final class LayananCatatanAkses
{
    public function __construct(private readonly CatatanAksesRepository $catatanAksesRepository) {}

    public function catat(
        string $jenis,
        ?string $organisasiId,
        ?string $penggunaId,
        bool $berhasil,
        ?string $alasanGagal = null,
    ): void {
        $this->catatanAksesRepository->simpan(new CatatanAkses([
            'OrganisasiId' => $organisasiId,
            'PenggunaId' => $penggunaId,
            'Jenis' => $jenis,
            'AlamatIp' => request()->ip(),
            'AgenPengguna' => request()->userAgent(),
            'Berhasil' => $berhasil,
            'AlasanGagal' => $alasanGagal,
        ]));
    }
}
