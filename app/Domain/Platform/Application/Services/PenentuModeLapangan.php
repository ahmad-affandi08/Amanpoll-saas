<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use App\Domain\Platform\Domain\Enums\ModeLapangan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;

/**
 * Menentukan apakah seorang pengguna memakai Mode Lapangan dan mode yang mana (PRD 8.20).
 *
 * Yang dibaca hanya penanda `Peran.TampilanLapangan` pada penugasan peran yang
 * sedang berlaku, tidak pernah kode peran harfiah: tenant bebas mengganti nama
 * dan kode perannya.
 *
 * - **Mode**: `Teknisi` bila ia memegang satu saja peran bertanda Teknisi --
 *   teknisi juga dapat melapor lewat aksi cepat -- selain itu `Pelapor` bila
 *   memegang peran bertanda Pelapor, selain itu kosong.
 * - **Lapangan murni**: punya sedikitnya satu peran dan *seluruh* perannya
 *   bertanda. Pengguna ini tidak memakai dasbor sama sekali.
 * - **Bisa beralih**: pengguna campuran, yakni punya peran lapangan dan peran
 *   meja sekaligus. Ia tetap masuk dasbor dan boleh berpindah ke Mode Lapangan.
 *
 * Hasilnya di-cache lima menit per organisasi dan pengguna, seperti
 * `LingkupAkses`. Setiap perubahan penugasan peran atau penanda peran wajib
 * memanggil `bersihkanCache()`, jika tidak pengalihan dasbor tertinggal dari
 * peran yang baru saja diubah.
 */
final class PenentuModeLapangan
{
    public function __construct(private readonly CacheRepository $cache) {}

    public function mode(Pengguna $pengguna): ?ModeLapangan
    {
        $penanda = $this->penanda($pengguna);

        if (in_array(ModeLapangan::Teknisi->value, $penanda, true)) {
            return ModeLapangan::Teknisi;
        }

        if (in_array(ModeLapangan::Pelapor->value, $penanda, true)) {
            return ModeLapangan::Pelapor;
        }

        return null;
    }

    public function lapanganMurni(Pengguna $pengguna): bool
    {
        $penanda = $this->penanda($pengguna);

        return $penanda !== [] && ! in_array(null, $penanda, true);
    }

    public function bisaBeralih(Pengguna $pengguna): bool
    {
        return $this->mode($pengguna) !== null && ! $this->lapanganMurni($pengguna);
    }

    public function bersihkanCache(string $organisasiId, string $penggunaId): void
    {
        $this->cache->forget($this->kunciCache($organisasiId, $penggunaId));
    }

    /**
     * Membersihkan cache seluruh pemegang satu peran, dipakai saat penanda
     * peran itu sendiri yang berubah.
     */
    public function bersihkanCachePeran(string $organisasiId, string $peranId): void
    {
        $penggunaId = DB::table('PenggunaPeran')
            ->where('OrganisasiId', $organisasiId)
            ->where('PeranId', $peranId)
            ->distinct()
            ->pluck('PenggunaId');

        foreach ($penggunaId as $satu) {
            $this->bersihkanCache($organisasiId, (string) $satu);
        }
    }

    /**
     * Penanda tiap peran yang sedang berlaku; `null` untuk peran meja.
     *
     * @return list<string|null>
     */
    private function penanda(Pengguna $pengguna): array
    {
        $organisasiId = (string) $pengguna->OrganisasiId;

        if ($organisasiId === '') {
            return [];
        }

        return $this->cache->remember(
            $this->kunciCache($organisasiId, (string) $pengguna->Id),
            now()->addMinutes(5),
            fn (): array => $this->hitung($organisasiId, (string) $pengguna->Id),
        );
    }

    /**
     * Dibaca lewat query builder, bukan model, supaya tidak bergantung pada
     * konteks organisasi: middleware pengalihan berjalan di seluruh host dasbor,
     * termasuk rute yang tidak menetapkan konteks.
     *
     * @return list<string|null>
     */
    private function hitung(string $organisasiId, string $penggunaId): array
    {
        $penanda = DB::table('PenggunaPeran as pp')
            ->join('Peran as p', 'p.Id', '=', 'pp.PeranId')
            ->where('pp.OrganisasiId', $organisasiId)
            ->where('pp.PenggunaId', $penggunaId)
            ->where('p.OrganisasiId', $organisasiId)
            ->whereNull('p.DihapusPada')
            ->where(function ($q): void {
                $q->whereNull('pp.BerlakuMulai')->orWhere('pp.BerlakuMulai', '<=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('pp.BerlakuSampai')->orWhere('pp.BerlakuSampai', '>=', now());
            })
            ->pluck('p.TampilanLapangan');

        return array_values(array_map(
            fn (mixed $nilai): ?string => is_string($nilai) ? ModeLapangan::tryFrom($nilai)?->value : null,
            $penanda->all(),
        ));
    }

    private function kunciCache(string $organisasiId, string $penggunaId): string
    {
        return "lapangan:{$organisasiId}:{$penggunaId}";
    }
}
