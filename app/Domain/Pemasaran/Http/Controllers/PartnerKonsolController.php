<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Services\LayananPayoutPartner;
use App\Domain\Pemasaran\Application\Services\LayananProgramPartner;
use App\Domain\Pemasaran\Application\Services\PelacakLeadPartner;
use App\Domain\Pemasaran\Application\Services\PenghitungKomisiPartner;
use App\Domain\Pemasaran\Domain\Enums\JenisKomisiPartner;
use App\Domain\Pemasaran\Domain\Enums\JenisPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusKomisiPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusLeadPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusPartner;
use App\Domain\Pemasaran\Http\Requests\SimpanAturanKomisiRequest;
use App\Domain\Pemasaran\Http\Requests\SimpanPartnerRequest;
use App\Domain\Pemasaran\Http\Requests\SimpanProgramPartnerRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AturanKomisiPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KomisiPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LeadPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PayoutPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ProgramPartner;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Konsol program partner, partnernya, lead, komisi, dan payout (MARKETING.md 21). */
final class PartnerKonsolController extends Controller
{
    public function __construct(
        private readonly LayananProgramPartner $layanan,
        private readonly PelacakLeadPartner $lead,
        private readonly PenghitungKomisiPartner $komisi,
        private readonly LayananPayoutPartner $payout,
    ) {}

    public function index(PetaHost $host): Response
    {
        return Inertia::render('PartnerPemasaran/Konsol', [
            'program' => $this->daftarProgram(),
            'partner' => $this->daftarPartner(),
            'aturan' => $this->daftarAturan(),
            'lead' => $this->daftarLead(),
            'komisi' => $this->daftarKomisi(),
            'payout' => $this->daftarPayout(),
            'ringkasanKomisi' => $this->komisi->ringkasStatus(),
            'pilihan' => [
                'Jenis' => JenisPartner::nilai(),
                'StatusPartner' => StatusPartner::nilai(),
                'JenisKomisi' => JenisKomisiPartner::nilai(),
            ],
            'hostPartner' => $host->portalPartnerAktif() ? $host->partner() : null,
        ]);
    }

    public function simpanProgram(SimpanProgramPartnerRequest $request): RedirectResponse
    {
        $this->layanan->simpanProgram($this->programDari($request), $this->dataProgram($request));

        return back()->with('sukses', 'Program partner berhasil disimpan.');
    }

    public function simpanPartner(SimpanPartnerRequest $request): RedirectResponse
    {
        $partner = $request->route('partner');
        $this->layanan->simpanPartner(
            $partner instanceof Partner ? $partner : null,
            $this->dataPartner($request),
        );

        return back()->with('sukses', 'Partner berhasil disimpan.');
    }

    public function simpanAturan(SimpanAturanKomisiRequest $request): RedirectResponse
    {
        $aturan = $request->route('aturan');
        $this->layanan->simpanAturan(
            $aturan instanceof AturanKomisiPartner ? $aturan : null,
            $this->dataAturan($request),
        );

        return back()->with('sukses', 'Aturan komisi berhasil disimpan.');
    }

    public function terimaLead(LeadPartner $lead): RedirectResponse
    {
        $this->lead->terima($lead);

        return back()->with('sukses', 'Lead diterima.');
    }

    public function tolakLead(Request $request, LeadPartner $lead): RedirectResponse
    {
        /** @var array{Alasan: string} $sah */
        $sah = $request->validate(['Alasan' => ['required', 'string', 'max:300']]);

        $this->lead->tolak($lead, $sah['Alasan']);

        return back()->with('sukses', 'Lead ditolak.');
    }

    public function setujuiKomisi(KomisiPartner $komisi): RedirectResponse
    {
        $this->komisi->setujui($komisi);

        return back()->with('sukses', 'Komisi disetujui dan siap masuk payout.');
    }

    public function batalkanKomisi(Request $request, KomisiPartner $komisi): RedirectResponse
    {
        /** @var array{Alasan: string} $sah */
        $sah = $request->validate(['Alasan' => ['required', 'string', 'max:500']]);

        $this->komisi->batalkan($komisi, $sah['Alasan']);

        return back()->with('sukses', 'Komisi dibatalkan.');
    }

    public function susunPayout(Partner $partner): RedirectResponse
    {
        $payout = $this->payout->susun($partner);

        return back()->with('sukses', "Payout {$payout->Nomor} disusun.");
    }

