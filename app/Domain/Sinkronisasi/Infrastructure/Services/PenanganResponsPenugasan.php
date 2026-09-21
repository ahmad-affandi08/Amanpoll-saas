<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Infrastructure\Services;

use App\Domain\Pemeliharaan\Application\Actions\ResponsPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Sinkronisasi\Domain\Contracts\PenanganOperasiSinkronisasi;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Teknisi menerima atau menolak penugasan dari lapangan (20.05). Ini transisi
 * pertama yang ia lakukan setelah pekerjaan masuk, jadi harus tersedia offline
 * supaya pekerjaan tidak macet di status Ditugaskan sampai sinyal kembali.
 */
final class PenanganResponsPenugasan implements PenanganOperasiSinkronisasi
{
    public function __construct(private readonly ResponsPenugasanPerintahKerja $aksi) {}

    public function operasi(): string
    {
        return 'PerintahKerja.ResponsPenugasan';
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
            'MuatanData.Respons' => ['required', Rule::in(['Terima', 'Tolak'])],
            'MuatanData.Catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function versiServer(?string $entitasId): ?int
    {
        return $this->cari($entitasId)?->Versi;
    }

    public function diizinkan(AntrianSinkronisasi $antrian, Pengguna $pengguna): bool
    {
        $perintahKerja = $this->cari($antrian->EntitasId);
        $penugasan = $this->penugasanAktif($antrian->EntitasId, $pengguna);

        if ($perintahKerja === null || $penugasan === null) {
            return false;
        }

        return Gate::forUser($pengguna)->allows('responsPenugasan', [$perintahKerja, $penugasan]);
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
            'NilaiKlien' => ['Respons' => $antrian->MuatanData['Respons'] ?? null],
            'NilaiServer' => ['Status' => $perintahKerja->Status],
        ];
    }

    public function terapkan(AntrianSinkronisasi $antrian, Pengguna $pengguna): array
    {
        $penugasan = $this->penugasanAktif($antrian->EntitasId, $pengguna)
            ?? throw new DataTidakDitemukan('Penugasan aktif untuk teknisi ini tidak ditemukan.');
        $muatan = $antrian->MuatanData;

        $this->aksi->jalankan(
            $penugasan,
            (string) $muatan['Respons'],
            isset($muatan['Catatan']) ? (string) $muatan['Catatan'] : null,
            $pengguna->Id,
        );

        return [
            'Id' => $penugasan->Id,
            'Status' => $penugasan->refresh()->Status,
        ];
    }

    private function penugasanAktif(?string $entitasId, Pengguna $pengguna): ?PenugasanPerintahKerja
    {
        if ($entitasId === null) {
            return null;
        }

        return PenugasanPerintahKerja::query()
            ->where('PerintahKerjaId', $entitasId)
            ->where('PenggunaId', $pengguna->Id)
            ->where('Status', StatusPenugasanPerintahKerja::Ditugaskan->value)
            ->first();
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
