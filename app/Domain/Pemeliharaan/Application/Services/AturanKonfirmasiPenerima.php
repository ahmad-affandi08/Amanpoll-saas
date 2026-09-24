<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Services;

use App\Core\Konfigurasi\LayananKonfigurasi;
use App\Domain\Pemeliharaan\Domain\Enums\HasilKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KonfirmasiPenerimaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Aturan konfirmasi penerima pekerjaan (PRD 8.22), menggantikan kewajiban tanda
 * tangan saat teknisi menyelesaikan tiket.
 *
 * - Teknisi SELALU bisa menyerahkan pekerjaan ke Menunggu Verifikasi.
 * - Satu konfirmasi "Diterima" berlaku per siklus penyelesaian (`Berlaku`). Siklus
 *   berakhir saat perintah kerja kembali ke Dikerjakan dari Menunggu Verifikasi,
 *   Selesai, atau Ditutup: konfirmasinya dicabut dan penerima diminta lagi.
 * - Bila organisasi menyalakan `Pemeliharaan.WajibKonfirmasiPenerima`, verifikasi
 *   (Menunggu Verifikasi → Selesai) ditolak sebelum ada konfirmasi "Diterima".
 *   Penjaganya ada di `UbahStatusPerintahKerja`, jadi berlaku dari dasbor maupun
 *   jalur lain yang memakai Action yang sama.
 */
final class AturanKonfirmasiPenerima
{
    public const KUNCI_KONFIGURASI = 'Pemeliharaan.WajibKonfirmasiPenerima';

    public const PESAN_VERIFIKASI_DIBLOKIR = 'Penerima belum mengonfirmasi pekerjaan ini. Organisasimu mewajibkan konfirmasi penerima sebelum perintah kerja diverifikasi.';

    public function __construct(private readonly LayananKonfigurasi $konfigurasi) {}

    public function wajib(string $organisasiId): bool
    {
        return (bool) $this->konfigurasi->ambil($organisasiId, self::KUNCI_KONFIGURASI);
    }

    /** Konfirmasi "Diterima" milik siklus penyelesaian yang sedang berjalan. */
    public function berlaku(PerintahKerja $perintahKerja): ?KonfirmasiPenerimaPerintahKerja
    {
        return KonfirmasiPenerimaPerintahKerja::query()
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->where('Berlaku', true)
            ->where('Hasil', HasilKonfirmasiPenerima::Diterima->value)
            ->latest('DikonfirmasiPada')
            ->orderByDesc('Id')
            ->first();
    }

    /**
     * Alasan tombol verifikasi dikunci, atau `null` bila boleh diverifikasi.
     * Dipakai layar dasbor supaya tombolnya menjelaskan diri sebelum server menolak.
     */
    public function alasanVerifikasiDiblokir(PerintahKerja $perintahKerja): ?string
    {
        if ($perintahKerja->Status !== StatusPerintahKerja::MenungguVerifikasi->value || ! $this->wajib($perintahKerja->OrganisasiId)) {
            return null;
        }

        return $this->berlaku($perintahKerja) === null ? self::PESAN_VERIFIKASI_DIBLOKIR : null;
    }

    public function pastikanBolehDiverifikasi(PerintahKerja $perintahKerja): void
    {
        if ($this->wajib($perintahKerja->OrganisasiId) && $this->berlaku($perintahKerja) === null) {
            throw new AturanBisnisDilanggar(self::PESAN_VERIFIKASI_DIBLOKIR);
        }
    }

    /** Siklus penyelesaian berakhir: konfirmasi lama tidak dihitung lagi. */
    public function cabut(PerintahKerja $perintahKerja): void
    {
        KonfirmasiPenerimaPerintahKerja::query()
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->where('Berlaku', true)
            ->update(['Berlaku' => false]);
    }

    /** Teknisi yang (pernah) ditugaskan pada siklus ini tidak boleh mengonfirmasi pekerjaannya sendiri. */
    public function ditugaskan(PerintahKerja $perintahKerja, string $penggunaId): bool
    {
        return $perintahKerja->penugasan()
            ->where('PenggunaId', $penggunaId)
            ->whereIn('Status', [
                StatusPenugasanPerintahKerja::Ditugaskan->value,
                StatusPenugasanPerintahKerja::Diterima->value,
                StatusPenugasanPerintahKerja::Selesai->value,
            ])
            ->exists();
    }
}
