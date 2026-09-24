<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Services;

use App\Core\Izin\ScopeLingkup;
use App\Domain\Pemeliharaan\Domain\Enums\HasilKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KonfirmasiPenerimaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;

/**
 * Penyatu konfirmasi pelapor di tahap perintah kerja dengan konfirmasi keluhan (PRD 4.6, 8.22).
 *
 * Pelapor yang sudah menjawab "Sudah beres" saat teknisi menyerahkan pekerjaan tidak
 * ditanya lagi saat keluhannya Selesai: keluhan ditutup otomatis begitu perintah
 * kerjanya diverifikasi. "Dari pelapor" berarti konfirmasi "Diterima" yang masih
 * berlaku dan dikirim dari akun pelapor keluhan itu, lewat cara apa pun (layar pelapor
 * atau pindai QR).
 *
 * Perintah kerja dibaca tanpa `ScopeLingkup`: pertanyaannya dijawab untuk pelapor
 * (yang tidak melihat tiket) maupun koordinator yang memverifikasi.
 */
final class KonfirmasiPelaporKeluhan
{
    /** Konfirmasi pelapor pada perintah kerja keluhan ini yang sudah diverifikasi koordinator. */
    public function terverifikasi(Keluhan $keluhan): ?KonfirmasiPenerimaPerintahKerja
    {
        return $this->cari($keluhan, [StatusPerintahKerja::Selesai, StatusPerintahKerja::Ditutup]);
    }

    /** Konfirmasi pelapor yang masih berlaku, sudah atau belum diverifikasi. */
    public function sudahDikonfirmasi(Keluhan $keluhan): bool
    {
        return $this->cari($keluhan, null) !== null;
    }

    /**
     * Permintaan konfirmasi yang sedang menunggu pelapor, per keluhan: perintah kerja
     * terbaru keluhan itu yang Menunggu Verifikasi, belum punya konfirmasi "Diterima"
     * yang berlaku, dan tidak dikerjakan pelapor itu sendiri.
     *
     * @param  list<string>  $keluhanId
     * @return array<string, PerintahKerja> kunci: KeluhanId
     */
    public function menungguPelapor(array $keluhanId, string $pelaporId): array
    {
        if ($keluhanId === []) {
            return [];
        }

        $daftar = PerintahKerja::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->whereIn('KeluhanId', $keluhanId)
            ->where('Status', StatusPerintahKerja::MenungguVerifikasi->value)
            ->whereDoesntHave('konfirmasiPenerima', fn ($kueri) => $kueri
                ->where('Berlaku', true)
                ->where('Hasil', HasilKonfirmasiPenerima::Diterima->value))
            ->whereDoesntHave('penugasan', fn ($kueri) => $kueri
                ->where('PenggunaId', $pelaporId)
                ->whereIn('Status', [
                    StatusPenugasanPerintahKerja::Ditugaskan->value,
                    StatusPenugasanPerintahKerja::Diterima->value,
                    StatusPenugasanPerintahKerja::Selesai->value,
                ]))
            ->whereHas('keluhan', fn ($kueri) => $kueri->withoutGlobalScope(ScopeLingkup::class)->where('PelaporId', $pelaporId))
            ->latest('DiperbaruiPada')
            ->orderBy('Id')
            ->get();

        $hasil = [];
        foreach ($daftar as $perintahKerja) {
            $hasil[(string) $perintahKerja->KeluhanId] ??= $perintahKerja;
        }

        return $hasil;
    }

    /**
     * Keluhan yang pelapornya sudah menjawab "Sudah beres" di tahap perintah kerja dan
     * masih menunggu verifikasi koordinator.
     *
     * @param  list<string>  $keluhanId
     * @return list<string>
     */
    public function sudahDikonfirmasiPelapor(array $keluhanId, string $pelaporId): array
    {
        if ($keluhanId === []) {
            return [];
        }

        return array_values(array_unique(PerintahKerja::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->whereIn('KeluhanId', $keluhanId)
            ->where('Status', StatusPerintahKerja::MenungguVerifikasi->value)
            ->whereHas('konfirmasiPenerima', fn ($kueri) => $kueri
                ->where('Berlaku', true)
                ->where('Hasil', HasilKonfirmasiPenerima::Diterima->value)
                ->where('PenggunaId', $pelaporId))
            ->pluck('KeluhanId')
            ->map(fn (mixed $id): string => (string) $id)
            ->all()));
    }

    /** @param  list<StatusPerintahKerja>|null  $statusPerintahKerja */
    private function cari(Keluhan $keluhan, ?array $statusPerintahKerja): ?KonfirmasiPenerimaPerintahKerja
    {
        if ($keluhan->PelaporId === null) {
            return null;
        }

        $perintahKerjaId = PerintahKerja::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->where('KeluhanId', $keluhan->Id)
            ->when($statusPerintahKerja !== null, fn ($kueri) => $kueri->whereIn(
                'Status',
                array_map(fn (StatusPerintahKerja $status): string => $status->value, $statusPerintahKerja ?? []),
            ))
            ->pluck('Id');

        if ($perintahKerjaId->isEmpty()) {
            return null;
        }

        return KonfirmasiPenerimaPerintahKerja::query()
            ->whereIn('PerintahKerjaId', $perintahKerjaId)
            ->where('PenggunaId', $keluhan->PelaporId)
            ->where('Hasil', HasilKonfirmasiPenerima::Diterima->value)
            ->where('Berlaku', true)
            ->latest('DikonfirmasiPada')
            ->orderByDesc('Id')
            ->first();
    }
}
