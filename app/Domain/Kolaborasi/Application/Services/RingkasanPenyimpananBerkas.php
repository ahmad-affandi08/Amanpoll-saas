<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Services;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use Illuminate\Support\Facades\DB;

/**
 * Pemakaian penyimpanan berkas organisasi yang sedang aktif (PRD 11.1).
 *
 * "Tersimpan" adalah ruang disk nyata: salinan fisik yang dipakai bersama
 * beberapa baris dihitung sekali, per `(MediaPenyimpanan, LokasiPenyimpanan)`.
 * "Asli" adalah jumlah ukuran seluruh berkas sebagaimana dikirim, seandainya
 * tidak ada kompresi maupun salinan bersama. Thumbnail tidak ikut dihitung.
 */
final class RingkasanPenyimpananBerkas
{
    /**
     * @return array{JumlahBerkas: int, UkuranAsliByte: int, UkuranTersimpanByte: int, PersenHemat: float}
     */
    public function sekarang(): array
    {
        $asli = Berkas::query()
            ->selectRaw('COUNT(*) AS Jumlah, COALESCE(SUM(COALESCE(UkuranAsliByte, UkuranByte)), 0) AS Asli')
            ->toBase()
            ->first();

        $perSalinan = Berkas::query()
            ->selectRaw('MAX(COALESCE(UkuranTersimpanByte, UkuranByte)) AS Ukuran')
            ->groupBy('MediaPenyimpanan', 'LokasiPenyimpanan');

        $tersimpan = (int) DB::query()->fromSub($perSalinan, 'Salinan')->sum('Ukuran');
        $ukuranAsli = (int) ($asli->Asli ?? 0);

        return [
            'JumlahBerkas' => (int) ($asli->Jumlah ?? 0),
            'UkuranAsliByte' => $ukuranAsli,
            'UkuranTersimpanByte' => $tersimpan,
            'PersenHemat' => $ukuranAsli > 0 ? round(max($ukuranAsli - $tersimpan, 0) / $ukuranAsli * 100, 1) : 0.0,
        ];
    }
}
