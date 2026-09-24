<?php

declare(strict_types=1);

namespace App\Core\Peristiwa;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\IntegrasiAudit\Domain\Enums\StatusKotakKeluarPeristiwa;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KotakKeluarPeristiwa;
use App\Shared\Infrastructure\Persistence\KunciBarisAntrean;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Kotak keluar peristiwa (19.06). */
final class LayananKotakKeluar
{
    public function __construct(private readonly KonteksOrganisasi $konteks) {}

    /**
     * @param  array<string, mixed>  $muatan
     */
    public function catat(
        string $namaPeristiwa,
        array $muatan,
        ?string $jenisAgregat = null,
        ?string $agregatId = null,
    ): KotakKeluarPeristiwa {
        return KotakKeluarPeristiwa::create([
            'OrganisasiId' => $this->konteks->ada() ? $this->konteks->wajibId() : null,
            'NamaPeristiwa' => $namaPeristiwa,
            'JenisAgregat' => $jenisAgregat,
            'AgregatId' => $agregatId,
            'MuatanData' => $muatan,
            'Status' => StatusKotakKeluarPeristiwa::Menunggu->value,
            'TersediaPada' => now(),
        ]);
    }

    /**
     * Mengambil sekumpulan peristiwa siap proses dan menandainya sedang diproses
     * dalam satu transaksi. Baris dikunci dengan SELECT ... FOR UPDATE SKIP LOCKED
     * (bila server mendukungnya, lihat `KunciBarisAntrean`) supaya dua worker yang
     * berjalan bersamaan tidak mengambil peristiwa sama dan tidak saling menunggu.
     *
     * @return Collection<int, KotakKeluarPeristiwa>
     */
    public function ambilUntukDiproses(int $batas = 50): Collection
    {
        return DB::transaction(function () use ($batas): Collection {
            $peristiwa = KotakKeluarPeristiwa::query()
                ->withoutGlobalScopes()
                ->where('Status', StatusKotakKeluarPeristiwa::Menunggu->value)
                ->where('TersediaPada', '<=', now())
                ->orderBy('TersediaPada')
                ->limit($batas)
                ->lock(KunciBarisAntrean::klausa(DB::connection()))
                ->get();

            if ($peristiwa->isEmpty()) {
                return $peristiwa;
            }

            KotakKeluarPeristiwa::query()
                ->withoutGlobalScopes()
                ->whereIn('Id', $peristiwa->pluck('Id'))
                ->update(['Status' => StatusKotakKeluarPeristiwa::Diproses->value]);

            return $peristiwa;
        });
    }

    public function tandaiSelesai(KotakKeluarPeristiwa $peristiwa): void
    {
        $peristiwa->Status = StatusKotakKeluarPeristiwa::Selesai->value;
        $peristiwa->DiprosesPada = now()->toImmutable();
        $peristiwa->KesalahanTerakhir = null;
        $peristiwa->save();
    }

    /** Mengembalikan peristiwa ke antrean dengan jeda menaik, atau menyerah setelah batas percobaan. */
    public function tandaiGagal(KotakKeluarPeristiwa $peristiwa, string $kesalahan): void
    {
        $percobaan = $peristiwa->Percobaan + 1;
        $peristiwa->Percobaan = $percobaan;
        $peristiwa->KesalahanTerakhir = mb_substr($kesalahan, 0, 1000);

        if ($percobaan >= KotakKeluarPeristiwa::BATAS_PERCOBAAN) {
            $peristiwa->Status = StatusKotakKeluarPeristiwa::Gagal->value;
            $peristiwa->DiprosesPada = now()->toImmutable();
        } else {
            $peristiwa->Status = StatusKotakKeluarPeristiwa::Menunggu->value;
            $peristiwa->TersediaPada = now()->addSeconds($this->jedaDetik($percobaan))->toImmutable();
        }

        $peristiwa->save();
    }

    /** Jeda menaik: 1, 2, 4, 8 menit. */
    private function jedaDetik(int $percobaan): int
    {
        return 60 * (2 ** max(0, $percobaan - 1));
    }
}
