<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Enums\StatusReferral;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KodeReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Referral;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/** Melacak referral dari klik sampai pembayaran; status hanya boleh maju dan klik kedua tidak melahirkan baris kedua (MARKETING.md 20). */
final class PelacakReferral
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PenjagaSelfReferral $penjaga,
        private readonly PerekamEventPemasaran $event,
        private readonly LayananAudit $audit,
    ) {}

    public function catatKlik(string $kode, string $pengenalPengunjung): ?Referral
    {
        $kodeReferral = $this->kodeAktif($kode);

        if ($kodeReferral === null) {
            return null;
        }

        $ada = Referral::query()
            ->where('ProgramReferralId', $kodeReferral->ProgramReferralId)
            ->where('PengenalPengunjung', $pengenalPengunjung)
            ->first();

        if ($ada !== null) {
            return $ada;
        }

        $alasan = $this->penjaga->alasanTolak($kodeReferral, $pengenalPengunjung);

        return $this->transaksi->jalankan(
            fn (): ?Referral => $this->tulisKlik($kodeReferral, $pengenalPengunjung, $alasan),
        );
    }

    /** Referral hidup milik satu pengunjung, atau null bila tidak ada yang masih berjalan. */
    public function untukPengunjung(?string $pengenalPengunjung): ?Referral
    {
        if ($pengenalPengunjung === null || $pengenalPengunjung === '') {
            return null;
        }

        return Referral::query()
            ->where('PengenalPengunjung', $pengenalPengunjung)
            ->whereNotIn('Status', [
                StatusReferral::Ditolak->value,
                StatusReferral::Kedaluwarsa->value,
            ])
            ->orderByDesc('DibuatPada')
            ->first();
    }

    public function tandaiLead(Prospek $prospek): ?Referral
    {
        $referral = $this->untukPengunjung($prospek->PengenalPengunjung);

        if ($referral === null) {
            return null;
        }

        $kode = $referral->kode;
        $alasan = $kode === null ? null : $this->penjaga->alasanTolak($kode, null, $prospek);

        if ($alasan !== null) {
            return $this->tolak($referral, $alasan);
        }

        $referral->ProspekId = $prospek->Id;

        return $this->majukan($referral, StatusReferral::Lead, KatalogPeristiwaPemasaran::REFERRAL_MENJADI_LEAD);
    }

    public function tandaiTrial(?Prospek $prospek, string $organisasiBaruId): ?Referral
    {
        $referral = $this->untukPengunjung($prospek?->PengenalPengunjung);

        if ($referral === null) {
            return null;
        }

        $kode = $referral->kode;
        $alasan = $kode === null ? null : $this->penjaga->alasanTolak($kode, null, $prospek, $organisasiBaruId);

        if ($alasan !== null) {
            return $this->tolak($referral, $alasan);
        }

        $referral->ProspekId ??= $prospek?->Id;
        $referral->OrganisasiBaruId = $organisasiBaruId;

        return $this->majukan($referral, StatusReferral::Trial, KatalogPeristiwaPemasaran::REFERRAL_MENJADI_TRIAL);
    }

    public function tandaiPaid(string $organisasiBaruId, ?string $langgananId = null): ?Referral
    {
        $referral = Referral::query()
            ->where('OrganisasiBaruId', $organisasiBaruId)
            ->whereNotIn('Status', [
                StatusReferral::Ditolak->value,
                StatusReferral::Kedaluwarsa->value,
            ])
            ->orderByDesc('DibuatPada')
            ->first();

        if ($referral === null) {
            return null;
        }

        $kode = $referral->kode;
        $alasan = $kode === null
            ? null
            : $this->penjaga->alasanTolak($kode, null, $referral->prospek, $organisasiBaruId);

        if ($alasan !== null) {
            return $this->tolak($referral, $alasan);
        }

        $referral->LanggananId = $langgananId;

        return $this->majukan($referral, StatusReferral::Paid, KatalogPeristiwaPemasaran::REFERRAL_MENJADI_PAID);
    }

    /** Referral yang lewat jendelanya ditutup, kecuali yang sudah berbuah. */
    public function kedaluwarsakan(int $batas = 500): int
    {
        $lewat = Referral::query()
            ->whereNotIn('Status', [
                StatusReferral::Paid->value,
                StatusReferral::RewardPending->value,
                StatusReferral::Rewarded->value,
                StatusReferral::Kedaluwarsa->value,
                StatusReferral::Ditolak->value,
            ])
            ->where('KedaluwarsaPada', '<', CarbonImmutable::now())
            ->limit($batas)
            ->get();

        foreach ($lewat as $satu) {
            $satu->Status = StatusReferral::Kedaluwarsa;
            $satu->save();
        }

        return $lewat->count();
    }

    public function majukan(Referral $referral, StatusReferral $tujuan, ?string $peristiwa = null): Referral
    {
        if (! $tujuan->lebihMajuDari($referral->Status)) {
            $referral->save();

            return $referral;
        }

        $referral->Status = $tujuan;
        $this->stempel($referral, $tujuan);
        $referral->save();

        if ($peristiwa !== null) {
            $this->event->catat(
                $peristiwa,
                pengenalPengunjung: $referral->PengenalPengunjung,
                dataTambahan: [
                    'ReferralId' => $referral->Id,
                    'ProspekId' => $referral->ProspekId,
                    'OrganisasiPerujukId' => $referral->OrganisasiPerujukId,
                ],
                organisasiId: $referral->OrganisasiBaruId,
            );
        }

        return $referral;
    }

    public function tolak(Referral $referral, string $alasan): Referral
    {
        $referral->Status = StatusReferral::Ditolak;
        $referral->AlasanDitolak = mb_substr($alasan, 0, 300);
        $referral->save();

        $this->audit->catat('Referral.Ditolak', 'Referral', $referral->Id, dataSesudah: [
            'OrganisasiPerujukId' => $referral->OrganisasiPerujukId,
            'Alasan' => $referral->AlasanDitolak,
        ]);

        return $referral;
    }

    private function tulisKlik(KodeReferral $kode, string $pengenalPengunjung, ?string $alasan): ?Referral
    {
        $program = $kode->program;

        if ($program === null) {
            return null;
        }

        $sekarang = CarbonImmutable::now();

        try {
            $referral = Referral::create([
                'ProgramReferralId' => $program->Id,
                'KodeReferralId' => $kode->Id,
                'OrganisasiPerujukId' => $kode->OrganisasiId,
                'PengenalPengunjung' => $pengenalPengunjung,
                'Status' => $alasan === null ? StatusReferral::Diklik : StatusReferral::Ditolak,
                'AlasanDitolak' => $alasan === null ? null : mb_substr($alasan, 0, 300),
                'DiklikPada' => $sekarang,
                'KedaluwarsaPada' => $sekarang->addDays(max($program->HariKedaluwarsa, 1)),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Dua klik berbarengan dari pengunjung yang sama; yang pertama menang.
            return Referral::query()
                ->where('ProgramReferralId', $program->Id)
                ->where('PengenalPengunjung', $pengenalPengunjung)
                ->first();
        }

        if ($alasan === null) {
            $this->event->catat(
                KatalogPeristiwaPemasaran::REFERRAL_DIKLIK,
                pengenalPengunjung: $pengenalPengunjung,
                dataTambahan: ['ReferralId' => $referral->Id, 'Kode' => $kode->Kode],
            );
        }

        return $referral;
    }

    private function stempel(Referral $referral, StatusReferral $tujuan): void
    {
        $sekarang = CarbonImmutable::now();

        match ($tujuan) {
            StatusReferral::Diklik => $referral->DiklikPada ??= $sekarang,
            StatusReferral::Lead => $referral->MenjadiLeadPada ??= $sekarang,
            StatusReferral::Trial => $referral->MenjadiTrialPada ??= $sekarang,
            StatusReferral::Paid => $referral->MenjadiPaidPada ??= $sekarang,
            default => null,
        };
    }

    private function kodeAktif(string $kode): ?KodeReferral
    {
        return KodeReferral::query()
            ->with('program')
            ->where('Kode', $kode)
            ->where('Aktif', true)
            ->whereHas('program', fn ($kueri) => $kueri->where('Aktif', true))
            ->first();
    }
}
