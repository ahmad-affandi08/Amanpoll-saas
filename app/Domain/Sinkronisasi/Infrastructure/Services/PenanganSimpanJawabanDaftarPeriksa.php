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
 * Jawaban daftar periksa yang diisi teknisi saat offline (20.05).
 *
 * PelaksanaanDaftarPeriksa tidak memakai kolom versi; penguncian terjadi lewat
 * status. Bila pelaksanaan sudah difinalisasi orang lain selagi perangkat ini
 * offline, jawaban lokal berhenti sebagai konflik dan menunggu keputusan
 * pengguna, bukan dibuang atau menimpa hasil final (20.06).
 */
final class PenanganSimpanJawabanDaftarPeriksa implements PenanganOperasiSinkronisasi
{
    public function __construct(private readonly KelolaPelaksanaanDaftarPeriksa $aksi) {}

    public function operasi(): string
    {
        return 'DaftarPeriksa.SimpanJawaban';
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
            'MuatanData.Jawaban' => ['required', 'array', 'min:1'],
            'MuatanData.Jawaban.*.ButirTemplatDaftarPeriksaId' => ['required', 'string', 'max:26'],
            'MuatanData.Jawaban.*.NilaiTeks' => ['nullable', 'string', 'max:2000'],
            'MuatanData.Jawaban.*.NilaiAngka' => ['nullable', 'numeric'],
            'MuatanData.Jawaban.*.NilaiBoolean' => ['nullable', 'boolean'],
            'MuatanData.Jawaban.*.NilaiTanggal' => ['nullable', 'date'],
            'MuatanData.Jawaban.*.Catatan' => ['nullable', 'string', 'max:1000'],
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
            'NilaiKlien' => ['JumlahJawaban' => count((array) ($antrian->MuatanData['Jawaban'] ?? []))],
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
        /** @var array<int, array<string, mixed>> $jawaban */
        $jawaban = (array) $antrian->MuatanData['Jawaban'];

        $this->aksi->simpanJawaban($pelaksanaan, $jawaban, $pengguna->Id);

        return [
            'Id' => $pelaksanaan->Id,
            'JumlahJawaban' => count($jawaban),
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
