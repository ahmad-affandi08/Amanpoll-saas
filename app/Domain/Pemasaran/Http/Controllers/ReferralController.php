<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Services\LayananProgramReferral;
use App\Domain\Pemasaran\Application\Services\PenerbitKodeReferral;
use App\Domain\Pemasaran\Application\Services\PenghitungRewardReferral;
use App\Domain\Pemasaran\Domain\Enums\JenisRewardReferral;
use App\Domain\Pemasaran\Domain\Enums\StatusReferral;
use App\Domain\Pemasaran\Domain\Enums\StatusRewardReferral;
use App\Domain\Pemasaran\Http\Requests\SimpanProgramReferralRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KodeReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ProgramReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Referral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RewardReferral;
use App\Domain\Pemasaran\Jobs\ProsesRewardReferral;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Konsol program referral, kode pelanggan, dan imbalannya (MARKETING.md 20). */
final class ReferralController extends Controller
{
    public function __construct(
        private readonly LayananProgramReferral $layanan,
        private readonly PenerbitKodeReferral $penerbit,
        private readonly PenghitungRewardReferral $penghitung,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Pemasaran/Referral/Index', [
            'program' => $this->daftarProgram(),
            'corong' => $this->corong(),
            'reward' => $this->daftarReward(),
            'pilihan' => [
                'Jenis' => array_column(JenisRewardReferral::cases(), 'value'),
                'JenisDidukung' => $this->layanan->jenisDidukung(),
            ],
        ]);
    }

    public function store(SimpanProgramReferralRequest $request): RedirectResponse
    {
        $this->layanan->simpan(null, $this->data($request));

        return back()->with('sukses', 'Program referral berhasil dibuat.');
    }

    public function update(
        SimpanProgramReferralRequest $request,
        ProgramReferral $program,
    ): RedirectResponse {
        $this->layanan->simpan($program, $this->data($request));

        return back()->with('sukses', 'Program referral berhasil diperbarui.');
    }

    /** Menerbitkan kode untuk satu pelanggan; memanggil ulang mengembalikan kode yang sama. */
    public function terbitkanKode(Request $request, ProgramReferral $program): RedirectResponse
    {
        /** @var array{OrganisasiId: string} $sah */
        $sah = $request->validate([
            'OrganisasiId' => ['required', 'string', 'exists:Organisasi,Id'],
        ]);

        $kode = $this->penerbit->untuk($program, $sah['OrganisasiId']);

        return back()->with('sukses', "Kode referral {$kode->Kode} siap dipakai.");
    }

    public function prosesReward(RewardReferral $reward): RedirectResponse
    {
        ProsesRewardReferral::dispatch($reward->Id);

        return back()->with('sukses', 'Imbalan diantrekan untuk diberikan.');
    }

    public function batalkanReward(Request $request, RewardReferral $reward): RedirectResponse
    {
        /** @var array{Alasan: string} $sah */
        $sah = $request->validate(['Alasan' => ['required', 'string', 'max:300']]);

        $this->penghitung->batalkan($reward, $sah['Alasan']);

        return back()->with('sukses', 'Imbalan dibatalkan.');
    }

    /** @return list<array<string, mixed>> */
    private function daftarProgram(): array
    {
        $program = ProgramReferral::query()
            ->with(['kode.organisasi'])
            ->withCount('referral')
            ->orderBy('Kode')
            ->get();

        return array_values($program->map(fn (ProgramReferral $satu): array => [
            'Id' => $satu->Id,
            'Kode' => $satu->Kode,
            'Nama' => $satu->Nama,
            'Keterangan' => $satu->Keterangan,
            'JenisReward' => $satu->JenisReward->value,
            'NilaiReward' => (float) $satu->NilaiReward,
            'HariKedaluwarsa' => $satu->HariKedaluwarsa,
            'Aktif' => $satu->Aktif,
            'JumlahReferral' => (int) ($satu->referral_count ?? 0),
            'Kodenya' => array_values($satu->kode->map(fn (KodeReferral $kode): array => [
                'Id' => $kode->Id,
                'Kode' => $kode->Kode,
                'Organisasi' => $kode->organisasi->Nama ?? 'Organisasi terhapus',
                'Url' => $this->penerbit->url($kode),
                'Aktif' => $kode->Aktif,
            ])->all()),
        ])->all());
    }

    /**
     * Berapa referral yang berhenti di tiap tahap; itulah bentuk corongnya.
     *
     * @return array<string, int>
     */
    private function corong(): array
    {
        $hitung = Referral::query()
            ->selectRaw('Status, count(*) as jumlah')
            ->groupBy('Status')
            ->pluck('jumlah', 'Status');

        $corong = [];

        foreach (StatusReferral::cases() as $status) {
            $corong[$status->value] = (int) ($hitung[$status->value] ?? 0);
        }

        return $corong;
    }

    /** @return list<array<string, mixed>> */
    private function daftarReward(): array
    {
        $reward = RewardReferral::query()
            ->with(['penerima', 'referral'])
            ->orderByRaw('field(Status, ?, ?, ?, ?)', [
                StatusRewardReferral::Gagal->value,
                StatusRewardReferral::Tertunda->value,
                StatusRewardReferral::Diberikan->value,
                StatusRewardReferral::Dibatalkan->value,
            ])
            ->orderByDesc('DibuatPada')
            ->limit(100)
            ->get();

        return array_values($reward->map(fn (RewardReferral $satu): array => [
            'Id' => $satu->Id,
            'Penerima' => $satu->penerima->Nama ?? 'Organisasi terhapus',
            'Jenis' => $satu->Jenis->value,
            'Nilai' => (float) $satu->Nilai,
            'Status' => $satu->Status->value,
            'Percobaan' => $satu->Percobaan,
            'Ringkasan' => $satu->Ringkasan,
            'Galat' => $satu->Galat,
            'DibuatPada' => $satu->DibuatPada->toIso8601String(),
            'DiberikanPada' => $satu->DiberikanPada?->toIso8601String(),
        ])->all());
    }

    /** @return array{Kode: string, Nama: string, Keterangan: string|null, JenisReward: string, NilaiReward: float, HariKedaluwarsa: int, Aktif: bool} */
    private function data(SimpanProgramReferralRequest $request): array
    {
        /** @var array{Kode: string, Nama: string, Keterangan?: string|null, JenisReward: string, NilaiReward: numeric-string|float, HariKedaluwarsa: int, Aktif?: bool} $sah */
        $sah = $request->validated();

        return [
            'Kode' => $sah['Kode'],
            'Nama' => $sah['Nama'],
            'Keterangan' => $sah['Keterangan'] ?? null,
            'JenisReward' => $sah['JenisReward'],
            'NilaiReward' => (float) $sah['NilaiReward'],
            'HariKedaluwarsa' => $sah['HariKedaluwarsa'],
            'Aktif' => $sah['Aktif'] ?? false,
        ];
    }
}
