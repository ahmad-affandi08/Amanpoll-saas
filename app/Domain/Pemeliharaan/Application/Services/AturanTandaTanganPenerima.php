<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Services;

use App\Core\Konfigurasi\LayananKonfigurasi;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Tanda tangan penerima saat teknisi menyelesaikan tiket (PRD 8.20, TASK 39.10).
 *
 * Opsional secara bawaan; organisasi bisa mewajibkannya lewat konfigurasi
 * `Pemeliharaan.WajibTandaTanganPenerima`. Yang diwajibkan adalah teknisi yang
 * ditugaskan ketika ia menyerahkan pekerjaannya ke Menunggu Verifikasi. Koordinator
 * yang tidak ditugaskan tetap bisa memindahkan status dari dasbor, karena dasbor tidak
 * punya kotak tanda tangan dan penerima menandatangani di hadapan teknisi, bukan
 * koordinator.
 */
final class AturanTandaTanganPenerima
{
    public const KUNCI_KONFIGURASI = 'Pemeliharaan.WajibTandaTanganPenerima';

    /** Kategori lampiran Kolaborasi yang diunggah layar Ringkasan teknisi. */
    public const KATEGORI_LAMPIRAN = 'TandaTangan';

    public function __construct(private readonly LayananKonfigurasi $konfigurasi) {}

    public function wajib(string $organisasiId): bool
    {
        return (bool) $this->konfigurasi->ambil($organisasiId, self::KUNCI_KONFIGURASI);
    }

    /** Menolak penyelesaian teknisi tanpa lampiran tanda tangan saat organisasi mewajibkannya. */
    public function pastikanTerpenuhi(PerintahKerja $perintahKerja, string $penggunaId): void
    {
        if (! $this->wajib($perintahKerja->OrganisasiId) || ! $this->diselesaikanTeknisi($perintahKerja, $penggunaId)) {
            return;
        }

        if (! $this->adaTandaTangan($perintahKerja)) {
            throw new AturanBisnisDilanggar('Tanda tangan penerima wajib dilampirkan sebelum pekerjaan dikirim untuk verifikasi.');
        }
    }

    private function diselesaikanTeknisi(PerintahKerja $perintahKerja, string $penggunaId): bool
    {
        return $perintahKerja->penugasan()
            ->where('PenggunaId', $penggunaId)
            ->whereIn('Status', [StatusPenugasanPerintahKerja::Ditugaskan->value, StatusPenugasanPerintahKerja::Diterima->value])
            ->exists();
    }

    private function adaTandaTangan(PerintahKerja $perintahKerja): bool
    {
        return LampiranEntitas::query()
            ->where('JenisEntitas', 'PerintahKerja')
            ->where('EntitasId', $perintahKerja->Id)
            ->where('Kategori', self::KATEGORI_LAMPIRAN)
            ->exists();
    }
}
