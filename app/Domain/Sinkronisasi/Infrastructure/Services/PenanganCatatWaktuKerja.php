<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Infrastructure\Services;

use App\Domain\Pemeliharaan\Application\Actions\KelolaWaktuKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Sinkronisasi\Domain\Contracts\PenanganOperasiSinkronisasi;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;

/**
 * Sesi waktu kerja yang direkam teknisi saat offline (20.05). Mutasi bersifat
 * append-only sehingga tidak pernah menimpa data server dan tidak memerlukan
 * pemeriksaan versi; penggandaan dicegah oleh KunciOperasi antrean.
 */
final class PenanganCatatWaktuKerja implements PenanganOperasiSinkronisasi
{
    public function __construct(private readonly KelolaWaktuKerja $aksi) {}

    public function operasi(): string
    {
        return 'PerintahKerja.CatatWaktuKerja';
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
            'MuatanData.MulaiPada' => ['required', 'date'],
            'MuatanData.SelesaiPada' => ['required', 'date', 'after:MuatanData.MulaiPada'],
            'MuatanData.Catatan' => ['nullable', 'string', 'max:1000'],
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
        $perintahKerja = $this->cari($antrian->EntitasId)
            ?? throw new DataTidakDitemukan('Perintah kerja pada antrean sinkronisasi tidak ditemukan.');
        $muatan = $antrian->MuatanData;

        $waktuKerja = $this->aksi->catatSelesai(
            $perintahKerja,
            $pengguna->Id,
            CarbonImmutable::parse((string) $muatan['MulaiPada']),
            CarbonImmutable::parse((string) $muatan['SelesaiPada']),
            isset($muatan['Catatan']) ? (string) $muatan['Catatan'] : null,
        );

        return [
            'Id' => $waktuKerja->Id,
            'DurasiMenit' => $waktuKerja->DurasiMenit,
        ];
    }

    private function cari(?string $entitasId): ?PerintahKerja
    {
        if ($entitasId === null) {
            return null;
        }

        return PerintahKerja::query()->find($entitasId);
    }
}
