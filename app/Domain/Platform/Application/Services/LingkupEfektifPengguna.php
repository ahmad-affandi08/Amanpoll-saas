<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use App\Core\Izin\LingkupAkses;
use App\Core\Izin\ScopeLingkup;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Lingkup data yang benar-benar berlaku bagi seorang pengguna, untuk halaman Pengguna (PRD 8.21).
 *
 * Satu penetapan peran tanpa unit dan ruangan membuat penggunanya melihat
 * seluruh organisasi, walau peran lainnya berlingkup. Aturan itu hidup di
 * LingkupAkses dan tidak dihitung ulang di sini: putusannya (seluruh
 * organisasi atau tidak, dan unit serta ruangan mana yang tercakup) dibaca
 * dari sana. Yang ditambahkan kelas ini hanya penjelasannya -- penetapan peran
 * mana yang menjadi sumber tiap cakupan -- supaya admin tahu apa yang harus
 * dicabut bila hasilnya terlalu lebar.
 *
 * Nama unit dan ruangan dibaca lepas dari ScopeLingkup: admin yang sendiri
 * berlingkup tetap harus bisa membaca cakupan pengguna lain. Tenancy tetap.
 */
final class LingkupEfektifPengguna
{
    public function __construct(private readonly LingkupAkses $lingkupAkses) {}

    /**
     * @return array{
     *     SeluruhOrganisasi: bool,
     *     TanpaPeran: bool,
     *     PeranTanpaLingkup: list<string>,
     *     Unit: list<array{Id: string, Nama: string, MengelolaAset: bool, Peran: list<string>}>,
     *     Lokasi: list<array{Id: string, Nama: string, Peran: list<string>}>,
     *     JumlahUnit: int,
     *     JumlahLokasi: int,
     * }
     */
    public function untuk(Pengguna $pengguna): array
    {
        $penggunaId = (string) $pengguna->Id;
        $penugasan = $this->penugasanBerlaku($penggunaId);

        if ($this->lingkupAkses->tanpaBatas($penggunaId)) {
            return [
                'SeluruhOrganisasi' => true,
                'TanpaPeran' => $penugasan->isEmpty(),
                'PeranTanpaLingkup' => $this->namaPeran(
                    $penugasan->filter(fn (PenggunaPeran $satu): bool => $satu->UnitOrganisasiId === null && $satu->LokasiId === null),
                ),
                'Unit' => [],
                'Lokasi' => [],
                'JumlahUnit' => 0,
                'JumlahLokasi' => 0,
            ];
        }

        $unitDiizinkan = $this->lingkupAkses->unitDiizinkan($penggunaId);
        $lokasiDiizinkan = $this->lingkupAkses->lokasiDiizinkan($penggunaId);

        return [
            'SeluruhOrganisasi' => false,
            'TanpaPeran' => false,
            'PeranTanpaLingkup' => [],
            'Unit' => $this->daftarUnit($penugasan, $unitDiizinkan),
            'Lokasi' => $this->daftarLokasi($penugasan, $lokasiDiizinkan),
            'JumlahUnit' => count($unitDiizinkan),
            'JumlahLokasi' => count($lokasiDiizinkan),
        ];
    }

    /**
     * Unit yang ditetapkan langsung, beserta peran sumbernya.
     *
     * Hanya unit yang memang tercakup menurut LingkupAkses yang ditampilkan,
     * supaya daftar ini tidak pernah menjanjikan lebih dari yang berlaku.
     *
     * @param  Collection<int, PenggunaPeran>  $penugasan
     * @param  list<string>  $unitDiizinkan
     * @return list<array{Id: string, Nama: string, MengelolaAset: bool, Peran: list<string>}>
     */
    private function daftarUnit(Collection $penugasan, array $unitDiizinkan): array
    {
        $perUnit = $penugasan
            ->filter(fn (PenggunaPeran $satu): bool => $satu->UnitOrganisasiId !== null && in_array($satu->UnitOrganisasiId, $unitDiizinkan, true))
            ->groupBy('UnitOrganisasiId');

        if ($perUnit->isEmpty()) {
            return [];
        }

        $unit = UnitOrganisasi::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->withTrashed()
            ->whereIn('Id', $perUnit->keys()->all())
            ->get(['Id', 'Nama', 'MengelolaAset'])
            ->keyBy('Id');

        return array_values($perUnit
            ->map(fn (Collection $milikUnit, string $unitId): array => [
                'Id' => $unitId,
                'Nama' => (string) ($unit->get($unitId)->Nama ?? 'Unit tidak ditemukan'),
                'MengelolaAset' => (bool) ($unit->get($unitId)->MengelolaAset ?? false),
                'Peran' => $this->namaPeran($milikUnit),
            ])
            ->sortBy('Nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->all());
    }

    /**
     * @param  Collection<int, PenggunaPeran>  $penugasan
     * @param  list<string>  $lokasiDiizinkan
     * @return list<array{Id: string, Nama: string, Peran: list<string>}>
     */
    private function daftarLokasi(Collection $penugasan, array $lokasiDiizinkan): array
    {
        $perLokasi = $penugasan
            ->filter(fn (PenggunaPeran $satu): bool => $satu->LokasiId !== null && in_array($satu->LokasiId, $lokasiDiizinkan, true))
            ->groupBy('LokasiId');

        if ($perLokasi->isEmpty()) {
            return [];
        }

        $lokasi = Lokasi::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->withTrashed()
            ->whereIn('Id', $perLokasi->keys()->all())
            ->get(['Id', 'Nama'])
            ->keyBy('Id');

        return array_values($perLokasi
            ->map(fn (Collection $milikLokasi, string $lokasiId): array => [
                'Id' => $lokasiId,
                'Nama' => (string) ($lokasi->get($lokasiId)->Nama ?? 'Ruangan tidak ditemukan'),
                'Peran' => $this->namaPeran($milikLokasi),
            ])
            ->sortBy('Nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->all());
    }

    /**
     * Penetapan peran yang sedang berlaku, rentang tanggalnya sama dengan yang dibaca LingkupAkses.
     *
     * @return Collection<int, PenggunaPeran>
     */
    private function penugasanBerlaku(string $penggunaId): Collection
    {
        return PenggunaPeran::query()
            ->with('peran:Id,Nama')
            ->where('PenggunaId', $penggunaId)
            ->where(function (Builder $kueri): void {
                $kueri->whereNull('BerlakuMulai')->orWhere('BerlakuMulai', '<=', now());
            })
            ->where(function (Builder $kueri): void {
                $kueri->whereNull('BerlakuSampai')->orWhere('BerlakuSampai', '>=', now());
            })
            ->get(['Id', 'PeranId', 'UnitOrganisasiId', 'LokasiId']);
    }

    /**
     * @param  Collection<int, PenggunaPeran>  $penugasan
     * @return list<string>
     */
    private function namaPeran(Collection $penugasan): array
    {
        return array_values($penugasan
            ->map(fn (PenggunaPeran $satu): string => (string) ($satu->peran->Nama ?? 'Peran'))
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->all());
    }
}
