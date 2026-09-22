<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Services\PelacakLeadPartner;
use App\Domain\Pemasaran\Application\Services\PelacakReferral;
use App\Domain\Pemasaran\Application\Services\PembacaKonfigurasiTrial;
use App\Domain\Pemasaran\Application\Services\PendaftarSequenceTrial;
use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\Enums\ButirAktivasi;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ButirAktivasiTrial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Carbon\CarbonImmutable;

/** Memulai trial; ProspekId dan PengenalPengunjung ikut disalin agar attribution tetap tertelusur (MARKETING.md 12). */
final class MulaiTrial
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PembacaKonfigurasiTrial $konfigurasi,
        private readonly PerekamEventPemasaran $event,
        private readonly LayananAudit $audit,
        private readonly PendaftarSequenceTrial $sequence,
        private readonly PelacakReferral $referral,
        private readonly PelacakLeadPartner $leadPartner,
    ) {}

    public function jalankan(string $organisasiId, ?Prospek $prospek = null, ?string $langgananId = null): Trial
    {
        return $this->transaksi->jalankan(function () use ($organisasiId, $prospek, $langgananId): Trial {
            $adaSebelumnya = Trial::query()->where('OrganisasiId', $organisasiId)->first();

            if ($adaSebelumnya !== null) {
                return $adaSebelumnya;
            }

            $setelan = $this->konfigurasi->berlaku();
            $mulai = CarbonImmutable::now();

            $trial = Trial::create([
                'OrganisasiId' => $organisasiId,
                'ProspekId' => $prospek?->Id,
                'LanggananId' => $langgananId,
                'PengenalPengunjung' => $prospek?->PengenalPengunjung,
                'Status' => StatusTrial::Terdaftar,
                'MulaiPada' => $mulai,
                'BerakhirPada' => $mulai->addDays($setelan->durasiHari),
                'HariPerpanjangan' => 0,
            ]);

            $this->tautkanProspek($prospek, $organisasiId);

            ButirAktivasiTrial::create([
                'TrialId' => $trial->Id,
                'Butir' => ButirAktivasi::OrganisasiDibuat,
                'SelesaiPada' => $mulai,
            ]);

            $trial->Status = StatusTrial::Setup;
            $trial->save();

            $this->event->catat(
                KatalogPeristiwaPemasaran::TRIAL_DIMULAI,
                pengenalPengunjung: $trial->PengenalPengunjung,
                dataTambahan: ['TrialId' => $trial->Id, 'DurasiHari' => $setelan->durasiHari],
                organisasiId: $organisasiId,
            );

            $this->sequence->daftarkan($prospek);
            $this->referral->tandaiTrial($prospek, $organisasiId);
            $this->leadPartner->tandaiTrial($prospek, $organisasiId);

            $this->audit->catat('Trial.Dimulai', 'Trial', $trial->Id, dataSesudah: [
                'OrganisasiId' => $organisasiId,
                'ProspekId' => $prospek?->Id,
                'BerakhirPada' => $trial->BerakhirPada->toIso8601String(),
            ]);

            return $trial;
        });
    }

    /** Tautan prospek ke organisasi, bukan scope: prospeknya tetap milik platform. */
    private function tautkanProspek(?Prospek $prospek, string $organisasiId): void
    {
        if ($prospek === null || $prospek->OrganisasiId !== null) {
            return;
        }

        $prospek->OrganisasiId = $organisasiId;
        $prospek->save();
    }
}
