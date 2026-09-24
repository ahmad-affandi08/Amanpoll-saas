<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Resources;

use App\Domain\Pemeliharaan\Domain\Enums\MetodeKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KonfirmasiPenerimaPerintahKerja;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Konfirmasi penerima untuk kartu di detail perintah kerja dan layar teknisi (PRD 8.22).
 *
 * `UrlTandaTangan` menunjuk rute terotorisasi per perintah kerja
 * (`pemeliharaan.perintah-kerja.konfirmasi-penerima.tanda-tangan`), bukan berkas profil
 * penerima: yang boleh melihatnya hanya yang boleh melihat perintah kerja itu.
 */
final class KonfirmasiPenerimaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $konfirmasi = $this->resource;

        if (! $konfirmasi instanceof KonfirmasiPenerimaPerintahKerja) {
            return [];
        }

        return self::ringkas($konfirmasi);
    }

    /** @return array<string, mixed> */
    public static function ringkas(KonfirmasiPenerimaPerintahKerja $konfirmasi): array
    {
        return [
            'Id' => $konfirmasi->Id,
            'Metode' => $konfirmasi->Metode,
            'LabelMetode' => MetodeKonfirmasiPenerima::tryFrom($konfirmasi->Metode)?->label() ?? $konfirmasi->Metode,
            'Hasil' => $konfirmasi->Hasil,
            'NamaPenerima' => $konfirmasi->NamaPenerima,
            'JabatanPenerima' => $konfirmasi->JabatanPenerima,
            'Alasan' => $konfirmasi->Alasan,
            'Ulasan' => $konfirmasi->Ulasan,
            'Penilaian' => $konfirmasi->Penilaian,
            'Berlaku' => $konfirmasi->Berlaku,
            'DikonfirmasiPada' => $konfirmasi->DikonfirmasiPada->toIso8601String(),
            'UrlTandaTangan' => $konfirmasi->TandaTanganBerkasId === null ? null : route('pemeliharaan.perintah-kerja.konfirmasi-penerima.tanda-tangan', [
                'perintahKerja' => $konfirmasi->PerintahKerjaId,
                'konfirmasi' => $konfirmasi->Id,
            ], false),
        ];
    }
}
