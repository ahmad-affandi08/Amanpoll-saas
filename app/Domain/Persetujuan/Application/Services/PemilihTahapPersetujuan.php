<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Services;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;

/**
 * Menentukan tahap mana yang dilalui sebuah permintaan berdasarkan ambang nilai.
 *
 * `Kondisi` tahap berbentuk `{"NilaiMinimum": 50000000}`: tahap itu hanya berlaku
 * bila nilai permintaan (`DataTambahan.Nilai`) mencapai ambangnya, selain itu dilewati.
 * Tahap pertama selalu berlaku agar setiap permintaan punya penyetuju; aktivasi alur
 * menolak ambang di tahap pertama. Nilai yang tidak diketahui tidak pernah melewati
 * tahap: lebih baik satu persetujuan berlebih daripada satu yang hilang.
 */
final class PemilihTahapPersetujuan
{
    public const KUNCI_NILAI = 'Nilai';

    public function tahapPertama(string $alurPersetujuanId): ?TahapPersetujuan
    {
        return TahapPersetujuan::query()
            ->where('AlurPersetujuanId', $alurPersetujuanId)
            ->orderBy('Urutan')
            ->first();
    }

    /** Tahap berlaku pertama sesudah urutan tertentu, atau null bila tidak ada lagi. */
    public function tahapBerikutnya(string $alurPersetujuanId, int $setelahUrutan, ?float $nilai): ?TahapPersetujuan
    {
        return TahapPersetujuan::query()
            ->where('AlurPersetujuanId', $alurPersetujuanId)
            ->where('Urutan', '>', $setelahUrutan)
            ->orderBy('Urutan')
            ->get()
            ->first(fn (TahapPersetujuan $tahap): bool => $this->berlaku($tahap, $nilai));
    }

    public function berlaku(TahapPersetujuan $tahap, ?float $nilai): bool
    {
        $ambang = self::ambangNilai($tahap);

        return $ambang === null || $nilai === null || $nilai >= $ambang;
    }

    public static function ambangNilai(TahapPersetujuan $tahap): ?float
    {
        $ambang = $tahap->Kondisi['NilaiMinimum'] ?? null;

        return is_numeric($ambang) ? (float) $ambang : null;
    }

    /**
     * Nilai permintaan dari snapshot yang dikirim domain asalnya saat mengajukan.
     *
     * @param  array<string, mixed>|null  $dataTambahan
     */
    public static function nilaiDari(?array $dataTambahan): ?float
    {
        $nilai = $dataTambahan[self::KUNCI_NILAI] ?? null;

        return is_numeric($nilai) ? (float) $nilai : null;
    }
}
