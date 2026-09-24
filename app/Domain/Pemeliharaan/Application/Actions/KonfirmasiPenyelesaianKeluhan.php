<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Verifikasi pelapor atas keluhan yang sudah diselesaikan (PRD 4.6, 8.20).
 *
 * Memakai transisi status yang sudah ada, bukan jalur baru:
 * - "Ya, sudah beres" menutup keluhan (Selesai → Ditutup) dan menyimpan
 *   penilaian 1–5 serta ulasannya di kolom `Rating`/`Ulasan`.
 * - "Belum, masih bermasalah" membuka kembali pekerjaan (Selesai → Diproses),
 *   dengan alasan pelapor tercatat di riwayat status, supaya koordinator
 *   melihatnya di antrean yang sama.
 */
final class KonfirmasiPenyelesaianKeluhan
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly UbahStatusKeluhan $ubahStatus,
        private readonly LayananAudit $audit,
    ) {}

    public function jalankan(Keluhan $keluhan, bool $beres, ?int $nilai, ?string $ulasan, int $versi, string $penggunaId): Keluhan
    {
        if ($beres && ($nilai === null || $nilai < 1 || $nilai > 5)) {
            throw new AturanBisnisDilanggar('Penilaian perbaikan wajib diisi 1 sampai 5 bintang.');
        }

        if (! $beres && blank($ulasan)) {
            throw new AturanBisnisDilanggar('Ceritakan apa yang masih bermasalah.');
        }

        return $this->transaksi->jalankan(function () use ($keluhan, $beres, $nilai, $ulasan, $versi, $penggunaId): Keluhan {
            if ($beres) {
                $diubah = $this->ubahStatus->jalankan($keluhan, StatusKeluhan::Ditutup, 'Dikonfirmasi beres oleh pelapor.', $versi, $penggunaId);
                $diubah->Rating = $nilai;
                $diubah->Ulasan = filled($ulasan) ? $ulasan : null;
                $diubah->save();
            } else {
                $diubah = $this->ubahStatus->jalankan($keluhan, StatusKeluhan::Diproses, 'Pelapor: masih bermasalah. '.$ulasan, $versi, $penggunaId);
            }

            $this->audit->catat('KonfirmasiPelapor', 'Keluhan', $diubah->Id, dataSesudah: [
                'Beres' => $beres,
                'Rating' => $beres ? $nilai : null,
                'Ulasan' => $ulasan,
                'Status' => $diubah->Status,
            ]);

            return $diubah;
        });
    }
}
