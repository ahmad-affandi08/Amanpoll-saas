<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Infrastructure\Services;

use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaPelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\Sinkronisasi\Domain\Contracts\PenanganOperasiSinkronisasi;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Support\Facades\Gate;

/**
 * Finalisasi daftar periksa yang ditekan teknisi saat offline (20.05).
 * Pelaksanaan yang sudah difinalisasi di server berhenti sebagai konflik
 * supaya skor yang sudah tercatat tidak dihitung ulang diam-diam (20.06).
 */
final class PenanganFinalisasiDaftarPeriksa implements PenanganOperasiSinkronisasi
{
    public function __construct(private readonly KelolaPelaksanaanDaftarPeriksa $aksi) {}

    public function operasi(): string
    {
        return 'DaftarPeriksa.Finalisasi';
    }

    public function jenisEntitas(): string
    {
        return 'PelaksanaanDaftarPeriksa';
    }

    public function membutuhkanEntitas(): bool
    {
        return true;
    }

    public function aturan(): array
    {
        return [
            'MuatanData.Catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function versiServer(?string $entitasId): ?int
    {
        return null;
    }

    public function diizinkan(AntrianSinkronisasi $antrian, Pengguna $pengguna): bool
    {
        $pelaksanaan = $this->cari($antrian->EntitasId);

        return $pelaksanaan !== null && Gate::forUser($pengguna)->allows('update', $pelaksanaan);
    }

    public function periksaKonflik(AntrianSinkronisasi $antrian): ?array
    {
        $pelaksanaan = $this->wajibAda($antrian->EntitasId);

        if ($pelaksanaan->Status !== 'Selesai') {
            return null;
        }

        return [
            'Alasan' => 'EntitasTerkunci',
            'Pesan' => 'Daftar periksa sudah difinalisasi di server selagi perangkat ini offline.',
            'VersiKlien' => $antrian->VersiKlien,
            'VersiServer' => null,
            'NilaiKlien' => ['Catatan' => $antrian->MuatanData['Catatan'] ?? null],
            'NilaiServer' => [
                'Status' => $pelaksanaan->Status,
                'Skor' => $pelaksanaan->Skor,
                'SelesaiPada' => $pelaksanaan->SelesaiPada?->toIso8601String(),
            ],
        ];
    }

    public function terapkan(AntrianSinkronisasi $antrian, Pengguna $pengguna): array
    {
        $pelaksanaan = $this->wajibAda($antrian->EntitasId);
        $catatan = $antrian->MuatanData['Catatan'] ?? null;

        $hasil = $this->aksi->finalisasi($pelaksanaan, $catatan === null ? null : (string) $catatan, $pengguna->Id);

        return [
            'Id' => $hasil->Id,
            'Status' => $hasil->Status,
            'Skor' => $hasil->Skor,
        ];
    }

    private function cari(?string $entitasId): ?PelaksanaanDaftarPeriksa
    {
        if ($entitasId === null) {
            return null;
        }

        return PelaksanaanDaftarPeriksa::query()->find($entitasId);
    }

    private function wajibAda(?string $entitasId): PelaksanaanDaftarPeriksa
    {
        return $this->cari($entitasId)
            ?? throw new DataTidakDitemukan('Pelaksanaan daftar periksa pada antrean sinkronisasi tidak ditemukan.');
    }
}