    public function bayarPayout(Request $request, PayoutPartner $payout): RedirectResponse
    {
        /** @var array{Referensi: string, Catatan?: string|null} $sah */
        $sah = $request->validate([
            'Referensi' => ['required', 'string', 'max:190'],
            'Catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $this->payout->tandaiDibayar($payout, $sah['Referensi'], $sah['Catatan'] ?? null);

        return back()->with('sukses', 'Payout ditandai dibayar.');
    }

    public function batalkanPayout(Request $request, PayoutPartner $payout): RedirectResponse
    {
        /** @var array{Alasan: string} $sah */
        $sah = $request->validate(['Alasan' => ['required', 'string', 'max:500']]);

        $this->payout->batalkan($payout, $sah['Alasan']);

        return back()->with('sukses', 'Payout dibatalkan dan komisinya kembali ke antrean.');
    }

    /** @return list<array<string, mixed>> */
    private function daftarProgram(): array
    {
        $program = ProgramPartner::query()->withCount('partner')->orderBy('Kode')->get();

        return array_values($program->map(fn (ProgramPartner $satu): array => [
            'Id' => $satu->Id,
            'Kode' => $satu->Kode,
            'Nama' => $satu->Nama,
            'Keterangan' => $satu->Keterangan,
            'HariAtribusi' => $satu->HariAtribusi,
            'Aktif' => $satu->Aktif,
            'JumlahPartner' => (int) ($satu->partner_count ?? 0),
        ])->all());
    }

    /** @return list<array<string, mixed>> */
    private function daftarPartner(): array
    {
        $partner = Partner::query()->with('program')->orderBy('NamaPerusahaan')->get();

        return array_values($partner->map(fn (Partner $satu): array => [
            'Id' => $satu->Id,
            'Kode' => $satu->Kode,
            'NamaPerusahaan' => $satu->NamaPerusahaan,
            'Jenis' => $satu->Jenis->value,
            'LabelJenis' => $satu->Jenis->label(),
            'NamaPic' => $satu->NamaPic,
            'EmailPic' => $satu->EmailPic,
            'TeleponPic' => $satu->TeleponPic,
            'Status' => $satu->Status->value,
            'Program' => $satu->program?->Nama,
            'ProgramPartnerId' => $satu->ProgramPartnerId,
            'ReferensiPerjanjian' => $satu->ReferensiPerjanjian,
            'ReferensiPayout' => $satu->ReferensiPayout,
            'TerakhirMasukPada' => $satu->TerakhirMasukPada?->toIso8601String(),
        ])->all());
    }

    /** @return list<array<string, mixed>> */
    private function daftarAturan(): array
    {
        $aturan = AturanKomisiPartner::query()->with(['program', 'partner'])->orderBy('Nama')->get();

        return array_values($aturan->map(fn (AturanKomisiPartner $satu): array => [
            'Id' => $satu->Id,
            'Nama' => $satu->Nama,
            'Program' => $satu->program?->Nama,
            'ProgramPartnerId' => $satu->ProgramPartnerId,
            'PartnerId' => $satu->PartnerId,
            'Partner' => $satu->partner?->NamaPerusahaan,
            'Jenis' => $satu->Jenis->value,
            'LabelJenis' => $satu->Jenis->label(),
            'Nilai' => (float) $satu->Nilai,
            'MaksPembayaran' => $satu->MaksPembayaran,
            'Aktif' => $satu->Aktif,
            'BerlakuDari' => $satu->BerlakuDari?->toIso8601String(),
            'BerlakuSampai' => $satu->BerlakuSampai?->toIso8601String(),
        ])->all());
    }

    /** @return list<array<string, mixed>> */
    private function daftarLead(): array
    {
        $lead = LeadPartner::query()
            ->with('partner')
            ->orderByRaw('field(Status, ?, ?, ?, ?, ?)', [
                StatusLeadPartner::Dikirim->value,
                StatusLeadPartner::Diterima->value,
                StatusLeadPartner::Trial->value,
                StatusLeadPartner::Paid->value,
                StatusLeadPartner::Ditolak->value,
            ])
            ->orderByDesc('DikirimPada')
            ->limit(200)
            ->get();

        return array_values($lead->map(fn (LeadPartner $satu): array => [
            'Id' => $satu->Id,
            'Partner' => $satu->partner->NamaPerusahaan ?? 'Partner terhapus',
            'NamaPerusahaan' => $satu->NamaPerusahaan,
            'NamaKontak' => $satu->NamaKontak,
            'Email' => $satu->Email,
            'Telepon' => $satu->Telepon,
            'Catatan' => $satu->Catatan,
            'Status' => $satu->Status->value,
            'AlasanDitolak' => $satu->AlasanDitolak,
            'DikirimPada' => $satu->DikirimPada->toIso8601String(),
        ])->all());
    }

    /** @return list<array<string, mixed>> */
    private function daftarKomisi(): array
    {
        $komisi = KomisiPartner::query()
            ->with(['partner', 'lead'])
            ->orderByRaw('field(Status, ?, ?, ?, ?)', [
                StatusKomisiPartner::Tertunda->value,
                StatusKomisiPartner::Disetujui->value,
                StatusKomisiPartner::Dibayar->value,
                StatusKomisiPartner::Dibatalkan->value,
            ])
            ->orderByDesc('DibuatPada')
            ->limit(200)
            ->get();

        return array_values($komisi->map(fn (KomisiPartner $satu): array => [
            'Id' => $satu->Id,
            'Partner' => $satu->partner->NamaPerusahaan ?? 'Partner terhapus',
            'Lead' => $satu->lead->NamaPerusahaan ?? 'Lead terhapus',
            'JumlahPembayaran' => (float) $satu->JumlahPembayaran,
            'Jumlah' => (float) $satu->Jumlah,
            'Status' => $satu->Status->value,
            'PayoutPartnerId' => $satu->PayoutPartnerId,
            'DibuatPada' => $satu->DibuatPada->toIso8601String(),
        ])->all());
    }

    /** @return list<array<string, mixed>> */
    private function daftarPayout(): array
    {
        $payout = PayoutPartner::query()->with('partner')->orderByDesc('DibuatPada')->limit(100)->get();

        return array_values($payout->map(fn (PayoutPartner $satu): array => [
            'Id' => $satu->Id,
            'Partner' => $satu->partner->NamaPerusahaan ?? 'Partner terhapus',
            'Nomor' => $satu->Nomor,
            'Jumlah' => (float) $satu->Jumlah,
            'JumlahKomisi' => $satu->JumlahKomisi,
            'Status' => $satu->Status->value,
            'ReferensiPembayaran' => $satu->ReferensiPembayaran,
            'Catatan' => $satu->Catatan,
            'DibayarPada' => $satu->DibayarPada?->toIso8601String(),
        ])->all());
    }

    private function programDari(SimpanProgramPartnerRequest $request): ?ProgramPartner
    {
        $program = $request->route('programPartner');

        return $program instanceof ProgramPartner ? $program : null;
    }

    /** @return array{Kode: string, Nama: string, Keterangan: string|null, HariAtribusi: int, Aktif: bool} */
    private function dataProgram(SimpanProgramPartnerRequest $request): array
    {
        /** @var array{Kode: string, Nama: string, Keterangan?: string|null, HariAtribusi: int, Aktif?: bool} $sah */
        $sah = $request->validated();

        return [
            'Kode' => $sah['Kode'],
            'Nama' => $sah['Nama'],
            'Keterangan' => $sah['Keterangan'] ?? null,
            'HariAtribusi' => $sah['HariAtribusi'],
            'Aktif' => $sah['Aktif'] ?? false,
        ];
    }

    /** @return array{ProgramPartnerId: string, NamaPerusahaan: string, Jenis: string, NamaPic: string, EmailPic: string, TeleponPic: string|null, Status: string, ReferensiPerjanjian: string|null, ReferensiPayout: string|null, KataSandi: string|null} */
    private function dataPartner(SimpanPartnerRequest $request): array
    {
        /** @var array{ProgramPartnerId: string, NamaPerusahaan: string, Jenis: string, NamaPic: string, EmailPic: string, TeleponPic?: string|null, Status: string, ReferensiPerjanjian?: string|null, ReferensiPayout?: string|null, KataSandi?: string|null} $sah */
        $sah = $request->validated();

        return [
            'ProgramPartnerId' => $sah['ProgramPartnerId'],
            'NamaPerusahaan' => $sah['NamaPerusahaan'],
            'Jenis' => $sah['Jenis'],
            'NamaPic' => $sah['NamaPic'],
            'EmailPic' => $sah['EmailPic'],
            'TeleponPic' => $sah['TeleponPic'] ?? null,
            'Status' => $sah['Status'],
            'ReferensiPerjanjian' => $sah['ReferensiPerjanjian'] ?? null,
            'ReferensiPayout' => $sah['ReferensiPayout'] ?? null,
            'KataSandi' => $sah['KataSandi'] ?? null,
        ];
    }

    /** @return array{ProgramPartnerId: string, PartnerId: string|null, Nama: string, Jenis: string, Nilai: float, MaksPembayaran: int|null, Aktif: bool, BerlakuDari: string|null, BerlakuSampai: string|null} */
    private function dataAturan(SimpanAturanKomisiRequest $request): array
    {
        /** @var array{ProgramPartnerId: string, PartnerId?: string|null, Nama: string, Jenis: string, Nilai: numeric-string|float, MaksPembayaran?: int|null, Aktif?: bool, BerlakuDari?: string|null, BerlakuSampai?: string|null} $sah */
        $sah = $request->validated();

        return [
            'ProgramPartnerId' => $sah['ProgramPartnerId'],
            'PartnerId' => $sah['PartnerId'] ?? null,
            'Nama' => $sah['Nama'],
            'Jenis' => $sah['Jenis'],
            'Nilai' => (float) $sah['Nilai'],
            'MaksPembayaran' => $sah['MaksPembayaran'] ?? null,
            'Aktif' => $sah['Aktif'] ?? true,
            'BerlakuDari' => $sah['BerlakuDari'] ?? null,
            'BerlakuSampai' => $sah['BerlakuSampai'] ?? null,
        ];
    }
}
