<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\MetrikEksperimen;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksperimenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HasilEksperimen;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VarianEksperimen;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Metrik eksperimen dibaca dari funnel yang sudah ada, bukan dari hitungan kedua (MARKETING.md 5, 22). */
final class PenghitungHasilEksperimen
{
    public function __construct(private readonly PenyusunFunnelGrowth $funnel) {}

    /**
     * Menghitung seluruh metrik tiap varian lalu menyimpannya.
     *
     * @return list<HasilEksperimen>
     */
    public function hitung(EksperimenPemasaran $eksperimen): array
    {
        $sekarang = CarbonImmutable::now();
        $hasil = [];

        foreach ($eksperimen->varian as $varian) {
            foreach (MetrikEksperimen::cases() as $metrik) {
                $hasil[] = $this->simpan($eksperimen, $varian, $metrik, $sekarang);
            }
        }

        return $hasil;
    }

    /** Penyebut tiap metrik adalah peserta varian itu; itulah semua orang yang melihatnya. */
    public function peserta(VarianEksperimen $varian): int
    {
        return $varian->partisipasi()->count();
    }

    public function pembilang(VarianEksperimen $varian, MetrikEksperimen $metrik): int
    {
        $pengunjung = $this->pengunjungVarian($varian);
        $filter = $this->seluruhWaktu();
        $tahap = $metrik->tahapFunnel();

        if ($tahap !== null) {
            return match ($tahap->value) {
                'Demo' => $this->funnel->demo($filter, $pengunjung),
                'Trial' => $this->funnel->trial($filter, $pengunjung),
                'Activated' => $this->funnel->activated($filter, $pengunjung),
                default => $this->funnel->paid($filter, $pengunjung),
            };
        }

        return match ($metrik) {
            MetrikEksperimen::Ctr => $this->peristiwaUnik($pengunjung, KatalogPeristiwaPemasaran::CTA_DIKLIK),
            default => $this->formulirUnik($pengunjung),
        };
    }

    /** Subkueri pengenal pengunjung yang ditetapkan ke varian ini; bentuk yang sama dipakai funnel growth. */
    public function pengunjungVarian(VarianEksperimen $varian): Builder
    {
        return DB::table('PartisipasiEksperimen')
            ->select('PengenalPengunjung')
            ->where('VarianEksperimenId', $varian->Id);
    }

    private function simpan(
        EksperimenPemasaran $eksperimen,
        VarianEksperimen $varian,
        MetrikEksperimen $metrik,
        CarbonImmutable $pada,
    ): HasilEksperimen {
        $penyebut = $this->peserta($varian);
        $pembilang = $penyebut === 0 ? 0 : $this->pembilang($varian, $metrik);

        return HasilEksperimen::query()->updateOrCreate(
            [
                'EksperimenPemasaranId' => $eksperimen->Id,
                'VarianEksperimenId' => $varian->Id,
                'Metrik' => $metrik,
            ],
            [
                'Penyebut' => $penyebut,
                'Pembilang' => $pembilang,
                'Rasio' => $penyebut === 0 ? 0 : round($pembilang / $penyebut, 4),
                'DihitungPada' => $pada,
            ],
        );
    }

    private function peristiwaUnik(Builder $pengunjung, string $jenis): int
    {
        return DB::table('EventPemasaran')
            ->where('Jenis', $jenis)
            ->whereIn('PengenalPengunjung', $pengunjung)
            ->distinct()
            ->count('PengenalPengunjung');
    }

    private function formulirUnik(Builder $pengunjung): int
    {
        return DB::table('PengirimanFormulir')
            ->whereIn('PengenalPengunjung', $pengunjung)
            ->distinct()
            ->count('PengenalPengunjung');
    }

    /** Eksperimen dinilai atas seluruh hidupnya, bukan atas rentang tanggal yang kebetulan dipilih. */
    private function seluruhWaktu(): FilterGrowth
    {
        return new FilterGrowth(
            CarbonImmutable::createFromTimestamp(0),
            CarbonImmutable::now()->addCentury(),
        );
    }
}
