<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Services\PelacakLeadPartner;
use App\Domain\Pemasaran\Domain\Enums\JenisKontenPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusKomisiPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusLeadPartner;
use App\Domain\Pemasaran\Http\Requests\KirimLeadPartnerRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KomisiPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LeadPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PayoutPartner;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AksesDitolak;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Portal partner: lead, trial, pelanggan berbayar, komisi, payout, dan materi pemasaran.
 *
 * Setiap kueri di sini berangkat dari `PartnerId` partner yang sedang masuk, dan
 * tidak satu pun menerima id partner dari permintaan. Itulah yang membuat satu
 * partner tidak pernah melihat lead partner lain (Gate 38.09).
 */
final class PortalPartnerController extends Controller
{
    public function __construct(
        private readonly PelacakLeadPartner $lead,
        private readonly PetaHost $host,
    ) {}

    public function beranda(Request $request): Response
    {
        $partner = $this->partner($request);

        return Inertia::render('PartnerPemasaran/Portal', [
            'partner' => [
                'Kode' => $partner->Kode,
                'NamaPerusahaan' => $partner->NamaPerusahaan,
                'Jenis' => $partner->Jenis->value,
                'LabelJenis' => $partner->Jenis->label(),
                'NamaPic' => $partner->NamaPic,
                'Status' => $partner->Status->value,
                'Program' => $partner->program?->Nama,
                'ReferensiPerjanjian' => $partner->ReferensiPerjanjian,
                'ReferensiPayout' => $partner->ReferensiPayout,
            ],
            'ringkasan' => $this->ringkasan($partner),
            'lead' => $this->daftarLead($partner),
            'komisi' => $this->daftarKomisi($partner),
            'payout' => $this->daftarPayout($partner),
            'materi' => $this->materiPemasaran(),
        ]);
    }

    public function kirimLead(KirimLeadPartnerRequest $request): RedirectResponse
    {
        /** @var array{NamaPerusahaan: string, NamaKontak: string, Email: string, Telepon?: string|null, Catatan?: string|null} $sah */
        $sah = $request->validated();

        $this->lead->kirim($this->partner($request), $sah);

        return back()->with('sukses', 'Lead berhasil dikirim dan sedang ditinjau.');
    }

    /** @return array<string, int|float> */
    private function ringkasan(Partner $partner): array
    {
        $perStatus = LeadPartner::query()
            ->where('PartnerId', $partner->Id)
            ->selectRaw('Status, count(*) as jumlah')
            ->groupBy('Status')
            ->pluck('jumlah', 'Status');

        $komisi = KomisiPartner::query()
            ->where('PartnerId', $partner->Id)
            ->selectRaw('Status, sum(Jumlah) as jumlah')
            ->groupBy('Status')
            ->pluck('jumlah', 'Status');

        return [
            'Lead' => (int) $perStatus->sum(),
            'Trial' => (int) ($perStatus[StatusLeadPartner::Trial->value] ?? 0),
            'Berbayar' => (int) ($perStatus[StatusLeadPartner::Paid->value] ?? 0),
            'Ditolak' => (int) ($perStatus[StatusLeadPartner::Ditolak->value] ?? 0),
            'KomisiTertunda' => round((float) ($komisi[StatusKomisiPartner::Tertunda->value] ?? 0), 2),
            'KomisiDisetujui' => round((float) ($komisi[StatusKomisiPartner::Disetujui->value] ?? 0), 2),
            'KomisiDibayar' => round((float) ($komisi[StatusKomisiPartner::Dibayar->value] ?? 0), 2),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function daftarLead(Partner $partner): array
    {
        $lead = LeadPartner::query()
            ->where('PartnerId', $partner->Id)
            ->orderByDesc('DikirimPada')
            ->limit(200)
            ->get();

        return array_values($lead->map(fn (LeadPartner $satu): array => [
            'Id' => $satu->Id,
            'NamaPerusahaan' => $satu->NamaPerusahaan,
            'NamaKontak' => $satu->NamaKontak,
            'Email' => $satu->Email,
            'Telepon' => $satu->Telepon,
            'Status' => $satu->Status->value,
            'AlasanDitolak' => $satu->AlasanDitolak,
            'DikirimPada' => $satu->DikirimPada->toIso8601String(),
            'MenjadiTrialPada' => $satu->MenjadiTrialPada?->toIso8601String(),
            'MenjadiPaidPada' => $satu->MenjadiPaidPada?->toIso8601String(),
        ])->all());
    }

    /** @return list<array<string, mixed>> */
    private function daftarKomisi(Partner $partner): array
    {
        $komisi = KomisiPartner::query()
            ->with('lead')
            ->where('PartnerId', $partner->Id)
            ->orderByDesc('DibuatPada')
            ->limit(200)
            ->get();

        return array_values($komisi->map(fn (KomisiPartner $satu): array => [
            'Id' => $satu->Id,
            'Lead' => $satu->lead->NamaPerusahaan ?? 'Lead terhapus',
            'JumlahPembayaran' => (float) $satu->JumlahPembayaran,
            'Jumlah' => (float) $satu->Jumlah,
            'Status' => $satu->Status->value,
            'DibuatPada' => $satu->DibuatPada->toIso8601String(),
            'DibayarPada' => $satu->DibayarPada?->toIso8601String(),
        ])->all());
    }

    /** @return list<array<string, mixed>> */
    private function daftarPayout(Partner $partner): array
    {
        $payout = PayoutPartner::query()
            ->where('PartnerId', $partner->Id)
            ->orderByDesc('DibuatPada')
            ->limit(100)
            ->get();

        return array_values($payout->map(fn (PayoutPartner $satu): array => [
            'Id' => $satu->Id,
            'Nomor' => $satu->Nomor,
            'Jumlah' => (float) $satu->Jumlah,
            'JumlahKomisi' => $satu->JumlahKomisi,
            'Status' => $satu->Status->value,
            'ReferensiPembayaran' => $satu->ReferensiPembayaran,
            'DibayarPada' => $satu->DibayarPada?->toIso8601String(),
        ])->all());
    }

    /**
     * Materi pemasaran adalah konten yang memang sudah tayang di situs publik.
     *
     * Tidak ada pustaka berkas tersendiri: yang dibagikan partner harus sama
     * dengan yang dibaca calon pelanggan, dan tautannya mati bila situs publik
     * belum menyala.
     *
     * @return list<array<string, mixed>>
     */
    private function materiPemasaran(): array
    {
        $konten = KontenPemasaran::query()
            ->whereNotNull('VersiTerbitId')
            ->whereIn('Jenis', JenisKontenPemasaran::nilaiMateriPartner())
            ->orderByDesc('TerbitPada')
            ->limit(50)
            ->get();

        return array_values($konten
            ->filter(fn (KontenPemasaran $satu): bool => $satu->tayang())
            ->map(fn (KontenPemasaran $satu): array => [
                'Judul' => $satu->Judul,
                'Jenis' => $satu->Jenis->value,
                'Url' => $this->host->urlKanonik($satu->Slug),
                'TerbitPada' => $satu->TerbitPada?->toIso8601String(),
            ])
            ->values()
            ->all());
    }

    private function partner(Request $request): Partner
    {
        $partner = $request->user('partner');

        if (! $partner instanceof Partner) {
            throw new AksesDitolak('Portal ini hanya untuk partner yang sudah masuk.');
        }

        return $partner;
    }
}
