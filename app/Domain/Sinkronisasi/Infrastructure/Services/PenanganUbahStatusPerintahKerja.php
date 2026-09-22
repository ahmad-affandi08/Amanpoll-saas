<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Infrastructure\Services;

use App\Domain\Pemeliharaan\Application\Actions\UbahStatusPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Sinkronisasi\Domain\Contracts\PenanganOperasiSinkronisasi;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Perubahan status pekerjaan yang dibuat teknisi saat offline (20.05). */
final class PenanganUbahStatusPerintahKerja implements PenanganOperasiSinkronisasi
{
    public function __construct(private readonly UbahStatusPerintahKerja $aksi) {}

    public function operasi(): string
    {
        return 'PerintahKerja.UbahStatus';
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
            'MuatanData.Status' => ['required', Rule::enum(StatusPerintahKerja::class)],
            'MuatanData.Catatan' => ['nullable', 'string', 'max:1000'],
            'MuatanData.Ringkasan' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function versiServer(?string $entitasId): ?int
    {
        return $this->cari($entitasId)?->Versi;
    }

    public function diizinkan(AntrianSinkronisasi $antrian, Pengguna $pengguna): bool
    {
        $perintahKerja = $this->cari($antrian->EntitasId);
        if ($perintahKerja === null) {
            return false;
        }

        $status = (string) ($antrian->MuatanData['Status'] ?? '');

        return Gate::forUser($pengguna)->allows('ubahStatus', [$perintahKerja, $status]);
    }

    public function periksaKonflik(AntrianSinkronisasi $antrian): ?array
    {
        $perintahKerja = $this->wajibAda($antrian->EntitasId);

        if ($antrian->VersiKlien === null || $antrian->VersiKlien === $perintahKerja->Versi) {
            return null;
        }

        return [
            'Alasan' => 'VersiBerbeda',
            'Pesan' => 'Perintah kerja sudah berubah di server sejak perangkat ini offline.',
            'VersiKlien' => $antrian->VersiKlien,
            'VersiServer' => $perintahKerja->Versi,
            'NilaiKlien' => ['Status' => $antrian->MuatanData['Status'] ?? null],
            'NilaiServer' => [
                'Status' => $perintahKerja->Status,
                'DiperbaruiPada' => $perintahKerja->DiperbaruiPada->toIso8601String(),
            ],
        ];
    }

    public function terapkan(AntrianSinkronisasi $antrian, Pengguna $pengguna): array
    {
        $perintahKerja = $this->wajibAda($antrian->EntitasId);
        $muatan = $antrian->MuatanData;

        $hasil = $this->aksi->jalankan(
            $perintahKerja,
            StatusPerintahKerja::from((string) $muatan['Status']),
            isset($muatan['Catatan']) ? (string) $muatan['Catatan'] : null,
            isset($muatan['Ringkasan']) ? (string) $muatan['Ringkasan'] : null,
            $antrian->VersiKlien ?? $perintahKerja->Versi,
            $pengguna->Id,
        );

        return [
            'Id' => $hasil->Id,
            'Status' => $hasil->Status,
            'Versi' => $hasil->Versi,
        ];
    }

    private function cari(?string $entitasId): ?PerintahKerja
    {
        if ($entitasId === null) {
            return null;
        }

        return PerintahKerja::query()->find($entitasId);
    }

    private function wajibAda(?string $entitasId): PerintahKerja
    {
        return $this->cari($entitasId)
            ?? throw new DataTidakDitemukan('Perintah kerja pada antrean sinkronisasi tidak ditemukan.');
    }
}
