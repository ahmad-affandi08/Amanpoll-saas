<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Services;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use Illuminate\Support\Collection;

/**
 * Membaca dan mengubah StokSukuCadang.JumlahDitahan di level gudang+suku
 * cadang (bukan per lokasi-gudang/kelompok, karena ReservasiSukuCadang
 * memang hanya berkaitan dengan gudang -- lihat ADR 0010). Hold selalu
 * ditulis ke SATU baris "default" (LokasiGudangId dan KelompokSukuCadangId
 * NULL) per gudang+suku cadang, dibuat otomatis bila belum ada.
 *
 * Method di sini HARUS dipanggil di dalam transaksi database milik
 * pemanggil (TransaksiDatabase::jalankan) supaya lockForUpdate() efektif
 * menyerialkan reservasi yang bersamaan.
 */
final class LayananSaldoReservasi
{
    /**
     * Mengunci semua baris StokSukuCadang milik gudang+suku cadang ini dan
     * mengembalikan kuantitas bersih yang boleh dijanjikan ke reservasi baru.
     */
    public function tersediaBersih(string $gudangId, string $sukuCadangId): float
    {
        $baris = $this->kunciSemuaBaris($gudangId, $sukuCadangId);

        $totalTersedia = $baris->sum(fn (StokSukuCadang $b): float => (float) $b->JumlahTersedia);
        $totalDitahan = $baris->sum(fn (StokSukuCadang $b): float => (float) $b->JumlahDitahan);

        return $totalTersedia - $totalDitahan;
    }

    public function ubahDitahan(string $organisasiId, string $gudangId, string $sukuCadangId, float $delta): void
    {
        $baris = $this->kunciSemuaBaris($gudangId, $sukuCadangId);

        $default = $baris->first(fn (StokSukuCadang $b): bool => $b->LokasiGudangId === null && $b->KelompokSukuCadangId === null);

        if (!$default) {
            $default = StokSukuCadang::create([
                'OrganisasiId' => $organisasiId,
                'GudangId' => $gudangId,
                'LokasiGudangId' => null,
                'SukuCadangId' => $sukuCadangId,
                'KelompokSukuCadangId' => null,
                'JumlahTersedia' => 0,
                'JumlahDipesan' => 0,
                'JumlahDitahan' => 0,
            ]);
        }

        $default->JumlahDitahan = (float) $default->JumlahDitahan + $delta;
        $default->Versi = $default->Versi + 1;
        $default->save();
    }

    /**
     * @return Collection<int, StokSukuCadang>
     */
    private function kunciSemuaBaris(string $gudangId, string $sukuCadangId): Collection
    {
        return StokSukuCadang::query()
            ->where('GudangId', $gudangId)
            ->where('SukuCadangId', $sukuCadangId)
            ->lockForUpdate()
            ->get();
    }
}
