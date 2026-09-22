<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\StatusLeadPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusReferral;
use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Filter dashboard menjadi satu subkueri pengunjung yang dipakai ulang; idnya tidak pernah ditarik ke PHP (MARKETING.md 5). */
final class PenyaringGrowth
{
    /** Subkueri PengenalPengunjung yang lolos filter, atau null bila hanya rentang tanggal yang dipakai. */
    public function pengunjung(FilterGrowth $filter): ?Builder
    {
        if (! $filter->menyaringPengunjung()) {
            return null;
        }

        $kueri = DB::table('SesiPengunjung')->select('PengenalPengunjung')->distinct();

        $this->batasiSesi($kueri, $filter);
        $this->batasiAttribution($kueri, $filter);
        $this->batasiProspek($kueri, $filter);
        $this->batasiReferral($kueri, $filter);
        $this->batasiPartner($kueri, $filter);

        return $kueri;
    }

    private function batasiSesi(Builder $kueri, FilterGrowth $filter): void
    {
        if ($filter->perangkat !== null) {
            $kueri->where('Perangkat', $filter->perangkat);
        }

        if ($filter->landing !== null) {
            $kueri->where('LandingUrl', 'like', '%'.$filter->landing.'%');
        }
    }

    /** Channel dan campaign dibaca dari sentuhan pertama: itulah yang menghasilkan pengunjungnya. */
    private function batasiAttribution(Builder $kueri, FilterGrowth $filter): void
    {
        if ($filter->channel === null && $filter->kampanye === null) {
            return;
        }

        $kueri->whereIn('SesiPengunjung.PengenalPengunjung', function (Builder $sub) use ($filter): void {
            $sub->select('PengenalPengunjung')->from('AttributionPemasaran');

            if ($filter->channel !== null) {
                $sub->where('SumberPertama', $filter->channel);
            }

            if ($filter->kampanye !== null) {
                $sub->where('KampanyePertama', $filter->kampanye);
            }
        });
    }

    private function batasiProspek(Builder $kueri, FilterGrowth $filter): void
    {
        if ($filter->industri === null && $filter->paket === null) {
            return;
        }

        $kueri->whereIn('SesiPengunjung.PengenalPengunjung', function (Builder $sub) use ($filter): void {
            $sub->select('Prospek.PengenalPengunjung')
                ->from('Prospek')
                ->whereNotNull('Prospek.PengenalPengunjung');

            if ($filter->industri !== null) {
                $sub->join('OrganisasiProspek', 'OrganisasiProspek.Id', '=', 'Prospek.OrganisasiProspekId')
                    ->where('OrganisasiProspek.Industri', $filter->industri);
            }

            if ($filter->paket !== null) {
                $sub->whereIn('Prospek.OrganisasiId', function (Builder $paket) use ($filter): void {
                    $paket->select('Langganan.OrganisasiId')
                        ->from('Langganan')
                        ->join('PaketLangganan', 'PaketLangganan.Id', '=', 'Langganan.PaketLanggananId')
                        ->where('PaketLangganan.Kode', $filter->paket);
                });
            }
        });
    }

    /** Pengunjung milik partner adalah pengunjung di balik prospek yang ditautkan lead kirimannya. */
    private function batasiPartner(Builder $kueri, FilterGrowth $filter): void
    {
        if ($filter->partner === null) {
            return;
        }

        $kueri->whereIn('SesiPengunjung.PengenalPengunjung', function (Builder $sub) use ($filter): void {
            $sub->select('Prospek.PengenalPengunjung')
                ->from('LeadPartner')
                ->join('Partner', 'Partner.Id', '=', 'LeadPartner.PartnerId')
                ->join('Prospek', 'Prospek.Id', '=', 'LeadPartner.ProspekId')
                ->whereNotNull('Prospek.PengenalPengunjung')
                ->where('Partner.Kode', $filter->partner)
                ->where('LeadPartner.Status', '!=', StatusLeadPartner::Ditolak->value);
        });
    }

    private function batasiReferral(Builder $kueri, FilterGrowth $filter): void
    {
        if ($filter->programReferral === null) {
            return;
        }

        $kueri->whereIn('SesiPengunjung.PengenalPengunjung', function (Builder $sub) use ($filter): void {
            $sub->select('Referral.PengenalPengunjung')
                ->from('Referral')
                ->join('ProgramReferral', 'ProgramReferral.Id', '=', 'Referral.ProgramReferralId')
                ->where('ProgramReferral.Kode', $filter->programReferral)
                ->where('Referral.Status', '!=', StatusReferral::Ditolak->value);
        });
    }
}
