<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Domain\Contracts\PemberiImbalanLangganan;
use App\Domain\Pemasaran\Domain\Enums\StatusReferral;
use App\Domain\Pemasaran\Domain\Enums\StatusRewardReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Referral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RewardReferral;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

/** Satu referral satu imbalan, dijaga indeks unik, dan pemberiannya lewat kontrak domain Langganan (MARKETING.md 20). */
final class PenghitungRewardReferral
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PemberiImbalanLangganan $pemberi,
        private readonly PenjagaSelfReferral $penjaga,
        private readonly KonteksOrganisasi $konteks,
        private readonly LayananAudit $audit,
    ) {}

    /** Imbalan hanya terbit untuk referral yang sudah dibayar dan belum punya imbalan. */
    public function terbitkan(Referral $referral): ?RewardReferral
    {
        if ($referral->Status !== StatusReferral::Paid) {
            return $referral->reward;
        }

        $program = $referral->program;

        if ($program === null) {
            return null;
        }

        $kode = $referral->kode;
        $alasan = $kode === null
            ? null
            : $this->penjaga->alasanTolak($kode, null, $referral->prospek, $referral->OrganisasiBaruId);

        if ($alasan !== null) {
            $this->tolakReferral($referral, $alasan);

            return null;
        }

        return $this->transaksi->jalankan(function () use ($referral, $program): RewardReferral {
            try {
                $reward = RewardReferral::create([
                    'ReferralId' => $referral->Id,
                    'OrganisasiPenerimaId' => $referral->OrganisasiPerujukId,
                    'Jenis' => $program->JenisReward,
                    'Nilai' => $program->NilaiReward,
                    'Status' => StatusRewardReferral::Tertunda,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Sudah pernah terbit untuk referral ini; itu justru hasil yang diinginkan.
                return RewardReferral::query()->where('ReferralId', $referral->Id)->firstOrFail();
            }

            $referral->Status = StatusReferral::RewardPending;
            $referral->save();

            $this->audit->catat('RewardReferral.Diterbitkan', 'RewardReferral', $reward->Id, dataSesudah: [
                'ReferralId' => $referral->Id,
                'OrganisasiPenerimaId' => $reward->OrganisasiPenerimaId,
                'Jenis' => $reward->Jenis->value,
                'Nilai' => (float) $reward->Nilai,
            ]);

            return $reward;
        });
    }

    /** Imbalan menyentuh tenant perujuk, jadi konteksnya dikosongkan agar auditnya tidak masuk ke buku tenant yang membayar. */
    public function berikan(RewardReferral $reward): StatusRewardReferral
    {
        if ($reward->Status->final()) {
            return $reward->Status;
        }

        $konteksSemula = $this->konteks->id();
        $this->konteks->bersihkan();

        try {
            return $this->jalankanPemberian($reward);
        } finally {
            $this->konteks->tetapkan($konteksSemula);
        }
    }

    private function jalankanPemberian(RewardReferral $reward): StatusRewardReferral
    {

        $reward->Percobaan++;

        try {
            $ringkasan = $reward->Jenis->manual()
                ? 'Imbalan kustom menunggu penyelesaian manual.'
                : $this->pemberi->beri(
                    (string) $reward->OrganisasiPenerimaId,
                    $reward->Jenis->value,
                    (float) $reward->Nilai,
                    ['ReferralId' => $reward->ReferralId],
                );
        } catch (Throwable $galat) {
            $reward->Status = StatusRewardReferral::Gagal;
            $reward->Galat = mb_substr($galat->getMessage(), 0, 500);
            $reward->save();

            return StatusRewardReferral::Gagal;
        }

        return $this->transaksi->jalankan(function () use ($reward, $ringkasan): StatusRewardReferral {
            $reward->Status = StatusRewardReferral::Diberikan;
            $reward->Ringkasan = mb_substr($ringkasan, 0, 500);
            $reward->Galat = null;
            $reward->DiberikanPada = CarbonImmutable::now();
            $reward->save();

            $referral = $reward->referral;

            if ($referral !== null) {
                $referral->Status = StatusReferral::Rewarded;
                $referral->save();
            }

            $this->audit->catat('RewardReferral.Diberikan', 'RewardReferral', $reward->Id, dataSesudah: [
                'OrganisasiPenerimaId' => $reward->OrganisasiPenerimaId,
                'Jenis' => $reward->Jenis->value,
                'Nilai' => (float) $reward->Nilai,
                'Ringkasan' => $reward->Ringkasan,
            ]);

            return StatusRewardReferral::Diberikan;
        });
    }

    public function batalkan(RewardReferral $reward, string $alasan): RewardReferral
    {
        if ($reward->Status === StatusRewardReferral::Diberikan) {
            throw new AturanBisnisDilanggar('Imbalan yang sudah diberikan tidak dapat dibatalkan.');
        }

        $reward->Status = StatusRewardReferral::Dibatalkan;
        $reward->Galat = mb_substr($alasan, 0, 500);
        $reward->save();

        $this->audit->catat('RewardReferral.Dibatalkan', 'RewardReferral', $reward->Id, dataSesudah: [
            'OrganisasiPenerimaId' => $reward->OrganisasiPenerimaId,
            'Alasan' => $reward->Galat,
        ]);

        return $reward;
    }

    /** @return list<RewardReferral> */
    public function tertunda(int $batas = 200): array
    {
        return array_values(RewardReferral::query()
            ->whereIn('Status', [StatusRewardReferral::Tertunda->value, StatusRewardReferral::Gagal->value])
            ->orderBy('DibuatPada')
            ->limit($batas)
            ->get()
            ->all());
    }

    private function tolakReferral(Referral $referral, string $alasan): void
    {
        $referral->Status = StatusReferral::Ditolak;
        $referral->AlasanDitolak = mb_substr($alasan, 0, 300);
        $referral->save();

        $this->audit->catat('Referral.Ditolak', 'Referral', $referral->Id, dataSesudah: [
            'OrganisasiPerujukId' => $referral->OrganisasiPerujukId,
            'Alasan' => $referral->AlasanDitolak,
        ]);
    }
}
