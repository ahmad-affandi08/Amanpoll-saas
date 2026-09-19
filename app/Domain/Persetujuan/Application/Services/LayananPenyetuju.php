<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Services;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Menentukan siapa yang berhak memutuskan satu TahapPersetujuan. "Unit"
 * dievaluasi terhadap unit milik entitas yang sedang diminta persetujuannya
 * (bukan kolom statis di TahapPersetujuan, karena skema tabel itu tidak
 * punya UnitOrganisasiId sendiri) -- memanfaatkan PenggunaPeran.UnitOrganisasiId
 * yang sudah ada tapi belum dipakai modul manapun sebelum FASE 06.
 */
final class LayananPenyetuju
{
    /**
     * @return Collection<int, Pengguna>
     */
    public function calonPenyetuju(TahapPersetujuan $tahap, Model $entitas): Collection
    {
        return match ($tahap->JenisPenyetuju) {
            'Pengguna' => Pengguna::query()->where('Id', $tahap->PenggunaId)->get(),
            'Peran' => $this->penggunaDenganPeran($tahap->PeranId),
            'Unit' => $this->penggunaDiUnitEntitas($tahap, $entitas),
            default => new Collection(),
        };
    }

    public function bolehMemutuskan(TahapPersetujuan $tahap, Model $entitas, Pengguna $pengguna, string $dimintaOlehId): bool
    {
        if (!$tahap->BolehMenyetujuiSendiri && $pengguna->Id === $dimintaOlehId) {
            return false;
        }

        return $this->calonPenyetuju($tahap, $entitas)->contains('Id', $pengguna->Id);
    }

    /**
     * @return Collection<int, Pengguna>
     */
    private function penggunaDenganPeran(?string $peranId): Collection
    {
        if ($peranId === null) {
            return new Collection();
        }

        $penggunaId = DB::table('PenggunaPeran')
            ->where('PeranId', $peranId)
            ->where(fn ($q) => $q->whereNull('BerlakuMulai')->orWhere('BerlakuMulai', '<=', now()))
            ->where(fn ($q) => $q->whereNull('BerlakuSampai')->orWhere('BerlakuSampai', '>=', now()))
            ->distinct()
            ->pluck('PenggunaId');

        return Pengguna::query()->whereIn('Id', $penggunaId)->get();
    }

    /**
     * @return Collection<int, Pengguna>
     */
    private function penggunaDiUnitEntitas(TahapPersetujuan $tahap, Model $entitas): Collection
    {
        $unitId = $entitas->getAttribute('UnitOrganisasiId');
        if (!$unitId) {
            throw new AturanBisnisDilanggar('Tahap persetujuan berbasis unit tidak dapat dievaluasi -- entitas tidak memiliki UnitOrganisasi.');
        }

        $query = DB::table('PenggunaPeran')->where('UnitOrganisasiId', $unitId);
        if ($tahap->PeranId !== null) {
            $query->where('PeranId', $tahap->PeranId);
        }

        $penggunaId = $query->distinct()->pluck('PenggunaId');

        return Pengguna::query()->whereIn('Id', $penggunaId)->get();
    }
}
