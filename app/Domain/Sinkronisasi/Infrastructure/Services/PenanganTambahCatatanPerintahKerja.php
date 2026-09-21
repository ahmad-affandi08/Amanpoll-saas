<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Infrastructure\Services;

use App\Domain\Kolaborasi\Application\Actions\TambahKomentar;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Sinkronisasi\Domain\Contracts\PenanganOperasiSinkronisasi;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use Illuminate\Support\Facades\Gate;

/**
 * Draft catatan lapangan yang ditulis teknisi saat offline (20.05). Catatan
 * masuk sebagai komentar entitas PerintahKerja, append-only dan tanpa versi.
 */
final class PenanganTambahCatatanPerintahKerja implements PenanganOperasiSinkronisasi
{
    public function __construct(private readonly TambahKomentar $aksi) {}

    public function operasi(): string
    {
        return 'PerintahKerja.TambahCatatan';
    }

    public function jenisEntitas(): string
    {
        return 'PerintahKerja';
    }

    public function membutuhkanEntitas(): bool
    {
        return true;
    }

    public function aturan(): array
    {
        return [
            'MuatanData.Isi' => ['required', 'string', 'max:5000'],
        ];
    }

    public function versiServer(?string $entitasId): ?int
    {
        return null;
    }

    public function diizinkan(AntrianSinkronisasi $antrian, Pengguna $pengguna): bool
    {
        $perintahKerja = $this->cari($antrian->EntitasId);

        return $perintahKerja !== null && Gate::forUser($pengguna)->allows('operate', $perintahKerja);
    }

    public function periksaKonflik(AntrianSinkronisasi $antrian): ?array
    {
        return null;
    }

    public function terapkan(AntrianSinkronisasi $antrian, Pengguna $pengguna): array
    {
        $komentar = $this->aksi->jalankan(
            'PerintahKerja',
            (string) $antrian->EntitasId,
            (string) $antrian->MuatanData['Isi'],
            null,
            $pengguna->Id,
        );

        return ['Id' => $komentar->Id];
    }

    private function cari(?string $entitasId): ?PerintahKerja
    {
        if ($entitasId === null) {
            return null;
        }

        return PerintahKerja::query()->find($entitasId);
    }
}
