<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Pemasaran\Domain\Enums\StatusLeadPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusReferral;
use App\Domain\Pemasaran\Domain\Enums\TahapFunnelGrowth;
use App\Domain\Pemasaran\Domain\KatalogKpiPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Menghitung KPI menurut rumus katalognya; rasio diambil dari funnel yang sama agar tidak pernah berselisih (MARKETING.md 5). */
final class PenghitungKpiPemasaran
{
    public function __construct(
        private readonly PenyaringGrowth $penyaring,
        private readonly PenyusunFunnelGrowth $funnel,
        private readonly PenghitungCacKampanye $cac,
        private readonly PenghitungRevenueAttribution $revenue,
    ) {}

    /**
     * @param  array<string, int>|null  $funnel  Funnel yang sudah dihitung, agar tidak dihitung dua kali.
     * @return array<string, float>
     */
    public function hitung(FilterGrowth $filter, ?array $funnel = null): array
    {
        $pengunjung = $this->penyaring->pengunjung($filter);
        $tahap = $funnel ?? $this->funnel->hitung($filter);

        return [
            KatalogKpiPemasaran::VISITOR => (float) $this->sesi($filter, $pengunjung),
            KatalogKpiPemasaran::VISITOR_UNIK => (float) $tahap[TahapFunnelGrowth::Visitor->value],
            KatalogKpiPemasaran::HALAMAN_DILIHAT => (float) $this->peristiwa(
                $filter, $pengunjung, KatalogPeristiwaPemasaran::HALAMAN_DILIHAT,
            ),
            KatalogKpiPemasaran::FORMULIR_DIKIRIM => (float) $this->pengirimanFormulir($filter, $pengunjung),
            KatalogKpiPemasaran::DEMO_DIMULAI => (float) $tahap[TahapFunnelGrowth::Demo->value],
            KatalogKpiPemasaran::DEMO_SELESAI => (float) $this->peristiwa(
                $filter, $pengunjung, KatalogPeristiwaPemasaran::DEMO_SELESAI, unik: true,
            ),
            KatalogKpiPemasaran::TRIAL_TERDAFTAR => (float) $tahap[TahapFunnelGrowth::Trial->value],
            KatalogKpiPemasaran::TRIAL_TERAKTIVASI => (float) $tahap[TahapFunnelGrowth::Activated->value],
            KatalogKpiPemasaran::LEAD_QUALIFIED => (float) $tahap[TahapFunnelGrowth::Qualified->value],
            KatalogKpiPemasaran::PELANGGAN_BAYAR => (float) $tahap[TahapFunnelGrowth::Paid->value],
            KatalogKpiPemasaran::MRR_BARU => $this->mrrBaru($filter, $pengunjung),
            KatalogKpiPemasaran::VISITOR_KE_LEAD => $this->rasio(
                $tahap[TahapFunnelGrowth::Lead->value], $tahap[TahapFunnelGrowth::Visitor->value],
            ),
            KatalogKpiPemasaran::VISITOR_KE_TRIAL => $this->rasio(
                $tahap[TahapFunnelGrowth::Trial->value], $tahap[TahapFunnelGrowth::Visitor->value],
            ),
            KatalogKpiPemasaran::TRIAL_KE_AKTIVASI => $this->rasio(
                $tahap[TahapFunnelGrowth::Activated->value], $tahap[TahapFunnelGrowth::Trial->value],
            ),
            KatalogKpiPemasaran::AKTIVASI_KE_BAYAR => $this->rasio(
                $tahap[TahapFunnelGrowth::Paid->value], $tahap[TahapFunnelGrowth::Activated->value],
            ),
            KatalogKpiPemasaran::REVENUE_PER_CHANNEL => array_sum($this->revenuePerChannel($filter)),
            KatalogKpiPemasaran::CAC_PER_CHANNEL => $this->cac->gabungan($filter),
            KatalogKpiPemasaran::REFERRAL_KONVERSI => $this->referralKonversi($filter),
            KatalogKpiPemasaran::REVENUE_PARTNER => $this->revenuePartner($filter),
        ];
    }

    /**
     * Revenue per channel menurut model attribution yang dipilih di setelan.
     * Pembagiannya dilakukan per pembayaran, bukan lewat satu GROUP BY, karena
     * satu pembayaran dapat terbagi ke beberapa sentuhan (MARKETING.md 14).
     *
     * @return array<string, float>
     */
    public function revenuePerChannel(FilterGrowth $filter): array
    {
        return $this->revenue->perChannel($filter);
    }

