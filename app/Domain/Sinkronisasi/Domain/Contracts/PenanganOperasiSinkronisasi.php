<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Domain\Contracts;

use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;

/**
 * Satu jenis mutasi offline yang boleh diantrikan klien PWA (20.03/20.05).
 *
 * Penangan sengaja tidak menulis sendiri ke tabel bisnis: ia memanggil Action
 * domain yang sama dengan jalur online, sehingga seluruh invariant, audit, dan
 * optimistic locking tetap berlaku untuk mutasi yang berasal dari offline.
 */
interface PenanganOperasiSinkronisasi
{
    /** Kode operasi yang dikirim klien, mis. `PerintahKerja.UbahStatus`. */
    public function operasi(): string;

    /**
     * Jenis entitas yang dimutasi; dipakai untuk penanda sinkronisasi dan
     * audit.
     */
    public function jenisEntitas(): string;

    /** Apakah operasi ini wajib menyertakan EntitasId. */
    public function membutuhkanEntitas(): bool;

    /**
     * Aturan validasi muatan offline, relatif terhadap key `MuatanData`.
     * Divalidasi di server karena klien offline tidak pernah menjadi sumber
     * kebenaran.
     *
     * @return array<string, mixed>
     */
    public function aturan(): array;

    /**
     * Versi entitas di server saat ini, atau null bila entitas tidak memakai
     * optimistic locking (mis. mutasi append-only seperti catatan).
     */
    public function versiServer(?string $entitasId): ?int;

    /** Apakah pengguna ini boleh menjalankan operasi tersebut. */
    public function diizinkan(AntrianSinkronisasi $antrian, Pengguna $pengguna): bool;

    /**
     * Mendeteksi konflik sebelum mutasi diterapkan (20.04/20.06).
     *
     * @return array<string, mixed>|null detail konflik untuk ditampilkan ke pengguna, null bila aman
     */
    public function periksaKonflik(AntrianSinkronisasi $antrian): ?array;

    /**
     * Menerapkan mutasi. Dipanggil hanya setelah otorisasi dan pemeriksaan
     * konflik lolos.
     *
     * @return array<string, mixed> ringkasan hasil untuk dikembalikan ke klien
     */
    public function terapkan(AntrianSinkronisasi $antrian, Pengguna $pengguna): array;
}
