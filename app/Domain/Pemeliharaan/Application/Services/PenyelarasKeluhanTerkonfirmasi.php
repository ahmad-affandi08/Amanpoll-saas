<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Services;

use App\Core\Izin\ScopeLingkup;
use App\Domain\Pemeliharaan\Application\Actions\UbahStatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;

/**
 * Menutup keluhan asal saat perintah kerjanya diverifikasi, bila pelapor sudah
 * menjawab "Sudah beres" di tahap perintah kerja (PRD 8.22).
 *
 * Keluhan yang masih Diterima/Diproses dibawa ke Selesai lewat transisi status yang
 * ada, lalu `UbahStatusKeluhan` sendiri yang menutupnya bersama penilaian pelapor.
 * Keluhan yang sudah Selesai langsung ditutup. Bila keluhan itu masih punya perintah
 * kerja lain yang belum tuntas, keluhan dibiarkan; koordinator yang memutuskannya.
 * Tanpa konfirmasi pelapor, tidak ada yang berubah: keluhan mengikuti alur lama.
 */
final class PenyelarasKeluhanTerkonfirmasi
{
    public function __construct(
        private readonly KonfirmasiPelaporKeluhan $konfirmasiPelapor,
        private readonly UbahStatusKeluhan $ubahStatusKeluhan,
    ) {}

    public function setelahDiverifikasi(PerintahKerja $perintahKerja, string $penggunaId): void
    {
        if ($perintahKerja->KeluhanId === null) {
            return;
        }

        $keluhan = Keluhan::query()->withoutGlobalScope(ScopeLingkup::class)->find($perintahKerja->KeluhanId);

        if (! $keluhan instanceof Keluhan || $this->konfirmasiPelapor->terverifikasi($keluhan) === null) {
            return;
        }

        if ($keluhan->Status === StatusKeluhan::Selesai->value) {
            $this->ubahStatusKeluhan->tutupBilaTerkonfirmasi($keluhan, $penggunaId);

            return;
        }

        if (! in_array($keluhan->Status, [StatusKeluhan::Diterima->value, StatusKeluhan::Diproses->value], true)
            || $this->adaPerintahKerjaLainTerbuka($perintahKerja)) {
            return;
        }

        if ($keluhan->Status === StatusKeluhan::Diterima->value) {
            $keluhan = $this->ubahStatusKeluhan->jalankan($keluhan, StatusKeluhan::Diproses, null, $keluhan->Versi, $penggunaId);
        }

        $this->ubahStatusKeluhan->jalankan(
            $keluhan,
            StatusKeluhan::Selesai,
            "Selesai otomatis: perintah kerja {$perintahKerja->Nomor} diverifikasi.",
            $keluhan->Versi,
            $penggunaId,
        );
    }

    private function adaPerintahKerjaLainTerbuka(PerintahKerja $perintahKerja): bool
    {
        return PerintahKerja::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->where('KeluhanId', $perintahKerja->KeluhanId)
            ->whereKeyNot($perintahKerja->Id)
            ->whereNotIn('Status', [
                StatusPerintahKerja::Selesai->value,
                StatusPerintahKerja::Ditutup->value,
                StatusPerintahKerja::Dibatalkan->value,
            ])
            ->exists();
    }
}
