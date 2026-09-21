<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Domain\Sinkronisasi\Application\Services\LayananPenandaSinkronisasi;
use App\Shared\Domain\Contracts\TransaksiDatabase;

/**
 * Pendaftaran perangkat yang memegang data offline (20.03).
 *
 * IdentitasPerangkat dibuat klien sekali lalu disimpan di perangkat, sehingga
 * pemasangan ulang PWA pada perangkat yang sama tetap memakai antrean yang
 * sama dan mutasi yang belum terkirim tidak berubah menjadi transaksi ganda.
 */
final class DaftarkanPerangkatPengguna
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananPenandaSinkronisasi $penanda,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function jalankan(Pengguna $pengguna, array $data): PerangkatPengguna
    {
        return $this->transaksi->jalankan(function () use ($pengguna, $data): PerangkatPengguna {
            $perangkat = PerangkatPengguna::query()->firstOrNew([
                'PenggunaId' => $pengguna->Id,
                'IdentitasPerangkat' => (string) $data['IdentitasPerangkat'],
            ]);

            $baru = ! $perangkat->exists;
            $perangkat->OrganisasiId = $pengguna->OrganisasiId;
            $perangkat->NamaPerangkat = $data['NamaPerangkat'] ?? $perangkat->NamaPerangkat;
            $perangkat->Platform = $data['Platform'] ?? $perangkat->Platform;
            $perangkat->Status = 'Aktif';
            $perangkat->save();

            if ($baru) {
                $this->audit->catat('PerangkatPengguna.Didaftarkan', 'PerangkatPengguna', $perangkat->Id, dataSesudah: [
                    'NamaPerangkat' => $perangkat->NamaPerangkat,
                    'Platform' => $perangkat->Platform,
                ]);
            }

            return $perangkat;
        });
    }

    /**
     * Melepas perangkat saat pengguna keluar. Penanda sinkronisasi dibuang
     * supaya paket berikutnya ditarik utuh, sedangkan antrean yang belum
     * terkirim sengaja dipertahankan agar pekerjaan lapangan tidak hilang.
     */
    public function lepaskan(PerangkatPengguna $perangkat): void
    {
        $this->transaksi->jalankan(function () use ($perangkat): void {
            $this->penanda->bersihkan($perangkat);
            $perangkat->Status = 'Nonaktif';
            $perangkat->save();

            $this->audit->catat('PerangkatPengguna.Dilepas', 'PerangkatPengguna', $perangkat->Id);
        });
    }
}
