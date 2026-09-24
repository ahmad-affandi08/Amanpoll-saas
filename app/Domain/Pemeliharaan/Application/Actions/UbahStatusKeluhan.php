<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemeliharaan\Application\Services\KonfirmasiPelaporKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KonfirmasiPenerimaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusKeluhan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\VersiDataBerubah;

final class UbahStatusKeluhan
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly KonfirmasiPelaporKeluhan $konfirmasiPelapor,
    ) {}

    public function jalankan(Keluhan $keluhan, StatusKeluhan $tujuan, ?string $catatan, int $versi, string $penggunaId): Keluhan
    {
        return $this->transaksi->jalankan(function () use ($keluhan, $tujuan, $catatan, $versi, $penggunaId): Keluhan {
            /** @var Keluhan $terkunci */
            $terkunci = Keluhan::query()->lockForUpdate()->findOrFail($keluhan->Id);
            if ($terkunci->Versi !== $versi) {
                throw new VersiDataBerubah('Keluhan telah berubah. Muat ulang halaman sebelum mencoba lagi.');
            }

            $asal = StatusKeluhan::from($terkunci->Status);
            if (! $asal->dapatBeralihKe($tujuan)) {
                throw new AturanBisnisDilanggar("Status {$asal->value} tidak dapat diubah menjadi {$tujuan->value}.");
            }

            $sebelum = $terkunci->toArray();
            $sekarang = now()->toImmutable();
            $terkunci->Status = $tujuan->value;
            $terkunci->Versi++;

            if ($terkunci->DiresponsPada === null && $asal === StatusKeluhan::Baru && $tujuan !== StatusKeluhan::Dibatalkan) {
                $terkunci->DiresponsPada = $sekarang;
            }
            if ($tujuan === StatusKeluhan::Selesai) {
                $terkunci->DiresolusikanPada = $sekarang;
            }
            if ($tujuan === StatusKeluhan::Diproses && $asal === StatusKeluhan::Selesai) {
                $terkunci->DiresolusikanPada = null;
            }
            if ($tujuan === StatusKeluhan::Ditutup) {
                $terkunci->DitutupPada = $sekarang;
            }

            $terkunci->save();
            RiwayatStatusKeluhan::create([
                'KeluhanId' => $terkunci->Id,
                'StatusSebelum' => $asal->value,
                'StatusSesudah' => $tujuan->value,
                'Catatan' => $catatan,
                'DiubahOleh' => $penggunaId,
                'DiubahPada' => $sekarang,
            ]);
            $this->audit->catat('UbahStatus', 'Keluhan', $terkunci->Id, $sebelum, $terkunci->toArray());

            if ($tujuan === StatusKeluhan::Selesai) {
                return $this->tutupBilaTerkonfirmasi($terkunci, $penggunaId);
            }

            return $terkunci;
        });
    }

    /**
     * Keluhan Selesai yang pelapornya sudah menjawab "Sudah beres" di tahap perintah
     * kerja (PRD 8.22) langsung ditutup, dengan penilaiannya, tanpa konfirmasi kedua.
     * Keluhan yang belum dikonfirmasi di tahap itu dibiarkan Selesai dan menunggu
     * konfirmasi pelapor seperti biasa (`KonfirmasiPenyelesaianKeluhan`).
     */
    public function tutupBilaTerkonfirmasi(Keluhan $keluhan, string $penggunaId): Keluhan
    {
        if ($keluhan->Status !== StatusKeluhan::Selesai->value) {
            return $keluhan;
        }

        $konfirmasi = $this->konfirmasiPelapor->terverifikasi($keluhan);

        if (! $konfirmasi instanceof KonfirmasiPenerimaPerintahKerja) {
            return $keluhan;
        }

        return $this->transaksi->jalankan(function () use ($keluhan, $konfirmasi, $penggunaId): Keluhan {
            $ditutup = $this->jalankan(
                $keluhan,
                StatusKeluhan::Ditutup,
                'Ditutup otomatis: pelapor sudah mengonfirmasi pekerjaan beres.',
                $keluhan->Versi,
                $penggunaId,
            );
            $ditutup->Rating = $konfirmasi->Penilaian;
            $ditutup->Ulasan = $konfirmasi->Ulasan;
            $ditutup->save();

            $this->audit->catat('KonfirmasiPelapor', 'Keluhan', $ditutup->Id, dataSesudah: [
                'Beres' => true,
                'Rating' => $ditutup->Rating,
                'Ulasan' => $ditutup->Ulasan,
                'Status' => $ditutup->Status,
                'KonfirmasiPenerimaId' => $konfirmasi->Id,
            ]);

            return $ditutup;
        });
    }
}
