<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use Illuminate\Support\Facades\DB;

/**
 * Siapa saja yang masih menunjuk sebuah unit sebagai unit pengelolanya (PRD 8.21).
 *
 * Tanda Mengelola Aset tidak boleh dicabut, dan unitnya tidak boleh dihapus,
 * selama aset, kategori keluhan, gudang, atau tiket yang belum final masih
 * memakainya -- kalau tidak, antrian itu kehilangan pemiliknya tanpa ada yang
 * menyadari. Tiket final (StatusKeluhan::final(), StatusPerintahKerja::final())
 * tidak dihitung: riwayatnya tetap menyebut unit itu, dan itu memang benar.
 *
 * Membaca tabel langsung, bukan lewat model, supaya tidak terikat ScopeLingkup
 * pengguna yang sedang masuk: admin berlingkup tidak boleh lolos hanya karena
 * pemakainya di luar pandangannya. Id unit berupa ULID, jadi tidak ada baris
 * organisasi lain yang dapat ikut terhitung.
 */
final class PemakaianUnitPengelola
{
    /**
     * Jumlah pemakai per jenis; hanya jenis yang jumlahnya lebih dari nol.
     *
     * @return array<string, int> label pemakai => jumlah
     */
    public function hitung(string $unitId): array
    {
        $statusKeluhanFinal = array_map(
            fn (StatusKeluhan $status): string => $status->value,
            array_values(array_filter(StatusKeluhan::cases(), fn (StatusKeluhan $status): bool => $status->final())),
        );

        $statusPerintahKerjaFinal = array_map(
            fn (StatusPerintahKerja $status): string => $status->value,
            array_values(array_filter(StatusPerintahKerja::cases(), fn (StatusPerintahKerja $status): bool => $status->final())),
        );

        $jumlah = [
            'aset' => DB::table('Aset')->where('UnitPengelolaId', $unitId)->whereNull('DihapusPada')->count(),
            'kategori keluhan' => DB::table('KategoriKeluhan')->where('UnitPengelolaId', $unitId)->count(),
            'gudang' => DB::table('Gudang')->where('UnitPengelolaId', $unitId)->count(),
            'keluhan yang belum final' => DB::table('Keluhan')
                ->where('UnitPengelolaId', $unitId)
                ->whereNull('DihapusPada')
                ->whereNotIn('Status', $statusKeluhanFinal)
                ->count(),
            'perintah kerja yang belum final' => DB::table('PerintahKerja')
                ->where('UnitPengelolaId', $unitId)
                ->whereNull('DihapusPada')
                ->whereNotIn('Status', $statusPerintahKerjaFinal)
                ->count(),
        ];

        return array_filter($jumlah, fn (int $satu): bool => $satu > 0);
    }

    /** Ringkasan pemakai untuk pesan penolakan, mis. "3 aset, 1 gudang"; null bila tidak dipakai. */
    public function ringkasan(string $unitId): ?string
    {
        $jumlah = $this->hitung($unitId);

        if ($jumlah === []) {
            return null;
        }

        $bagian = [];

        foreach ($jumlah as $label => $banyak) {
            $bagian[] = "{$banyak} {$label}";
        }

        return implode(', ', $bagian);
    }

    /** Pesan penolakan mencabut tanda Mengelola Aset; null bila boleh dicabut. */
    public function alasanTolakCabut(string $unitId): ?string
    {
        $ringkasan = $this->ringkasan($unitId);

        return $ringkasan === null ? null
            : "Tanda Mengelola Aset tidak dapat dicabut: unit ini masih menjadi unit pengelola {$ringkasan}. Pindahkan dulu ke unit pengelola lain.";
    }

    /** Pesan penolakan menghapus unit yang masih menjadi unit pengelola; null bila tidak dipakai. */
    public function alasanTolakHapus(string $unitId): ?string
    {
        $ringkasan = $this->ringkasan($unitId);

        return $ringkasan === null ? null
            : "Unit masih menjadi unit pengelola {$ringkasan} dan tidak dapat dihapus.";
    }
}
