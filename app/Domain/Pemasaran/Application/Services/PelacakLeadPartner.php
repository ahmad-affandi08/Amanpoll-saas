<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Actions\CatatProspek;
use App\Domain\Pemasaran\Domain\Enums\StatusLeadPartner;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LeadPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/** Lead kiriman partner, dari pengiriman sampai pembayaran pertamanya; statusnya hanya boleh maju (MARKETING.md 21). */
final class PelacakLeadPartner
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly CatatProspek $catatProspek,
        private readonly PerekamEventPemasaran $event,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * @param  array{NamaPerusahaan: string, NamaKontak: string, Email: string, Telepon?: string|null, Catatan?: string|null}  $data
     */
    public function kirim(Partner $partner, array $data): LeadPartner
    {
        if (! $partner->Status->bolehMasuk()) {
            throw new AturanBisnisDilanggar('Partner ini sedang tidak berhak mengirim lead.');
        }

        $program = $partner->program;

        if ($program === null || ! $program->Aktif) {
            throw new AturanBisnisDilanggar('Program partner ini sedang tidak aktif.');
        }

        $email = mb_strtolower(trim($data['Email']));
        $this->pastikanBelumDiklaim($email, $partner);

        return $this->transaksi->jalankan(function () use ($partner, $program, $data, $email): LeadPartner {
            $prospek = $this->catatProspek->jalankan(
                [
                    'Nama' => $data['NamaKontak'],
                    'Email' => $email,
                    'Telepon' => $data['Telepon'] ?? null,
                    'Perusahaan' => $data['NamaPerusahaan'],
                    'Catatan' => $data['Catatan'] ?? null,
                ],
                SumberProspek::Partner,
                dariFormulir: false,
            );

            $sekarang = CarbonImmutable::now();

            try {
                $lead = LeadPartner::create([
                    'PartnerId' => $partner->Id,
                    'ProspekId' => $prospek->Id,
                    'OrganisasiId' => $prospek->OrganisasiId,
                    'NamaPerusahaan' => $data['NamaPerusahaan'],
                    'NamaKontak' => $data['NamaKontak'],
                    'Email' => $email,
                    'Telepon' => $data['Telepon'] ?? null,
                    'Catatan' => $data['Catatan'] ?? null,
                    'Status' => StatusLeadPartner::Dikirim,
                    'DikirimPada' => $sekarang,
                    'KedaluwarsaPada' => $sekarang->addDays(max($program->HariAtribusi, 1)),
                ]);
            } catch (UniqueConstraintViolationException) {
                // Dua pengiriman berbarengan untuk alamat yang sama; yang pertama memilikinya.
                throw new AturanBisnisDilanggar('Lead dengan alamat email ini sudah pernah dikirim.');
            }

            $this->event->catat(
                KatalogPeristiwaPemasaran::PARTNER_MENGIRIM_LEAD,
                pengenalPengunjung: $prospek->PengenalPengunjung,
                dataTambahan: [
                    'LeadPartnerId' => $lead->Id,
                    'PartnerId' => $partner->Id,
                    'ProspekId' => $prospek->Id,
                ],
                organisasiId: $prospek->OrganisasiId,
            );

            $this->audit->catat('LeadPartner.Dikirim', 'LeadPartner', $lead->Id, dataSesudah: [
                'PartnerId' => $partner->Id,
                'Email' => $lead->Email,
                'NamaPerusahaan' => $lead->NamaPerusahaan,
            ]);

            return $lead;
        });
    }

    /** Lead hidup milik satu prospek, atau null bila tidak ada yang masih berjalan. */
    public function untukProspek(?Prospek $prospek): ?LeadPartner
    {
        if ($prospek === null) {
            return null;
        }

        return LeadPartner::query()
            ->where(function ($kueri) use ($prospek): void {
                $kueri->where('ProspekId', $prospek->Id);

                if (is_string($prospek->Email) && $prospek->Email !== '') {
                    $kueri->orWhere('Email', mb_strtolower(trim($prospek->Email)));
                }
            })
            ->where('Status', '!=', StatusLeadPartner::Ditolak->value)
            ->orderByDesc('DikirimPada')
            ->first();
    }

    /** Satu-satunya tempat yang memutuskan lead mana yang berhak atas komisi satu organisasi. */
    public function untukOrganisasi(string $organisasiId): ?LeadPartner
    {
        return LeadPartner::query()
            ->where('OrganisasiId', $organisasiId)
            ->whereNotIn('Status', StatusLeadPartner::nilaiTanpaKomisi())
            ->orderByDesc('DikirimPada')
            ->first();
    }

    public function tandaiTrial(?Prospek $prospek, string $organisasiId): ?LeadPartner
    {
        $lead = $this->untukProspek($prospek);

        if ($lead === null) {
            return null;
        }

        $lead->OrganisasiId = $organisasiId;
        $lead->ProspekId ??= $prospek?->Id;

        return $this->majukan($lead, StatusLeadPartner::Trial);
    }

    public function tandaiPaid(string $organisasiId): ?LeadPartner
    {
        $lead = $this->untukOrganisasi($organisasiId);

        return $lead === null ? null : $this->majukan($lead, StatusLeadPartner::Paid);
    }

    public function terima(LeadPartner $lead): LeadPartner
    {
        if ($lead->Status === StatusLeadPartner::Ditolak) {
            throw new AturanBisnisDilanggar('Lead yang sudah ditolak tidak dapat diterima kembali.');
        }

        $lead->DiterimaPada ??= CarbonImmutable::now();

        return $this->majukan($lead, StatusLeadPartner::Diterima);
    }

    public function tolak(LeadPartner $lead, string $alasan): LeadPartner
    {
        if ($lead->Status === StatusLeadPartner::Paid) {
            throw new AturanBisnisDilanggar('Lead yang sudah membayar tidak dapat ditolak.');
        }

        $lead->Status = StatusLeadPartner::Ditolak;
        $lead->AlasanDitolak = mb_substr($alasan, 0, 300);
        $lead->save();

        $this->audit->catat('LeadPartner.Ditolak', 'LeadPartner', $lead->Id, dataSesudah: [
            'PartnerId' => $lead->PartnerId,
            'Alasan' => $lead->AlasanDitolak,
        ]);

        return $lead;
    }

    /** Lead yang lewat jendela atribusinya ditutup, kecuali yang sudah berbuah. */
    public function kedaluwarsakan(int $batas = 500): int
    {
        $lewat = LeadPartner::query()
            ->whereIn('Status', [StatusLeadPartner::Dikirim->value, StatusLeadPartner::Diterima->value])
            ->where('KedaluwarsaPada', '<', CarbonImmutable::now())
            ->limit($batas)
            ->get();

        foreach ($lewat as $satu) {
            $this->tolak($satu, 'Lewat jendela atribusi program partner.');
        }

        return $lewat->count();
    }

    private function majukan(LeadPartner $lead, StatusLeadPartner $tujuan): LeadPartner
    {
        if ($lead->Status->final() || $tujuan->urutan() <= $lead->Status->urutan()) {
            $lead->save();

            return $lead;
        }

        $lead->Status = $tujuan;
        $this->stempel($lead, $tujuan);
        $lead->save();

        return $lead;
    }

    private function stempel(LeadPartner $lead, StatusLeadPartner $tujuan): void
    {
        $sekarang = CarbonImmutable::now();

        match ($tujuan) {
            StatusLeadPartner::Diterima => $lead->DiterimaPada ??= $sekarang,
            StatusLeadPartner::Trial => $lead->MenjadiTrialPada ??= $sekarang,
            StatusLeadPartner::Paid => $lead->MenjadiPaidPada ??= $sekarang,
            default => null,
        };
    }

    /** Satu alamat hanya boleh diklaim satu partner; pengirim pertama yang memilikinya. */
    private function pastikanBelumDiklaim(string $email, Partner $partner): void
    {
        $ada = LeadPartner::query()->where('Email', $email)->first();

        if ($ada === null) {
            return;
        }

        throw new AturanBisnisDilanggar(
            $ada->PartnerId === $partner->Id
                ? 'Anda sudah pernah mengirim lead dengan alamat email ini.'
                : 'Lead dengan alamat email ini sudah diklaim partner lain.',
        );
    }
}
