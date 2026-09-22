<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Domain\Contracts\PembacaPembayaranLangganan;
use App\Domain\Pemasaran\Domain\Enums\StatusKomisiPartner;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KomisiPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LeadPartner;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Melahirkan komisi dari satu pembayaran yang benar-benar terjadi (Gate 38.09).
 *
 * Yang menentukan bukan muatan peristiwa melainkan baris pembayaran di domain
 * Langganan: idnya ditanyakan ulang lewat kontrak, jumlahnya dibaca dari sana,
 * dan `PembayaranId` yang unik menahan kelahiran komisi kedua atas pembayaran
 * yang sama. Billing sendiri tidak pernah ditulis dari sini.
 */
final class PenghitungKomisiPartner
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PembacaPembayaranLangganan $pembayaran,
        private readonly PelacakLeadPartner $lead,
        private readonly PemilihAturanKomisi $pemilih,
        private readonly PerekamEventPemasaran $event,
        private readonly KonteksOrganisasi $konteks,
        private readonly LayananAudit $audit,
    ) {}

    /** Komisi menyentuh partner lintas tenant, jadi konteksnya dikosongkan agar auditnya tidak masuk buku tenant yang membayar. */
    public function dariPembayaran(string $pembayaranId): ?KomisiPartner
    {
        $konteksSemula = $this->konteks->id();
        $this->konteks->bersihkan();

        try {
            return $this->terbitkan($pembayaranId);
        } finally {
            $this->konteks->tetapkan($konteksSemula);
        }
    }

    private function terbitkan(string $pembayaranId): ?KomisiPartner
    {
        $terkonfirmasi = $this->pembayaran->konfirmasi($pembayaranId);

        if ($terkonfirmasi === null) {
            return null;
        }

        // Kelayakan leadnya diputuskan `untukOrganisasi`; tidak ada saringan kedua di sini yang dapat berselisih.
        $lead = $this->lead->untukOrganisasi($terkonfirmasi->organisasiId);

        if ($lead === null) {
            return null;
        }

        $partner = $lead->partner;

        if ($partner === null || ! $partner->Status->berhakKomisi()) {
            return null;
        }

        $aturan = $this->pemilih->untuk($partner, $terkonfirmasi->dibayarPada);

        if ($aturan === null) {
            return null;
        }

        if ($this->sudahMencapaiBatas($lead, $aturan->MaksPembayaran)) {
            return null;
        }

        $jumlah = $aturan->Jenis->hitung((float) $aturan->Nilai, $terkonfirmasi->jumlah);

        if ($jumlah <= 0.0) {
            return null;
        }

        return $this->transaksi->jalankan(function () use ($lead, $partner, $aturan, $terkonfirmasi, $jumlah): ?KomisiPartner {
            try {
                $komisi = KomisiPartner::create([
                    'PartnerId' => $partner->Id,
                    'LeadPartnerId' => $lead->Id,
                    'AturanKomisiPartnerId' => $aturan->Id,
                    'OrganisasiId' => $terkonfirmasi->organisasiId,
                    'LanggananId' => $terkonfirmasi->langgananId,
                    'PembayaranId' => $terkonfirmasi->pembayaranId,
                    'JumlahPembayaran' => $terkonfirmasi->jumlah,
                    'Jumlah' => $jumlah,
                    'Status' => StatusKomisiPartner::Tertunda,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Pembayaran ini sudah melahirkan komisinya; itu justru hasil yang diinginkan.
                return KomisiPartner::query()
                    ->where('PembayaranId', $terkonfirmasi->pembayaranId)
                    ->first();
            }

            $this->event->catat(
                KatalogPeristiwaPemasaran::KOMISI_PARTNER_DIBUAT,
                dataTambahan: [
                    'KomisiPartnerId' => $komisi->Id,
                    'PartnerId' => $partner->Id,
                    'LeadPartnerId' => $lead->Id,
                    'Jumlah' => $jumlah,
                ],
                organisasiId: $terkonfirmasi->organisasiId,
            );

            $this->audit->catat('KomisiPartner.Dibuat', 'KomisiPartner', $komisi->Id, dataSesudah: [
                'PartnerId' => $partner->Id,
                'PembayaranId' => $komisi->PembayaranId,
                'JumlahPembayaran' => $terkonfirmasi->jumlah,
                'Jumlah' => $jumlah,
            ]);

            return $komisi;
        });
    }

    public function setujui(KomisiPartner $komisi): KomisiPartner
    {
        if ($komisi->Status->final()) {
            throw new AturanBisnisDilanggar('Komisi yang sudah selesai tidak dapat disetujui ulang.');
        }

        $komisi->Status = StatusKomisiPartner::Disetujui;
        $komisi->save();

        $this->audit->catat('KomisiPartner.Disetujui', 'KomisiPartner', $komisi->Id, dataSesudah: [
            'PartnerId' => $komisi->PartnerId,
            'Jumlah' => (float) $komisi->Jumlah,
        ]);

        return $komisi;
    }

    public function batalkan(KomisiPartner $komisi, string $alasan): KomisiPartner
    {
        if ($komisi->Status === StatusKomisiPartner::Dibayar) {
            throw new AturanBisnisDilanggar('Komisi yang sudah dibayar tidak dapat dibatalkan.');
        }

        $komisi->Status = StatusKomisiPartner::Dibatalkan;
        $komisi->Catatan = mb_substr($alasan, 0, 500);
        $komisi->save();

        $this->audit->catat('KomisiPartner.Dibatalkan', 'KomisiPartner', $komisi->Id, dataSesudah: [
            'PartnerId' => $komisi->PartnerId,
            'Alasan' => $komisi->Catatan,
        ]);

        return $komisi;
    }

    /** Batas dihitung dari komisi yang masih berlaku; yang dibatalkan tidak ikut memakan jatah. */
    private function sudahMencapaiBatas(LeadPartner $lead, ?int $maks): bool
    {
        if ($maks === null || $maks < 1) {
            return false;
        }

        $terpakai = KomisiPartner::query()
            ->where('LeadPartnerId', $lead->Id)
            ->where('Status', '!=', StatusKomisiPartner::Dibatalkan->value)
            ->count();

        return $terpakai >= $maks;
    }

    /** Lead yang ditandai lunas oleh pembayaran ini, supaya portalnya menampilkan pelanggan berbayar. */
    public function tandaiLeadLunas(string $organisasiId): ?LeadPartner
    {
        return $this->lead->tandaiPaid($organisasiId);
    }

    /** @return list<KomisiPartner> */
    public function tertunda(int $batas = 200): array
    {
        return array_values(KomisiPartner::query()
            ->where('Status', StatusKomisiPartner::Tertunda->value)
            ->orderBy('DibuatPada')
            ->limit($batas)
            ->get()
            ->all());
    }

    /** @return array<string, int> */
    public function ringkasStatus(): array
    {
        $hitung = KomisiPartner::query()
            ->selectRaw('Status, count(*) as jumlah')
            ->groupBy('Status')
            ->pluck('jumlah', 'Status');

        $ringkas = [];

        foreach (StatusKomisiPartner::cases() as $status) {
            $ringkas[$status->value] = (int) ($hitung[$status->value] ?? 0);
        }

        return $ringkas;
    }
}