    private function sesi(FilterGrowth $filter, ?Builder $pengunjung): int
    {
        $kueri = DB::table('SesiPengunjung')
            ->whereBetween('DimulaiPada', [$filter->dari, $filter->sampai]);

        if ($pengunjung !== null) {
            $kueri->whereIn('SesiPengunjung.PengenalPengunjung', $pengunjung);
        }

        return $kueri->count();
    }

    private function peristiwa(
        FilterGrowth $filter,
        ?Builder $pengunjung,
        string $jenis,
        bool $unik = false,
    ): int {
        $kueri = DB::table('EventPemasaran')
            ->where('Jenis', $jenis)
            ->whereBetween('TerjadiPada', [$filter->dari, $filter->sampai]);

        if ($pengunjung !== null) {
            $kueri->whereIn('EventPemasaran.PengenalPengunjung', $pengunjung);
        }

        return $unik
            ? $kueri->distinct()->count('EventPemasaran.PengenalPengunjung')
            : $kueri->count();
    }

    private function pengirimanFormulir(FilterGrowth $filter, ?Builder $pengunjung): int
    {
        $kueri = DB::table('PengirimanFormulir')
            ->whereBetween('DikirimPada', [$filter->dari, $filter->sampai]);

        if ($pengunjung !== null) {
            $kueri->whereIn('PengirimanFormulir.PengenalPengunjung', $pengunjung);
        }

        return $kueri->count();
    }

    /** Revenue dari organisasi yang trialnya berkonversi di rentang ini; itulah pendapatan baru. */
    private function mrrBaru(FilterGrowth $filter, ?Builder $pengunjung): float
    {
        $trial = DB::table('Trial')
            ->select('OrganisasiId')
            ->whereNotNull('KonversiPada')
            ->whereBetween('KonversiPada', [$filter->dari, $filter->sampai]);

        if ($pengunjung !== null) {
            $trial->whereIn('Trial.PengenalPengunjung', $pengunjung);
        }

        $total = DB::table('PembayaranLangganan')
            ->where('Status', StatusPembayaranLangganan::Berhasil->value)
            ->whereBetween('DibayarPada', [$filter->dari, $filter->sampai])
            ->whereIn('OrganisasiId', $trial)
            ->sum('Jumlah');

        return round((float) $total, 2);
    }

    /**
     * Revenue dari organisasi yang datang lewat partner.
     *
     * Dihitung dari pembayaran yang benar-benar berhasil, bukan dari komisi yang
     * lahir darinya: komisi masih dapat dibatalkan, sedangkan uang yang masuk
     * tetap uang yang masuk.
     */
    private function revenuePartner(FilterGrowth $filter): float
    {
        $lead = DB::table('LeadPartner')
            ->select('OrganisasiId')
            ->whereNotNull('OrganisasiId')
            ->where('Status', '!=', StatusLeadPartner::Ditolak->value)
            ->when(
                $filter->partner !== null,
                fn ($kueri) => $kueri->whereIn('PartnerId', function (Builder $sub) use ($filter): void {
                    $sub->select('Id')->from('Partner')->where('Kode', $filter->partner);
                }),
            );

        $total = DB::table('PembayaranLangganan')
            ->where('Status', StatusPembayaranLangganan::Berhasil->value)
            ->whereBetween('DibayarPada', [$filter->dari, $filter->sampai])
            ->whereIn('OrganisasiId', $lead)
            ->sum('Jumlah');

        return round((float) $total, 2);
    }

    private function referralKonversi(FilterGrowth $filter): float
    {
        $dasar = fn (): Builder => DB::table('Referral')
            ->whereBetween('DibuatPada', [$filter->dari, $filter->sampai])
            ->when(
                $filter->programReferral !== null,
                fn ($kueri) => $kueri->whereIn('ProgramReferralId', function (Builder $sub) use ($filter): void {
                    $sub->select('Id')->from('ProgramReferral')->where('Kode', $filter->programReferral);
                }),
            );

        $diklik = $dasar()->where('Status', '!=', StatusReferral::Ditolak->value)->count();
        $berbuah = $dasar()->whereIn('Status', [
            StatusReferral::Paid->value,
            StatusReferral::RewardPending->value,
            StatusReferral::Rewarded->value,
        ])->count();

        return $this->rasio($berbuah, $diklik);
    }

    private function rasio(int $atas, int $bawah): float
    {
        return $bawah === 0 ? 0.0 : round($atas / $bawah * 100, 1);
    }
}
