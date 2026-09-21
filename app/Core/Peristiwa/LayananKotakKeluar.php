<?php

declare(strict_types=1);

namespace App\Core\Peristiwa;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KotakKeluarPeristiwa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Kotak keluar peristiwa (19.06). Peristiwa ditulis di dalam transaksi bisnis
 * yang sama dengan perubahan datanya, lalu dipublikasikan worker terpisah.
 * Dengan begitu tidak mungkin ada peristiwa terkirim untuk transaksi yang
 * gagal, maupun transaksi sukses yang peristiwanya hilang.
 */
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
            'Status' => KotakKeluarPeristiwa::STATUS_MENUNGGU,
            'TersediaPada' => now(),
        ]);
    }

    /**
     * Mengambil sekumpulan peristiwa siap proses dan menandainya sedang diproses
     * dalam satu transaksi. Baris dikunci dengan SELECT ... FOR UPDATE SKIP LOCKED
     * supaya dua worker yang berjalan bersamaan tidak mengambil peristiwa sama.
     *
     * @return Collection<int, KotakKeluarPeristiwa>
     */
    public function ambilUntukDiproses(int $batas = 50): Collection
    {
        return DB::transaction(function () use ($batas): Collection {
            $peristiwa = KotakKeluarPeristiwa::query()
                ->withoutGlobalScopes()
                ->where('Status', KotakKeluarPeristiwa::STATUS_MENUNGGU)
                ->where('TersediaPada', '<=', now())
                ->orderBy('TersediaPada')
                ->limit($batas)
                ->lockForUpdate()
                ->get();

            if ($peristiwa->isEmpty()) {
                return $peristiwa;
            }

            KotakKeluarPeristiwa::query()
                ->withoutGlobalScopes()
                ->whereIn('Id', $peristiwa->pluck('Id'))
                ->update(['Status' => KotakKeluarPeristiwa::STATUS_DIPROSES]);

            return $peristiwa;
        });
    }

    public function tandaiSelesai(KotakKeluarPeristiwa $peristiwa): void
    {
        $peristiwa->Status = KotakKeluarPeristiwa::STATUS_SELESAI;
        $peristiwa->DiprosesPada = now()->toImmutable();
        $peristiwa->KesalahanTerakhir = null;
        $peristiwa->save();
    }

    /**
     * Mengembalikan peristiwa ke antrean dengan jeda menaik, atau menyerah
     * setelah batas percobaan supaya tidak berputar selamanya.
     */
    public function tandaiGagal(KotakKeluarPeristiwa $peristiwa, string $kesalahan): void
    {
        $percobaan = $peristiwa->Percobaan + 1;
        $peristiwa->Percobaan = $percobaan;
        $peristiwa->KesalahanTerakhir = mb_substr($kesalahan, 0, 1000);

        if ($percobaan >= KotakKeluarPeristiwa::BATAS_PERCOBAAN) {
            $peristiwa->Status = KotakKeluarPeristiwa::STATUS_GAGAL;
            $peristiwa->DiprosesPada = now()->toImmutable();
        } else {
            $peristiwa->Status = KotakKeluarPeristiwa::STATUS_MENUNGGU;
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
